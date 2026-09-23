/*!
 * Widget de Signature Électronique — YAKOA AFRICASSUR
 * Vanilla JS, sans build, embarquable dans n'importe quelle application.
 *
 * ============================================================================
 * CORRECTIONS DE CETTE VERSION
 * ============================================================================
 * 1. `token` est fourni explicitement par la configuration. On ne le déduit plus
 *    de `new URL(...).pathname`, qui pouvait le renvoyer percent-encodé (%7C) et
 *    provoquer des INVALID_TOKEN côté serveur.
 * 2. La signature est postée sur le backend Laravel (`backendWebhookUrl`), même
 *    origine que la page : plus de CORS, plus de preflight, plus de 404. C'est
 *    Laravel qui relaie vers l'app hôte ET consomme le token, en une transaction.
 * 3. `apiKey` n'est plus utilisée côté navigateur : un secret partagé rendu dans
 *    une page publique n'authentifie plus rien.
 * 4. Le redimensionnement du canvas n'efface plus la signature (setTransform au
 *    lieu d'un scale cumulatif, et redimensionnement ignoré si la largeur n'a pas
 *    changé — cas du clavier virtuel mobile).
 * 5. Le polling s'arrête proprement et signale l'expiration.
 * 6. Bouton « Annuler » ajouté (cancelRedirectUrl devenait sinon inatteignable).
 *
 * ============================================================================
 * INITIALISATION
 * ============================================================================
 *   new SignatureWidget({
 *     container: '#signature-container',
 *     token: 'aB3…',                                   // token public (64 car.)
 *     documentUrl: 'https://…/contrat.pdf',            // optionnel
 *     documentDescription: 'Contrat de souscription',
 *     backendWebhookUrl: 'https://api.votre-domaine.com/api/v1/signature/webhook',
 *     apiUrl: 'https://api.votre-domaine.com/api/v1/signature',
 *     signingLink: 'https://api.votre-domaine.com/signature/widget/aB3…',
 *     enableAutoPolling: true,
 *     successRedirectUrl: '…', cancelRedirectUrl: '…',
 *     onSigned: function (data) {}, onError: function (err) {}
 *   });
 *
 * ============================================================================
 * COMPORTEMENT RESPONSIVE
 * ============================================================================
 *   Desktop (>= 768px) + signingLink : QR code + polling du statut.
 *   Mobile  (<  768px)               : document + canvas de signature tactile.
 *
 * ============================================================================
 * DÉPENDANCES CDN (chargées à la demande)
 * ============================================================================
 *   QRCode.js, PDF.js — cdnjs.cloudflare.com
 */
(function (global) {
  'use strict';

  // ============================================================
  // LIBRAIRIES EXTERNES
  // ============================================================
  var CDN = {
    qrcode:      'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
    pdfjs:       'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
    pdfjsWorker: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
  };

  var loadedScripts = {};

  function loadScript(src) {
    if (loadedScripts[src]) return loadedScripts[src];
    loadedScripts[src] = new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = resolve;
      s.onerror = function () { reject(new Error('Impossible de charger ' + src)); };
      document.head.appendChild(s);
    });
    return loadedScripts[src];
  }

  function isPdf(url) {
    return /\.pdf(\?|#|$)/i.test(url || '');
  }

  // ============================================================
  // STYLES (Shadow DOM — isolation totale vis-à-vis de l'app hôte)
  // Couleurs YAKOA AFRICASSUR : #075429 (vert), #F7A400 (orange)
  // ============================================================
  var CSS = ''
    + ':host, .sw-root { all: initial; font-family: -apple-system, "Segoe UI", Roboto, sans-serif; }'
    + '.sw-root { display: block; box-sizing: border-box; color: #1c1c1e; width: 100%; }'
    + '.sw-root *, .sw-root *::before, .sw-root *::after { box-sizing: border-box; }'
    + '.sw-card { border: 1px solid #e3e3e6; border-radius: 10px; overflow: hidden; background: #fff; }'
    + '.sw-header { padding: 14px 16px; border-bottom: 1px solid #eee; background: #075429; color: #fff; }'
    + '.sw-title { font-size: 15px; font-weight: 600; margin: 0 0 2px; color: #fff; }'
    + '.sw-desc { font-size: 13px; color: rgba(255,255,255,.9); margin: 0; }'
    + '.sw-preview { max-height: 420px; overflow: auto; background: #f5f5f7; padding: 12px; text-align: center; }'
    + '.sw-preview img, .sw-preview canvas { max-width: 100%; height: auto; box-shadow: 0 1px 4px rgba(0,0,0,.15); margin-bottom: 10px; }'
    + '.sw-preview p { font-size: 13px; color: #6b6b70; margin: 0; }'
    + '.sw-section { padding: 16px; border-top: 1px solid #eee; }'
    + '.sw-label { font-size: 13px; font-weight: 600; margin: 0 0 8px; color: #075429; }'
    + '.sw-canvas-wrap { border: 2px solid #F7A400; border-radius: 8px; background: #fff; touch-action: none; }'
    + '.sw-canvas-wrap canvas { display: block; width: 100%; height: 180px; cursor: crosshair; }'
    + '.sw-row { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }'
    + '.sw-btn { appearance: none; border: 1px solid #d0d0d5; background: #fff; color: #1c1c1e;'
    +   ' font-size: 13px; font-weight: 500; padding: 9px 14px; border-radius: 8px; cursor: pointer; }'
    + '.sw-btn:hover { background: #f2f2f4; }'
    + '.sw-btn-primary { background: #F7A400; border-color: #F7A400; color: #fff; }'
    + '.sw-btn-primary:hover { background: #e59400; }'
    + '.sw-btn-primary:disabled { opacity: .45; cursor: default; }'
    + '.sw-qr-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 24px 16px; text-align: center; background: #f9f9f9; }'
    + '.sw-qr-box { padding: 12px; background: #fff; border: 2px solid #075429; border-radius: 10px; }'
    + '.sw-qr-hint { font-size: 13px; color: #6b6b70; max-width: 320px; }'
    + '.sw-qr-state { font-size: 12.5px; color: #075429; font-weight: 600; }'
    + '.sw-status { font-size: 12.5px; margin-top: 8px; min-height: 16px; }'
    + '.sw-status.ok { color: #075429; }'
    + '.sw-status.err { color: #c62828; }'
    + '.sw-done { padding: 28px 16px; text-align: center; background: #d4edda; }'
    + '.sw-done .sw-check { font-size: 34px; color: #075429; }';

  // ============================================================
  // CLASSE PRINCIPALE
  // ============================================================

  /**
   * @param {Object} config
   * @param {string|HTMLElement} config.container
   * @param {string}  config.token               Token public (64 caractères alphanumériques)
   * @param {string}  config.backendWebhookUrl   Endpoint Laravel qui reçoit la signature
   * @param {string}  [config.apiUrl]            Base de l'API pour le polling
   * @param {string}  [config.documentUrl]       Document à afficher (optionnel)
   * @param {string}  [config.documentDescription]
   * @param {string}  [config.signingLink]       Lien encodé dans le QR code (mode desktop)
   * @param {string}  [config.forceMode]         'desktop' | 'mobile'
   * @param {boolean} [config.enableAutoPolling]
   * @param {number}  [config.pollingInterval=5000]
   * @param {number}  [config.maxPollingAttempts=120]
   * @param {string}  [config.successRedirectUrl]
   * @param {string}  [config.cancelRedirectUrl]
   * @param {number}  [config.breakpoint=768]
   * @param {Function}[config.onSigned]
   * @param {Function}[config.onError]
   */
  function SignatureWidget(config) {
    if (!config) {
      throw new Error('SignatureWidget: configuration requise.');
    }
    if (!config.backendWebhookUrl) {
      throw new Error('SignatureWidget: "backendWebhookUrl" est requis.');
    }
    if (!config.token) {
      throw new Error('SignatureWidget: "token" est requis.');
    }

    this.cfg = Object.assign({
      documentUrl:        null,
      documentDescription: '',
      signingLink:        null,
      apiUrl:             null,
      forceMode:          null,
      breakpoint:         768,
      enableAutoPolling:  false,
      pollingInterval:    5000,
      maxPollingAttempts: 120,   // 5 s x 120 = 10 min
      successRedirectUrl: null,
      cancelRedirectUrl:  null,
      onSigned:           null,
      onError:            null
    }, config);

    this.container = typeof config.container === 'string'
      ? document.querySelector(config.container)
      : config.container;

    if (!this.container) {
      throw new Error('SignatureWidget: container introuvable.');
    }

    this.host = document.createElement('div');
    this.container.appendChild(this.host);
    this.shadow = this.host.attachShadow({ mode: 'open' });

    var style = document.createElement('style');
    style.textContent = CSS;
    this.shadow.appendChild(style);

    this.root = document.createElement('div');
    this.root.className = 'sw-root';
    this.shadow.appendChild(this.root);

    this._pollingInterval = null;
    this._destroyed = false;

    this._render();
  }

  // ------------------------------------------------------------
  // Rendu
  // ------------------------------------------------------------

  SignatureWidget.prototype._mode = function () {
    if (this.cfg.forceMode === 'desktop' || this.cfg.forceMode === 'mobile') {
      return this.cfg.forceMode;
    }
    return window.innerWidth >= this.cfg.breakpoint ? 'desktop' : 'mobile';
  };

  SignatureWidget.prototype._render = function () {
    var mode = this._mode();
    this.root.innerHTML = '';

    var card = document.createElement('div');
    card.className = 'sw-card';
    this.root.appendChild(card);

    var header = document.createElement('div');
    header.className = 'sw-header';
    header.innerHTML = '<p class="sw-title">Document à signer</p><p class="sw-desc"></p>';
    header.querySelector('.sw-desc').textContent = this.cfg.documentDescription || '';
    card.appendChild(header);

    if (this.cfg.documentUrl) {
      var preview = document.createElement('div');
      preview.className = 'sw-preview';
      card.appendChild(preview);
      this._renderPreview(preview);
    }

    if (mode === 'desktop' && this.cfg.signingLink) {
      this._renderQrSection(card);
    } else {
      this._renderSignSection(card);
    }
  };

  SignatureWidget.prototype._renderPreview = function (container) {
    var self = this;
    var url  = this.cfg.documentUrl;

    if (!isPdf(url)) {
      var img = document.createElement('img');
      img.src = url;
      img.alt = this.cfg.documentDescription || 'Document à signer';
      img.onerror = function () {
        container.innerHTML = '<p>Aperçu indisponible.</p>';
      };
      container.appendChild(img);
      return;
    }

    var msg = document.createElement('p');
    msg.textContent = 'Chargement du document…';
    container.appendChild(msg);

    loadScript(CDN.pdfjs).then(function () {
      var pdfjsLib = global.pdfjsLib;
      pdfjsLib.GlobalWorkerOptions.workerSrc = CDN.pdfjsWorker;
      return pdfjsLib.getDocument(url).promise;
    }).then(function (pdf) {
      container.innerHTML = '';
      var chain = Promise.resolve();
      var renderPage = function (num) {
        return pdf.getPage(num).then(function (page) {
          var viewport = page.getViewport({ scale: 1.4 });
          var canvas = document.createElement('canvas');
          canvas.width  = viewport.width;
          canvas.height = viewport.height;
          container.appendChild(canvas);
          return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
        });
      };
      for (var i = 1; i <= pdf.numPages; i++) {
        (function (n) { chain = chain.then(function () { return renderPage(n); }); })(i);
      }
      return chain;
    }).catch(function (err) {
      container.innerHTML = '';
      var p = document.createElement('p');
      p.textContent = 'Aperçu indisponible. ';
      var a = document.createElement('a');
      a.href = url; a.target = '_blank'; a.rel = 'noopener';
      a.textContent = 'Ouvrir le document';
      p.appendChild(a);
      container.appendChild(p);
      self._error(err);
    });
  };

  // ------------------------------------------------------------
  // Mode desktop : QR code + polling
  // ------------------------------------------------------------

  SignatureWidget.prototype._renderQrSection = function (card) {
    var self = this;

    var section = document.createElement('div');
    section.className = 'sw-section sw-qr-wrap';
    section.innerHTML =
      '<p class="sw-label">Signez depuis votre mobile</p>' +
      '<div class="sw-qr-box"></div>' +
      '<p class="sw-qr-hint">Scannez ce QR code avec votre téléphone pour ouvrir la page de signature. ' +
      'Le lien est à usage unique et expire après utilisation.</p>' +
      '<p class="sw-qr-state"></p>';
    card.appendChild(section);

    var qrBox = section.querySelector('.sw-qr-box');
    var state = section.querySelector('.sw-qr-state');

    loadScript(CDN.qrcode).then(function () {
      new global.QRCode(qrBox, {
        text: self.cfg.signingLink,
        width: 168,
        height: 168,
        correctLevel: global.QRCode.CorrectLevel.M
      });

      if (self.cfg.enableAutoPolling) {
        state.textContent = 'En attente de la signature…';
        self.startAutoPolling(card, state);
      }
    }).catch(function (err) {
      qrBox.textContent = 'QR indisponible.';
      self._error(err);
    });
  };

  // ------------------------------------------------------------
  // Mode mobile : canvas de signature
  // ------------------------------------------------------------

  SignatureWidget.prototype._renderSignSection = function (card) {
    var self = this;

    var section = document.createElement('div');
    section.className = 'sw-section';
    section.innerHTML =
      '<p class="sw-label">Votre signature</p>' +
      '<div class="sw-canvas-wrap"><canvas></canvas></div>' +
      '<div class="sw-row">' +
        '<button type="button" class="sw-btn sw-clear">Effacer</button>' +
        '<button type="button" class="sw-btn sw-cancel">Annuler</button>' +
        '<button type="button" class="sw-btn sw-btn-primary sw-submit" disabled>Valider la signature</button>' +
      '</div>' +
      '<div class="sw-status"></div>';
    card.appendChild(section);

    var canvas    = section.querySelector('canvas');
    var status    = section.querySelector('.sw-status');
    var submitBtn = section.querySelector('.sw-submit');
    var clearBtn  = section.querySelector('.sw-clear');
    var cancelBtn = section.querySelector('.sw-cancel');

    var ctx = canvas.getContext('2d');
    var drawing = false;
    var hasSignature = false;
    var last = null;
    var lastWidth = 0;

    /**
     * Redimensionne le canvas sans cumuler les transformations.
     * Ignoré si la largeur n'a pas bougé : sur mobile, l'ouverture du clavier
     * déclenche un resize vertical qui effaçait la signature.
     */
    function resizeCanvas() {
      var rect = canvas.getBoundingClientRect();
      if (Math.round(rect.width) === lastWidth) return;
      lastWidth = Math.round(rect.width);

      var ratio = window.devicePixelRatio || 1;
      canvas.width  = rect.width * ratio;
      canvas.height = rect.height * ratio;

      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth   = 2;
      ctx.lineCap     = 'round';
      ctx.lineJoin    = 'round';
      ctx.strokeStyle = '#1c1c1e';

      hasSignature = false;
      submitBtn.disabled = true;
    }
    resizeCanvas();
    this._onResize = resizeCanvas;
    window.addEventListener('resize', resizeCanvas);

    function pointerPos(e) {
      var rect = canvas.getBoundingClientRect();
      var p = e.touches ? e.touches[0] : e;
      return { x: p.clientX - rect.left, y: p.clientY - rect.top };
    }

    function start(e) { e.preventDefault(); drawing = true; last = pointerPos(e); }

    function move(e) {
      if (!drawing) return;
      e.preventDefault();
      var p = pointerPos(e);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      last = p;
      if (!hasSignature) {
        hasSignature = true;
        submitBtn.disabled = false;
      }
    }

    function end() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   end);

    clearBtn.addEventListener('click', function () {
      ctx.save();
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.restore();
      hasSignature = false;
      submitBtn.disabled = true;
      status.textContent = '';
      status.className = 'sw-status';
    });

    cancelBtn.addEventListener('click', function () {
      if (self.cfg.cancelRedirectUrl) {
        window.location.href = self.cfg.cancelRedirectUrl;
      } else {
        status.className = 'sw-status';
        status.textContent = 'Vous pouvez fermer cette page.';
      }
    });

    submitBtn.addEventListener('click', function () {
      if (!hasSignature) return;

      submitBtn.disabled = true;
      clearBtn.disabled  = true;
      status.className   = 'sw-status';
      status.textContent = 'Envoi en cours…';

      var payload = {
        token:     self.cfg.token,
        signature: canvas.toDataURL('image/png')
      };

      // Une seule requête, vers Laravel, en même origine.
      fetch(self.cfg.backendWebhookUrl, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify(payload)
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          if (!res.ok || !body.success) {
            var msg = (body && body.message) ? body.message : 'Le serveur a répondu ' + res.status;
            throw new Error(msg);
          }
          return body;
        });
      }).then(function (body) {
        self._renderDone(card);
        if (typeof self.cfg.onSigned === 'function') self.cfg.onSigned(body.data || body);
      }).catch(function (err) {
        submitBtn.disabled = false;
        clearBtn.disabled  = false;
        status.className   = 'sw-status err';
        status.textContent = 'Erreur : ' + err.message;
        self._error(err);
      });
    });
  };

  // ------------------------------------------------------------
  // Écran de confirmation
  // ------------------------------------------------------------

  SignatureWidget.prototype._renderDone = function (card) {
    var self = this;

    Array.prototype.forEach.call(
      card.querySelectorAll('.sw-section, .sw-qr-wrap'),
      function (el) { el.remove(); }
    );

    var done = document.createElement('div');
    done.className = 'sw-done';
    done.innerHTML = '<div class="sw-check">&#10003;</div>' +
                     '<p class="sw-label">Document signé avec succès</p>';
    card.appendChild(done);

    this.stopAutoPolling();

    if (this.cfg.successRedirectUrl) {
      setTimeout(function () {
        window.location.href = self.cfg.successRedirectUrl;
      }, 2000);
    }
  };

  SignatureWidget.prototype._error = function (err) {
    if (typeof this.cfg.onError === 'function') this.cfg.onError(err);
    else if (global.console) console.error('[SignatureWidget]', err);
  };

  // ------------------------------------------------------------
  // Polling (poste desktop)
  // ------------------------------------------------------------

  SignatureWidget.prototype.startAutoPolling = function (card, stateEl) {
    var self = this;
    var apiUrl = this.cfg.apiUrl;

    if (!apiUrl || !this.cfg.token) return;

    var attempts = 0;
    var statusUrl = apiUrl.replace(/\/+$/, '') + '/token/' + encodeURIComponent(this.cfg.token) + '/status';

    this._pollingInterval = setInterval(function () {
      attempts++;

      if (attempts > self.cfg.maxPollingAttempts) {
        self.stopAutoPolling();
        if (stateEl) stateEl.textContent = 'Délai dépassé. Rechargez la page pour réessayer.';
        self._error(new Error('Polling arrêté : nombre maximum de tentatives atteint.'));
        return;
      }

      fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
        .then(function (res) { return res.json(); })
        .then(function (body) {
          if (!body || !body.success || !body.data) return;

          if (body.data.is_used) {
            self.stopAutoPolling();
            self._renderDone(card);
            if (typeof self.cfg.onSigned === 'function') {
              self.cfg.onSigned({
                status:          'completed',
                signed_at:       body.data.signed_at,
                delivery_status: body.data.delivery_status,
                token:           self.cfg.token
              });
            }
            return;
          }

          if (body.data.expired) {
            self.stopAutoPolling();
            if (stateEl) stateEl.textContent = 'Le lien de signature a expiré.';
            self._error(new Error('Token expiré.'));
          }
        })
        .catch(function (error) {
          // Erreur réseau ponctuelle : on continue jusqu'au plafond de tentatives.
          if (global.console) console.warn('[SignatureWidget] polling', error);
        });
    }, this.cfg.pollingInterval);
  };

  SignatureWidget.prototype.stopAutoPolling = function () {
    if (this._pollingInterval) {
      clearInterval(this._pollingInterval);
      this._pollingInterval = null;
    }
  };

  /** Libère les écouteurs globaux et les timers. */
  SignatureWidget.prototype.destroy = function () {
    this.stopAutoPolling();
    if (this._onResize) window.removeEventListener('resize', this._onResize);
    if (this.host && this.host.parentNode) this.host.parentNode.removeChild(this.host);
    this._destroyed = true;
  };

  global.SignatureWidget = SignatureWidget;
})(window);
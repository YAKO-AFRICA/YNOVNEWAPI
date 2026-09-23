/*!
 * Widget de Signature Électronique — YAKOA AFRICASSUR
 * Vanilla JS, sans build, embarquable dans n'importe quelle application.
 *
 * ============================================================================
 * MODES DE SIGNATURE
 * ============================================================================
 *   - OTP (par défaut) : SMS / Email / WhatsApp -> code à 6 chiffres
 *     Preuve = QR code encodant une URL construite depuis otpQrUrlTemplate
 *              + données autoritaires renvoyées par Laravel + géoloc navigateur
 *   - Handwritten      : canvas (comportement historique)
 *     - Sur mobile (< 768 px) : ouvre directement le canvas.
 *     - Sur desktop (>= 768 px) : affiche un QR d'appairage ; le mobile qui
 *       scanne l'URL est redirigé vers ?mode=handwritten et ouvre le canvas.
 *
 * ============================================================================
 * INITIALISATION
 * ============================================================================
 *   new SignatureWidget({
 *     container: '#signature-container',
 *     token: 'aB3…',
 *     documentUrl: 'https://…/contrat.pdf',
 *     documentDescription: 'Contrat',
 *     backendWebhookUrl: 'https://api…/api/v1/signature/webhook',
 *     apiUrl:            'https://api…/api/v1/signature',
 *     signingLink:       'https://api…/signature/widget/aB3…',
 *
 *     // OTP
 *     otpSendUrl:        'https://api…/api/v1/auth/otp/send',
 *     otpVerifyUrl:      'https://api…/api/v1/signature/otp/verify',
 *     signerLogin:       null,
 *     signerUserUuid:    null,
 *     signerEmail:       null,
 *     signerPhone:       null,
 *     otpPurpose:        'signature',
 *     otpQrUrlTemplate:  'https://hote/verify?user={user_uuid}&ch={channel}&contact={contact}&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}',
 *
 *     enableAutoPolling: true,
 *     successRedirectUrl: '…', cancelRedirectUrl: '…',
 *     onSigned: function (data) {}, onError: function (err) {}
 *   });
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

  function isExternalUrl(url) {
    if (!url) return false;
    try {
      var hostname = window.location.hostname;
      var urlHostname = new URL(url).hostname;
      return hostname !== urlHostname;
    } catch (e) {
      return false;
    }
  }

  // ============================================================
  // STYLES
  // ============================================================

  // Helper pour détecter les URLs externes
  function isExternalUrl(url) {
    if (!url) return false;
    try {
      var hostname = window.location.hostname;
      var urlHostname = new URL(url).hostname;
      return hostname !== urlHostname;
    } catch (e) {
      return false;
    }
  }
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
    + '.sw-done .sw-check { font-size: 34px; color: #075429; }'
    // --- Styles OTP ---
    + '.sw-channels { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }'
    + '.sw-channel { flex: 1; min-width: 90px; padding: 10px 8px; border: 1.5px solid #d0d0d5; background: #fff;'
    +   ' border-radius: 8px; cursor: pointer; text-align: center; font-size: 13px; font-weight: 500; color: #1c1c1e; }'
    + '.sw-channel:hover { background: #f2f2f4; }'
    + '.sw-channel.sw-active { border-color: #075429; background: #e8f3ec; color: #075429; }'
    + '.sw-channel.sw-disabled { opacity: 0.5; cursor: not-allowed; background: #f5f5f5; color: #9ca3af; }'
    + '.sw-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }'
    + '.sw-field input { width: 100%; padding: 11px 12px; font-size: 14px; border: 1.5px solid #d0d0d5;'
    +   ' border-radius: 8px; background: #fff; color: #1c1c1e; outline: none; }'
    + '.sw-field input:focus { border-color: #075429; }'
    + '.sw-code-input { letter-spacing: 8px; text-align: center; font-size: 22px; font-weight: 600; }'
    + '.sw-link { display: inline-block; font-size: 12.5px; color: #6b6b70; text-decoration: underline;'
    +   ' cursor: pointer; margin-top: 14px; }'
    + '.sw-link:hover { color: #075429; }'
    + '.sw-otp-info { font-size: 12.5px; color: #6b6b70; margin-top: 6px; }'
    + '.sw-otp-expired { padding: 14px; background: #fff4e5; border: 1px solid #f7c98b; border-radius: 8px;'
    +   ' font-size: 13px; color: #8a5300; margin-top: 10px; }'
    + '.sw-hidden { display: none !important; }';

  // ============================================================
  // CLASSE PRINCIPALE
  // ============================================================
  function SignatureWidget(config) {
    if (!config) throw new Error('SignatureWidget: configuration requise.');
    if (!config.backendWebhookUrl) throw new Error('SignatureWidget: "backendWebhookUrl" est requis.');
    if (!config.token) throw new Error('SignatureWidget: "token" est requis.');

    this.cfg = Object.assign({
      documentUrl:        null,
      documentDescription: '',
      signingLink:        null,
      apiUrl:             null,
      forceMode:          null,
      breakpoint:         768,
      enableAutoPolling:  false,
      pollingInterval:    5000,
      maxPollingAttempts: 120,
      successRedirectUrl: null,
      cancelRedirectUrl:  null,

      // OTP
      otpSendUrl:        null,
      otpVerifyUrl:      null,
      signerLogin:       null,
      signerUserUuid:    null,
      signerEmail:       null,
      signerPhone:       null,
      otpPurpose:        'signature',
      otpQrUrlTemplate:  null,

      onSigned:           null,
      onError:            null
    }, config);

    this.container = typeof config.container === 'string'
      ? document.querySelector(config.container)
      : config.container;

    if (!this.container) throw new Error('SignatureWidget: container introuvable.');

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

    // État OTP
    this._otpChannel = 'sms';
    this._otpContact = '';
    this._otpCodeSent = false;

    this._render();
  }

  // ------------------------------------------------------------
  // Mode responsive
  // ------------------------------------------------------------
  SignatureWidget.prototype._mode = function () {
    if (this.cfg.forceMode === 'desktop' || this.cfg.forceMode === 'mobile') {
      return this.cfg.forceMode;
    }
    return window.innerWidth >= this.cfg.breakpoint ? 'desktop' : 'mobile';
  };

  // ------------------------------------------------------------
  // Rendu principal
  // ------------------------------------------------------------
  SignatureWidget.prototype._render = function () {
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

    // Le QR d'appairage desktop encode ?mode=handwritten : au chargement
    // sur mobile, on ouvre directement le canvas.
    if (this._readForcedMode() === 'handwritten') {
      this._switchToHandwritten(card);
    } else {
      this._renderOtpSection(card);
    }
  };

  /**
   * Lit ?mode=handwritten dans l'URL pour forcer l'ouverture du canvas.
   * Utilisé par le QR d'appairage desktop -> mobile.
   */
  SignatureWidget.prototype._readForcedMode = function () {
    try {
      var params = new URLSearchParams(window.location.search);
      return (params.get('mode') === 'handwritten') ? 'handwritten' : null;
    } catch (e) {
      return null;
    }
  };

  // ------------------------------------------------------------
  // Aperçu du document
  // ------------------------------------------------------------
  SignatureWidget.prototype._renderPreview = function (container) {
    var self = this;
    var url  = this.cfg.documentUrl;

    // Utiliser le proxy pour les URLs externes (éviter CORS)
    if (isExternalUrl(url) && this.cfg.apiUrl) {
      var baseUrl = this.cfg.apiUrl.replace(/\/api\/v1\/signature$/, '');
      url = baseUrl + '/signature/proxy-document?url=' + encodeURIComponent(url);
    }

    if (!isPdf(url)) {
      var img = document.createElement('img');
      img.src = url;
      img.alt = this.cfg.documentDescription || 'Document à signer';
      img.onerror = function () { container.innerHTML = '<p>Aperçu indisponible.</p>'; };
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

  // ============================================================
  // ÉCRAN OTP (par défaut)
  // ============================================================
  SignatureWidget.prototype._renderOtpSection = function (card) {
    var self = this;

    var section = document.createElement('div');
    section.className = 'sw-section sw-otp-section';
    card.appendChild(section);

    // Titre
    var title = document.createElement('p');
    title.className = 'sw-label';
    title.textContent = 'Recevez un code de vérification';
    section.appendChild(title);

    // Sélecteur de canal
    var channels = document.createElement('div');
    channels.className = 'sw-channels';
    section.appendChild(channels);

    ['sms', 'email', 'whatsapp'].forEach(function (ch) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sw-channel';
      btn.dataset.channel = ch;
      btn.textContent = ch === 'sms' ? 'SMS' : ch === 'email' ? 'Email' : 'WhatsApp';
      
      // Désactiver WhatsApp (pas encore configuré)
      if (ch === 'whatsapp') {
        btn.classList.add('sw-disabled');
        btn.disabled = true;
        btn.title = 'WhatsApp non disponible pour le moment';
      } else {
        btn.addEventListener('click', function () {
          self._otpChannel = ch;
          Array.prototype.forEach.call(channels.children, function (c) {
            c.classList.toggle('sw-active', c.dataset.channel === ch);
          });
          // Pré-remplissage selon canal
          var prefill = (ch === 'email') ? self.cfg.signerEmail : self.cfg.signerPhone;
          if (prefill) contactInput.value = prefill;
          contactInput.type = (ch === 'email') ? 'email' : 'tel';
          contactInput.placeholder = (ch === 'email') ? 'adresse@email.com' : '+225 07 00 00 00 00';
        });
      }
      
      if (ch === self._otpChannel) btn.classList.add('sw-active');
      channels.appendChild(btn);
    });

    // Champ contact
    var field = document.createElement('div');
    field.className = 'sw-field';
    section.appendChild(field);

    var contactInput = document.createElement('input');
    contactInput.type = (self._otpChannel === 'email') ? 'email' : 'tel';
    contactInput.placeholder = (self._otpChannel === 'email') ? 'adresse@email.com' : '+225 07 00 00 00 00';
    contactInput.value = (self._otpChannel === 'email')
      ? (self.cfg.signerEmail || '')
      : (self.cfg.signerPhone || '');
    field.appendChild(contactInput);

    // Bouton recevoir
    var receiveBtn = document.createElement('button');
    receiveBtn.type = 'button';
    receiveBtn.className = 'sw-btn sw-btn-primary';
    receiveBtn.textContent = 'Recevoir le code';
    receiveBtn.style.width = '100%';
    section.appendChild(receiveBtn);

    // Zone code (cachée au départ)
    var codeZone = document.createElement('div');
    codeZone.className = 'sw-hidden';
    section.appendChild(codeZone);

    var codeLabel = document.createElement('p');
    codeLabel.className = 'sw-label';
    codeLabel.textContent = 'Saisissez le code à 6 chiffres';
    codeLabel.style.marginTop = '14px';
    codeZone.appendChild(codeLabel);

    var codeInput = document.createElement('input');
    codeInput.type = 'text';
    codeInput.inputMode = 'numeric';
    codeInput.maxLength = 6;
    codeInput.className = 'sw-code-input';
    codeInput.placeholder = '••••••';
    codeZone.appendChild(codeInput);

    var verifyBtn = document.createElement('button');
    verifyBtn.type = 'button';
    verifyBtn.className = 'sw-btn sw-btn-primary';
    verifyBtn.textContent = 'Vérifier';
    verifyBtn.style.width = '100%';
    verifyBtn.style.marginTop = '10px';
    codeZone.appendChild(verifyBtn);

    var otpInfo = document.createElement('p');
    otpInfo.className = 'sw-otp-info';
    otpInfo.textContent = 'Le code est valable quelques minutes.';
    codeZone.appendChild(otpInfo);

    // Zone expirée (cachée)
    var expiredZone = document.createElement('div');
    expiredZone.className = 'sw-otp-expired sw-hidden';
    expiredZone.textContent = 'Votre code a expiré. Vous pouvez signer à la main.';
    section.appendChild(expiredZone);

    // Lien "Signer à la main"
    var manualLink = document.createElement('span');
    manualLink.className = 'sw-link';
    manualLink.textContent = 'Signer à la main';
    manualLink.addEventListener('click', function () {
      self._goToHandwritten(card);
    });
    section.appendChild(manualLink);

    // Statut
    var status = document.createElement('div');
    status.className = 'sw-status';
    section.appendChild(status);

    // ------------------------------------------------------------
    // Handlers OTP
    // ------------------------------------------------------------

    receiveBtn.addEventListener('click', function () {
      var contact = (contactInput.value || '').trim();
      if (!contact) {
        status.className = 'sw-status err';
        status.textContent = (self._otpChannel === 'email')
          ? 'Veuillez saisir votre adresse email.'
          : 'Veuillez saisir votre numéro de téléphone.';
        return;
      }

      self._otpContact = contact;

      var payload = {
        channel: self._otpChannel,
        purpose: self.cfg.otpPurpose
      };
      if (self.cfg.signerLogin)    payload.login     = self.cfg.signerLogin;
      if (self.cfg.signerUserUuid) payload.user_uuid = self.cfg.signerUserUuid;
      if (self._otpChannel === 'email') payload.email = contact;
      else                              payload.tel   = contact;

      receiveBtn.disabled = true;
      status.className = 'sw-status';
      status.textContent = 'Envoi du code…';

      fetch(self.cfg.otpSendUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          if (!res.ok || !body.success) {
            throw new Error(body.message || ('Erreur ' + res.status));
          }
          return body;
        });
      }).then(function () {
        self._otpCodeSent = true;
        codeZone.classList.remove('sw-hidden');
        receiveBtn.disabled = false;
        receiveBtn.textContent = 'Code envoyé';
        status.className = 'sw-status ok';
        status.textContent = 'Un code vous a été envoyé.';
        codeInput.focus();
      }).catch(function (err) {
        receiveBtn.disabled = false;
        status.className = 'sw-status err';
        status.textContent = 'Erreur : ' + err.message;
        self._error(err);
      });
    });

    verifyBtn.addEventListener('click', function () {
      var code = (codeInput.value || '').trim();
      if (!/^[0-9]{6}$/.test(code)) {
        status.className = 'sw-status err';
        status.textContent = 'Le code doit contenir 6 chiffres.';
        return;
      }

      var payload = {
        code:    code,
        purpose: self.cfg.otpPurpose,
        channel: self._otpChannel,
        contact: self._otpContact
      };
      if (self.cfg.signerLogin)    payload.login     = self.cfg.signerLogin;
      if (self.cfg.signerUserUuid) payload.user_uuid = self.cfg.signerUserUuid;

      verifyBtn.disabled = true;
      status.className = 'sw-status';
      status.textContent = 'Vérification…';

      fetch(self.cfg.otpVerifyUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          if (!res.ok || !body.success) {
            var err = new Error(body.message || 'Code invalide ou expiré.');
            err.code = body.code;
            throw err;
          }
          return body;
        });
      }).then(function (body) {
        // Preuve autoritaire renvoyée par le backend
        return self._buildOtpQrAndSubmit(body.data, card, status);
      }).catch(function (err) {
        verifyBtn.disabled = false;

        if (err.code === 'OTP_INVALID') {
          // Repli automatique proposé
          expiredZone.classList.remove('sw-hidden');
          status.className = 'sw-status err';
          status.textContent = 'Code invalide ou expiré.';
        } else {
          status.className = 'sw-status err';
          status.textContent = 'Erreur : ' + err.message;
        }
        self._error(err);
      });
    });
  };

  // ============================================================
  // Bascule vers la signature manuscrite
  // ============================================================
  /**
   * Point d'entrée du mode « Signer à la main ».
   *
   * - Mobile  : bascule immédiate sur le canvas.
   * - Desktop : affiche le QR d'appairage ; le signataire scanne avec son
   *             téléphone, qui ouvrira cette même page avec ?mode=handwritten
   *             et affichera le canvas. Le poste desktop détecte la signature
   *             par polling (enableAutoPolling).
   */
  SignatureWidget.prototype._goToHandwritten = function (card) {
    var otp = card.querySelector('.sw-otp-section');
    if (otp) otp.remove();

    if (this._mode() === 'mobile' || !this.cfg.signingLink) {
      this._switchToHandwritten(card);
      return;
    }

    this._renderHandwrittenQr(card);
  };

  /**
   * QR d'appairage pour la signature manuscrite.
   * L'URL encodée contient ?mode=handwritten, ce qui force le mobile scanné
   * à ouvrir directement le canvas au lieu de l'écran OTP.
   */
  SignatureWidget.prototype._renderHandwrittenQr = function (card) {
    var self = this;

    var section = document.createElement('div');
    section.className = 'sw-section sw-qr-wrap';
    section.innerHTML =
      '<p class="sw-label">Signez depuis votre mobile</p>' +
      '<div class="sw-qr-box"></div>' +
      '<p class="sw-qr-hint">Scannez ce QR code avec votre téléphone pour ouvrir le pad de signature. ' +
      'Le lien est à usage unique et expire après utilisation.</p>' +
      '<p class="sw-qr-state"></p>' +
      '<span class="sw-link sw-back">← Revenir à la vérification OTP</span>';
    card.appendChild(section);

    var qrBox = section.querySelector('.sw-qr-box');
    var state = section.querySelector('.sw-qr-state');
    var back  = section.querySelector('.sw-back');

    // URL forcée en mode canvas
    var base = this.cfg.signingLink || window.location.href;
    var url  = base + (base.indexOf('?') === -1 ? '?' : '&') + 'mode=handwritten';

    loadScript(CDN.qrcode).then(function () {
      new global.QRCode(qrBox, {
        text: url,
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

    back.addEventListener('click', function () {
      self.stopAutoPolling();
      section.remove();
      self._renderOtpSection(card);
    });
  };

  // ============================================================
  // Construction du QR preuve + envoi au backend Laravel (mode OTP)
  // ============================================================
  SignatureWidget.prototype._buildOtpQrAndSubmit = function (otpData, card, statusEl) {
    var self = this;

    if (statusEl) {
      statusEl.className = 'sw-status';
      statusEl.textContent = 'Génération de la preuve…';
    }

    // 1) Géolocalisation (best-effort)
    return this._getGeo().then(function (geo) {
      // 2) Construire l'URL du QR
      var qrUrl = self._buildOtpQrUrl(otpData, geo);

      // 3) Générer le QR en base64
      return self._generateQrBase64(qrUrl).then(function (qrBase64) {
        // 4) Poster au backend Laravel
        var payload = {
          token:     self.cfg.token,
          method:    'otp_qr',
          signature: qrBase64,
          otp: {
            channel:    otpData.channel    || null,
            contact:    otpData.contact    || null,
            purpose:    otpData.purpose    || null,
            ip_address: otpData.ip_address || null,
            user_agent: otpData.user_agent || null,
            used_at:    otpData.used_at    || null
          },
          geo: {
            lat: geo.lat,
            lng: geo.lng
          }
        };

        return fetch(self.cfg.backendWebhookUrl, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body:    JSON.stringify(payload)
        }).then(function (res) {
          return res.json().catch(function () { return {}; }).then(function (body) {
            if (!res.ok || !body.success) {
              throw new Error(body.message || ('Erreur ' + res.status));
            }
            return body;
          });
        }).then(function (body) {
          self._renderDone(card);
          if (typeof self.cfg.onSigned === 'function') self.cfg.onSigned(body.data || body);
        });
      });
    });
  };

  SignatureWidget.prototype._getGeo = function () {
    return new Promise(function (resolve) {
      if (!navigator.geolocation) return resolve({ lat: null, lng: null });

      var done = false;
      var timer = setTimeout(function () {
        if (done) return;
        done = true;
        resolve({ lat: null, lng: null });
      }, 5000);

      navigator.geolocation.getCurrentPosition(
        function (pos) {
          if (done) return;
          done = true;
          clearTimeout(timer);
          resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        },
        function () {
          if (done) return;
          done = true;
          clearTimeout(timer);
          resolve({ lat: null, lng: null });
        },
        { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 }
      );
    });
  };

  SignatureWidget.prototype._buildOtpQrUrl = function (otpData, geo) {
    var template = this.cfg.otpQrUrlTemplate;

    var values = {
      // Identité de base
      user_uuid:  otpData.user_uuid  || '',
      login:      otpData.login      || '',
      email:      otpData.email      || '',

      // Informations personnelles essentielles
      nom:        otpData.nom        || '',
      prenoms:    otpData.prenoms    || '',
      mobile_1:   otpData.mobile_1   || '',

      // Adresse
      adresse_complete: otpData.adresse_complete || '',

      // Technique
      channel:    otpData.channel    || '',
      contact:    otpData.contact    || '',
      purpose:    otpData.purpose    || '',
      ip_address: otpData.ip_address || '',
      user_agent: otpData.user_agent || '',
      used_at:    otpData.used_at    || '',
      lat:        (geo && geo.lat != null) ? geo.lat : '',
      lng:        (geo && geo.lng != null) ? geo.lng : ''
    };

    // Fallback : pas de template -> on encode les infos dans l'URL du widget
    if (!template) {
      var base = this.cfg.signingLink || window.location.href;
      var params = [];
      Object.keys(values).forEach(function (k) {
        if (values[k] !== '' && values[k] != null) {
          params.push(encodeURIComponent(k) + '=' + encodeURIComponent(values[k]));
        }
      });
      params.push('proof=otp_qr');
      return base + (base.indexOf('?') === -1 ? '?' : '&') + params.join('&');
    }

    // Substitution {clé}
    return template.replace(/\{([a-z_]+)\}/gi, function (_, key) {
      var v = values[key];
      return (v == null) ? '' : encodeURIComponent(String(v));
    });
  };

  SignatureWidget.prototype._generateQrBase64 = function (url) {
    return loadScript(CDN.qrcode).then(function () {
      return new Promise(function (resolve, reject) {
        try {
          // Conteneur offscreen
          var holder = document.createElement('div');
          holder.style.position = 'fixed';
          holder.style.left = '-9999px';
          holder.style.top  = '-9999px';
          document.body.appendChild(holder);

          new global.QRCode(holder, {
            text: url,
            width: 512,
            height: 512,
            correctLevel: global.QRCode.CorrectLevel.M,
            colorDark: '#000000',
            colorLight: '#ffffff'
          });

          // QRCode.js génère soit un canvas, soit des <img>/<table>.
          // On laisse le temps au DOM de se mettre à jour puis on cherche un canvas.
          setTimeout(function () {
            var canvas = holder.querySelector('canvas');
            if (!canvas) {
              // Si QRCode.js a généré des images, on les dessine sur un canvas manuel.
              var imgs = holder.querySelectorAll('img');
              if (imgs.length) {
                canvas = document.createElement('canvas');
                canvas.width = 512; canvas.height = 512;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 512, 512);
                // On redessine via une image intermédiaire
                var img = imgs[0];
                img.onload = function () {
                  ctx.drawImage(img, 0, 0, 512, 512);
                  var b64 = canvas.toDataURL('image/png');
                  holder.remove();
                  resolve(b64);
                };
                img.onerror = function () { holder.remove(); reject(new Error('QR image load failed')); };
                return;
              }
              holder.remove();
              return reject(new Error('Canvas QR introuvable.'));
            }

            var b64 = canvas.toDataURL('image/png');
            holder.remove();
            resolve(b64);
          }, 50);
        } catch (e) {
          reject(e);
        }
      });
    });
  };

  // ============================================================
  // ÉCRAN SIGNATURE GRAPHIQUE (canvas — comportement historique)
  // ============================================================
  SignatureWidget.prototype._switchToHandwritten = function (card) {
    var self = this;

    // Nettoyage des sections précédentes
    var otp = card.querySelector('.sw-otp-section');
    if (otp) otp.remove();
    var qr = card.querySelector('.sw-qr-wrap');
    if (qr) qr.remove();

    var section = document.createElement('div');
    section.className = 'sw-section sw-handwritten-section';
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
      if (!hasSignature) { hasSignature = true; submitBtn.disabled = false; }
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
        method:    'handwritten',
        signature: canvas.toDataURL('image/png')
      };

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

  // ============================================================
  // Confirmation
  // ============================================================
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

  // ============================================================
  // Polling (poste desktop avec auto-polling)
  // ============================================================
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

  SignatureWidget.prototype.destroy = function () {
    this.stopAutoPolling();
    if (this._onResize) window.removeEventListener('resize', this._onResize);
    if (this.host && this.host.parentNode) this.host.parentNode.removeChild(this.host);
    this._destroyed = true;
  };

  global.SignatureWidget = SignatureWidget;
})(window);
// /*!
//  * Widget de Signature Électronique - Version Consolidée
//  * Vanilla JS pur, sans dépendance de build, embarquable dans n'importe quelle application
//  * 
//  * ============================================================================
//  * SPÉCIFICATIONS CLÉS :
//  * ============================================================================
//  * - Aucune persistance du document ni de la signature (tout transite en mémoire)
//  * - Support PDF + images (JPG/PNG)
//  * - Comportement responsive : QR code sur desktop, signature directe sur mobile
//  * - Canvas manuscrit avec support tactile
//  * - Transmission de la signature via webhook à l'application hôte
//  * - Utilisation de Shadow DOM pour isolation CSS
//  * 
//  * ============================================================================
//  * INITIALISATION :
//  * ============================================================================
//  * 
//  *   <!-- Inclusion du script -->
//  *   <script src="https://votre-domaine.com/signature-widget.js"></script>
//  *   
//  *   <!-- Conteneur pour le widget -->
//  *   <div id="signature-container"></div>
//  *   
//  *   <!-- Initialisation -->
//  *   <script>
//  *   new SignatureWidget({
//  *     container: '#signature-container',          // Sélecteur CSS ou élément DOM
//  *     documentUrl: 'https://example.com/document.pdf',
//  *     documentDescription: 'Contrat à signer',
//  *     webhookUrl: 'https://app-host.com/api/signature-webhook',
//  *     apiKey: 'your-secret-api-key',              // Secret partagé pour auth webhook
//  *     signingLink: 'https://backend.com/sign/xyz', // Optionnel : lien avec token pour QR desktop
//  *     backendWebhookUrl: 'https://backend.com/api/v1/signature/webhook', // Backend Laravel pour marquer le token
//  *     forceMode: null,                            // Optionnel : 'desktop' | 'mobile' pour forcer le mode
//  *     onSigned: function(data) { ... },           // Callback après signature réussie
//  *     onError: function(error) { ... }           // Callback en cas d'erreur
//  *   });
//  *   </script>
//  * 
//  * ============================================================================
//  * COMPORTEMENT RESPONSIVE :
//  * ============================================================================
//  * 
//  * Desktop (écran >= 768px) :
//  * - Affiche le document à signer
//  * - Affiche un QR code pointant vers signingLink
//  * - Au scan du QR, ouverture automatique sur mobile avec forceMode: 'mobile'
//  * 
//  * Mobile (écran < 768px) :
//  * - Affiche directement le document et la zone de signature
//  * - Pas de QR code (signature directe)
//  * - Canvas tactile pour signature manuscrite
//  * 
//  * ============================================================================
//  * SÉCURITÉ :
//  * ============================================================================
//  * - Le widget ne stocke RIEN (ni document, ni signature)
//  * - La signature est envoyée directement au webhook de l'app hôte
//  * - L'authentification webhook se fait via header X-Api-Key
//  * - Le backend Laravel gère les tokens Sanctum pour les liens de signature
//  * - Les tokens sont à usage unique et expirent après signature
//  * 
//  * ============================================================================
//  * FORMAT DU WEBHOOK :
//  * ============================================================================
//  * 
//  * POST {webhookUrl}
//  * Headers:
//  *   Content-Type: application/json
//  *   X-Api-Key: {apiKey}
//  * Body:
//  * {
//  *   "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...", // Signature en base64
//  *   "token": "xyz...", // Token de signature (si signingLink fourni)
//  *   "documentUrl": "https://example.com/document.pdf",
//  *   "signedAt": "2024-09-18T10:30:00.000Z"
//  * }
//  * 
//  * ============================================================================
//  * DÉPENDANCES EXTERNES (CDN) :
//  * ============================================================================
//  * - QRCode.js : https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js
//  * - PDF.js : https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js
//  * Ces librairies sont chargées à la demande uniquement si nécessaire
//  */
// (function (global) {
//   'use strict';

//   // ============================================================
//   // CONFIGURATION DES LIBRAIRIES EXTERNES (CDN)
//   // ============================================================
//   // Ces librairies sont chargées à la demande pour les fonctionnalités avancées
//   var CDN = {
//     qrcode: 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
//     pdfjs: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
//     pdfjsWorker: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
//   };

//   // Cache des scripts chargés pour éviter les chargements multiples
//   var loadedScripts = {};

//   /**
//    * Charge dynamiquement un script depuis un CDN
//    * @param {string} src - URL du script à charger
//    * @returns {Promise} Promise résolue quand le script est chargé
//    */
//   function loadScript(src) {
//     if (loadedScripts[src]) return loadedScripts[src];
//     loadedScripts[src] = new Promise(function (resolve, reject) {
//       var s = document.createElement('script');
//       s.src = src;
//       s.async = true;
//       s.onload = resolve;
//       s.onerror = function () { reject(new Error('Impossible de charger ' + src)); };
//       document.head.appendChild(s);
//     });
//     return loadedScripts[src];
//   }

//   /**
//    * Détermine si une URL pointe vers un fichier PDF
//    * @param {string} url - URL à tester
//    * @returns {boolean} True si c'est un PDF
//    */
//   function isPdf(url) {
//     return /\.pdf(\?|#|$)/i.test(url);
//   }

//   // ============================================================
//   // STYLES CSS EMBARQUÉS (Shadow DOM pour isolation)
//   // ============================================================
//   // Utilisation de Shadow DOM pour éviter les conflits CSS avec l'app hôte
//   // Couleurs personnalisées : #075429 (vert foncé), #F7A400 (orange)
//   var CSS = ''
//     + ':host, .sw-root { all: initial; font-family: -apple-system, "Segoe UI", Roboto, sans-serif; }'
//     + '.sw-root { display: block; box-sizing: border-box; color: #1c1c1e; width: 100%; }'
//     + '.sw-root *, .sw-root *::before, .sw-root *::after { box-sizing: border-box; }'
//     + '.sw-card { border: 1px solid #e3e3e6; border-radius: 10px; overflow: hidden; background: #fff; }'
//     + '.sw-header { padding: 14px 16px; border-bottom: 1px solid #eee; background: #075429; color: white; }'
//     + '.sw-title { font-size: 15px; font-weight: 600; margin: 0 0 2px; color: white; }'
//     + '.sw-desc { font-size: 13px; color: rgba(255,255,255,0.9); margin: 0; }'
//     + '.sw-preview { max-height: 420px; overflow: auto; background: #f5f5f7; padding: 12px; text-align: center; }'
//     + '.sw-preview img, .sw-preview canvas { max-width: 100%; height: auto; box-shadow: 0 1px 4px rgba(0,0,0,.15); margin-bottom: 10px; }'
//     + '.sw-section { padding: 16px; border-top: 1px solid #eee; }'
//     + '.sw-label { font-size: 13px; font-weight: 600; margin: 0 0 8px; color: #075429; }'
//     + '.sw-canvas-wrap { border: 2px solid #F7A400; border-radius: 8px; background: #fff; touch-action: none; }'
//     + '.sw-canvas-wrap canvas { display: block; width: 100%; height: 180px; cursor: crosshair; }'
//     + '.sw-row { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }'
//     + '.sw-btn { appearance: none; border: 1px solid #d0d0d5; background: #fff; color: #1c1c1e; '
//     +   'font-size: 13px; font-weight: 500; padding: 9px 14px; border-radius: 8px; cursor: pointer; }'
//     + '.sw-btn:hover { background: #f2f2f4; }'
//     + '.sw-btn-primary { background: #F7A400; border-color: #F7A400; color: #fff; }'
//     + '.sw-btn-primary:hover { background: #e59400; }'
//     + '.sw-btn-primary:disabled { opacity: .45; cursor: default; }'
//     + '.sw-qr-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 24px 16px; text-align: center; background: #f9f9f9; }'
//     + '.sw-qr-box { padding: 12px; background: #fff; border: 2px solid #075429; border-radius: 10px; }'
//     + '.sw-qr-hint { font-size: 13px; color: #6b6b70; max-width: 320px; }'
//     + '.sw-status { font-size: 12.5px; margin-top: 8px; }'
//     + '.sw-status.ok { color: #075429; }'
//     + '.sw-status.err { color: #c62828; }'
//     + '.sw-done { padding: 28px 16px; text-align: center; background: #d4edda; }'
//     + '.sw-done .sw-check { font-size: 34px; color: #075429; }'
//     + '.sw-done p { color: #075429; }';

//   // ============================================================
//   // CLASSE PRINCIPALE DU WIDGET
//   // ============================================================
  
//   /**
//    * Constructeur du Widget de Signature
//    * @param {Object} config - Configuration du widget
//    * @param {string|HTMLElement} config.container - Conteneur du widget (sélecteur CSS ou élément DOM)
//    * @param {string} config.documentUrl - URL du document à signer (HTTP(S))
//    * @param {string} config.webhookUrl - URL du webhook de l'app hôte pour recevoir la signature
//    * @param {string} config.backendWebhookUrl - URL du webhook du backend Laravel (pour marquer le token comme utilisé)
//    * @param {string} config.apiKey - Secret partagé pour authentification webhook
//    * @param {string} [config.documentDescription] - Description du document affichée
//    * @param {string} [config.signingLink] - Lien de signature avec token (pour QR code desktop)
//    * @param {string} [config.forceMode] - Force le mode : 'desktop' | 'mobile'
//    * @param {Function} [config.onSigned] - Callback après signature réussie
//    * @param {Function} [config.onError] - Callback en cas d'erreur
//    * @param {number} [config.breakpoint=768] - Breakpoint responsive (pixels)
//    * @param {boolean} [config.useProxy=false] - Utiliser le proxy pour contourner CORS
//    */
//   function SignatureWidget(config) {
//     // Validation des paramètres obligatoires
//     if (!config || !config.webhookUrl) {
//       throw new Error('SignatureWidget: "webhookUrl" est requis.');
//     }
    
//     // documentUrl est maintenant optionnel (signature sans document)
    
//     // Fusion avec la configuration par défaut
//     this.cfg = Object.assign({
//       breakpoint: 768,                      // Breakpoint responsive standard
//       documentDescription: '',              // Description vide par défaut
//       apiKey: null,                         // API key optionnelle
//       signingLink: null,                    // Lien de signature optionnel
//       forceMode: null,                      // Mode forcé optionnel
//       onSigned: null,                       // Callback signature optionnel
//       onError: null,                        // Callback erreur optionnel
//       enableAutoPolling: false,             // Activer polling automatique (scénario agence)
//       pollingInterval: 5000,                // Intervalle de polling en ms
//       maxPollingAttempts: 60,               // Max tentatives de polling
//       successRedirectUrl: null,             // URL de redirection après succès
//       cancelRedirectUrl: null,              // URL de redirection après annulation
//       useProxy: false,                      // Utiliser le proxy pour contourner CORS
//       backendWebhookUrl: null               // URL du webhook backend Laravel (pour marquer le token comme utilisé)
//     }, config);
    
//     // Appliquer le proxy si activé et si l'URL n'est pas déjà un proxy
//     if (this.cfg.useProxy && this.cfg.documentUrl && !this.cfg.documentUrl.includes('/signature/proxy-document')) {
//       this.cfg.documentUrl = window.location.origin + '/signature/proxy-document?url=' + encodeURIComponent(this.cfg.documentUrl);
//     }

//     // Récupération du conteneur
//     this.container = typeof config.container === 'string'
//       ? document.querySelector(config.container)
//       : config.container;

//     if (!this.container) {
//       throw new Error('SignatureWidget: container introuvable.');
//     }

//     // Création du Shadow DOM pour isolation CSS
//     this.host = document.createElement('div');
//     this.container.appendChild(this.host);
//     this.shadow = this.host.attachShadow({ mode: 'open' });

//     // Injection des styles CSS
//     var style = document.createElement('style');
//     style.textContent = CSS;
//     this.shadow.appendChild(style);

//     // Création de la racine du widget
//     this.root = document.createElement('div');
//     this.root.className = 'sw-root';
//     this.shadow.appendChild(this.root);

//     // Rendu initial
//     this._render();
//   }

//   /**
//    * Détermine le mode d'affichage (desktop ou mobile)
//    * @returns {string} 'desktop' ou 'mobile'
//    */
//   SignatureWidget.prototype._mode = function () {
//     // Si le mode est forcé, l'utiliser
//     if (this.cfg.forceMode === 'desktop' || this.cfg.forceMode === 'mobile') return this.cfg.forceMode;
//     // Sinon, détecter automatiquement selon la largeur d'écran
//     return window.innerWidth >= this.cfg.breakpoint ? 'desktop' : 'mobile';
//   };

//   /**
//    * Rendu principal du widget selon le mode (desktop/mobile)
//    * Desktop : affiche QR code si signingLink fourni
//    * Mobile : affiche directement la zone de signature
//    */
//   SignatureWidget.prototype._render = function () {
//     var mode = this._mode();
//     this.root.innerHTML = '';

//     // Création de la carte principale
//     var card = document.createElement('div');
//     card.className = 'sw-card';
//     this.root.appendChild(card);

//     // Header avec titre et description
//     var header = document.createElement('div');
//     header.className = 'sw-header';
//     header.innerHTML =
//       '<p class="sw-title">Document à signer</p>' +
//       '<p class="sw-desc"></p>';
//     header.querySelector('.sw-desc').textContent = this.cfg.documentDescription || '';
//     card.appendChild(header);

//     // Zone de prévisualisation du document
//     var preview = document.createElement('div');
//     preview.className = 'sw-preview';
//     card.appendChild(preview);
//     this._renderPreview(preview);

//     // Affichage conditionnel selon le mode
//     if (mode === 'desktop' && this.cfg.signingLink) {
//       // Desktop avec lien de signature : afficher QR code
//       this._renderQrSection(card);
//     } else {
//       // Mobile ou desktop sans lien : afficher zone de signature directe
//       this._renderSignSection(card);
//     }
//   };

//   /**
//    * Rendu de la prévisualisation du document
//    * Supporte PDF (via PDF.js) et images (JPG/PNG)
//    * @param {HTMLElement} container - Conteneur pour la prévisualisation
//    */
//   SignatureWidget.prototype._renderPreview = function (container) {
//     var url = this.cfg.documentUrl;
    
//     if (isPdf(url)) {
//       // Gestion des PDF : chargement via PDF.js
//       var self = this;
//       var msg = document.createElement('p');
//       msg.className = 'sw-desc';
//       msg.textContent = 'Chargement du document…';
//       container.appendChild(msg);
      
//       // Chargement de PDF.js depuis CDN
//       loadScript(CDN.pdfjs).then(function () {
//         var pdfjsLib = global.pdfjsLib;
//         pdfjsLib.GlobalWorkerOptions.workerSrc = CDN.pdfjsWorker;
//         return pdfjsLib.getDocument(url).promise;
//       }).then(function (pdf) {
//         container.innerHTML = '';
//         // Rendu de chaque page du PDF
//         var renderPage = function (num) {
//           return pdf.getPage(num).then(function (page) {
//             var viewport = page.getViewport({ scale: 1.4 });
//             var canvas = document.createElement('canvas');
//             canvas.width = viewport.width;
//             canvas.height = viewport.height;
//             container.appendChild(canvas);
//             return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
//           });
//         };
//         // Chainage du rendu des pages
//         var chain = Promise.resolve();
//         for (var i = 1; i <= pdf.numPages; i++) {
//           (function (n) { chain = chain.then(function () { return renderPage(n); }); })(i);
//         }
//         return chain;
//       }).catch(function (err) {
//         // En cas d'erreur de chargement PDF, afficher un lien
//         container.innerHTML = '';
//         var p = document.createElement('p');
//         p.className = 'sw-desc';
//         p.textContent = 'Aperçu indisponible. ';
//         var a = document.createElement('a');
//         a.href = url; a.target = '_blank'; a.rel = 'noopener'; a.textContent = 'Ouvrir le document';
//         p.appendChild(a);
//         container.appendChild(p);
//         self._error(err);
//       });
//     } else {
//       // Gestion des images : affichage direct
//       var img = document.createElement('img');
//       img.src = url;
//       img.alt = this.cfg.documentDescription || 'Document à signer';
//       container.appendChild(img);
//     }
//   };

//   /**
//    * Rendu de la section QR code (mode desktop)
//    * Affiche un QR code pointant vers le lien de signature
//    * Optionnel : formulaire d'envoi du lien par email/SMS/WhatsApp
//    * Démarre automatiquement le polling si enableAutoPolling est activé
//    * @param {HTMLElement} card - Carte principale du widget
//    */
//   SignatureWidget.prototype._renderQrSection = function (card) {
//     var self = this;
//     var section = document.createElement('div');
//     section.className = 'sw-section sw-qr-wrap';
//     section.innerHTML =
//       '<p class="sw-label">Signez depuis votre mobile</p>' +
//       '<div class="sw-qr-box"></div>' +
//       '<p class="sw-qr-hint">Scannez ce QR code avec votre téléphone pour ouvrir la page de signature. Le lien expire après usage.</p>';
//     card.appendChild(section);

//     // Génération du QR code via QRCode.js
//     var qrBox = section.querySelector('.sw-qr-box');
//     loadScript(CDN.qrcode).then(function () {
//       new global.QRCode(qrBox, {
//         text: self.cfg.signingLink,
//         width: 168,
//         height: 168,
//         correctLevel: global.QRCode.CorrectLevel.M
//       });
      
//       // Démarrer le polling automatique si activé (scénario agence)
//       if (self.cfg.enableAutoPolling && self.cfg.signingLink) {
//         var token = self._tokenFromLink();
//         if (token) {
//           self.startAutoPolling(token);
//         }
//       }
//     }).catch(function (err) {
//       qrBox.textContent = 'QR indisponible.';
//       self._error(err);
//     });
//   };

//   /**
//    * Rendu de la section de signature (mode mobile)
//    * Affiche un canvas pour la signature manuscrite
//    * Gère les événements souris et tactiles
//    * @param {HTMLElement} card - Carte principale du widget
//    */
//   SignatureWidget.prototype._renderSignSection = function (card) {
//     var self = this;
//     var section = document.createElement('div');
//     section.className = 'sw-section';
//     section.innerHTML =
//       '<p class="sw-label">Votre signature</p>' +
//       '<div class="sw-canvas-wrap"><canvas></canvas></div>' +
//       '<div class="sw-row">' +
//         '<button type="button" class="sw-btn sw-clear">Effacer</button>' +
//         '<button type="button" class="sw-btn sw-btn-primary sw-submit" disabled>Valider la signature</button>' +
//       '</div>' +
//       '<div class="sw-status"></div>';
//     card.appendChild(section);

//     var canvas = section.querySelector('canvas');
//     var status = section.querySelector('.sw-status');
//     var submitBtn = section.querySelector('.sw-submit');
//     var clearBtn = section.querySelector('.sw-clear');

//     var ctx = canvas.getContext('2d');
//     var drawing = false;
//     var hasSignature = false;
//     var last = null;

//     /**
//      * Redimensionne le canvas en tenant compte du device pixel ratio
//      * Pour un rendu net sur les écrans retina
//      */
//     function resizeCanvas() {
//       var rect = canvas.getBoundingClientRect();
//       var ratio = window.devicePixelRatio || 1;
//       canvas.width = rect.width * ratio;
//       canvas.height = rect.height * ratio;
//       ctx.scale(ratio, ratio);
//       ctx.lineWidth = 2;
//       ctx.lineCap = 'round';
//       ctx.lineJoin = 'round';
//       ctx.strokeStyle = '#1c1c1e';
//     }
//     resizeCanvas();
//     window.addEventListener('resize', resizeCanvas);

//     /**
//      * Calcule la position du pointeur relativement au canvas
//      * Gère à la fois souris et tactile
//      */
//     function pointerPos(e) {
//       var rect = canvas.getBoundingClientRect();
//       var p = e.touches ? e.touches[0] : e;
//       return { x: p.clientX - rect.left, y: p.clientY - rect.top };
//     }
    
//     /**
//      * Début du dessin (mousedown ou touchstart)
//      */
//     function start(e) {
//       e.preventDefault();
//       drawing = true;
//       last = pointerPos(e);
//     }
    
//     /**
//      * Dessin en cours (mousemove ou touchmove)
//      */
//     function move(e) {
//       if (!drawing) return;
//       e.preventDefault();
//       var p = pointerPos(e);
//       ctx.beginPath();
//       ctx.moveTo(last.x, last.y);
//       ctx.lineTo(p.x, p.y);
//       ctx.stroke();
//       last = p;
//       // Activer le bouton de validation dès qu'il y a une signature
//       if (!hasSignature) {
//         hasSignature = true;
//         submitBtn.disabled = false;
//       }
//     }
    
//     /**
//      * Fin du dessin (mouseup ou touchend)
//      */
//     function end() { drawing = false; }

//     // Événements souris
//     canvas.addEventListener('mousedown', start);
//     canvas.addEventListener('mousemove', move);
//     window.addEventListener('mouseup', end);
    
//     // Événements tactiles (mobile)
//     canvas.addEventListener('touchstart', start, { passive: false });
//     canvas.addEventListener('touchmove', move, { passive: false });
//     canvas.addEventListener('touchend', end);

//     // Bouton d'effacement
//     clearBtn.addEventListener('click', function () {
//       ctx.clearRect(0, 0, canvas.width, canvas.height);
//       hasSignature = false;
//       submitBtn.disabled = true;
//       status.textContent = '';
//       status.className = 'sw-status';
//     });

//     // Bouton de validation et envoi
//     submitBtn.addEventListener('click', function () {
//       if (!hasSignature) return;
//       submitBtn.disabled = true;
//       status.className = 'sw-status';
//       status.textContent = 'Envoi en cours…';

//       // Export de la signature en base64
//       var signatureBase64 = canvas.toDataURL('image/png');

//       // Déterminer l'URL du webhook backend (si fournie) ou directe
//       var webhookUrl = self.cfg.backendWebhookUrl || self.cfg.webhookUrl;
      
//       // Préparer le payload
//       var payload = {
//         success: true,
//         status: 200,
//         signature: signatureBase64,
//         token: self._tokenFromLink(),
//         documentUrl: self.cfg.documentUrl,
//         signedAt: new Date().toISOString()
//       };
      
//       // Si backendWebhookUrl est fourni, inclure le webhook de l'app hôte pour le relais
//       if (self.cfg.backendWebhookUrl && self.cfg.webhookUrl) {
//         payload.forward_webhook_url = self.cfg.webhookUrl;
//       }

//       // Envoi au webhook (backend Laravel ou direct)
//       fetch(webhookUrl, {
//         method: 'POST',
//         headers: Object.assign(
//           { 'Content-Type': 'application/json' },
//           self.cfg.apiKey ? { 'X-Api-Key': self.cfg.apiKey } : {}
//         ),
//         body: JSON.stringify(payload)
//       }).then(function (res) {
//         if (!res.ok) throw new Error('Le webhook a répondu ' + res.status);
//         return res.json().catch(function () { return {}; });
//       }).then(function (data) {
//         // Signature réussie
//         self._renderDone(card);
//         if (typeof self.cfg.onSigned === 'function') self.cfg.onSigned(data);
//       }).catch(function (err) {
//         // Erreur d'envoi
//         submitBtn.disabled = false;
//         status.className = 'sw-status err';
//         status.textContent = 'Erreur: ' + err.message;
//         self._error(err);
//       });
//     });
//   };

//   /**
//    * Extrait le token depuis le lien de signature
//    * Utile pour l'envoi au webhook
//    * @returns {string|null} Token extrait ou null
//    */
//   SignatureWidget.prototype._tokenFromLink = function () {
//     if (!this.cfg.signingLink) return null;

//     try {
//       var raw = String(this.cfg.signingLink).trim();
//       if (!raw) return null;

//       var normalized = raw.replace(/[?#].*$/, '').replace(/\/+$/, '');
//       var parsed;

//       try {
//         parsed = new URL(normalized);
//         normalized = parsed.pathname || normalized;
//       } catch (e) {
//         // URL relative or non-standard input: keep as-is
//       }

//       normalized = normalized.split('/').filter(Boolean).pop() || null;
//       return normalized;
//     } catch (e) {
//       return null;
//     }
//   };

//   /**
//    * Rendu de l'écran de succès après signature
//    * Remplace les sections de signature par un message de confirmation
//    * Redirige vers l'URL de succès si fournie
//    * @param {HTMLElement} card - Carte principale du widget
//    */
//   SignatureWidget.prototype._renderDone = function (card) {
//     // Supprimer les sections de signature et QR
//     card.querySelectorAll('.sw-section, .sw-qr-wrap').forEach(function (el) { el.remove(); });
    
//     // Afficher le message de succès
//     var done = document.createElement('div');
//     done.className = 'sw-done';
//     done.innerHTML = '<div class="sw-check">✓</div><p class="sw-label">Document signé avec succès</p>';
//     card.appendChild(done);
    
//     // Arrêter le polling auto s'il est actif
//     if (this._pollingInterval) {
//       this.stopAutoPolling();
//     }
    
//     // Redirection automatique si successRedirectUrl est fourni
//     if (this.cfg.successRedirectUrl) {
//       setTimeout(function () {
//         window.location.href = this.cfg.successRedirectUrl;
//       }.bind(this), 2000); // Redirection après 2 secondes
//     }
//   };

//   /**
//    * Gestionnaire d'erreurs
//    * Appelle le callback onError si fourni, sinon log dans la console
//    * @param {Error} err - Erreur à gérer
//    */
//   SignatureWidget.prototype._error = function (err) {
//     if (typeof this.cfg.onError === 'function') this.cfg.onError(err);
//     else if (global.console) console.error('[SignatureWidget]', err);
//   };

//   /**
//    * Démarrer le polling automatique pour vérifier le statut de signature
//    * Utilisé dans le scénario grand écran (QR code) où l'app client doit attendre
//    * @param {string} token - Token de signature à vérifier
//    */
//   SignatureWidget.prototype.startAutoPolling = function (token) {
//     if (!token || !this.cfg.signingLink) {
//       return;
//     }

//     var self = this;
//     var pollingAttempts = 0;
//     var apiUrl = this.cfg.apiUrl || '/api/v1/signature';
    
//     this._pollingInterval = setInterval(function () {
//       pollingAttempts++;
      
//       if (pollingAttempts > self.cfg.maxPollingAttempts) {
//         self.stopAutoPolling();
//         if (typeof self.cfg.onError === 'function') {
//           self.cfg.onError(new Error('Polling arrêté: nombre maximum de tentatives atteint'));
//         }
//         return;
//       }
      
//       // Vérifier le statut du token
//       fetch(apiUrl + '/token/' + token + '/status')
//         .then(function (response) { return response.json(); })
//         .then(function (data) {
//           if (data.success && data.data) {
//             if (data.data.is_used) {
//               // Signature terminée
//               self.stopAutoPolling();
//               if (typeof self.cfg.onSigned === 'function') {
//                 self.cfg.onSigned({
//                   status: 'completed',
//                   signed_at: data.data.signed_at,
//                   token: token
//                 });
//               }
//             }
            
//             if (data.data.expired) {
//               // Token expiré
//               self.stopAutoPolling();
//               if (typeof self.cfg.onError === 'function') {
//                 self.cfg.onError(new Error('Token expiré'));
//               }
//             }
//           }
//         })
//         .catch(function (error) {
//           // Erreur de polling, on continue jusqu'au max tentatives
//           if (global.console) console.warn('[SignatureWidget] Erreur polling:', error);
//         });
//     }, this.cfg.pollingInterval);
//   };

//   /**
//    * Arrêter le polling automatique
//    */
//   SignatureWidget.prototype.stopAutoPolling = function () {
//     if (this._pollingInterval) {
//       clearInterval(this._pollingInterval);
//       this._pollingInterval = null;
//     }
//   };

//   // Export global de la classe
//   global.SignatureWidget = SignatureWidget;
// })(window);


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
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
 * DESIGN
 * ============================================================================
 *   Palette YAKOA : #075429 (vert), #F7A400 (orange), #0a6b35 (vert clair)
 *   Saisie OTP : 6 cases individuelles avec auto-avance et collage.
 *   Après envoi du code : la zone de demande (canal + contact) est masquée,
 *   seules restent la saisie du code + un compte à rebours.
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
 *     otpQrUrlTemplate:  'https://hote/verify?user={user_uuid}&ch={channel}…',
 *
 *     enableAutoPolling: true,
 *     successRedirectUrl: '…', cancelRedirectUrl: '…',
 *     onSigned: function (data) {}, onError: function (err) {}
 *   });
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

  /**
   * Formate un nombre de secondes en mm:ss.
   */
  function formatCountdown(seconds) {
    if (seconds < 0) seconds = 0;
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
  }

  // ============================================================
  // STYLES — refonte moderne, palette YAKOA
  // ============================================================
  var CSS = ''
    // --- Reset / base ---
    + ':host, .sw-root { all: initial; font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; -webkit-font-smoothing: antialiased; }'
    + '.sw-root { display: block; box-sizing: border-box; color: #0f172a; width: 100%; font-size: 14px; line-height: 1.5; }'
    + '.sw-root *, .sw-root *::before, .sw-root *::after { box-sizing: border-box; }'

    // --- Variables ---
    + ':host {'
    +   ' --sw-green: #075429;'
    +   ' --sw-green-light: #0a6b35;'
    +   ' --sw-orange: #F7A400;'
    +   ' --sw-orange-dark: #e59400;'
    +   ' --sw-gray-50: #f9fafb;'
    +   ' --sw-gray-100: #f3f4f6;'
    +   ' --sw-gray-200: #e5e7eb;'
    +   ' --sw-gray-300: #d1d5db;'
    +   ' --sw-gray-500: #6b7280;'
    +   ' --sw-gray-700: #374151;'
    +   ' --sw-red: #dc2626;'
    +   ' --sw-radius: 12px;'
    +   ' --sw-shadow-sm: 0 1px 2px rgba(15, 23, 42, .04);'
    +   ' --sw-shadow-md: 0 4px 12px rgba(15, 23, 42, .08);'
    +   ' --sw-shadow-lg: 0 12px 32px rgba(15, 23, 42, .12);'
    +   ' --sw-transition: 220ms cubic-bezier(.4, 0, .2, 1);'
    + '}'

    // --- Carte principale ---
    + '.sw-card {'
    +   ' border: 1px solid var(--sw-gray-200);'
    +   ' border-radius: 16px;'
    +   ' overflow: hidden;'
    +   ' background: #fff;'
    +   ' box-shadow: var(--sw-shadow-md);'
    +   ' transition: box-shadow var(--sw-transition);'
    + '}'
    + '.sw-card:hover { box-shadow: var(--sw-shadow-lg); }'

    // --- En-tête ---
    + '.sw-header {'
    +   ' padding: 20px 22px;'
    +   ' background: linear-gradient(135deg, var(--sw-green) 0%, var(--sw-green-light) 100%);'
    +   ' color: #fff;'
    +   ' position: relative;'
    +   ' overflow: hidden;'
    + '}'
    + '.sw-header::after {'
    +   ' content: "";'
    +   ' position: absolute;'
    +   ' top: -60px; right: -40px;'
    +   ' width: 160px; height: 160px;'
    +   ' border-radius: 50%;'
    +   ' background: radial-gradient(circle, rgba(247,164,0,.28) 0%, rgba(247,164,0,0) 70%);'
    +   ' pointer-events: none;'
    + '}'
    + '.sw-title { font-size: 16px; font-weight: 700; margin: 0 0 4px; color: #fff; letter-spacing: -.01em; }'
    + '.sw-desc { font-size: 13px; color: rgba(255,255,255,.85); margin: 0; }'

    // --- Aperçu document ---
    + '.sw-preview { max-height: 400px; overflow: auto; background: var(--sw-gray-50); padding: 16px; text-align: center; }'
    + '.sw-preview img, .sw-preview canvas { max-width: 100%; height: auto; border-radius: 6px; box-shadow: var(--sw-shadow-md); margin-bottom: 10px; transition: transform var(--sw-transition); }'
    + '.sw-preview img:hover { transform: scale(1.01); }'
    + '.sw-preview p { font-size: 13px; color: var(--sw-gray-500); margin: 0; }'

    // --- Sections ---
    + '.sw-section { padding: 22px; border-top: 1px solid var(--sw-gray-100); animation: sw-fade-slide 280ms cubic-bezier(.4,0,.2,1); }'
    + '@keyframes sw-fade-slide {'
    +   ' from { opacity: 0; transform: translateY(6px); }'
    +   ' to   { opacity: 1; transform: translateY(0); }'
    + '}'
    + '.sw-label { font-size: 13px; font-weight: 700; margin: 0 0 12px; color: var(--sw-green); letter-spacing: -.005em; }'

    // --- Canvas ---
    + '.sw-canvas-wrap {'
    +   ' border: 2px dashed var(--sw-orange);'
    +   ' border-radius: var(--sw-radius);'
    +   ' background: #fff;'
    +   ' touch-action: none;'
    +   ' transition: border-color var(--sw-transition), background var(--sw-transition);'
    +   ' position: relative;'
    + '}'
    + '.sw-canvas-wrap:hover { background: #fffdf7; }'
    + '.sw-canvas-wrap canvas { display: block; width: 100%; height: 180px; cursor: crosshair; border-radius: 10px; }'

    // --- Rangée de boutons ---
    + '.sw-row { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }'

    // --- Boutons ---
    + '.sw-btn {'
    +   ' appearance: none;'
    +   ' border: 1px solid var(--sw-gray-300);'
    +   ' background: #fff;'
    +   ' color: var(--sw-gray-700);'
    +   ' font-size: 13px; font-weight: 600;'
    +   ' padding: 11px 18px;'
    +   ' border-radius: 10px;'
    +   ' cursor: pointer;'
    +   ' transition: all var(--sw-transition);'
    +   ' font-family: inherit;'
    +   ' box-shadow: var(--sw-shadow-sm);'
    +   ' user-select: none;'
    + '}'
    + '.sw-btn:hover:not(:disabled) { background: var(--sw-gray-50); transform: translateY(-1px); box-shadow: var(--sw-shadow-md); }'
    + '.sw-btn:active:not(:disabled) { transform: translateY(0); }'
    + '.sw-btn:focus-visible { outline: 2px solid var(--sw-orange); outline-offset: 2px; }'
    + '.sw-btn:disabled { opacity: .5; cursor: not-allowed; }'
    + '.sw-btn-primary {'
    +   ' background: linear-gradient(135deg, var(--sw-orange) 0%, #ffb928 100%);'
    +   ' border-color: var(--sw-orange);'
    +   ' color: #fff;'
    +   ' text-shadow: 0 1px 0 rgba(0,0,0,.06);'
    + '}'
    + '.sw-btn-primary:hover:not(:disabled) {'
    +   ' background: linear-gradient(135deg, var(--sw-orange-dark) 0%, var(--sw-orange) 100%);'
    +   ' box-shadow: 0 6px 18px rgba(247,164,0,.35);'
    + '}'

    // --- QR ---
    + '.sw-qr-wrap {'
    +   ' display: flex; flex-direction: column; align-items: center;'
    +   ' gap: 14px; padding: 28px 20px; text-align: center;'
    +   ' background: linear-gradient(180deg, #fafbfc 0%, var(--sw-gray-50) 100%);'
    + '}'
    + '.sw-qr-box {'
    +   ' padding: 14px; background: #fff;'
    +   ' border: 2px solid var(--sw-green);'
    +   ' border-radius: 14px;'
    +   ' box-shadow: var(--sw-shadow-md);'
    +   ' animation: sw-pop-in 320ms cubic-bezier(.34, 1.56, .64, 1);'
    + '}'
    + '.sw-qr-box img, .sw-qr-box canvas { display: block; border-radius: 4px; }'
    + '@keyframes sw-pop-in {'
    +   ' from { opacity: 0; transform: scale(.92); }'
    +   ' to   { opacity: 1; transform: scale(1); }'
    + '}'
    + '.sw-qr-hint { font-size: 13px; color: var(--sw-gray-500); max-width: 340px; line-height: 1.55; }'
    + '.sw-qr-state { font-size: 12.5px; color: var(--sw-green); font-weight: 600; min-height: 18px; }'
    + '.sw-qr-state::before {'
    +   ' content: "";'
    +   ' display: inline-block;'
    +   ' width: 8px; height: 8px; border-radius: 50%;'
    +   ' background: var(--sw-orange);'
    +   ' margin-right: 8px;'
    +   ' vertical-align: middle;'
    +   ' animation: sw-pulse 1.6s ease-in-out infinite;'
    + '}'
    + '@keyframes sw-pulse {'
    +   ' 0%, 100% { opacity: 1; transform: scale(1); }'
    +   ' 50%      { opacity: .5; transform: scale(1.25); }'
    + '}'

    // --- Statut ---
    + '.sw-status {'
    +   ' font-size: 13px; margin-top: 12px; min-height: 20px;'
    +   ' display: flex; align-items: center; gap: 8px;'
    +   ' transition: color var(--sw-transition);'
    + '}'
    + '.sw-status.ok { color: var(--sw-green); font-weight: 600; }'
    + '.sw-status.ok::before { content: "✓"; font-weight: 700; }'
    + '.sw-status.err { color: var(--sw-red); font-weight: 500; }'
    + '.sw-status.err::before { content: "⚠"; font-weight: 700; }'
    + '.sw-status.loading::before {'
    +   ' content: "";'
    +   ' width: 14px; height: 14px; border-radius: 50%;'
    +   ' border: 2px solid var(--sw-gray-200);'
    +   ' border-top-color: var(--sw-orange);'
    +   ' animation: sw-spin .7s linear infinite;'
    + '}'
    + '@keyframes sw-spin { to { transform: rotate(360deg); } }'

    // --- Écran terminé ---
    + '.sw-done {'
    +   ' padding: 36px 20px; text-align: center;'
    +   ' background: linear-gradient(180deg, #e8f5ec 0%, #d4edda 100%);'
    +   ' animation: sw-fade-slide 320ms cubic-bezier(.4,0,.2,1);'
    + '}'
    + '.sw-done .sw-check {'
    +   ' font-size: 44px; color: var(--sw-green);'
    +   ' display: inline-block;'
    +   ' width: 72px; height: 72px; line-height: 72px;'
    +   ' border-radius: 50%; background: #fff;'
    +   ' box-shadow: 0 8px 24px rgba(7,84,41,.18);'
    +   ' animation: sw-check-pop 480ms cubic-bezier(.34, 1.56, .64, 1);'
    +   ' margin-bottom: 12px;'
    + '}'
    + '@keyframes sw-check-pop {'
    +   ' 0%   { transform: scale(.5); opacity: 0; }'
    +   ' 60%  { transform: scale(1.12); }'
    +   ' 100% { transform: scale(1); opacity: 1; }'
    + '}'
    + '.sw-done .sw-label { color: var(--sw-green); font-size: 15px; margin: 0; }'

    // --- OTP : canaux ---
    + '.sw-channels { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }'
    + '.sw-channel {'
    +   ' display: flex; flex-direction: column; align-items: center; justify-content: center;'
    +   ' gap: 6px;'
    +   ' padding: 14px 8px;'
    +   ' border: 1.5px solid var(--sw-gray-200);'
    +   ' background: #fff;'
    +   ' border-radius: var(--sw-radius);'
    +   ' cursor: pointer;'
    +   ' font-size: 12.5px; font-weight: 600;'
    +   ' color: var(--sw-gray-700);'
    +   ' transition: all var(--sw-transition);'
    +   ' font-family: inherit;'
    +   ' position: relative;'
    +   ' overflow: hidden;'
    + '}'
    + '.sw-channel .sw-channel-icon { font-size: 20px; line-height: 1; transition: transform var(--sw-transition); }'
    + '.sw-channel:hover:not(.sw-disabled) {'
    +   ' border-color: var(--sw-green);'
    +   ' background: #f7fbf8;'
    +   ' transform: translateY(-2px);'
    +   ' box-shadow: var(--sw-shadow-md);'
    + '}'
    + '.sw-channel:hover:not(.sw-disabled) .sw-channel-icon { transform: scale(1.12); }'
    + '.sw-channel.sw-active {'
    +   ' border-color: var(--sw-green);'
    +   ' background: linear-gradient(180deg, #eafaf1 0%, #dcefe4 100%);'
    +   ' color: var(--sw-green);'
    +   ' box-shadow: 0 4px 12px rgba(7,84,41,.12);'
    + '}'
    + '.sw-channel.sw-active::after {'
    +   ' content: "✓";'
    +   ' position: absolute; top: 6px; right: 8px;'
    +   ' font-size: 11px; color: var(--sw-green); font-weight: 700;'
    + '}'
    + '.sw-channel.sw-disabled { opacity: .45; cursor: not-allowed; background: var(--sw-gray-100); }'
    + '.sw-channel.sw-disabled .sw-channel-icon { filter: grayscale(1); }'
    + '.sw-channel:focus-visible { outline: 2px solid var(--sw-orange); outline-offset: 2px; }'

    // --- OTP : champ contact ---
    + '.sw-field { position: relative; margin-bottom: 14px; }'
    + '.sw-field input {'
    +   ' width: 100%;'
    +   ' padding: 14px 16px 14px 44px;'
    +   ' font-size: 14.5px;'
    +   ' border: 1.5px solid var(--sw-gray-200);'
    +   ' border-radius: var(--sw-radius);'
    +   ' background: #fff;'
    +   ' color: #0f172a;'
    +   ' outline: none;'
    +   ' transition: all var(--sw-transition);'
    +   ' font-family: inherit;'
    + '}'
    + '.sw-field input:hover { border-color: var(--sw-gray-300); }'
    + '.sw-field input:focus {'
    +   ' border-color: var(--sw-green);'
    +   ' box-shadow: 0 0 0 4px rgba(7,84,41,.10);'
    +   ' background: #fafffc;'
    + '}'
    + '.sw-field input:disabled {'
    +   ' background: #f5f5f5;'
    +   ' color: #6b7280;'
    +   ' cursor: not-allowed;'
    +   ' opacity: 0.7;'
    + '}'
    + '.sw-field::before {'
    +   ' content: "☎";'
    +   ' position: absolute;'
    +   ' left: 16px; top: 50%;'
    +   ' transform: translateY(-50%);'
    +   ' font-size: 16px;'
    +   ' color: var(--sw-gray-500);'
    +   ' pointer-events: none;'
    +   ' transition: color var(--sw-transition);'
    + '}'
    + '.sw-field.sw-field-email::before { content: "✉"; }'
    + '.sw-field:focus-within::before { color: var(--sw-green); }'

    // --- OTP : 6 cases individuelles ---
    + '.sw-code-row {'
    +   ' display: grid;'
    +   ' grid-template-columns: repeat(6, 1fr);'
    +   ' gap: 10px;'
    +   ' margin-bottom: 4px;'
    + '}'
    + '.sw-code-box {'
    +   ' width: 100%;'
    +   ' aspect-ratio: 1 / 1;'
    +   ' max-height: 60px;'
    +   ' text-align: center;'
    +   ' font-size: 24px;'
    +   ' font-weight: 700;'
    +   ' padding: 0;'
    +   ' border: 2px solid var(--sw-gray-200);'
    +   ' border-radius: 12px;'
    +   ' background: #fff;'
    +   ' color: var(--sw-green);'
    +   ' outline: none;'
    +   ' font-family: "SFMono-Regular", Consolas, monospace;'
    +   ' transition: all var(--sw-transition);'
    +   ' caret-color: var(--sw-orange);'
    + '}'
    + '.sw-code-box:hover { border-color: var(--sw-gray-300); }'
    + '.sw-code-box:focus {'
    +   ' border-color: var(--sw-orange);'
    +   ' box-shadow: 0 0 0 4px rgba(247,164,0,.15);'
    +   ' background: #fffdf7;'
    +   ' transform: translateY(-2px);'
    + '}'
    + '.sw-code-box.sw-filled {'
    +   ' border-color: var(--sw-green);'
    +   ' background: linear-gradient(180deg, #f0faf4 0%, #e4f4ea 100%);'
    + '}'
    + '.sw-code-box.sw-error {'
    +   ' border-color: var(--sw-red);'
    +   ' animation: sw-shake 320ms cubic-bezier(.36,.07,.19,.97);'
    + '}'
    + '@keyframes sw-shake {'
    +   ' 10%, 90% { transform: translateX(-2px); }'
    +   ' 20%, 80% { transform: translateX(4px); }'
    +   ' 30%, 50%, 70% { transform: translateX(-6px); }'
    +   ' 40%, 60% { transform: translateX(6px); }'
    + '}'

    // --- OTP : compte à rebours ---
    + '.sw-countdown {'
    +   ' display: inline-flex; align-items: center; gap: 6px;'
    +   ' font-size: 13px; font-weight: 600;'
    +   ' color: var(--sw-green);'
    +   ' background: linear-gradient(180deg, #eafaf1 0%, #dcefe4 100%);'
    +   ' padding: 6px 12px;'
    +   ' border-radius: 999px;'
    +   ' margin: 6px 0 12px;'
    + '}'
    + '.sw-countdown::before { content: "⏱"; font-size: 13px; }'
    + '.sw-countdown.sw-countdown-warn { color: #b45309; background: #fef3c7; }'
    + '.sw-countdown.sw-countdown-danger { color: var(--sw-red); background: #fee2e2; }'

    // --- Lien discret ---
    + '.sw-link {'
    +   ' display: inline-flex; align-items: center; gap: 6px;'
    +   ' font-size: 13px; color: var(--sw-gray-500);'
    +   ' text-decoration: none;'
    +   ' cursor: pointer;'
    +   ' margin-top: 16px;'
    +   ' padding: 6px 4px;'
    +   ' border-radius: 6px;'
    +   ' transition: all var(--sw-transition);'
    +   ' font-weight: 500;'
    + '}'
    + '.sw-link::before { content: "✎"; font-size: 14px; transition: transform var(--sw-transition); }'
    + '.sw-link:hover { color: var(--sw-green); background: rgba(7,84,41,.06); }'
    + '.sw-link:hover::before { transform: rotate(-8deg) scale(1.1); }'
    + '.sw-back::before { content: "←"; font-size: 14px; }'
    + '.sw-back:hover::before { transform: translateX(-3px); }'
    + '.sw-change-contact::before { content: "↺"; font-size: 14px; }'
    + '.sw-change-contact:hover::before { transform: rotate(-180deg); }'

    // --- Info OTP ---
    + '.sw-otp-info { font-size: 12.5px; color: var(--sw-gray-500); margin: 8px 0 0; text-align: center; }'

    // --- Zone expiration ---
    + '.sw-otp-expired {'
    +   ' padding: 14px 16px;'
    +   ' background: #fff8ec;'
    +   ' border: 1px solid #f7c98b;'
    +   ' border-left: 4px solid var(--sw-orange);'
    +   ' border-radius: 10px;'
    +   ' font-size: 13px;'
    +   ' color: #8a5300;'
    +   ' margin-top: 14px;'
    +   ' animation: sw-fade-slide 280ms cubic-bezier(.4,0,.2,1);'
    + '}'

    // --- Utilitaires ---
    + '.sw-hidden { display: none !important; }'
    + '.sw-otp-request-zone { animation: sw-fade-slide 280ms cubic-bezier(.4,0,.2,1); }'

    // --- Responsive ---
    + '@media (max-width: 480px) {'
    +   ' .sw-section { padding: 18px; }'
    +   ' .sw-channels { gap: 8px; }'
    +   ' .sw-channel { padding: 12px 6px; font-size: 11.5px; }'
    +   ' .sw-channel .sw-channel-icon { font-size: 18px; }'
    +   ' .sw-btn { padding: 10px 14px; font-size: 12.5px; }'
    +   ' .sw-code-row { gap: 6px; }'
    +   ' .sw-code-box { font-size: 20px; border-radius: 10px; }'
    + '}';

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
      otpExpiryMinutes:  5,   // durée de validité du code (aligné avec le backend)

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
    this._countdownInterval = null;
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

    if (this._readForcedMode() === 'handwritten') {
      this._switchToHandwritten(card);
    } else {
      this._renderOtpSection(card);
    }
  };

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
  // Helper : rangée de 6 cases OTP
  // ============================================================
  SignatureWidget.prototype._buildOtpCodeRow = function () {
    var row = document.createElement('div');
    row.className = 'sw-code-row';

    var inputs = [];

    for (var i = 0; i < 6; i++) {
      (function (index) {
        var input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.autocomplete = (index === 0) ? 'one-time-code' : 'off';
        input.maxLength = 1;
        input.className = 'sw-code-box';
        input.dataset.index = index;
        input.setAttribute('aria-label', 'Chiffre ' + (index + 1) + ' sur 6');

        input.addEventListener('input', function (e) {
          var v = (e.target.value || '').replace(/\D/g, '');

          if (v.length > 1) {
            e.target.value = v.charAt(0);
            distributeFrom(index, v);
            return;
          }

          e.target.value = v;

          if (v) {
            e.target.classList.add('sw-filled');
            if (index < 5) inputs[index + 1].focus();
          } else {
            e.target.classList.remove('sw-filled');
          }
        });

        input.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace' && !e.target.value && index > 0) {
            inputs[index - 1].focus();
            inputs[index - 1].value = '';
            inputs[index - 1].classList.remove('sw-filled');
            e.preventDefault();
          }
          if (e.key === 'ArrowLeft' && index > 0) {
            inputs[index - 1].focus();
            e.preventDefault();
          }
          if (e.key === 'ArrowRight' && index < 5) {
            inputs[index + 1].focus();
            e.preventDefault();
          }
        });

        input.addEventListener('paste', function (e) {
          e.preventDefault();
          var pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
          var digits = pasted.replace(/\D/g, '').slice(0, 6);
          if (!digits) return;
          distributeFrom(0, digits);
        });

        input.addEventListener('focus', function () {
          input.select();
        });

        inputs.push(input);
        row.appendChild(input);
      })(i);
    }

    function distributeFrom(startIndex, digits) {
      for (var j = 0; j < digits.length && (startIndex + j) < 6; j++) {
        var target = inputs[startIndex + j];
        target.value = digits.charAt(j);
        target.classList.add('sw-filled');
      }
      var nextEmpty = Math.min(startIndex + digits.length, 5);
      inputs[nextEmpty].focus();
    }

    return {
      row: row,
      getValue: function () {
        return inputs.map(function (inp) { return inp.value || ''; }).join('');
      },
      clear: function () {
        inputs.forEach(function (inp) {
          inp.value = '';
          inp.classList.remove('sw-filled', 'sw-error');
        });
      },
      focusFirst: function () {
        inputs[0].focus();
      },
      setError: function (isError) {
        inputs.forEach(function (inp) {
          inp.classList.toggle('sw-error', !!isError);
        });
        if (isError) {
          setTimeout(function () {
            inputs.forEach(function (inp) { inp.classList.remove('sw-error'); });
          }, 400);
        }
      }
    };
  };

  // ============================================================
  // ÉCRAN OTP (par défaut)
  // ============================================================
  SignatureWidget.prototype._renderOtpSection = function (card) {
    var self = this;

    var section = document.createElement('div');
    section.className = 'sw-section sw-otp-section';
    card.appendChild(section);

    // ========================================================
    // Zone de demande (masquée après envoi réussi)
    // ========================================================
    var requestZone = document.createElement('div');
    requestZone.className = 'sw-otp-request-zone';
    section.appendChild(requestZone);

    var title = document.createElement('p');
    title.className = 'sw-label';
    title.textContent = 'Recevez un code de vérification';
    requestZone.appendChild(title);

    // --- Sélecteur de canaux ---
    var channels = document.createElement('div');
    channels.className = 'sw-channels';
    requestZone.appendChild(channels);

    var CHANNELS = [
      { id: 'sms',      label: 'SMS',      icon: '💬' },
      { id: 'email',    label: 'Email',    icon: '✉'  },
      { id: 'whatsapp', label: 'WhatsApp', icon: '📱' }
    ];

    CHANNELS.forEach(function (c) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'sw-channel';
      btn.dataset.channel = c.id;

      var ico = document.createElement('span');
      ico.className = 'sw-channel-icon';
      ico.textContent = c.icon;
      btn.appendChild(ico);

      var lbl = document.createElement('span');
      lbl.textContent = c.label;
      btn.appendChild(lbl);

      if (c.id === 'whatsapp') {
        btn.classList.add('sw-disabled');
        btn.disabled = true;
        btn.title = 'WhatsApp non disponible pour le moment';
      } else {
        btn.addEventListener('click', function () {
          self._otpChannel = c.id;
          Array.prototype.forEach.call(channels.children, function (el) {
            el.classList.toggle('sw-active', el.dataset.channel === c.id);
          });
          
          // Pré-remplissage selon canal
          var prefill = (c.id === 'email') ? self.cfg.signerEmail : self.cfg.signerPhone;
          if (prefill) {
            contactInput.value = prefill;
            contactInput.disabled = true;
            contactInput.title = 'Contact pré-rempli par l\'application hôte';
          } else {
            contactInput.value = '';
            contactInput.disabled = false;
            contactInput.title = '';
          }
          
          contactInput.type = (c.id === 'email') ? 'email' : 'tel';
          contactInput.placeholder = (c.id === 'email') ? 'adresse@email.com' : '+225 07 00 00 00 00';
          field.classList.toggle('sw-field-email', c.id === 'email');
        });
      }

      if (c.id === self._otpChannel) btn.classList.add('sw-active');
      channels.appendChild(btn);
    });

    // --- Champ contact ---
    var field = document.createElement('div');
    field.className = 'sw-field' + (self._otpChannel === 'email' ? ' sw-field-email' : '');
    requestZone.appendChild(field);

    var contactInput = document.createElement('input');
    contactInput.type = (self._otpChannel === 'email') ? 'email' : 'tel';
    contactInput.placeholder = (self._otpChannel === 'email') ? 'adresse@email.com' : '+225 07 00 00 00 00';
    
    // Pré-remplissage et désactivation si valeur fournie par l'app hôte
    var prefillValue = (self._otpChannel === 'email')
      ? (self.cfg.signerEmail || '')
      : (self.cfg.signerPhone || '');
    contactInput.value = prefillValue;
    
    // Désactiver le champ si pré-rempli par l'app hôte
    if (prefillValue) {
      contactInput.disabled = true;
      contactInput.title = 'Contact pré-rempli par l\'application hôte';
    }
    
    field.appendChild(contactInput);

    // --- Bouton recevoir ---
    var receiveBtn = document.createElement('button');
    receiveBtn.type = 'button';
    receiveBtn.className = 'sw-btn sw-btn-primary';
    receiveBtn.textContent = 'Recevoir le code';
    receiveBtn.style.width = '100%';
    requestZone.appendChild(receiveBtn);

    // ========================================================
    // Zone de saisie (cachée au départ)
    // ========================================================
    var codeZone = document.createElement('div');
    codeZone.className = 'sw-hidden';
    section.appendChild(codeZone);

    var codeLabel = document.createElement('p');
    codeLabel.className = 'sw-label';
    codeLabel.textContent = 'Saisissez le code à 6 chiffres';
    codeLabel.style.marginTop = '0';
    codeZone.appendChild(codeLabel);

    // Message d'info "Code envoyé au 07…"
    var codeSentTo = document.createElement('p');
    codeSentTo.className = 'sw-otp-info';
    codeSentTo.style.textAlign = 'left';
    codeSentTo.style.marginTop = '0';
    codeSentTo.style.marginBottom = '8px';
    codeZone.appendChild(codeSentTo);

    // Compte à rebours
    var countdown = document.createElement('div');
    countdown.className = 'sw-countdown';
    countdown.textContent = 'Code valable 05:00';
    codeZone.appendChild(countdown);

    // Rangée de 6 cases
    var codeRow = self._buildOtpCodeRow();
    codeZone.appendChild(codeRow.row);

    var otpInfo = document.createElement('p');
    otpInfo.className = 'sw-otp-info';
    otpInfo.textContent = 'Entrez les chiffres reçus.';
    codeZone.appendChild(otpInfo);

    var verifyBtn = document.createElement('button');
    verifyBtn.type = 'button';
    verifyBtn.className = 'sw-btn sw-btn-primary';
    verifyBtn.textContent = 'Vérifier';
    verifyBtn.style.width = '100%';
    verifyBtn.style.marginTop = '12px';
    codeZone.appendChild(verifyBtn);

    // Lien "Changer de contact" (seulement si champ modifiable)
    var changeContactLink = document.createElement('span');
    changeContactLink.className = 'sw-link sw-change-contact';
    changeContactLink.textContent = 'Changer de contact';
    changeContactLink.style.marginTop = '10px';
    
    // Masquer le lien si le champ est pré-rempli par l'app hôte
    var isPrefilled = (self._otpChannel === 'email' && self.cfg.signerEmail) || 
                     (self._otpChannel !== 'email' && self.cfg.signerPhone);
    if (isPrefilled) {
      changeContactLink.style.display = 'none';
    }
    
    changeContactLink.addEventListener('click', function () {
      self._stopCountdown();
      requestZone.classList.remove('sw-hidden');
      codeZone.classList.add('sw-hidden');
      self._otpCodeSent = false;
      receiveBtn.disabled = false;
      receiveBtn.textContent = 'Recevoir le code';
    });
    codeZone.appendChild(changeContactLink);

    // ========================================================
    // Zone expirée
    // ========================================================
    var expiredZone = document.createElement('div');
    expiredZone.className = 'sw-otp-expired sw-hidden';
    expiredZone.textContent = 'Votre code a expiré. Vous pouvez signer à la main.';
    section.appendChild(expiredZone);

    // ========================================================
    // Lien "Signer à la main"
    // ========================================================
    var manualLink = document.createElement('span');
    manualLink.className = 'sw-link';
    manualLink.textContent = 'Signer à la main';
    manualLink.addEventListener('click', function () {
      self._goToHandwritten(card);
    });
    section.appendChild(manualLink);

    // ========================================================
    // Statut
    // ========================================================
    var status = document.createElement('div');
    status.className = 'sw-status';
    section.appendChild(status);

    // ------------------------------------------------------------
    // Handlers
    // ------------------------------------------------------------

    receiveBtn.addEventListener('click', function () {
      var contact = (contactInput.value || '').trim();
      
      // Si le champ est désactivé (pré-rempli), utiliser directement la valeur
      if (contactInput.disabled) {
        contact = contactInput.value;
      }
      
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
      status.className = 'sw-status loading';
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
      }).then(function (body) {
        self._otpCodeSent = true;

        // Masquer la zone de demande
        requestZone.classList.add('sw-hidden');

        // Configurer et afficher la zone de saisie
        var isEmail = (self._otpChannel === 'email');
        codeSentTo.textContent = 'Code envoyé '
          + (isEmail ? 'à ' : 'au ')
          + self._otpContact + '.';

        // Démarrer le décompte (durée lue dans la réponse ou valeur par défaut)
        var expiresInMinutes = (body && body.data && body.data.expires_in)
          ? parseInt(body.data.expires_in, 10)
          : (self.cfg.otpExpiryMinutes || 5);
        if (!isFinite(expiresInMinutes) || expiresInMinutes <= 0) expiresInMinutes = 5;

        self._startCountdown(countdown, expiresInMinutes * 60, function () {
          // Fin du décompte : on bascule sur la zone expirée
          countdown.textContent = 'Code expiré';
          countdown.className = 'sw-countdown sw-countdown-danger';
          expiredZone.classList.remove('sw-hidden');
          verifyBtn.disabled = true;
        });

        codeZone.classList.remove('sw-hidden');
        status.className = 'sw-status ok';
        status.textContent = 'Un code vous a été envoyé.';
        codeRow.focusFirst();
      }).catch(function (err) {
        receiveBtn.disabled = false;
        status.className = 'sw-status err';
        status.textContent = 'Erreur : ' + err.message;
        self._error(err);
      });
    });

    verifyBtn.addEventListener('click', function () {
      var code = codeRow.getValue();

      if (!/^[0-9]{6}$/.test(code)) {
        codeRow.setError(true);
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
      status.className = 'sw-status loading';
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
        self._stopCountdown();
        return self._buildOtpQrAndSubmit(body.data, card, status);
      }).catch(function (err) {
        verifyBtn.disabled = false;

        if (err.code === 'OTP_INVALID') {
          codeRow.setError(true);
          codeRow.clear();
          codeRow.focusFirst();
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
  // Gestion du décompte
  // ============================================================
  /**
   * Démarre un compte à rebours affiché dans `el`.
   * - Format mm:ss
   * - Change de style à 60s (warn) puis à 10s (danger)
   * - Appelle `onEnd()` à zéro
   */
  SignatureWidget.prototype._startCountdown = function (el, totalSeconds, onEnd) {
    var self = this;
    this._stopCountdown();

    var remaining = Math.max(0, totalSeconds);

    function tick() {
      if (remaining <= 0) {
        self._stopCountdown();
        el.textContent = 'Code expiré';
        el.className = 'sw-countdown sw-countdown-danger';
        if (typeof onEnd === 'function') onEnd();
        return;
      }

      el.textContent = 'Code valable ' + formatCountdown(remaining);
      el.className = 'sw-countdown'
        + (remaining <= 60 ? ' sw-countdown-warn' : '')
        + (remaining <= 10 ? ' sw-countdown-danger' : '');

      remaining--;
    }

    tick();
    this._countdownInterval = setInterval(tick, 1000);
  };

  SignatureWidget.prototype._stopCountdown = function () {
    if (this._countdownInterval) {
      clearInterval(this._countdownInterval);
      this._countdownInterval = null;
    }
  };

  // ============================================================
  // Bascule vers la signature manuscrite
  // ============================================================
  SignatureWidget.prototype._goToHandwritten = function (card) {
    this._stopCountdown();

    var otp = card.querySelector('.sw-otp-section');
    if (otp) otp.remove();

    if (this._mode() === 'mobile' || !this.cfg.signingLink) {
      this._switchToHandwritten(card);
      return;
    }

    this._renderHandwrittenQr(card);
  };

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
      '<span class="sw-link sw-back">Revenir à la vérification OTP</span>';
    card.appendChild(section);

    var qrBox = section.querySelector('.sw-qr-box');
    var state = section.querySelector('.sw-qr-state');
    var back  = section.querySelector('.sw-back');

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
      statusEl.className = 'sw-status loading';
      statusEl.textContent = 'Génération de la preuve…';
    }

    return this._getGeo().then(function (geo) {
      var qrUrl = self._buildOtpQrUrl(otpData, geo);

      return self._generateQrBase64(qrUrl).then(function (qrBase64) {
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
      user_uuid:  otpData.user_uuid  || '',
      login:      otpData.login      || '',
      email:      otpData.email      || '',
      nom:        otpData.nom        || '',
      prenoms:    otpData.prenoms    || '',
      mobile_1:   otpData.mobile_1   || '',
      adresse_complete: otpData.adresse_complete || '',
      channel:    otpData.channel    || '',
      contact:    otpData.contact    || '',
      purpose:    otpData.purpose    || '',
      ip_address: otpData.ip_address || '',
      user_agent: otpData.user_agent || '',
      used_at:    otpData.used_at    || '',
      lat:        (geo && geo.lat != null) ? geo.lat : '',
      lng:        (geo && geo.lng != null) ? geo.lng : ''
    };

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

    return template.replace(/\{([a-z_]+)\}/gi, function (_, key) {
      var v = values[key];
      return (v == null) ? '' : encodeURIComponent(String(v));
    });
  };

  SignatureWidget.prototype._generateQrBase64 = function (url) {
    return loadScript(CDN.qrcode).then(function () {
      return new Promise(function (resolve, reject) {
        try {
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

          setTimeout(function () {
            var canvas = holder.querySelector('canvas');
            if (!canvas) {
              var imgs = holder.querySelectorAll('img');
              if (imgs.length) {
                canvas = document.createElement('canvas');
                canvas.width = 512; canvas.height = 512;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 512, 512);
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
  // ÉCRAN SIGNATURE GRAPHIQUE (canvas)
  // ============================================================
  SignatureWidget.prototype._switchToHandwritten = function (card) {
    var self = this;

    var otp = card.querySelector('.sw-otp-section');
    if (otp) otp.remove();
    var qr = card.querySelector('.sw-qr-wrap');
    if (qr) qr.remove();

    var section = document.createElement('div');
    section.className = 'sw-section sw-handwritten-section';
    section.innerHTML =
      '<p class="sw-label">Tracez votre signature</p>' +
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
      ctx.lineWidth   = 2.2;
      ctx.lineCap     = 'round';
      ctx.lineJoin    = 'round';
      ctx.strokeStyle = '#0f172a';

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
      status.className   = 'sw-status loading';
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

    this._stopCountdown();

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
  // Polling
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
    this._stopCountdown();
    if (this._onResize) window.removeEventListener('resize', this._onResize);
    if (this.host && this.host.parentNode) this.host.parentNode.removeChild(this.host);
    this._destroyed = true;
  };

  global.SignatureWidget = SignatureWidget;
})(window);



<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow">
    <title>Widget Signature Électronique - Intégration &amp; démonstration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px 40px;
            color: #1f2937;
            background: #f3f4f6;
        }

        .container {
            background: #fff;
            border-radius: 18px;
            padding: 32px 28px 24px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 2px solid #edf1ee;
            margin-bottom: 24px;
        }

        .header-left { display: flex; align-items: center; gap: 14px; }

        .logo {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #075429, #0a6b35);
            color: #fff; font-weight: 800; font-size: 18px;
        }

        .header h1 { font-size: 24px; margin: 0; color: #111827; }
        .subtitle { margin-top: 2px; color: #6b7280; font-size: 13px; }

        .badge {
            background: #eafaf1; color: #075429; font-weight: 700;
            font-size: 11px; padding: 6px 12px; border-radius: 999px;
        }

        .demo-section {
            background: #f7faf7; border: 1px solid #e8efe8; border-radius: 14px;
            padding: 20px 22px 16px; margin-bottom: 26px;
        }

        .demo-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 700; color: #374151; margin-bottom: 14px;
        }

        .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
        .dot.green { background: #22c55e; }
        .dot.orange { background: #f59e0b; }
        .dot.purple { background: #8b5cf6; }
        .dot.blue { background: #3b82f6; }

        .demo-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px; margin-bottom: 18px;
        }

        .demo-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 16px 18px; transition: all 0.2s ease;
        }

        .demo-card:hover {
            transform: translateY(-2px); border-color: #075429;
            box-shadow: 0 8px 20px rgba(7, 84, 41, 0.08);
        }

        .card-badge {
            display: inline-block; margin-bottom: 8px; font-size: 11px;
            padding: 3px 10px; border-radius: 999px; font-weight: 700;
        }

        .green { background: #dcfce7; color: #166534; }
        .orange { background: #fef3c7; color: #92400e; }
        .purple { background: #ede9fe; color: #6d28d9; }
        .blue { background: #dbeafe; color: #1e40af; }

        .card-title { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .card-text { font-size: 12.5px; color: #6b7280; margin-bottom: 10px; }

        .demo-card button {
            width: 100%; border: none; border-radius: 10px; padding: 10px 14px;
            background: #075429; color: white; font-weight: 600; cursor: pointer;
        }
        .demo-card button:hover { background: #064320; }

        .widget-demo {
            min-height: 500px; background: #ffffff; border: 1px solid #e5e7eb;
            border-radius: 12px; overflow: hidden;
        }

        .demo-notice {
            margin-top: 12px; font-size: 12.5px; color: #92400e;
            background: #fef3c7; border-radius: 8px; padding: 10px 12px;
        }

        .tabs-container { margin-top: 12px; }

        .tabs-header {
            display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;
            padding-bottom: 10px; border-bottom: 1px solid #edf1ee;
        }

        .tab-btn {
            background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;
            border-radius: 10px; padding: 8px 14px; font-size: 13px;
            font-weight: 600; cursor: pointer;
        }
        .tab-btn.active { background: #075429; color: white; border-color: #075429; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .code-block {
            background: #0c1f15; color: #d7ecdf; border-radius: 12px;
            padding: 14px 16px; overflow-x: auto; font-size: 12px; line-height: 1.6;
        }

        .code-block pre {
            white-space: pre-wrap; word-break: break-word; margin: 0;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .code-description { margin: 0 0 14px; color: #4b5563; font-size: 14px; line-height: 1.6; }

        .info-box {
            margin-top: 20px; background: #f7faf7; border-left: 4px solid #075429;
            padding: 14px 16px; border-radius: 10px; color: #374151;
        }
        .info-box strong { color: #111827; }
        .separator { margin-top: 10px; color: #4b5563; line-height: 1.7; }

        .info-box.warn { background: #fff7ed; border-left-color: #f59e0b; }
        .info-box.info { background: #eff6ff; border-left-color: #3b82f6; }

        code {
            background: rgba(7, 84, 41, 0.08); padding: 2px 6px; border-radius: 6px;
            font-size: 12px; color: #0f172a;
        }

        /* Les <code> inline dans les info-box ne doivent pas hériter du fond
           sombre des .code-block. Exception appliquée plus bas. */
        .code-block code {
            background: transparent;
            padding: 0;
            border-radius: 0;
            color: inherit;
            font-size: inherit;
        }

        .flow-diagram {
            background: #0c1f15; color: #d7ecdf; border-radius: 12px;
            padding: 16px; font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 12.5px; line-height: 1.7; overflow-x: auto;
            white-space: pre;
        }

        @media (max-width: 700px) {
            .header { flex-direction: column; align-items: flex-start; }
            .container { padding: 22px 18px; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="logo">S</div>
                <div>
                    <h1>Widget Signature Électronique</h1>
                    <div class="subtitle">Intégration front-end, démonstration et documentation</div>
                </div>
            </div>
            <div class="badge">v2.1 — OTP + QR</div>
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION DÉMO                                                 --}}
        {{-- ============================================================ --}}
        <div class="demo-section">
            <div class="demo-title">
                <span class="dot green"></span>
                Démo du widget — flux réel de bout en bout
            </div>

            <div class="demo-grid">
                <div class="demo-card">
                    <span class="card-badge blue">Par défaut</span>
                    <div class="card-title">OTP multicanal</div>
                    <div class="card-text">
                        SMS / Email / WhatsApp. Le signataire reçoit un code à 6 chiffres.
                        Preuve = QR code signé avec les données autoritaires.
                    </div>
                    <button type="button" data-demo-mode="otp">Tester l'OTP</button>
                </div>

                <div class="demo-card">
                    <span class="card-badge orange">Repli</span>
                    <div class="card-title">Signature manuscrite</div>
                    <div class="card-text">
                        Canvas tactile. Accessible via « Signer à la main » ou
                        automatiquement proposé si l'OTP expire.
                    </div>
                    <button type="button" data-demo-mode="handwritten">Signer à la main</button>
                </div>

                <div class="demo-card">
                    <span class="card-badge green">Desktop</span>
                    <div class="card-title">QR d'appairage</div>
                    <div class="card-text">
                        Le client scanne le QR code pour signer depuis son mobile
                        (mode auto-polling, écran d'agence).
                    </div>
                    <button type="button" data-demo-mode="desktop">Voir le QR</button>
                </div>
            </div>

            <div id="widget-demo-host" class="widget-demo"></div>

            <p class="demo-notice">
                Ce lien de démonstration est réel et à usage unique (expire dans 15 minutes).
                En mode OTP, <code>signer_login</code> = <code>demo.signataire</code> et
                le contact est pré-rempli (<code>demo@yakoafrica.ci</code> / <code>0700000000</code>).
                Si vous signez réellement, rechargez cette page pour un nouveau lien.
            </p>
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION WORKFLOW                                              --}}
        {{-- ============================================================ --}}
        <div class="info-box info" style="margin-bottom: 26px;">
            <strong>🧭 Deux modes de signature, un seul flux de preuve</strong>
            <div class="flow-diagram">Mode OTP (défaut)
  Widget  ──POST──►  /api/v1/auth/otp/send          (canal: sms|email|whatsapp)
          ◄────────  { success: true, code: OTP_SENT }
  Widget  ──POST──►  /api/v1/signature/otp/verify   (code, channel, contact)
          ◄────────  { success, data: { user_uuid, login, email, nom, prenoms,
                                        mobile_1, adresse_complete, channel, contact,
                                        ip_address, user_agent, used_at } }
  Widget  :  génère QR (URL = otpQrUrlTemplate + data + géoloc navigateur)
  Widget  ──POST──►  /api/v1/signature/webhook      (method=otp_qr, signature=QR base64)
  Laravel ──POST──►  webhook_url de l'app hôte      (avec X-Api-Key)
  Laravel  :  marque token is_used=true (une seule transaction)

Mode Handwritten (repli)
  Widget  ──POST──►  /api/v1/signature/webhook      (method=handwritten, signature=canvas base64)
  Laravel ──POST──►  webhook_url de l'app hôte
  Laravel  :  marque token is_used=true</div>
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION INTÉGRATION PAR FRAMEWORK                            --}}
        {{-- ============================================================ --}}
        <div class="tabs-container">
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-tab="html">HTML / Vanilla JS</button>
                <button type="button" class="tab-btn" data-tab="react">React</button>
                <button type="button" class="tab-btn" data-tab="vue">Vue</button>
                <button type="button" class="tab-btn" data-tab="angular">Angular</button>
                <button type="button" class="tab-btn" data-tab="next">Next.js</button>
                <button type="button" class="tab-btn" data-tab="params">Paramètres</button>
                <button type="button" class="tab-btn" data-tab="otp">Flux OTP en détail</button>
                <button type="button" class="tab-btn" data-tab="webhook">Payload webhook</button>
                <button type="button" class="tab-btn" data-tab="api">API generate-link</button>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- HTML                                                       --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content active" data-tab-panel="html">
                <p class="code-description">
                    Le lien de signature s'obtient côté serveur via
                    <code>POST /api/v1/signature/generate-link</code> (authentifié). La réponse contient
                    <code>token</code>, <code>widget_url</code> et (si l'app hôte a fourni les champs OTP)
                    les pré-remplissages signataire. Ni secret partagé, ni URL de webhook ne transitent côté client.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>&lt;script src="https://api.yakoafrica.ci/api/v1/signature/signature-widget.js"&gt;&lt;/script&gt;

&lt;div id="signature-widget"&gt;&lt;/div&gt;

&lt;script&gt;
  new SignatureWidget({
    container: '#signature-widget',

    // --- Identité du flux ---
    token: '{{ TOKEN_REÇU_DU_BACKEND }}',
    signingLink: '{{ WIDGET_URL_REÇUE_DU_BACKEND }}',

    // --- Document (optionnel) ---
    documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
    documentDescription: 'Contrat de souscription',

    // --- Backend Laravel (toujours, jamais l'app hôte directement) ---
    backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
    apiUrl: 'https://api.yakoafrica.ci/api/v1/signature',

    // --- OTP (mode par défaut) ---
    otpSendUrl:   'https://api.yakoafrica.ci/api/v1/auth/otp/send',
    otpVerifyUrl: 'https://api.yakoafrica.ci/api/v1/signature/otp/verify',
    signerLogin:    '{{ SIGNER_LOGIN }}',      // ou null
    signerUserUuid: '{{ SIGNER_USER_UUID }}',  // ou null
    signerEmail:    'client@exemple.ci',       // pré-remplit le champ si canal=email
    signerPhone:    '0700000000',              // pré-remplit le champ si canal=sms/whatsapp
    otpPurpose:     'signature',
    otpQrUrlTemplate:
      'https://app-hote.com/verify?user={user_uuid}&login={login}&email={email}' +
      '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}' +
      '&ch={channel}&contact={contact}&purpose={purpose}' +
      '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}',

    // --- Redirections & UX ---
    successRedirectUrl: 'https://app-hote.com/success',
    cancelRedirectUrl:  'https://app-hote.com/cancel',
    enableAutoPolling:  true,   // utile pour les postes desktop en agence

    onSigned: function (data) { console.log('Signature terminée', data); },
    onError:  function (error) { console.error('Erreur', error); }
  });
&lt;/script&gt;</code></pre>
                    @endverbatim
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- React                                                      --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="react">
                <p class="code-description">
                    Exemple React. Le <code>token</code>, <code>widgetUrl</code> et les pré-remplissages
                    signataire viennent d'un appel serveur à <code>generate-link</code> — passez-les en props.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>import { useEffect, useRef } from 'react';

export default function SignatureWidgetHost({
  token, widgetUrl,
  signerLogin, signerUserUuid, signerEmail, signerPhone,
  otpQrUrlTemplate,
}) {
  const ref = useRef(null);

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://api.yakoafrica.ci/api/v1/signature/signature-widget.js';

    script.onload = () => {
      if (!window.SignatureWidget) return;

      new window.SignatureWidget({
        container: ref.current,
        token,
        signingLink: widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
        apiUrl:            'https://api.yakoafrica.ci/api/v1/signature',

        // OTP
        otpSendUrl:   'https://api.yakoafrica.ci/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://api.yakoafrica.ci/api/v1/signature/otp/verify',
        signerLogin, signerUserUuid, signerEmail, signerPhone,
        otpPurpose: 'signature',
        otpQrUrlTemplate,

        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl:  'https://app-hote.com/cancel',
        onSigned: (data) => console.log('OK', data),
        onError:  (err)  => console.error('Erreur', err),
      });
    };

    document.body.appendChild(script);
    return () => { document.body.removeChild(script); };
  }, [token, widgetUrl, signerLogin, signerUserUuid, signerEmail, signerPhone, otpQrUrlTemplate]);

  return &lt;div ref={ref} /&gt;;
}</code></pre>
                    @endverbatim
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Vue                                                        --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="vue">
                <p class="code-description">
                    Sous Vue.js, le token et les pré-remplissages OTP arrivent en props.
                    L'initialisation se fait dans <code>mounted</code>.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>&lt;template&gt;
  &lt;div ref="signatureHost" /&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
  props: [
    'token', 'widgetUrl',
    'signerLogin', 'signerUserUuid', 'signerEmail', 'signerPhone',
    'otpQrUrlTemplate',
  ],
  mounted() {
    const script = document.createElement('script');
    script.src = 'https://api.yakoafrica.ci/api/v1/signature/signature-widget.js';
    script.onload = () =&gt; {
      new window.SignatureWidget({
        container: this.$refs.signatureHost,
        token: this.token,
        signingLink: this.widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
        apiUrl:            'https://api.yakoafrica.ci/api/v1/signature',

        otpSendUrl:   'https://api.yakoafrica.ci/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://api.yakoafrica.ci/api/v1/signature/otp/verify',
        signerLogin: this.signerLogin,
        signerUserUuid: this.signerUserUuid,
        signerEmail: this.signerEmail,
        signerPhone: this.signerPhone,
        otpPurpose: 'signature',
        otpQrUrlTemplate: this.otpQrUrlTemplate,

        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl:  'https://app-hote.com/cancel',
      });
    };

    document.body.appendChild(script);
  }
};
&lt;/script&gt;</code></pre>
                    @endverbatim
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Angular                                                    --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="angular">
                <p class="code-description">
                    Exemple Angular. Le widget est instancié dans <code>ngOnInit</code>.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>import { Component, Input, OnInit, ElementRef, ViewChild } from '@angular/core';

@Component({
  selector: 'app-signature-widget',
  template: '&lt;div #host&gt;&lt;/div&gt;'
})
export class SignatureWidgetComponent implements OnInit {
  @Input() token!: string;
  @Input() widgetUrl!: string;
  @Input() signerLogin?: string;
  @Input() signerUserUuid?: string;
  @Input() signerEmail?: string;
  @Input() signerPhone?: string;
  @Input() otpQrUrlTemplate?: string;

  @ViewChild('host', { static: true }) host!: ElementRef;

  ngOnInit(): void {
    const script = document.createElement('script');
    script.src = 'https://api.yakoafrica.ci/api/v1/signature/signature-widget.js';
    script.onload = () =&gt; {
      new (window as any).SignatureWidget({
        container: this.host.nativeElement,
        token: this.token,
        signingLink: this.widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
        apiUrl:            'https://api.yakoafrica.ci/api/v1/signature',

        otpSendUrl:   'https://api.yakoafrica.ci/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://api.yakoafrica.ci/api/v1/signature/otp/verify',
        signerLogin: this.signerLogin,
        signerUserUuid: this.signerUserUuid,
        signerEmail: this.signerEmail,
        signerPhone: this.signerPhone,
        otpPurpose: 'signature',
        otpQrUrlTemplate: this.otpQrUrlTemplate,

        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl:  'https://app-hote.com/cancel',
      });
    };
    document.body.appendChild(script);
  }
}</code></pre>
                    @endverbatim
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Next.js                                                    --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="next">
                <p class="code-description">
                    Next.js (App Router). Le <code>token</code> est obtenu via un appel serveur
                    à <code>generate-link</code>, jamais depuis le client.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>'use client';

import { useEffect, useRef } from 'react';

export default function SignatureWidgetClient(props) {
  const hostRef = useRef(null);

  useEffect(() => {
    const existing = document.querySelector('script[src*="signature-widget.js"]');
    const init = () => {
      if (!window.SignatureWidget) return;
      new window.SignatureWidget({
        container: hostRef.current,
        token: props.token,
        signingLink: props.widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
        apiUrl:            'https://api.yakoafrica.ci/api/v1/signature',

        otpSendUrl:   'https://api.yakoafrica.ci/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://api.yakoafrica.ci/api/v1/signature/otp/verify',
        signerLogin: props.signerLogin,
        signerUserUuid: props.signerUserUuid,
        signerEmail: props.signerEmail,
        signerPhone: props.signerPhone,
        otpPurpose: 'signature',
        otpQrUrlTemplate: props.otpQrUrlTemplate,

        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl:  'https://app-hote.com/cancel',
      });
    };

    if (existing) {
      init();
    } else {
      const script = document.createElement('script');
      script.src = 'https://api.yakoafrica.ci/api/v1/signature/signature-widget.js';
      script.async = true;
      script.onload = init;
      document.body.appendChild(script);
    }
  }, [props]);

  return &lt;div ref={hostRef} /&gt;;
}</code></pre>
                    @endverbatim
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Paramètres                                                 --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="params">
                <p class="code-description">
                    Liste exhaustive des options acceptées par <code>new SignatureWidget({...})</code>.
                </p>

                <div class="info-box">
                    <strong>Identité du flux (obligatoire)</strong>
                    <div class="separator"><code>container</code> — sélecteur CSS ou élément DOM hôte</div>
                    <div class="separator"><code>token</code> — identifiant 64 car. renvoyé par <code>generate-link</code></div>
                    <div class="separator"><code>backendWebhookUrl</code> — URL Laravel de relais (jamais l'app hôte directement)</div>
                </div>

                <div class="info-box">
                    <strong>Document (optionnel)</strong>
                    <div class="separator"><code>documentUrl</code> — URL du PDF/image à signer</div>
                    <div class="separator"><code>documentDescription</code> — libellé affiché en en-tête</div>
                </div>

                <div class="info-box">
                    <strong>Backend &amp; polling</strong>
                    <div class="separator"><code>apiUrl</code> — base de l'API signature (utilisée pour le polling)</div>
                    <div class="separator"><code>signingLink</code> — URL complète du widget (encodée dans le QR d'appairage desktop)</div>
                    <div class="separator"><code>enableAutoPolling</code> — booléen ; utile pour les postes en agence</div>
                    <div class="separator"><code>pollingInterval</code> — défaut 5000 ms</div>
                    <div class="separator"><code>maxPollingAttempts</code> — défaut 120 (10 min)</div>
                </div>

                <div class="info-box info">
                    <strong>OTP (mode par défaut)</strong>
                    <div class="separator"><code>otpSendUrl</code> — <code>POST /api/v1/auth/otp/send</code> (existant, inchangé)</div>
                    <div class="separator"><code>otpVerifyUrl</code> — <code>POST /api/v1/signature/otp/verify</code> (passerelle autoritaire)</div>
                    <div class="separator"><code>signerLogin</code> — login du signataire (transmis à <code>auth/otp/send</code> et <code>signature/otp/verify</code>)</div>
                    <div class="separator"><code>signerUserUuid</code> — alternative à <code>signerLogin</code></div>
                    <div class="separator"><code>signerEmail</code> — pré-remplit le champ contact si canal = email</div>
                    <div class="separator"><code>signerPhone</code> — pré-remplit le champ contact si canal = sms / whatsapp</div>
                    <div class="separator"><code>otpPurpose</code> — défaut <code>'signature'</code> ; doit correspondre au purpose envoyé à <code>auth/otp/send</code></div>
                    <div class="separator"><code>otpQrUrlTemplate</code> — URL avec placeholders <code>{user_uuid}</code>, <code>{login}</code>, <code>{email}</code>, <code>{nom}</code>, <code>{prenoms}</code>, <code>{mobile_1}</code>, <code>{adresse_complete}</code>, <code>{channel}</code>, <code>{contact}</code>, <code>{purpose}</code>, <code>{ip_address}</code>, <code>{user_agent}</code>, <code>{used_at}</code>, <code>{lat}</code>, <code>{lng}</code></div>
                </div>

                <div class="info-box warn">
                    <strong>Redirections &amp; callbacks</strong>
                    <div class="separator"><code>successRedirectUrl</code> — redirigé 2 s après confirmation</div>
                    <div class="separator"><code>cancelRedirectUrl</code> — utilisé par le bouton « Annuler » (canvas)</div>
                    <div class="separator"><code>onSigned(data)</code> — callback après confirmation backend</div>
                    <div class="separator"><code>onError(err)</code> — callback d'erreur</div>
                    <div class="separator"><code>forceMode</code> — <code>'desktop'</code> | <code>'mobile'</code> | <code>null</code></div>
                    <div class="separator"><code>breakpoint</code> — défaut 768 px</div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Flux OTP en détail                                         --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="otp">
                <p class="code-description">
                    Le mode OTP est proposé par défaut à l'ouverture du widget. Voici son cycle complet.
                </p>

                <div class="info-box info">
                    <strong>Étape 1 — Saisie du contact</strong>
                    <div class="separator">
                        Le widget affiche un sélecteur SMS / Email / WhatsApp et un champ contact.
                        Si <code>signerEmail</code> ou <code>signerPhone</code> ont été fournis à l'init,
                        le champ est pré-rempli.
                    </div>
                </div>

                <div class="info-box info">
                    <strong>Étape 2 — Demande de code</strong>
                    <div class="separator">
                        Le widget appelle <code>POST /api/v1/auth/otp/send</code> (endpoint existant,
                        throttle <code>5,10</code>) avec <code>{ channel, purpose, login?, user_uuid?, email?|tel? }</code>.
                        Le code est généré et envoyé par <code>OtpService</code>.
                    </div>
                </div>

                <div class="info-box info">
                    <strong>Étape 3 — Vérification</strong>
                    <div class="separator">
                        Le widget poste le code sur <code>POST /api/v1/signature/otp/verify</code>.
                        Cette passerelle :
                    </div>
                    <div class="separator">1. résout l'utilisateur (login / user_uuid) ;</div>
                    <div class="separator">2. appelle <code>OtpService::verify()</code> (réutilisation totale) ;</div>
                    <div class="separator">3. renvoie les données <strong>autoritaires</strong> :</div>
                    <div class="separator">   - Identité : <code>user_uuid</code>, <code>login</code>, <code>email</code></div>
                    <div class="separator">   - Personnel : <code>nom</code>, <code>prenoms</code>, <code>mobile_1</code></div>
                    <div class="separator">   - Adresse : <code>adresse_complete</code></div>
                    <div class="separator">   - Technique : <code>channel</code>, <code>contact</code>, <code>ip_address</code>, <code>user_agent</code>, <code>used_at</code></div>
                </div>

                <div class="info-box info">
                    <strong>Étape 4 — Preuve QR</strong>
                    <div class="separator">
                        Le widget demande la géolocalisation (best-effort, timeout 5 s), construit
                        l'URL de preuve à partir de <code>otpQrUrlTemplate</code> en substituant chaque
                        <code>{placeholder}</code>, génère le QR en base64 (QRCode.js, canvas 512×512),
                        puis poste sur <code>backendWebhookUrl</code>.
                    </div>
                </div>

                <div class="info-box warn">
                    <strong>Expiration du code</strong>
                    <div class="separator">
                        S'il n'y a pas de bouton « renvoyer » : si le code est invalide ou expiré,
                        le widget affiche un message et propose de basculer sur la signature manuscrite.
                        Le lien discret « Signer à la main » reste visible à tout moment.
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Payload webhook                                            --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="webhook">
                <p class="code-description">
                    Payload envoyé par Laravel au <code>webhook_url</code> de l'app hôte, signé par
                    l'en-tête <code>X-Api-Key</code>. Le champ <code>method</code> distingue les deux modes.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>// POST https://app-hote.com/webhooks/signature
// Headers: X-Api-Key: <api_key fournie à generate-link>
// Content-Type: application/json

// --- Mode OTP (method = 'otp_qr') ---
{
  "success": true,
  "status": 200,
  "method": "otp_qr",
  "signature": "data:image/png;base64,iVBORw0KGgoAAAANS...",   // QR encodant l'URL de preuve
  "token": "aB3k...64car...",
  "document_url": "https://…/contrat.pdf",
  "signature_request_uuid": "…",
  "signed_at": "2026-01-15T10:32:11+00:00",
  "timestamp":  "2026-01-15T10:32:11+00:00",
  "otp": {
    "channel":    "sms",                    // canal utilisé
    "contact":    "0700000000",             // contact utilisé (email ou tel)
    "purpose":    "signature",              // purpose fourni à auth/otp/send
    "ip_address": "41.207.xx.xx",           // autoritaire (serveur)
    "user_agent": "Mozilla/5.0 …",          // autoritaire (serveur)
    "used_at":    "2026-01-15T10:32:09+00:00",
    "user_uuid":  "9f8c-...",                // identifiant utilisateur
    "login":      "client.dupont",           // login utilisateur
    "email":      "client@exemple.ci",       // email utilisateur
    "nom":        "DUPONT",                  // nom
    "prenoms":    "Jean Pierre",             // prénoms
    "mobile_1":   "0700000000",              // mobile principal
    "adresse_complete": "123 Rue de la République, Cocody" // adresse complète
  },
  "geo": { "lat": 5.359952, "lng": -4.008256 } // best-effort navigateur
}

// --- Mode Handwritten (method = 'handwritten') ---
{
  "success": true,
  "status": 200,
  "method": "handwritten",
  "signature": "data:image/png;base64,iVBORw0KGgoAAAANS...",   // canvas
  "token": "aB3k...64car...",
  "document_url": "https://…/contrat.pdf",
  "signature_request_uuid": "…",
  "signed_at": "2026-01-15T10:32:11+00:00",
  "timestamp":  "2026-01-15T10:32:11+00:00"
}</code></pre>
                    @endverbatim
                </div>

                <div class="info-box warn">
                    <strong>Recommandation de traitement côté app hôte</strong>
                    <div class="separator">
                        Vérifier <code>X-Api-Key</code> avant tout. Puis, pour un usage probatoire :
                    </div>
                    <div class="separator">• si <code>method === 'otp_qr'</code> : se baser sur <code>otp.ip_address</code>, <code>otp.user_agent</code>, <code>otp.used_at</code> (autoritaires) ; traiter <code>geo</code> comme déclaratif ;</div>
                    <div class="separator">• si <code>method === 'handwritten'</code> : conserver <code>signature</code> comme élément graphique.</div>
                    <div class="separator">
                        Dans les deux cas, <strong>ne jamais</strong> considérer <code>signature</code> comme un secret :
                        c'est une preuve visuelle, pas un jeton.
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- API generate-link                                          --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="api">
                <p class="code-description">
                    Endpoint de création d'un lien de signature. Authentifié (Sanctum) : c'est l'app hôte
                    qui l'appelle depuis son backend, jamais le navigateur.
                </p>

                <div class="info-box">
                    <strong>Requête</strong>
                    <div class="separator"><code>POST /api/v1/signature/generate-link</code> (auth : <code>auth:sanctum</code>)</div>
                </div>

                <div class="code-block">
                    @verbatim
                    <pre><code>// --- Champs historiques ---
{
  "document_url": "https://…/contrat.pdf",          // optionnel
  "document_description": "Contrat de souscription", // optionnel, max 500
  "webhook_url": "https://app-hote.com/webhooks/signature",  // requis, HTTPS
  "api_key": "<secret partagé, min 16 car.>",       // requis, reste côté serveur
  "success_redirect_url": "https://app-hote.com/success",   // optionnel
  "cancel_redirect_url":  "https://app-hote.com/cancel",    // optionnel
  "enable_auto_polling": true,                       // optionnel, booléen
  "expires_in": 3600,                                // optionnel, 60..86400

  // --- Champs OTP (optionnels) ---
  "signer_login":        "client.dupont",            // max 100
  "signer_user_uuid":    "9f8c…",                    // uuid, alternative
  "signer_email":        "client@exemple.ci",        // pré-remplit le champ contact
  "signer_phone":        "0700000000",               // idem
  "otp_purpose":         "signature",                // défaut 'signature', max 120
  "otp_qr_url_template": "https://app-hote.com/verify?user={user_uuid}&ch={channel}&contact={contact}&purpose={purpose}&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}"
}</code></pre>
                    @endverbatim
                </div>

                <div class="code-block" style="margin-top: 14px;">
                    @verbatim
                    <pre><code>// --- Réponse (201) ---
{
  "success": true,
  "message": "Lien de signature généré avec succès.",
  "code": "SIGNATURE_LINK_GENERATED",
  "data": {
    "token": "aB3k…64car…",
    "widget_url": "https://api…/signature/widget/aB3k…",
    "status_url": "https://api…/api/v1/signature/token/aB3k…/status",
    "expires_at": "2026-01-15T11:32:11+00:00",
    "expires_in": 3600,
    "document_url": "https://…/contrat.pdf",
    "document_description": "Contrat de souscription",
    "signature_request_uuid": "…",
    "success_redirect_url": "…",
    "cancel_redirect_url": "…",
    "enable_auto_polling": true,
    "otp_purpose": "signature",
    "has_otp_qr_url_template": true
  }
}</code></pre>
                    @endverbatim
                </div>

                <div class="info-box warn">
                    <strong>Ce que la réponse ne contient jamais</strong>
                    <div class="separator">• <code>api_key</code> — reste en base</div>
                    <div class="separator">• <code>webhook_url</code> — reste en base</div>
                    <div class="separator">• <code>token</code> brut côté modèle — masqué par <code>$hidden</code></div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- POINTS DE SÉCURITÉ                                          --}}
        {{-- ============================================================ --}}
        <div class="info-box">
            <strong>📌 Points clés de sécurité</strong>
            <div class="separator">• Ni le document ni la signature ne sont stockés côté Laravel.</div>
            <div class="separator">• Le secret partagé (<code>api_key</code>) ne quitte jamais le serveur.</div>
            <div class="separator">• Le widget authentifie ses appels via le <code>token</code> lui-même : imprévisible, à usage unique, expirant.</div>
            <div class="separator">• La génération du lien exige une authentification côté app hôte.</div>
            <div class="separator">• En mode OTP, <code>ip_address</code>, <code>user_agent</code> et <code>used_at</code> sont récupérés côté serveur (autoritaires).</div>
            <div class="separator">• La géolocalisation (<code>lat</code>, <code>lng</code>) est <strong>déclarative</strong> : best-effort navigateur, peut être refusée.</div>
        </div>
    </div>

    <script src="{{ asset('assets/js/signature-widget.js') }}"></script>
    <script>
        const widgetHost = document.getElementById('widget-demo-host');

        // Valeurs générées côté serveur pour CE lien de démonstration (voir la route /signature/demo).
        const demoConfig = {
            token: @json($token),
            documentUrl: @json($document_url),
            documentDescription: @json($document_description),
            backendWebhookUrl: @json($backend_webhook_url),
            apiUrl: @json($api_url),
            signingLink: @json($widget_url),
            enableAutoPolling: true,

            // OTP (démo)
            otpSendUrl:        @json($otp_send_url),
            otpVerifyUrl:      @json($otp_verify_url),
            signerLogin:       @json($signer_login),
            signerUserUuid:    null,
            signerEmail:       @json($signer_email),
            signerPhone:       @json($signer_phone),
            otpPurpose:        @json($otp_purpose),
            otpQrUrlTemplate:  @json($otp_qr_url_template),
        };

        function renderWidget(mode) {
            widgetHost.innerHTML = '';
            const demoContainer = document.createElement('div');
            widgetHost.appendChild(demoContainer);

            // mode 'otp'          -> mode par défaut (OTP)
            // mode 'handwritten'  -> canvas direct (forceMode 'mobile')
            // mode 'desktop'      -> QR d'appairage (forceMode 'desktop')
            const cfg = Object.assign({}, demoConfig, {
                container: demoContainer,
                onSigned: function (data) {
                    console.log('Signature de démonstration terminée', data);
                },
                onError: function (err) {
                    console.error('Erreur widget', err);
                }
            });

            if (mode === 'handwritten') {
                cfg.forceMode = 'mobile';
                // On indique au widget d'aller directement sur le canvas :
                // petite astuce — on clique programmatiquement sur le lien
                // « Signer à la main » après le rendu.
                setTimeout(function () {
                    const link = demoContainer.shadowRoot
                        ? demoContainer.shadowRoot.querySelector('.sw-link')
                        : null;
                    if (link) link.click();
                }, 50);
            } else if (mode === 'desktop') {
                cfg.forceMode = 'desktop';
            }
            // mode 'otp' : on ne force rien -> écran OTP par défaut

            try {
                new SignatureWidget(cfg);
            } catch (e) {
                widgetHost.innerHTML =
                    '<div style="padding:20px;color:#b91c1c;">Erreur de chargement du widget : ' + e.message + '</div>';
            }
        }

        document.querySelectorAll('.demo-card button').forEach((button) => {
            button.addEventListener('click', function () {
                renderWidget(this.dataset.demoMode);
            });
        });

        document.querySelectorAll('.tab-btn').forEach((button) => {
            button.addEventListener('click', function () {
                const tab = this.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach((el) => el.classList.toggle('active', el === button));
                document.querySelectorAll('.tab-content').forEach((panel) => {
                    panel.classList.toggle('active', panel.dataset.tabPanel === tab);
                });
            });
        });

        // Écran par défaut : OTP
        renderWidget('otp');
    </script>
</body>

</html>
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

        .step-list { counter-reset: step; list-style: none; padding: 0; margin: 0; }
        .step-list li {
            counter-increment: step; position: relative; padding-left: 42px;
            margin-bottom: 14px; line-height: 1.6; color: #374151;
        }
        .step-list li::before {
            content: counter(step); position: absolute; left: 0; top: 0;
            width: 28px; height: 28px; border-radius: 50%;
            background: #075429; color: #fff; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }

        .table-wrap { overflow-x: auto; margin-top: 10px; }
        table.doc-table {
            width: 100%; border-collapse: collapse; font-size: 13px;
            background: #fff; border-radius: 10px; overflow: hidden;
        }
        table.doc-table th, table.doc-table td {
            padding: 10px 12px; text-align: left; border-bottom: 1px solid #edf1ee;
            vertical-align: top;
        }
        table.doc-table th {
            background: #f7faf7; color: #111827; font-weight: 700; font-size: 12.5px;
        }
        table.doc-table td code { font-size: 11.5px; }

        @media (max-width: 700px) {
            .header { flex-direction: column; align-items: flex-start; }
            .container { padding: 22px 18px; }
        }
    </style>
</head>

<body>

@if (($webhook_mode ?? 'internal_echo') === 'webhook_site')
    <div style="max-width: 1100px; margin: 0 auto 20px; padding: 14px 18px;
                background: #dcfce7; border-left: 4px solid #22c55e;
                border-radius: 10px; color: #166534; font-size: 14px;">
        ✅ <strong>Mode webhook.site activé</strong> — la signature sera envoyée à
        <a href="{{ $webhook_base }}" target="_blank" rel="noopener"
           style="color: #166534; text-decoration: underline;">
            webhook.site
        </a>.
        Ouvrez ce lien dans un autre onglet <strong>avant</strong> de signer pour voir
        le payload en temps réel.
    </div>
@else
    <div style="max-width: 1100px; margin: 0 auto 20px; padding: 14px 18px;
                background: #fef3c7; border-left: 4px solid #f59e0b;
                border-radius: 10px; color: #92400e; font-size: 14px;">
        ⚠️ <strong>Mode écho interne</strong> — le webhook n'est pas réellement appelé.
        Pour tester le flux complet, définissez <code>DEMO_WEBHOOK_BASE</code> dans
        <code>.env</code> (ex: <code>https://webhook.site/votre-uuid</code>).
    </div>
@endif
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
          ◄────────  { success: true, code: OTP_SENT, data: { expires_in } }
  Widget  :  affiche 6 cases OTP + décompte (mm:ss)
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
                <button type="button" class="tab-btn" data-tab="security">Sécurité</button>
                <button type="button" class="tab-btn" data-tab="troubleshoot">Dépannage</button>
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
                    <pre><code>&lt;script src="https://apidev.yakoafricassur.com/api/v1/signature/signature-widget.js"&gt;&lt;/script&gt;

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
    backendWebhookUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/webhook',
    apiUrl:            'https://apidev.yakoafricassur.com/api/v1/signature',

    // --- OTP (mode par défaut) ---
    otpSendUrl:   'https://apidev.yakoafricassur.com/api/v1/auth/otp/send',
    otpVerifyUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/otp/verify',
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

                <div class="info-box">
                    <strong>💡 Intégration en 4 étapes</strong>
                    <ol class="step-list" style="margin-top: 12px;">
                        <li>Votre backend appelle <code>generate-link</code> et récupère <code>token</code> + <code>widget_url</code>.</li>
                        <li>Vous affichez le widget (redirection ou embarquement) avec ces valeurs + les pré-remplissages OTP.</li>
                        <li>Le signataire signe (OTP ou canvas). Le widget poste sur <code>backendWebhookUrl</code>.</li>
                        <li>Laravel relaie la signature à votre <code>webhook_url</code> avec <code>X-Api-Key</code>. Vous la traitez.</li>
                    </ol>
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
    script.src = 'https://apidev.yakoafricassur.com/api/v1/signature/signature-widget.js';

    script.onload = () => {
      if (!window.SignatureWidget) return;

      new window.SignatureWidget({
        container: ref.current,
        token,
        signingLink: widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/webhook',
        apiUrl:            'https://apidev.yakoafricassur.com/api/v1/signature',

        // OTP
        otpSendUrl:   'https://apidev.yakoafricassur.com/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/otp/verify',
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
    script.src = 'https://apidev.yakoafricassur.com/api/v1/signature/signature-widget.js';
    script.onload = () =&gt; {
      new window.SignatureWidget({
        container: this.$refs.signatureHost,
        token: this.token,
        signingLink: this.widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/webhook',
        apiUrl:            'https://apidev.yakoafricassur.com/api/v1/signature',

        otpSendUrl:   'https://apidev.yakoafricassur.com/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/otp/verify',
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
    script.src = 'https://apidev.yakoafricassur.com/api/v1/signature/signature-widget.js';
    script.onload = () =&gt; {
      new (window as any).SignatureWidget({
        container: this.host.nativeElement,
        token: this.token,
        signingLink: this.widgetUrl,

        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',

        backendWebhookUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/webhook',
        apiUrl:            'https://apidev.yakoafricassur.com/api/v1/signature',

        otpSendUrl:   'https://apidev.yakoafricassur.com/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/otp/verify',
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

        backendWebhookUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/webhook',
        apiUrl:            'https://apidev.yakoafricassur.com/api/v1/signature',

        otpSendUrl:   'https://apidev.yakoafricassur.com/api/v1/auth/otp/send',
        otpVerifyUrl: 'https://apidev.yakoafricassur.com/api/v1/signature/otp/verify',
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
      script.src = 'https://apidev.yakoafricassur.com/api/v1/signature/signature-widget.js';
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

                <div class="table-wrap">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Paramètre</th>
                                <th>Requis</th>
                                <th>Défaut</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>container</code></td>
                                <td>✅</td>
                                <td>—</td>
                                <td>Sélecteur CSS ou élément DOM hôte</td>
                            </tr>
                            <tr>
                                <td><code>token</code></td>
                                <td>✅</td>
                                <td>—</td>
                                <td>Token 64 car. reçu de <code>generate-link</code></td>
                            </tr>
                            <tr>
                                <td><code>backendWebhookUrl</code></td>
                                <td>✅</td>
                                <td>—</td>
                                <td>Toujours <code>https://apidev.yakoafricassur.com/api/v1/signature/webhook</code></td>
                            </tr>
                            <tr>
                                <td><code>signingLink</code></td>
                                <td>⚠️</td>
                                <td><code>window.location.href</code></td>
                                <td>URL complète du widget (QR d'appairage)</td>
                            </tr>
                            <tr>
                                <td><code>apiUrl</code></td>
                                <td>⚠️</td>
                                <td>—</td>
                                <td>Base API pour le polling</td>
                            </tr>
                            <tr>
                                <td><code>documentUrl</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>URL du document à signer</td>
                            </tr>
                            <tr>
                                <td><code>documentDescription</code></td>
                                <td>❌</td>
                                <td><code>''</code></td>
                                <td>Libellé affiché en en-tête</td>
                            </tr>
                            <tr>
                                <td><code>otpSendUrl</code></td>
                                <td>⚠️ OTP</td>
                                <td>—</td>
                                <td><code>https://apidev.yakoafricassur.com/api/v1/auth/otp/send</code></td>
                            </tr>
                            <tr>
                                <td><code>otpVerifyUrl</code></td>
                                <td>⚠️ OTP</td>
                                <td>—</td>
                                <td><code>https://apidev.yakoafricassur.com/api/v1/signature/otp/verify</code></td>
                            </tr>
                            <tr>
                                <td><code>signerLogin</code></td>
                                <td>⚠️ OTP</td>
                                <td><code>null</code></td>
                                <td>Login du signataire</td>
                            </tr>
                            <tr>
                                <td><code>signerUserUuid</code></td>
                                <td>⚠️ OTP</td>
                                <td><code>null</code></td>
                                <td>Alternative à <code>signerLogin</code></td>
                            </tr>
                            <tr>
                                <td><code>signerEmail</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Pré-remplit le champ email</td>
                            </tr>
                            <tr>
                                <td><code>signerPhone</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Pré-remplit le champ téléphone</td>
                            </tr>
                            <tr>
                                <td><code>otpPurpose</code></td>
                                <td>⚠️ OTP</td>
                                <td><code>'signature'</code></td>
                                <td>Doit correspondre au backend</td>
                            </tr>
                            <tr>
                                <td><code>otpQrUrlTemplate</code></td>
                                <td>⚠️ OTP</td>
                                <td><code>null</code></td>
                                <td>URL avec placeholders pour le QR de preuve</td>
                            </tr>
                            <tr>
                                <td><code>otpExpiryMinutes</code></td>
                                <td>❌</td>
                                <td><code>5</code></td>
                                <td>Durée fallback (si absente du backend)</td>
                            </tr>
                            <tr>
                                <td><code>successRedirectUrl</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Redirection après succès (2 s)</td>
                            </tr>
                            <tr>
                                <td><code>cancelRedirectUrl</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Redirection sur annulation</td>
                            </tr>
                            <tr>
                                <td><code>enableAutoPolling</code></td>
                                <td>❌</td>
                                <td><code>false</code></td>
                                <td>Active le polling desktop</td>
                            </tr>
                            <tr>
                                <td><code>pollingInterval</code></td>
                                <td>❌</td>
                                <td><code>5000</code></td>
                                <td>Intervalle de polling (ms)</td>
                            </tr>
                            <tr>
                                <td><code>maxPollingAttempts</code></td>
                                <td>❌</td>
                                <td><code>120</code></td>
                                <td>Nombre max de tentatives</td>
                            </tr>
                            <tr>
                                <td><code>forceMode</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td><code>'desktop'</code> ou <code>'mobile'</code></td>
                            </tr>
                            <tr>
                                <td><code>breakpoint</code></td>
                                <td>❌</td>
                                <td><code>768</code></td>
                                <td>Seuil px mobile/desktop</td>
                            </tr>
                            <tr>
                                <td><code>onSigned</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Callback après succès</td>
                            </tr>
                            <tr>
                                <td><code>onError</code></td>
                                <td>❌</td>
                                <td><code>null</code></td>
                                <td>Callback erreur</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="info-box info" style="margin-top: 20px;">
                    <strong>Placeholders du <code>otpQrUrlTemplate</code></strong>
                    <div class="table-wrap">
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Placeholder</th>
                                    <th>Source</th>
                                    <th>Autoritaire ?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>{user_uuid}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{login}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{email}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{nom}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{prenoms}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{mobile_1}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{adresse_complete}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{channel}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{contact}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{purpose}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{ip_address}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{user_agent}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{used_at}</code></td><td>Backend</td><td>✅</td></tr>
                                <tr><td><code>{lat}</code></td><td>Navigateur</td><td>❌ déclaratif</td></tr>
                                <tr><td><code>{lng}</code></td><td>Navigateur</td><td>❌ déclaratif</td></tr>
                            </tbody>
                        </table>
                    </div>
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
                        le champ est <strong>pré-rempli et désactivé</strong> (le signataire ne peut pas
                        modifier un contact imposé par l'app hôte). Le lien « Changer de contact »
                        est alors masqué.
                    </div>
                </div>

                <div class="info-box info">
                    <strong>Étape 2 — Demande de code</strong>
                    <div class="separator">
                        Le widget appelle <code>POST /api/v1/auth/otp/send</code> (endpoint existant,
                        throttle <code>5,10</code>) avec <code>{ channel, purpose, login?, user_uuid?, email?|tel? }</code>.
                        Le code est généré et envoyé par <code>OtpService</code>.
                    </div>
                    <div class="separator">
                        <strong>Après succès</strong> : la zone de demande (canal + contact + bouton)
                        est <strong>masquée</strong>, la zone de saisie du code devient visible, et un
                        <strong>compte à rebours</strong> démarre (5 min par défaut, lu dans <code>expires_in</code>).
                    </div>
                </div>

                <div class="info-box info">
                    <strong>Étape 3 — Saisie du code</strong>
                    <div class="separator">
                        6 cases individuelles avec :
                    </div>
                    <div class="separator">• <strong>Auto-avance</strong> à la saisie d'un chiffre</div>
                    <div class="separator">• <strong>Backspace</strong> qui recule et efface la case précédente</div>
                    <div class="separator">• <strong>Coller</strong> un code de 6 chiffres le répartit automatiquement</div>
                    <div class="separator">• <strong>Autofill SMS</strong> (<code>autocomplete="one-time-code"</code>)</div>
                    <div class="separator">• <strong>Flèches</strong> ← → pour naviguer</div>
                    <div class="separator">
                        Le <strong>compte à rebours</strong> change de couleur : vert > 60 s, orange ≤ 60 s, rouge ≤ 10 s, puis « Code expiré ».
                    </div>
                </div>

                <div class="info-box info">
                    <strong>Étape 4 — Vérification</strong>
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
                    <strong>Étape 5 — Preuve QR</strong>
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
// Headers: X-Api-Key: <secret partagé communiqué par YAKOA>
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
    "adresse_complete": "123 Rue de la République, Cocody"
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

                <div class="info-box info">
                    <strong>Exemple de contrôleur côté app hôte (Laravel)</strong>
                    <div class="code-block" style="margin-top: 10px;">
                        @verbatim
                        <pre><code>public function signatureCallback(Request $request)
{
    // 1. Vérifier la clé API
    if ($request->header('X-Api-Key') !== config('services.yakoa.webhook_key')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // 2. Récupérer les données
    $token     = $request->input('token');
    $method    = $request->input('method', 'handwritten');
    $signature = $request->input('signature');           // base64 PNG
    $uuid      = $request->input('signature_request_uuid');

    // 3. Retrouver la demande en local
    $demand = SignatureDemand::where('yakoa_request_uuid', $uuid)->firstOrFail();

    // 4. Sauvegarder la signature en fichier
    [$meta, $b64] = explode(',', $signature, 2);
    Storage::disk('local')->put("signatures/{$uuid}.png", base64_decode($b64));

    // 5. Enregistrer les métadonnées
    $demand->update([
        'status'         => 'signed',
        'method'         => $method,
        'signature_path' => "signatures/{$uuid}.png",
        'signed_at'      => $request->input('signed_at'),
        'otp_channel'    => $request->input('otp.channel'),
        'otp_contact'    => $request->input('otp.contact'),
        'ip_address'     => $request->input('otp.ip_address'),      // autoritaire
        'user_agent'     => $request->input('otp.user_agent'),      // autoritaire
        'used_at'        => $request->input('otp.used_at'),         // autoritaire
        'geo_lat'        => $request->input('geo.lat'),             // déclaratif
        'geo_lng'        => $request->input('geo.lng'),             // déclaratif
    ]);

    return response()->json(['success' => true]);
}</code></pre>
                        @endverbatim
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
                    <div class="separator"><code>POST /api/v1/signature/generate-link</code> (auth : <code>auth:sanctum</code>, throttle 60/min)</div>
                </div>

                <div class="code-block">
                    @verbatim
                    <pre><code>// --- Champs historiques ---
{
  "document_url": "https://…/contrat.pdf",          // optionnel
  "document_description": "Contrat de souscription", // optionnel, max 500
  "webhook_url": "https://app-hote.com/webhooks/signature",  // requis, HTTPS
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
  "otp_qr_url_template": "https://app-hote.com/verify?user={user_uuid}&login={login}&email={email}&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}&ch={channel}&contact={contact}&purpose={purpose}&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}"
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
    "widget_url": "https://apidev.yakoafricassur.com/signature/widget/aB3k…",
    "status_url": "https://apidev.yakoafricassur.com/api/v1/signature/token/aB3k…/status",
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

                <div class="info-box info">
                    <strong>Champs à conserver côté app hôte</strong>
                    <div class="separator">• <code>token</code> → à passer au frontend</div>
                    <div class="separator">• <code>widget_url</code> → à passer au frontend (QR d'appairage)</div>
                    <div class="separator">• <code>signature_request_uuid</code> → identifiant à stocker dans votre base</div>
                </div>

                <div class="info-box warn">
                    <strong>Erreurs courantes</strong>
                    <div class="separator">• <code>USER_NOT_FOUND</code> : l'utilisateur n'existe pas dans YAKOA (au moment de l'envoi OTP)</div>
                    <div class="separator">• <code>INVALID_TOKEN</code> : token inconnu, expiré ou déjà utilisé</div>
                    <div class="separator">• <code>WEBHOOK_DELIVERY_FAILED</code> : l'app hôte n'a pas répondu 2xx</div>
                    <div class="separator">• <code>VALIDATION_ERROR</code> : un champ requis manque ou est invalide</div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Sécurité                                                   --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="security">
                <p class="code-description">
                    Principes de sécurité appliqués sur toute la chaîne.
                </p>

                <div class="info-box">
                    <strong>🔒 Principes</strong>
                    <div class="separator">• <strong>Token opaque</strong> : 64 caractères aléatoires, usage unique, expirant</div>
                    <div class="separator">• <strong>Aucun secret côté client</strong> : <code>api_key</code> et <code>webhook_url</code> ne quittent jamais le serveur YAKOA</div>
                    <div class="separator">• <strong>Passerelle OTP autoritaire</strong> : IP, User-Agent et horodatage viennent du serveur, pas du navigateur</div>
                    <div class="separator">• <strong>Aucune persistance</strong> : document et signature ne sont jamais stockés côté YAKOA</div>
                    <div class="separator">• <strong>Verrou pessimiste</strong> : <code>lockForUpdate()</code> empêche les doubles soumissions</div>
                    <div class="separator">• <strong>Throttling</strong> : chaque endpoint a sa limite</div>
                    <div class="separator">• <strong>SSRF-hardened</strong> : le proxy document bloque IP privées, loopback, métadonnées cloud</div>
                </div>

                <div class="info-box info">
                    <strong>Authentification webhook</strong>
                    <div class="separator">• Header <code>X-Api-Key</code> envoyé par YAKOA à l'app hôte lors du relais</div>
                    <div class="separator">• Vérification obligatoire côté app hôte <strong>avant tout traitement</strong></div>
                </div>

                <div class="info-box warn">
                    <strong>Règle de persistance stricte</strong>
                    <div class="separator">• <strong>Aucune persistance</strong> du document ni de la signature côté YAKOA</div>
                    <div class="separator">• Seules les métadonnées (<code>token</code>, <code>expires_at</code>, <code>delivery_status</code>, <code>metadata</code>) vivent dans <code>signature_requests</code></div>
                    <div class="separator">• <strong>L'app hôte est seule responsable</strong> d'apposer et de conserver la signature</div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- Dépannage                                                  --}}
            {{-- ---------------------------------------------------------- --}}
            <div class="tab-content" data-tab-panel="troubleshoot">
                <p class="code-description">
                    Problèmes les plus fréquents et solutions.
                </p>

                <div class="info-box warn">
                    <strong>Token invalide ou expiré</strong>
                    <div class="separator">• Vérifier <code>expires_at</code> (durée max 24 h)</div>
                    <div class="separator">• Vérifier que le token n'a pas déjà été utilisé (<code>is_used: true</code>)</div>
                    <div class="separator">• Vérifier le format du token (64 caractères alphanumériques)</div>
                </div>

                <div class="info-box warn">
                    <strong><code>USER_NOT_FOUND</code> à l'envoi OTP</strong>
                    <div class="separator">• L'app hôte n'a pas fourni <code>signer_login</code> ou <code>signer_user_uuid</code></div>
                    <div class="separator">• L'utilisateur n'existe pas dans YAKOA</div>
                    <div class="separator">• Le login ne correspond à aucun utilisateur actif</div>
                </div>

                <div class="info-box warn">
                    <strong>Erreur d'envoi SMS / Email</strong>
                    <div class="separator">• Vérifier la configuration Infobip / SMTP côté YAKOA</div>
                    <div class="separator">• Vérifier le format du numéro (10 chiffres pour la Côte d'Ivoire)</div>
                    <div class="separator">• Vérifier que le canal WhatsApp est configuré (sinon désactivé dans le widget)</div>
                </div>

                <div class="info-box warn">
                    <strong>Document ne se charge pas</strong>
                    <div class="separator">• Vérifier que l'URL est accessible publiquement</div>
                    <div class="separator">• Vérifier le format (PDF, JPG, PNG)</div>
                    <div class="separator">• Si externe : vérifier la liste blanche <code>allowed_document_hosts</code></div>
                </div>

                <div class="info-box warn">
                    <strong>Le code OTP arrive mais ne valide pas</strong>
                    <div class="separator">• Vérifier que <code>purpose</code> correspond exactement (défaut <code>'signature'</code>)</div>
                    <div class="separator">• Vérifier que <code>channel</code> et <code>contact</code> sont identiques à ceux d'<code>otp/send</code></div>
                    <div class="separator">• Vérifier que l'utilisateur est correctement résolu (<code>login</code> ou <code>user_uuid</code>)</div>
                </div>

                <div class="info-box warn">
                    <strong>Le webhook hôte ne reçoit rien</strong>
                    <div class="separator">• Vérifier que <code>webhook_url</code> est en <strong>HTTPS</strong> (HTTP refusé)</div>
                    <div class="separator">• Vérifier que l'URL est accessible publiquement</div>
                    <div class="separator">• Vérifier les logs YAKOA (<code>delivery_status</code> = <code>failed</code>)</div>
                    <div class="separator">• Vérifier le <code>X-Api-Key</code> (doit correspondre au secret configuré)</div>
                </div>

                <div class="info-box warn">
                    <strong>Le QR de preuve n'est pas lisible</strong>
                    <div class="separator">• Vérifier que <code>otpQrUrlTemplate</code> est bien fourni (sinon fallback : URL du widget)</div>
                    <div class="separator">• Vérifier que le template ne contient pas de caractères spéciaux non encodés</div>
                    <div class="separator">• La taille du QR est fixe à 512×512, largement suffisante</div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- POINTS DE SÉCURITÉ                                          --}}
        {{-- ============================================================ --}}
        <div class="info-box">
            <strong>📌 Points clés à retenir</strong>
            <div class="separator">1. Tous les endpoints du widget doivent pointer vers <strong>YAKOA</strong> (<code>apidev.yakoafricassur.com</code>), jamais vers l'app hôte.</div>
            <div class="separator">2. Le <code>webhook_url</code> est <strong>toujours HTTPS</strong>.</div>
            <div class="separator">3. L'app hôte doit fournir <code>signer_login</code> ou <code>signer_user_uuid</code> pour le mode OTP.</div>
            <div class="separator">4. Le token est <strong>à usage unique</strong> : une nouvelle signature = un nouveau <code>generate-link</code>.</div>
            <div class="separator">5. L'app hôte doit <strong>vérifier <code>X-Api-Key</code></strong> avant tout traitement de webhook.</div>
            <div class="separator">6. <code>ip_address</code>, <code>user_agent</code>, <code>used_at</code> sont <strong>autoritaires</strong> (mode OTP).</div>
            <div class="separator">7. <code>geo.lat</code> et <code>geo.lng</code> sont <strong>déclaratifs</strong> (best-effort navigateur).</div>
            <div class="separator">8. Le document et la signature ne sont <strong>jamais stockés</strong> côté YAKOA.</div>
            <div class="separator">9. Le proxy document bloque les IP privées, loopback et métadonnées cloud.</div>
            <div class="separator">10. Le throttling protège chaque endpoint contre les abus.</div>
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
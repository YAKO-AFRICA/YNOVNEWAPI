<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Widget Signature Électronique - Intégration</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #075429, #0a6b35);
            color: #fff;
            font-weight: 800;
            font-size: 18px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #111827;
        }

        .subtitle {
            margin-top: 2px;
            color: #6b7280;
            font-size: 13px;
        }

        .badge {
            background: #eafaf1;
            color: #075429;
            font-weight: 700;
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 999px;
        }

        .demo-section {
            background: #f7faf7;
            border: 1px solid #e8efe8;
            border-radius: 14px;
            padding: 20px 22px 16px;
            margin-bottom: 26px;
        }

        .demo-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 14px;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot.green { background: #22c55e; }
        .dot.orange { background: #f59e0b; }
        .dot.purple { background: #8b5cf6; }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .demo-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 18px;
            transition: all 0.2s ease;
        }

        .demo-card:hover {
            transform: translateY(-2px);
            border-color: #075429;
            box-shadow: 0 8px 20px rgba(7, 84, 41, 0.08);
        }

        .card-badge {
            display: inline-block;
            margin-bottom: 8px;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 700;
        }

        .green { background: #dcfce7; color: #166534; }
        .orange { background: #fef3c7; color: #92400e; }
        .purple { background: #ede9fe; color: #6d28d9; }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .card-text {
            font-size: 12.5px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .demo-card button {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            background: #075429;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .demo-card button:hover {
            background: #064320;
        }

        .widget-demo {
            min-height: 500px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .tabs-container {
            margin-top: 12px;
        }

        .tabs-header {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #edf1ee;
        }

        .tab-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #075429;
            color: white;
            border-color: #075429;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .code-block {
            background: #0c1f15;
            color: #d7ecdf;
            border-radius: 12px;
            padding: 14px 16px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
        }

        .code-block pre {
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .code-description {
            margin: 0 0 14px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box {
            margin-top: 20px;
            background: #f7faf7;
            border-left: 4px solid #075429;
            padding: 14px 16px;
            border-radius: 10px;
            color: #374151;
        }

        .info-box strong {
            color: #111827;
        }

        .separator {
            margin-top: 10px;
            color: #4b5563;
            line-height: 1.7;
        }

        code {
            background: rgba(7, 84, 41, 0.08);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 12px;
            color: #0f172a;
        }

        .highlight {
            color: #0f172a;
            font-weight: 600;
        }

        @media (max-width: 700px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                padding: 22px 18px;
            }
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
                    <div class="subtitle">Intégration front-end et démonstration</div>
                </div>
            </div>
            <div class="badge">v1.0</div>
        </div>

        <div class="demo-section">
            <div class="demo-title">
                <span class="dot green"></span>
                Démo du widget
            </div>

            <div class="demo-grid">
                <div class="demo-card">
                    <span class="card-badge green">Desktop</span>
                    <div class="card-title">QR code + mobile</div>
                    <div class="card-text">Le client scanne le QR code pour signer depuis son téléphone.</div>
                    <button type="button" data-demo-mode="desktop">Tester</button>
                </div>

                <div class="demo-card">
                    <span class="card-badge orange">Mobile</span>
                    <div class="card-title">Signature directe</div>
                    <div class="card-text">Signature manuscrite intégrée sur écran tactile.</div>
                    <button type="button" data-demo-mode="mobile">Tester</button>
                </div>

                <div class="demo-card">
                    <span class="card-badge purple">Webhook</span>
                    <div class="card-title">Transmission sécurisée</div>
                    <div class="card-text">La signature est envoyée au webhook avec header API key.</div>
                    <button type="button" data-demo-mode="desktop">Voir le flux</button>
                </div>
            </div>

            <div id="widget-demo-host" class="widget-demo"></div>
        </div>

        <div class="tabs-container">
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-tab="html">HTML / Vanilla JS</button>
                <button type="button" class="tab-btn" data-tab="react">React</button>
                <button type="button" class="tab-btn" data-tab="vue">Vue</button>
                <button type="button" class="tab-btn" data-tab="angular">Angular</button>
                <button type="button" class="tab-btn" data-tab="next">Next.js</button>
            </div>

            <div class="tab-content active" data-tab-panel="html">
                <p class="code-description">
                    L’intégration la plus simple consiste à inclure le script du widget, créer un conteneur et l’initialiser avec
                    les paramètres du document et du webhook de votre application hôte.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>&lt;script src="https://votre-domaine.com/assets/js/signature-widget.js"&gt;&lt;/script&gt;

&lt;div id="signature-widget"&gt;&lt;/div&gt;

&lt;script&gt;
  new SignatureWidget({
    container: '#signature-widget',
    documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
    documentDescription: 'Contrat de souscription',
    webhookUrl: 'https://app-hote.com/api/signature-webhook',
    apiKey: 'secret-partage',
    signingLink: 'https://api.votre-domaine.com/signature/widget/abc123xyz',
    successRedirectUrl: 'https://app-hote.com/success',
    cancelRedirectUrl: 'https://app-hote.com/cancel',
    enableAutoPolling: true,
    onSigned: function (data) {
      console.log('Signature OK', data);
    },
    onError: function (error) {
      console.error('Erreur', error);
    }
  });
&lt;/script&gt;</code></pre>
                    @endverbatim
                </div>

                <div class="info-box">
                    <strong>🔧 Paramètres principaux :</strong>
                    <div class="separator"><code>container</code> : conteneur HTML du widget</div>
                    <div class="separator"><code>documentUrl</code> : URL du PDF ou image à signer</div>
                    <div class="separator"><code>webhookUrl</code> : endpoint de réception de la signature côté app hôte</div>
                    <div class="separator"><code>apiKey</code> : secret partagé pour authentifier le webhook</div>
                    <div class="separator"><code>signingLink</code> : lien avec token pour le mode desktop QR code</div>
                    <div class="separator"><code>successRedirectUrl</code> : redirection après signature réussie</div>
                </div>
            </div>

            <div class="tab-content" data-tab-panel="react">
                <p class="code-description">
                    Exemple d’intégration React. Le composant initialise le widget et transmet les callbacks de succès et d’erreur.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>import { useEffect, useRef } from 'react';

export default function SignatureButton() {
  const ref = useRef(null);

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://votre-domaine.com/assets/js/signature-widget.js';

    script.onload = () => {
      if (!window.SignatureWidget) return;

      new window.SignatureWidget({
        container: ref.current,
        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',
        webhookUrl: 'https://app-hote.com/api/signature-webhook',
        apiKey: 'secret-partage',
        signingLink: 'https://api.votre-domaine.com/signature/widget/abc123xyz',
        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl: 'https://app-hote.com/cancel',
        onSigned: (data) => console.log('OK', data),
        onError: (err) => console.error('Erreur', err),
      });
    };

    document.body.appendChild(script);

    return () => {
      document.body.removeChild(script);
    };
  }, []);

  return &lt;div ref={ref} /&gt;;
}</code></pre>
                    @endverbatim
                </div>
            </div>

            <div class="tab-content" data-tab-panel="vue">
                <p class="code-description">
                    Sous Vue.js, on charge le script dynamiquement puis on instancie le widget dans le hook mounted.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>&lt;template&gt;
  &lt;div ref="signatureHost" /&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
  mounted() {
    const script = document.createElement('script');
    script.src = 'https://domaine-de-api.com/api/v1/signature/signature-widget.js';
    script.onload = () =&gt; {
      new window.SignatureWidget({
        container: this.$refs.signatureHost,
        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',
        webhookUrl: 'https://app-hote.com/api/signature-webhook',
        apiKey: 'secret-partage',
        signingLink: 'https://api.votre-domaine.com/signature/widget/abc123xyz',
        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl: 'https://app-hote.com/cancel',
      });
    };

    document.body.appendChild(script);
  }
};
&lt;/script&gt;</code></pre>
                    @endverbatim
                </div>
            </div>

            <div class="tab-content" data-tab-panel="angular">
                <p class="code-description">
                    Exemple Angular. Le widget est instancié dans le cycle de vie du composant après chargement du script.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>import { Component, OnInit, ElementRef, ViewChild } from '@angular/core';

@Component({
  selector: 'app-signature-widget',
  template: '&lt;div #host&gt;&lt;/div&gt;'
})
export class SignatureWidgetComponent implements OnInit {
  @ViewChild('host', { static: true }) host!: ElementRef;

  ngOnInit(): void {
    const script = document.createElement('script');
    script.src = 'https://votre-domaine.com/assets/js/signature-widget.js';
    script.onload = () =&gt; {
      new (window as any).SignatureWidget({
        container: this.host.nativeElement,
        documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
        documentDescription: 'Contrat de souscription',
        webhookUrl: 'https://app-hote.com/api/signature-webhook',
        apiKey: 'secret-partage',
        signingLink: 'https://api.votre-domaine.com/signature/widget/abc123xyz',
        successRedirectUrl: 'https://app-hote.com/success',
        cancelRedirectUrl: 'https://app-hote.com/cancel',
      });
    };
    document.body.appendChild(script);
  }
}</code></pre>
                    @endverbatim
                </div>
            </div>

            <div class="tab-content" data-tab-panel="next">
                <p class="code-description">
                    Exemple de composant Next.js. Idéal pour un contexte où le widget doit être chargé côté client.
                </p>

                <div class="code-block">
                    @verbatim
                    <pre><code>'use client';

import { useEffect, useRef } from 'react';

export default function SignatureWidgetClient() {
  const hostRef = useRef(null);

  useEffect(() => {
    const loadWidget = async () =&gt; {
      const existing = document.querySelector('script[src*="signature-widget.js"]');
      if (!existing) {
        const script = document.createElement('script');
        script.src = 'https://votre-domaine.com/assets/js/signature-widget.js';
        script.async = true;
        script.onload = () =&gt; {
          if (window.SignatureWidget) {
            new window.SignatureWidget({
              container: hostRef.current,
              documentUrl: 'https://votre-domaine.com/documents/contrat.pdf',
              documentDescription: 'Contrat de souscription',
              webhookUrl: 'https://app-hote.com/api/signature-webhook',
              apiKey: 'secret-partage',
              signingLink: 'https://api.votre-domaine.com/signature/widget/abc123xyz',
              successRedirectUrl: 'https://app-hote.com/success',
              cancelRedirectUrl: 'https://app-hote.com/cancel',
            });
          }
        };
        document.body.appendChild(script);
      }
    };

    loadWidget();
  }, []);

  return &lt;div ref={hostRef} /&gt;;
}</code></pre>
                    @endverbatim
                </div>
            </div>
        </div>

        <div class="info-box">
            <strong>📌 Points clés de sécurité :</strong>
            <div class="separator">• Le widget ne stocke ni le document ni la signature en mémoire durable.</div>
            <div class="separator">• La signature est envoyée directement au webhook de votre application hôte.</div>
            <div class="separator">• L’authentification se fait via le header <code>X-Api-Key</code>.</div>
            <div class="separator">• Le token de signature est unique et expire après usage ou timeout.</div>
        </div>

        <div class="info-box">
            <strong>🧩 API de génération du lien :</strong>
            <div class="separator">Endpoint : <code>POST /api/v1/signature/generate-link</code></div>
            <div class="separator">Payload recommandé :</div>
            <div class="separator"><code>{ document_url, document_description, webhook_url, api_key, success_redirect_url, cancel_redirect_url, expires_in }</code></div>
            <div class="separator">Réponse attendue : <code>token</code>, <code>widget_url</code>, <code>expires_at</code>, <code>expires_in</code></div>
        </div>
    </div>

    <script src="{{ asset('assets/js/signature-widget.js') }}"></script>
    <script>
        const widgetHost = document.getElementById('widget-demo-host');

        function renderWidget(mode) {
            widgetHost.innerHTML = '';
            const demoContainer = document.createElement('div');
            widgetHost.appendChild(demoContainer);

            const demoConfig = {
                container: demoContainer,
                forceMode: mode,
                documentUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                documentDescription: 'Contrat de démonstration à signer',
                webhookUrl: 'https://httpbin.org/post',
                apiKey: 'demo-api-key',
                signingLink: window.location.origin + '/signature/widget/demo-token',
                successRedirectUrl: 'https://example.com/success',
                cancelRedirectUrl: 'https://example.com/cancel',
                enableAutoPolling: true,
                apiUrl: window.location.origin + '/api/v1/signature',
                useProxy: true,
                onSigned: function (data) {
                    console.log('Signature envoyée', data);
                },
                onError: function (err) {
                    console.error('Erreur widget', err);
                }
            };

            try {
                new SignatureWidget(demoConfig);
            } catch (e) {
                widgetHost.innerHTML = '<div style="padding:20px;color:#b91c1c;">Erreur de chargement du widget : ' + e.message + '</div>';
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

        renderWidget('desktop');
    </script>
</body>

</html>

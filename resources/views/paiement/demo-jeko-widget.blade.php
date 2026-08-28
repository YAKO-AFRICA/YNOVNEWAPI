<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jeko Payment Widget - Intégration</title>
    <style>
        /* ============================================================
           STYLES GLOBAUX
           ============================================================ */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 820px;
            margin: 40px auto;
            padding: 0 20px;
            color: #1f2937;
            background: #f9fafb;
        }

        .container {
            background: #fff;
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        /* ============================================================
           EN-TÊTE
           ============================================================ */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f2f1;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left .logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #1D603D, #0B482F);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 18px;
        }

        .header-left h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .header-left .subtitle {
            font-size: 13px;
            color: #6b7280;
            font-weight: 400;
        }

        .badge-version {
            background: #e8f5ee;
            color: #1D603D;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* ============================================================
           DÉMO WIDGET
           ============================================================ */
        .demo-section {
            background: #f8faf9;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            border: 1px solid #eef2f0;
        }

        .demo-section .demo-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-section .demo-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .demo-section .demo-title .dot.green {
            background: #22c55e;
        }

        .demo-section .demo-title .dot.orange {
            background: #f59e0b;
        }

        .demo-section .demo-title .dot.purple {
            background: #8b5cf6;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .demo-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 18px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
            cursor: pointer;
        }

        .demo-card:hover {
            border-color: #1D603D;
            box-shadow: 0 4px 12px rgba(29, 96, 61, 0.1);
            transform: translateY(-2px);
        }

        .demo-card .card-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .demo-card .card-badge.green {
            background: #d1fae5;
            color: #065f46;
        }

        .demo-card .card-badge.orange {
            background: #fef3c7;
            color: #92400e;
        }

        .demo-card .card-badge.purple {
            background: #ede9fe;
            color: #5b21b6;
        }

        .demo-card .card-badge.blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .demo-card .card-badge.red {
            background: #fee2e2;
            color: #991b1b;
        }

        .demo-card h4 {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px;
        }

        .demo-card p {
            font-size: 12.5px;
            color: #6b7280;
            margin: 0 0 10px;
        }

        .demo-card .btn-pay-sm {
            background: #1D603D;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .demo-card .btn-pay-sm:hover {
            background: #0B482F;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(29, 96, 61, 0.25);
        }

        .demo-card .btn-pay-sm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ============================================================
           ONGLETS D'INTÉGRATION
           ============================================================ */
        .tabs-container {
            margin-top: 28px;
        }

        .tabs-header {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0;
            background: #f3f5f4;
            border-radius: 10px 10px 0 0;
            padding: 4px 4px 0;
        }

        .tab-btn {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #1D603D;
            background: rgba(29, 96, 61, 0.06);
        }

        .tab-btn.active {
            color: #1D603D;
            background: #fff;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.04);
        }

        .tab-btn .tab-icon {
            font-size: 16px;
        }

        .tab-content {
            display: none;
            padding: 20px 0 0;
            background: #fff;
            border-radius: 0 0 10px 10px;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content .code-block {
            background: #0c1f15;
            border-radius: 10px;
            padding: 20px 24px;
            position: relative;
            overflow-x: auto;
            margin: 8px 0 16px;
        }

        .tab-content .code-block pre {
            margin: 0;
            color: #d7ecdf;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .tab-content .code-block .copy-btn {
            position: absolute;
            top: 10px;
            right: 12px;
            background: rgba(247, 164, 0, 0.15);
            border: 1px solid rgba(247, 164, 0, 0.3);
            color: #F7A400;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }

        .tab-content .code-block .copy-btn:hover {
            background: rgba(247, 164, 0, 0.3);
            color: #fff;
        }

        .tab-content .code-block .lang-tag {
            position: absolute;
            top: 10px;
            left: 14px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(215, 236, 223, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tab-content .code-description {
            font-size: 13.5px;
            color: #4b5563;
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .tab-content .code-description strong {
            color: #1D603D;
        }

        /* ============================================================
           INFOS
           ============================================================ */
        .info-box {
            background: #f0f7f3;
            border-left: 4px solid #1D603D;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
            color: #374151;
            margin-top: 20px;
            line-height: 1.6;
        }

        .info-box code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .info-box .separator {
            margin: 4px 0;
        }

        .info-box .highlight {
            color: #1D603D;
            font-weight: 600;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 640px) {
            .container {
                padding: 20px 16px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .demo-grid {
                grid-template-columns: 1fr;
            }

            .tabs-header {
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 2px;
            }

            .tab-btn {
                padding: 8px 14px;
                font-size: 12px;
                white-space: nowrap;
            }

            .tab-btn .tab-icon {
                font-size: 14px;
            }

            .tab-content .code-block {
                padding: 16px;
                font-size: 12px;
            }

            .tab-content .code-block pre {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 18px;
            }

            .demo-card {
                padding: 14px;
            }

            .tab-btn {
                padding: 6px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
@verbatim

<body>

    <div class="container">
        <!-- ============================================================
        EN-TÊTE
        ============================================================ -->
        <div class="header">
            <div class="header-left">
                <div class="logo">💳</div>
                <div>
                    <h1>Jeko Payment Widget</h1>
                    <span class="subtitle">Intégration pour développeurs front-end</span>
                </div>
            </div>
            <span class="badge-version">v1.0</span>
        </div>

        <!-- ============================================================
        DÉMO DU WIDGET
        ============================================================ -->
        <div class="demo-section">
            <div class="demo-title">
                <span class="dot green"></span>
                Tester les 3 types de paiement
                <span style="font-weight:400; color:#6b7280; font-size:13px; margin-left:8px;">
                    — Cliquez sur un scénario pour ouvrir le widget
                </span>
            </div>

            <div class="demo-grid">
                <!-- Carte 1: Premier paiement -->
                <div class="demo-card" id="cardFirstPayment">
                    <span class="card-badge green">1️⃣ Souscription</span>
                    <h4>Premier paiement</h4>
                    <p>Après validation d'un nouveau contrat</p>
                    <button class="btn-pay-sm" id="btnFirstPayment">Payer la première prime</button>
                </div>

                <!-- Carte 2: Paiement anticipé -->
                <div class="demo-card" id="cardEarlyPayment">
                    <span class="card-badge orange">2️⃣ Avance</span>
                    <h4>Paiement anticipé</h4>
                    <p>Renseignez l'identifiant du contrat</p>
                    <div style="margin-bottom:8px;">
                        <input type="text" id="contractIdEarly" placeholder="Ex: 3593104" value="3593104" style="width:100%; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <button class="btn-pay-sm" id="btnEarlyPayment">Payer en avance</button>
                </div>

                <!-- Carte 3: Régularisation -->
                <div class="demo-card" id="cardRecoveryPrime">
                    <span class="card-badge purple">3️⃣ Régularisation</span>
                    <h4>Récupération d'impayés</h4>
                    <p>Renseignez l'identifiant du contrat et Sélectionnez les factures à régulariser</p>
                    <div style="margin-bottom:8px;">
                        <input
                            type="text"
                            id="contractIdRecovery"
                            placeholder="Ex: 12452"
                            value="82718"  style="width:100%; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:13px; box-sizing:border-box;"/>
                    </div>
                    <button class="btn-pay-sm" id="btnRecoveryPrime">Régulariser mes impayés</button>
                </div>
            </div>

            <!-- Zone de pré-sélection des factures -->
            <div style="margin-top:16px; padding:12px 16px; background:#fff; border-radius:8px; border:1px solid #e5e7eb;">
                <p style="font-size:12px; font-weight:600; color:#374151; margin:0 0 8px;">
                    🧪 Factures pré-sélectionnées pour la régularisation :
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:8px 16px;">
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="173669" checked /> #173669
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3073532" /> #3073532
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3132514" checked /> #3132514
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3191079" checked /> #3191079
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3565293" checked /> #3565293
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3628563" checked /> #3628563
                    </label>
                </div>
                <p style="font-size:11px; color:#6b7280; margin:6px 0 0;">
                    💡 Ces factures seront automatiquement pré-sélectionnées dans le widget
                </p>
            </div>
        </div>

        <!-- ============================================================
        ONGLETS D'INTÉGRATION
        ============================================================ -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="html">
                    <span class="tab-icon">🌐</span> HTML / Vanilla JS
                </button>
                <button class="tab-btn" data-tab="react">
                    <span class="tab-icon">⚛️</span> React
                </button>
                <button class="tab-btn" data-tab="vue">
                    <span class="tab-icon">🟢</span> Vue.js
                </button>
                <button class="tab-btn" data-tab="angular">
                    <span class="tab-icon">🅰️</span> Angular
                </button>
                <button class="tab-btn" data-tab="nextjs">
                    <span class="tab-icon">▲</span> Next.js
                </button>
            </div>

            <!-- ==========================================================
            TAB 1: HTML / Vanilla JS
            ========================================================== -->
            <div class="tab-content active" id="tab-html">
                <p class="code-description">
                    Intégration simple dans une page HTML classique.
                    Chargez le script du widget et initialisez-le avec vos paramètres.
                </p>
                <div class="code-block">
                    <span class="lang-tag">HTML</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>&lt;!-- 1. Inclure le widget JS --&gt;
&lt;script src="{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}"&gt;&lt;/script&gt;

&lt;!-- 2. Initialiser le widget --&gt;
&lt;script&gt;
    const widget = new JekoWidget({
        backendEndpoint: '{{ url("/api/v1/paiements/jeko/init") }}',
        contractCheckEndpoint: '{{ url("/api/v1/paiements/jeko/contrat/verifier") }}',
        currency: 'XOF',
        autoVerifyContract: true,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        callbacks: {
            onSuccess: (redirectUrl, data) => {
                window.open(redirectUrl, '_blank');
            },
            onError: (message, data) => {
                alert('Erreur: ' + message);
            },
        },
    });

    function generateReference() {
        const now = new Date();
        const timestamp = now.getTime();
        const random = Math.random().toString(36).substring(2, 10);
        const code = String(Math.floor(Math.random() * 9999) + 1).padStart(
            4,
            "0",
        );
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");
        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");
        return `PAI-${year}${month}${day}${hours}${minutes}${seconds}-${timestamp}-${random}-${code}`;
    }

    // 3. Ouvrir le widget
    document.getElementById('payBtn').addEventListener('click', () => {
        widget.open({
            reference: generateReference(),
            paymentType: 'firstPayment', // firstPayment | earlyPayment | recoveryPrime
            contractId: '1093',
            customerEmail: 'client@example.com',
            customerName: 'Jean Dupont',
        });
    });
&lt;/script&gt;</code></pre>
                </div>

                <div class="info-box">
                    <strong>💡 Utilisation minimale :</strong>
                    <div class="separator">• Le widget est autonome — pas besoin de dépendances externes</div>
                    <div class="separator">• Les clés API sont sécurisées côté serveur</div>
                    <div class="separator">• Supporte les 3 types de paiement : <code>firstPayment</code>, <code>earlyPayment</code>, <code>recoveryPrime</code></div>
                </div>
            </div>

            <!-- ==========================================================
            TAB 2: React
            ========================================================== -->
            <div class="tab-content" id="tab-react">
                <p class="code-description">
                    Composant React réutilisable avec gestion d'état et chargement dynamique du widget.
                </p>
                <div class="code-block">
                    <span class="lang-tag">React</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>import React, { useState, useEffect, useRef } from "react";

// Chargement dynamique du widget
const loadJekoWidget = () => new Promise((resolve) => {
    if (window.JekoWidget) return resolve(window.JekoWidget);
    const script = document.createElement("script");
    script.src = "{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}";
    script.onload = () => resolve(window.JekoWidget);
    document.head.appendChild(script);
});

const JekoPaymentButton = ({
    contractId,
    paymentType = "firstPayment",
    customerEmail = "",
    customerName = "",
    preselectedInvoiceIds = [],
    onSuccess,
    onError,
    className = "",
    children,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [isReady, setIsReady] = useState(false);
    const widgetRef = useRef(null);

    useEffect(() => {
        const init = async () => {
            try {
                const JekoWidget = await loadJekoWidget();
                widgetRef.current = new JekoWidget({
                    backendEndpoint: process.env.REACT_APP_JEKO_API_URL || "{{ url('/api/v1/paiements/jeko/init') }}",
                    contractCheckEndpoint: process.env.REACT_APP_JEKO_CONTRACT_CHECK_URL || "{{ url('/api/v1/paiements/jeko/contrat/verifier') }}",
                    currency: "XOF",
                    autoVerifyContract: true,
                    callbacks: {
                        onSuccess: (url, data) => {
                            setIsLoading(false);
                            onSuccess?.(url, data);
                            window.open(url, "_blank");
                        },
                        onError: (msg, data) => {
                            setIsLoading(false);
                            onError?.(msg, data);
                        },
                        onOpen: () => setIsLoading(true),
                        onClose: () => setIsLoading(false),
                    },
                });
                setIsReady(true);
            } catch (error) {
                onError?.(error.message);
            }
        };
        init();
    }, []);

    const handlePayment = () => {
        if (!widgetRef.current || !isReady) return;
        const reference = `PAI-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;
        widgetRef.current.open({
            reference,
            paymentType,
            contractId,
            customerEmail,
            customerName,
            preselectedInvoiceIds: paymentType === "recoveryPrime" ? preselectedInvoiceIds : undefined,
            metadata: { source: "react_app" },
        });
    };

    return (
        <button
            onClick={handlePayment}
            disabled={!isReady || isLoading}
            className={`btn-pay ${className}`}
            style={{
                background: "#1D603D",
                color: "#fff",
                border: "none",
                padding: "13px 24px",
                borderRadius: "10px",
                fontWeight: 600,
                fontSize: "15px",
                cursor: "pointer",
                width: "100%",
                opacity: (!isReady || isLoading) ? 0.5 : 1,
            }}
        >
            {isLoading ? "Chargement..." : (children || "Payer avec Jeko")}
        </button>
    );
};

export default JekoPaymentButton;</code></pre>
                </div>

                <div class="info-box">
                    <strong>⚛️ Exemples d'utilisation :</strong>
                    <div class="separator">
                        <code>firstPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="1093" paymentType="firstPayment" /&gt;</span>
                    </div>
                    <div class="separator">
                        <code>earlyPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="earlyPayment" /&gt;</span>
                    </div>
                    <div class="separator">
                        <code>recoveryPrime</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="recoveryPrime" preselectedInvoiceIds={['173669']} /&gt;</span>
                    </div>
                </div>
            </div>

            <!-- ==========================================================
            TAB 3: Vue.js
            ========================================================== -->
            <div class="tab-content" id="tab-vue">
                <p class="code-description">
                    Composant Vue.js avec gestion d'état réactive et événements personnalisés.
                </p>
                <div class="code-block">
                    <span class="lang-tag">Vue.js</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>&lt;template&gt;
    &lt;button
        @click="handlePayment"
        :disabled="!isReady || isLoading"
        class="btn-pay"
    &gt;
        {{ isLoading ? "Chargement..." : label }}
    &lt;/button&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
    name: "JekoPaymentButton",
    props: {
        contractId: { type: String, required: true },
        paymentType: { type: String, default: "firstPayment" },
        customerEmail: { type: String, default: "" },
        customerName: { type: String, default: "" },
        preselectedInvoiceIds: { type: Array, default: () => [] },
        label: { type: String, default: "Payer avec Jeko" },
    },
    data() {
        return {
            widget: null,
            isReady: false,
            isLoading: false,
        };
    },
    mounted() {
        this.loadWidget();
    },
    methods: {
        loadWidget() {
            if (window.JekoWidget) {
                this.initWidget();
                return;
            }
            const script = document.createElement("script");
            script.src = "{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}";
            script.onload = this.initWidget;
            document.head.appendChild(script);
        },
        initWidget() {
            this.widget = new JekoWidget({
                backendEndpoint: "{{ url('/api/v1/paiements/jeko/init') }}",
                contractCheckEndpoint: "{{ url('/api/v1/paiements/jeko/contrat/verifier') }}",
                currency: "XOF",
                autoVerifyContract: true,
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                callbacks: {
                    onSuccess: (redirectUrl, data) => {
                        this.isLoading = false;
                        this.$emit("success", { redirectUrl, data });
                        window.open(redirectUrl, "_blank");
                    },
                    onError: (message, data) => {
                        this.isLoading = false;
                        this.$emit("error", { message, data });
                    },
                    onOpen: () => { this.isLoading = true; },
                    onClose: () => { this.isLoading = false; },
                },
            });
            this.isReady = true;
        },
        handlePayment() {
            if (!this.widget || !this.isReady) return;
            const reference = `PAI-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;
            this.widget.open({
                reference,
                paymentType: this.paymentType,
                contractId: this.contractId,
                customerEmail: this.customerEmail,
                customerName: this.customerName,
                preselectedInvoiceIds: this.paymentType === "recoveryPrime" ? this.preselectedInvoiceIds : undefined,
                metadata: { source: "vue_app" },
            });
        },
    },
};
&lt;/script&gt;

&lt;style scoped&gt;
.btn-pay {
    background: #1D603D;
    color: #fff;
    border: none;
    padding: 13px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;
}
.btn-pay:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3);
}
.btn-pay:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
&lt;/style&gt;</code></pre>
                </div>

                <div class="info-box">
                    <strong>🟢 Utilisation :</strong>
                    <div class="separator"><span class="highlight">&lt;JekoPaymentButton contract-id="1093" payment-type="firstPayment" /&gt;</span></div>
                    <div class="separator"><span class="highlight">&lt;JekoPaymentButton contract-id="12452" payment-type="recoveryPrime" :preselected-invoice-ids="['173669', '3132514']" /&gt;</span></div>
                </div>
            </div>

            <!-- ==========================================================
            TAB 4: Angular
            ========================================================== -->
            <div class="tab-content" id="tab-angular">
                <p class="code-description">
                    Service et composant Angular pour une intégration propre et typée.
                </p>
                <div class="code-block">
                    <span class="lang-tag">Angular</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre>
<code>// jeko-widget.service.ts
import { Injectable, Inject, DOCUMENT } from "@angular/core";
import { Subject } from "rxjs";

export interface JekoWidgetConfig {
    backendEndpoint: string;
    contractCheckEndpoint: string;
    currency: string;
    headers?: Record&lt;string, string&gt;;
}

@Injectable({ providedIn: "root" })
export class JekoWidgetService {
    private widget: any = null;
    private isReady = false;
    private successSubject = new Subject&lt;{ redirectUrl: string; data: any }&gt;();
    private errorSubject = new Subject&lt;{ message: string; data?: any }&gt;();

    constructor(@Inject(DOCUMENT) private document: Document) {}

    loadWidget(config: JekoWidgetConfig): Promise&lt;void&gt; {
        return new Promise((resolve) => {
            if (window["JekoWidget"]) {
                this.initWidget(config);
                resolve();
                return;
            }
            const script = this.document.createElement("script");
            script.src = "{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}";
            script.onload = () => { this.initWidget(config); resolve(); };
            this.document.head.appendChild(script);
        });
    }

    private initWidget(config: JekoWidgetConfig): void {
        if (!window["JekoWidget"]) return;
        this.widget = new window["JekoWidget"]({
            backendEndpoint: config.backendEndpoint,
            contractCheckEndpoint: config.contractCheckEndpoint,
            currency: config.currency || "XOF",
            headers: config.headers || {},
            callbacks: {
                onSuccess: (url: string, data: any) => {
                    this.successSubject.next({ redirectUrl: url, data });
                    window.open(url, "_blank");
                },
                onError: (msg: string, data: any) => {
                    this.errorSubject.next({ message: msg, data });
                },
            },
        });
        this.isReady = true;
    }

    openPayment(data: any): void {
        if (!this.widget || !this.isReady) return;
        this.widget.open(data);
    }

    get onSuccess() { return this.successSubject.asObservable(); }
    get onError() { return this.errorSubject.asObservable(); }

    close(): void {
        if (this.widget) this.widget.close();
    }
}

// jeko-payment.component.ts
import { Component, Input, OnInit } from "@angular/core";
import { JekoWidgetService } from "./jeko-widget.service";

@Component({
    selector: "app-jeko-payment",
    template: `
        &lt;button
            (click)="handlePayment()"
            [disabled]="!isReady || isLoading"
            class="btn-pay"
        &gt;
            {{ isLoading ? "Chargement..." : label }}
        &lt;/button&gt;
    `,
    styles: [`
        .btn-pay {
            background: #1D603D;
            color: #fff;
            border: none;
            padding: 13px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
        }
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3);
        }
        .btn-pay:disabled { opacity: 0.5; cursor: not-allowed; }
    `]
})
export class JekoPaymentComponent implements OnInit {
    @Input() contractId!: string;
    @Input() paymentType: string = "firstPayment";
    @Input() customerEmail: string = "";
    @Input() customerName: string = "";
    @Input() preselectedInvoiceIds: string[] = [];
    @Input() label: string = "Payer avec Jeko";

    isReady = false;
    isLoading = false;

    constructor(private widgetService: JekoWidgetService) {}

    ngOnInit(): void {
        this.widgetService.loadWidget({
            backendEndpoint: "{{ url('/api/v1/paiements/jeko/init') }}",
            contractCheckEndpoint: "{{ url('/api/v1/paiements/jeko/contrat/verifier') }}",
            currency: "XOF",
        }).then(() => { this.isReady = true; });
    }

    handlePayment(): void {
        if (!this.isReady) return;
        this.isLoading = true;
        const reference = `PAI-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;
        this.widgetService.openPayment({
            reference,
            paymentType: this.paymentType,
            contractId: this.contractId,
            customerEmail: this.customerEmail,
            customerName: this.customerName,
            preselectedInvoiceIds: this.paymentType === "recoveryPrime" ? this.preselectedInvoiceIds : undefined,
            metadata: { source: "angular_app" },
        });
    }
}</code>
</pre>
                </div>
            </div>

            <!-- ==========================================================
            TAB 5: Next.js
            ========================================================== -->
            <div class="tab-content" id="tab-nextjs">
                <p class="code-description">
                    Composant Next.js avec App Router et "use client" pour le chargement côté client.
                </p>
                <div class="code-block">
                    <span class="lang-tag">Next.js</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>'use client';

import React, { useState, useEffect, useRef } from "react";

interface JekoPaymentButtonProps {
    contractId: string;
    paymentType?: "firstPayment" | "earlyPayment" | "recoveryPrime";
    customerEmail?: string;
    customerName?: string;
    preselectedInvoiceIds?: string[];
    onSuccess?: (redirectUrl: string, data: any) => void;
    onError?: (message: string, data?: any) => void;
    className?: string;
    children?: React.ReactNode;
}

declare global {
    interface Window {
        JekoWidget: any;
    }
}

const loadJekoWidget = (): Promise&lt;any&gt; => {
    return new Promise((resolve) => {
        if (typeof window === "undefined") return resolve(null);
        if (window.JekoWidget) return resolve(window.JekoWidget);
        const script = document.createElement("script");
        script.src = "{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}";
        script.onload = () => resolve(window.JekoWidget);
        script.onerror = () => resolve(null);
        document.head.appendChild(script);
    });
};

export const JekoPaymentButton: React.FC&lt;JekoPaymentButtonProps&gt; = ({
    contractId,
    paymentType = "firstPayment",
    customerEmail = "",
    customerName = "",
    preselectedInvoiceIds = [],
    onSuccess,
    onError,
    className = "",
    children,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [isReady, setIsReady] = useState(false);
    const widgetRef = useRef&lt;any&gt;(null);

    useEffect(() => {
        const init = async () => {
            try {
                const JekoWidget = await loadJekoWidget();
                if (!JekoWidget) {
                    console.error("JekoWidget non chargé");
                    return;
                }
                widgetRef.current = new JekoWidget({
                    backendEndpoint: process.env.NEXT_PUBLIC_JEKO_API_URL || "{{ url('/api/v1/paiements/jeko/init') }}",
                    contractCheckEndpoint: process.env.NEXT_PUBLIC_JEKO_CONTRACT_CHECK_URL || "{{ url('/api/v1/paiements/jeko/contrat/verifier') }}",
                    currency: "XOF",
                    autoVerifyContract: true,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
                    },
                    callbacks: {
                        onSuccess: (url: string, data: any) => {
                            setIsLoading(false);
                            onSuccess?.(url, data);
                            window.open(url, "_blank");
                        },
                        onError: (msg: string, data: any) => {
                            setIsLoading(false);
                            onError?.(msg, data);
                        },
                        onOpen: () => setIsLoading(true),
                        onClose: () => setIsLoading(false),
                    },
                });
                setIsReady(true);
            } catch (error) {
                onError?.(error instanceof Error ? error.message : "Erreur inconnue");
            }
        };
        init();
    }, []);

    const handlePayment = () => {
        if (!widgetRef.current || !isReady) return;
        const reference = `PAI-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;
        widgetRef.current.open({
            reference,
            paymentType,
            contractId,
            customerEmail,
            customerName,
            preselectedInvoiceIds: paymentType === "recoveryPrime" ? preselectedInvoiceIds : undefined,
            metadata: { source: "nextjs_app" },
        });
    };

    return (
        <button
            onClick={handlePayment}
            disabled={!isReady || isLoading}
            className={`btn-pay ${className}`}
            style={{
                background: "#1D603D",
                color: "#fff",
                border: "none",
                padding: "13px 24px",
                borderRadius: "10px",
                fontWeight: 600,
                fontSize: "15px",
                cursor: "pointer",
                width: "100%",
                opacity: (!isReady || isLoading) ? 0.5 : 1,
            }}
        >
            {isLoading ? "Chargement..." : (children || "Payer avec Jeko")}
        </button>
    );
};

export default JekoPaymentButton;</code></pre>
                </div>

                <div class="info-box">
                    <strong>▲ Configuration Next.js :</strong>
                    <div class="separator">
                        Ajoutez ces variables dans votre <code>.env.local</code> :
                    </div>
                    <div class="separator">
                        <code>NEXT_PUBLIC_JEKO_API_URL={{ url('/api/v1/paiements/jeko/init') }}</code>
                    </div>
                    <div class="separator">
                        <code>NEXT_PUBLIC_JEKO_CONTRACT_CHECK_URL={{ url('/api/v1/paiements/jeko/contrat/verifier') }}</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
        NOTE DE BAS
        ============================================================ -->
        <div class="info-box" style="margin-top:24px;">
            <strong>🔒 Sécurité :</strong>
            <div class="separator">• Le widget n'appelle <strong>JAMAIS</strong> l'API Jeko directement</div>
            <div class="separator">• Toutes les clés secrètes restent côté serveur</div>
            <div class="separator">• Le montant est <strong>recalculé et vérifié côté serveur</strong> pour éviter toute fraude</div>
            <div class="separator">• API accessible via <code>/api/v1/paiements/jeko/...</code></div>
        </div>
    </div>
    @endverbatim

    <!-- ============================================================
    CHARGEMENT DU WIDGET
    ============================================================ -->
    <script src="{{ url('/api/v1/paiements/jeko/jeko-payment-widget.js') }}"></script>

    <!-- ============================================================
    CODE PRINCIPAL
    ============================================================ -->
    <script src="{{ asset('assets/js/demo-jeko-widget.js') }}"></script>

</body>

</html>
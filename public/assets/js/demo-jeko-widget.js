(function () {
    "use strict";

    // ============================================================
    // 1) GESTION DES ONGLETS
    // ============================================================
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");

    tabButtons.forEach(function (btn) {
        btn.addEventListener("click", function () {
            // Désactiver tous les onglets
            tabButtons.forEach(function (b) {
                b.classList.remove("active");
            });
            tabContents.forEach(function (c) {
                c.classList.remove("active");
            });

            // Activer l'onglet cliqué
            btn.classList.add("active");
            const tabId = btn.dataset.tab;
            const content = document.getElementById("tab-" + tabId);
            if (content) content.classList.add("active");
        });
    });

    // ============================================================
    // 2) COPIE DE CODE
    // ============================================================
    window.copyCode = function (btn) {
        const pre = btn.parentElement.querySelector("pre");
        if (!pre) return;
        const code = pre.textContent;
        navigator.clipboard
            .writeText(code)
            .then(function () {
                btn.textContent = "Copié ✓";
                setTimeout(function () {
                    btn.textContent = "Copier";
                }, 2000);
            })
            .catch(function () {
                // Fallback
                const textArea = document.createElement("textarea");
                textArea.value = code;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand("copy");
                document.body.removeChild(textArea);
                btn.textContent = "Copié ✓";
                setTimeout(function () {
                    btn.textContent = "Copier";
                }, 2000);
            });
    };

    // ============================================================
    // 3) VÉRIFICATION DU WIDGET
    // ============================================================
    if (typeof JekoWidget === "undefined") {
        console.error("❌ JekoWidget non chargé.");
        document.querySelectorAll(".btn-pay-sm").forEach(function (btn) {
            btn.disabled = true;
            btn.textContent = "⚠️ Indisponible";
        });
        return;
    }

    console.log("✅ JekoWidget chargé avec succès");

    // ============================================================
    // 4) INITIALISATION DU WIDGET
    // ============================================================
    var widget = new JekoWidget({
        backendEndpoint: "/api/v1/paiements/jeko/init",
        contractCheckEndpoint: "/api/v1/paiements/jeko/contrat/verifier",
        currency: "XOF",
        timeout: 30000,
        autoVerifyContract: true,
        headers: {
            "X-CSRF-TOKEN":
                document.querySelector('meta[name="csrf-token"]')?.content ||
                "",
            Accept: "application/json",
        },
        callbacks: {
            onSuccess: function (redirectUrl, data) {
                console.log("✅ Paiement initialisé", { redirectUrl, data });
            },
            onError: function (message, data) {
                console.error("❌ Erreur de paiement", { message, data });
                alert("Erreur: " + message);
            },
            onOpen: function (data) {
                console.log("🔄 Widget ouvert", data);
            },
            onClose: function () {
                console.log("❌ Widget fermé");
            },
        },
    });

    // ============================================================
    // 5) FONCTIONS UTILITAIRES
    // ============================================================

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

    function getPreselectedInvoices() {
        var ids = [];
        document
            .querySelectorAll('input[name="invoice_ids"]:checked')
            .forEach(function (el) {
                ids.push(el.value);
            });
        return ids;
    }

    // ============================================================
    // 6) BOUTONS DE PAIEMENT
    // ============================================================

    // ---- 6.1) PREMIER PAIEMENT ----
    document
        .getElementById("btnFirstPayment")
        .addEventListener("click", function () {
            var contractId =
                document.getElementById("contractIdFirstPayment")?.value ||
                "1093";
            widget.open({
                reference: generateReference(),
                paymentType: "firstPayment",
                contractId: contractId,
                description: "Souscription — première prime",
                customerEmail: "client@example.com",
                customerName: "Jean Dupont",
                metadata: {
                    source: "web_demo",
                    scenario: "firstPayment",
                    timestamp: new Date().toISOString(),
                },
            });
        });

    // ---- 6.2) PAIEMENT ANTICIPÉ ----
    document
        .getElementById("btnEarlyPayment")
        .addEventListener("click", function () {
            var contractId = document
                .getElementById("contractIdEarly")
                .value.trim();
            if (!contractId) {
                alert("⚠️ Veuillez saisir un identifiant de contrat.");
                return;
            }
            widget.open({
                reference: generateReference(),
                paymentType: "earlyPayment",
                contractId: contractId,
                description: "Paiement anticipé de primes",
                customerEmail: "client@example.com",
                customerName: "Jean Dupont",
                metadata: {
                    source: "web_demo",
                    scenario: "earlyPayment",
                    contractId: contractId,
                },
            });
        });

    // ---- 6.3) RÉGULARISATION ----
    document
        .getElementById("btnRecoveryPrime")
        .addEventListener("click", function () {
            var contractId = document
                .getElementById("contractIdRecovery")
                .value.trim();
            var preselectedIds = getPreselectedInvoices();

            if (!contractId) {
                alert("⚠️ Veuillez saisir un identifiant de contrat.");
                return;
            }

            widget.open({
                reference: generateReference(),
                paymentType: "recoveryPrime",
                contractId: contractId,
                preselectedInvoiceIds:
                    preselectedIds.length > 0 ? preselectedIds : undefined,
                description: "Régularisation de primes impayées",
                customerEmail: "client@example.com",
                customerName: "Jean Dupont",
                metadata: {
                    source: "web_demo",
                    scenario: "recoveryPrime",
                    contractId: contractId,
                    preselectedCount: preselectedIds.length,
                },
            });
        });

    console.log("✅ Widget Jeko initialisé avec succès");
})();



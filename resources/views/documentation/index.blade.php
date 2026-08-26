<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YNOV API Documentation - YAKO AFRICA</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Prism.js -->
    <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           PALETTE YNOV — Vert institutionnel + Orange accent
           ============================================================ */
        :root {
            --ynov-primary: #096835;
            --ynov-primary-dark: #06471f;
            --ynov-primary-darker: #043216;
            --ynov-primary-light: #0e8a47;
            --ynov-primary-lighter: #12a856;
            --ynov-primary-50: #e8f5ee;
            --ynov-primary-100: #cdead9;
            --ynov-primary-200: #9ad6b5;

            --ynov-accent: #F7A400;
            --ynov-accent-dark: #d68b00;
            --ynov-accent-darker: #b57400;
            --ynov-accent-50: #fff6e5;
            --ynov-accent-100: #ffe9b8;

            --ynov-secondary: #5b6b63;
            --ynov-success: #0e8a47;
            --ynov-danger: #c9372c;
            --ynov-warning: #F7A400;
            --ynov-info: #0f7a8c;
            --ynov-dark: #12241a;
            --ynov-light: #f4f8f5;
            --ynov-border: #dce6e0;

            --sidebar-width: 290px;
            --header-height: 64px;
            --code-bg: #0c1f15;
            --code-color: #d7ecdf;
            --transition-speed: 0.25s;
            --font-display: 'Manrope', 'Segoe UI', sans-serif;
            --font-mono: 'JetBrains Mono', 'Courier New', monospace;
        }

        * { box-sizing: border-box; }

        body {
            font-family: var(--font-display);
            background: var(--ynov-light);
            color: var(--ynov-dark);
            padding-top: var(--header-height);
            overflow-x: hidden;
        }

        a { text-decoration: none; color: var(--ynov-primary); }
        a:hover { color: var(--ynov-primary-dark); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #eef3ef; }
        ::-webkit-scrollbar-thumb { background: #b7c9bd; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ab0a1; }

        /* ============ HEADER ============ */
        .ynov-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
            height: var(--header-height);
            background: linear-gradient(120deg, var(--ynov-primary-darker) 0%, var(--ynov-primary) 55%, var(--ynov-primary-light) 100%);
            box-shadow: 0 2px 18px rgba(9, 104, 53, 0.35);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid var(--ynov-accent);
        }

        .ynov-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: 0.3px;
        }
        .ynov-header .brand i { color: var(--ynov-accent); }

        .ynov-header .brand .badge-version {
            background: var(--ynov-accent);
            color: var(--ynov-primary-darker);
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 700;
        }

        .ynov-header .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .ynov-header .env-selector {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .ynov-header .env-selector:hover { background: rgba(255,255,255,0.25); }
        .ynov-header .env-selector option { color: var(--ynov-dark); background: white; }

        .ynov-header .auth-status {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 0.85rem;
        }
        .ynov-header .auth-status .status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .ynov-header .auth-status .status-dot.online { background: var(--ynov-accent); box-shadow: 0 0 0 3px rgba(247,164,0,0.25); }
        .ynov-header .auth-status .status-dot.offline { background: #e57373; }

        .btn-header {
            background: var(--ynov-accent);
            border: 1px solid var(--ynov-accent);
            color: var(--ynov-primary-darker);
            font-weight: 700;
            border-radius: 8px;
            padding: 5px 15px;
            font-size: 0.85rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-header:hover { background: var(--ynov-accent-dark); color: var(--ynov-primary-darker); }
        .btn-header i { margin-right: 6px; }

        /* ============ SIDEBAR ============ */
        .ynov-sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid var(--ynov-border);
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem 0;
            z-index: 1040;
            transition: transform var(--transition-speed) ease;
        }

        .ynov-sidebar .sidebar-search {
            padding: 0 1rem 1rem;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }

        .ynov-sidebar .sidebar-search input {
            border-radius: 8px;
            border: 1px solid var(--ynov-border);
            padding: 8px 12px 8px 35px;
            width: 100%;
            font-size: 0.85rem;
            background: var(--ynov-light);
            transition: all 0.2s;
        }
        .ynov-sidebar .sidebar-search input:focus {
            outline: none;
            border-color: var(--ynov-primary);
            box-shadow: 0 0 0 3px rgba(9,104,53,0.12);
        }
        .ynov-sidebar .sidebar-search .search-icon {
            position: absolute;
            left: 22px; top: 50%;
            transform: translateY(-50%);
            color: #a3b3aa;
            font-size: 0.85rem;
        }

        .ynov-sidebar .nav-module {
            padding: 0.5rem 1rem;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--ynov-secondary);
            margin-top: 0.6rem;
        }

        .ynov-sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 1rem;
            font-size: 0.87rem;
            color: #384a3f;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        .ynov-sidebar .nav-item:hover {
            background: var(--ynov-primary-50);
            color: var(--ynov-primary);
        }
        .ynov-sidebar .nav-item.active {
            background: var(--ynov-primary-50);
            color: var(--ynov-primary);
            border-left-color: var(--ynov-accent);
            font-weight: 600;
        }
        .ynov-sidebar .nav-item .method-badge {
            font-size: 0.62rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            min-width: 40px;
            text-align: center;
            flex-shrink: 0;
        }
        .ynov-sidebar .nav-item .method-badge.get { background: var(--ynov-primary-50); color: var(--ynov-primary); }
        .ynov-sidebar .nav-item .method-badge.post { background: var(--ynov-accent-50); color: var(--ynov-accent-darker); }
        .ynov-sidebar .nav-item .method-badge.put { background: #fff3cd; color: #8a6300; }
        .ynov-sidebar .nav-item .method-badge.patch { background: #ffe3d1; color: #9a4b12; }
        .ynov-sidebar .nav-item .method-badge.delete { background: #fbdede; color: #a12b23; }

        .ynov-sidebar .nav-item .nav-icon {
            width: 20px;
            text-align: center;
            color: var(--ynov-secondary);
            font-size: 0.8rem;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: var(--header-height); left: 0; right: 0; bottom: 0;
            background: rgba(6,20,12,0.4);
            z-index: 1035;
        }
        .sidebar-overlay.show { display: block; }

        /* ============ MAIN ============ */
        .ynov-main {
            margin-left: var(--sidebar-width);
            padding: 2rem 2rem 4rem;
            min-height: 100vh;
            transition: margin var(--transition-speed) ease;
        }

        .ynov-main .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--ynov-border);
        }
        .ynov-main .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--ynov-dark);
        }
        .ynov-main .page-header .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 0.9rem; }
        .ynov-main .page-header .breadcrumb-item a { color: var(--ynov-secondary); }
        .ynov-main .page-header .breadcrumb-item.active { color: var(--ynov-primary); }

        /* ============ ENDPOINT CARD ============ */
        .endpoint-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--ynov-border);
            box-shadow: 0 2px 10px rgba(9,104,53,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .endpoint-card:hover { box-shadow: 0 6px 24px rgba(9,104,53,0.1); }

        .endpoint-card .endpoint-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--ynov-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: var(--ynov-primary-50);
        }
        .endpoint-card .endpoint-header .endpoint-method {
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .endpoint-card .endpoint-header .endpoint-method.get { background: #d3ecdd; color: var(--ynov-primary-dark); }
        .endpoint-card .endpoint-header .endpoint-method.post { background: var(--ynov-accent-100); color: var(--ynov-accent-darker); }
        .endpoint-card .endpoint-header .endpoint-method.put { background: #fff3cd; color: #8a6300; }
        .endpoint-card .endpoint-header .endpoint-method.patch { background: #ffe3d1; color: #9a4b12; }
        .endpoint-card .endpoint-header .endpoint-method.delete { background: #fbdede; color: #a12b23; }

        .endpoint-card .endpoint-header .endpoint-path {
            font-family: var(--font-mono);
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--ynov-dark);
            flex: 1;
        }
        .endpoint-card .endpoint-header .endpoint-path .base-url { color: var(--ynov-secondary); font-weight: 400; font-size: 0.8rem; }

        .endpoint-card .endpoint-header .endpoint-badges { display: flex; gap: 6px; flex-wrap: wrap; }
        .endpoint-card .endpoint-header .endpoint-badges .badge { font-weight: 600; font-size: 0.7rem; padding: 4px 10px; }

        .endpoint-card .endpoint-body { padding: 1.5rem; }

        .endpoint-card .endpoint-body .section-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--ynov-dark);
            margin: 1.5rem 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .endpoint-card .endpoint-body .section-title:first-child { margin-top: 0; }
        .endpoint-card .endpoint-body .section-title i { color: var(--ynov-primary); font-size: 0.9rem; }

        .endpoint-card .endpoint-body .description { color: #445a4c; line-height: 1.7; }
        .endpoint-card .endpoint-body .description code {
            background: var(--ynov-light);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--ynov-accent-darker);
        }

        /* ============ TABLES ============ */
        .doc-table { font-size: 0.9rem; border-radius: 8px; overflow: hidden; }
        .doc-table thead {
            background: var(--ynov-light);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--ynov-secondary);
        }
        .doc-table thead th { border-bottom: 2px solid var(--ynov-border); padding: 10px 14px; }
        .doc-table tbody td { padding: 10px 14px; border-bottom: 1px solid #eef3ef; vertical-align: middle; }
        .doc-table tbody tr:last-child td { border-bottom: none; }
        .doc-table .required-badge {
            background: var(--ynov-accent-100);
            color: var(--ynov-accent-darker);
            font-size: 0.65rem; font-weight: 800; padding: 1px 8px; border-radius: 4px;
        }
        .doc-table .optional-badge {
            background: #e9ede9; color: #56655c;
            font-size: 0.65rem; font-weight: 700; padding: 1px 8px; border-radius: 4px;
        }

        /* ============ CODE BLOCKS ============ */
        .code-block {
            background: var(--code-bg);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            position: relative;
            overflow-x: auto;
            border: 1px solid #123a24;
        }
        .code-block pre { margin: 0; color: var(--code-color); font-family: var(--font-mono); font-size: 0.85rem; line-height: 1.6; }
        .code-block .copy-btn {
            position: absolute; top: 8px; right: 8px;
            background: rgba(247,164,0,0.15);
            border: 1px solid rgba(247,164,0,0.3);
            color: var(--ynov-accent);
            padding: 4px 10px; border-radius: 4px; font-size: 0.75rem;
            cursor: pointer; transition: all 0.2s;
        }
        .code-block .copy-btn:hover { background: rgba(247,164,0,0.3); color: white; }

        /* ============ TRY IT ============ */
        .try-it-section { margin-top: 1.5rem; border-top: 1px solid var(--ynov-border); padding-top: 1.5rem; }
        .try-it-section .try-it-toggle {
            font-weight: 700;
            color: var(--ynov-primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 18px;
            border-radius: 8px;
            border: 1.5px solid var(--ynov-primary);
            background: white;
            transition: all 0.2s;
        }
        .try-it-section .try-it-toggle:hover { background: var(--ynov-primary); color: white; }

        .try-it-panel {
            display: none;
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--ynov-light);
            border-radius: 8px;
            border: 1px solid var(--ynov-border);
        }
        .try-it-panel.show { display: block; }

        .try-it-panel .form-group { margin-bottom: 1rem; }
        .try-it-panel .form-group label { font-weight: 600; font-size: 0.85rem; color: #445a4c; }
        .try-it-panel .form-group .form-control,
        .try-it-panel .form-group .form-select { font-size: 0.9rem; border-radius: 6px; border: 1px solid #ced9d0; }
        .try-it-panel .form-group .form-control:focus,
        .try-it-panel .form-group .form-select:focus { border-color: var(--ynov-primary); box-shadow: 0 0 0 3px rgba(9,104,53,0.12); }
        .try-it-panel .json-editor { font-family: var(--font-mono); font-size: 0.85rem; min-height: 120px; resize: vertical; }

        .try-it-panel .send-btn {
            padding: 8px 30px; font-weight: 700; border-radius: 8px;
            background: var(--ynov-accent); border-color: var(--ynov-accent); color: var(--ynov-primary-darker);
        }
        .try-it-panel .send-btn:hover { background: var(--ynov-accent-dark); border-color: var(--ynov-accent-dark); color: white; }

        /* ============ RESPONSE VIEWER ============ */
        .response-viewer { margin-top: 1rem; display: none; }
        .response-viewer.show { display: block; }

        .response-viewer .response-meta {
            display: flex; flex-wrap: wrap; gap: 15px;
            padding: 10px 14px;
            background: white;
            border-radius: 8px 8px 0 0;
            border: 1px solid var(--ynov-border);
            border-bottom: none;
        }
        .response-viewer .response-meta .meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; }
        .response-viewer .response-meta .meta-item .label { color: var(--ynov-secondary); }
        .response-viewer .response-meta .status-badge { font-weight: 800; padding: 2px 12px; border-radius: 4px; font-size: 0.85rem; }
        .response-viewer .response-meta .status-badge.success { background: #d3ecdd; color: var(--ynov-primary-dark); }
        .response-viewer .response-meta .status-badge.error { background: #fbdede; color: #a12b23; }
        .response-viewer .response-meta .status-badge.warning { background: var(--ynov-accent-100); color: var(--ynov-accent-darker); }
        .response-viewer .response-meta .status-badge.info { background: #d9edf1; color: #0f7a8c; }

        .response-viewer .response-body {
            background: var(--code-bg);
            border-radius: 0 0 8px 8px;
            padding: 1.25rem;
            overflow-x: auto;
            border: 1px solid #123a24;
            border-top: none;
        }
        .response-viewer .response-body pre { margin: 0; color: var(--code-color); font-family: var(--font-mono); font-size: 0.85rem; line-height: 1.6; }

        .spinner-overlay { display: none; align-items: center; justify-content: center; padding: 2rem; }
        .spinner-overlay.show { display: flex; }
        .spinner-overlay .spinner-border { color: var(--ynov-primary); }

        .toast-container { z-index: 1060; }

        /* ============ HOME HERO ============ */
        .home-hero {
            background: linear-gradient(120deg, var(--ynov-primary-darker) 0%, var(--ynov-primary) 60%, var(--ynov-primary-light) 100%);
            border-radius: 16px;
            padding: 3rem 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid var(--ynov-accent);
        }
        .home-hero::after {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(247,164,0,0.35) 0%, rgba(247,164,0,0) 70%);
        }
        .home-hero h1 { font-size: 2.5rem; font-weight: 800; position: relative; z-index: 1; }
        .home-hero p { font-size: 1.1rem; opacity: 0.92; max-width: 700px; position: relative; z-index: 1; }
        .home-hero .quick-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 1.5rem; position: relative; z-index: 1; }
        .home-hero .quick-links .btn { border-radius: 8px; padding: 8px 20px; font-weight: 600; }
        .home-hero .quick-links .btn-light { background: var(--ynov-accent); border-color: var(--ynov-accent); color: var(--ynov-primary-darker); }
        .home-hero .quick-links .btn-light:hover { background: var(--ynov-accent-dark); }
        .home-hero .quick-links .btn-outline-light { border-color: rgba(255,255,255,0.4); }
        .home-hero .quick-links .btn-outline-light:hover { background: rgba(255,255,255,0.2); }

        .home-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .home-stats .stat-card {
            background: white; border-radius: 12px; padding: 1.25rem; text-align: center;
            border: 1px solid var(--ynov-border);
            border-top: 3px solid var(--ynov-accent);
        }
        .home-stats .stat-card .stat-number { font-size: 2rem; font-weight: 800; color: var(--ynov-primary); }
        .home-stats .stat-card .stat-label { font-size: 0.85rem; color: var(--ynov-secondary); }

        .permission-badge { font-size: 0.7rem; background: #e9ede9; color: #56655c; padding: 2px 10px; border-radius: 12px; font-weight: 600; }
        .permission-badge.required { background: var(--ynov-accent-100); color: var(--ynov-accent-darker); }

        /* Bootstrap overrides */
        .btn-primary { background-color: var(--ynov-primary); border-color: var(--ynov-primary); }
        .btn-primary:hover, .btn-primary:focus { background-color: var(--ynov-primary-dark); border-color: var(--ynov-primary-dark); }
        .btn-outline-primary { color: var(--ynov-primary); border-color: var(--ynov-primary); }
        .btn-outline-primary:hover { background-color: var(--ynov-primary); border-color: var(--ynov-primary); }
        .text-primary { color: var(--ynov-primary) !important; }
        .bg-primary { background-color: var(--ynov-primary) !important; }
        .bg-warning { background-color: var(--ynov-accent) !important; color: var(--ynov-primary-darker) !important; }
        .badge.bg-warning { color: var(--ynov-primary-darker) !important; }
        .badge.bg-success { background-color: var(--ynov-primary) !important; }
        .badge.bg-danger { background-color: #c9372c !important; }
        .badge.bg-info { background-color: #0f7a8c !important; }
        .form-check-input:checked { background-color: var(--ynov-primary); border-color: var(--ynov-primary); }
        .accordion-button:not(.collapsed) { color: var(--ynov-primary); }
        .modal-header { border-bottom-color: var(--ynov-border); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease; }

        .text-muted-small { color: var(--ynov-secondary); font-size: 0.8rem; }
        .gap-1 { gap: 0.25rem; } .gap-2 { gap: 0.5rem; } .gap-3 { gap: 1rem; }

        .info-box {
            border: 1.5px solid var(--ynov-primary-200);
            background: var(--ynov-primary-50);
            border-radius: 8px;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            color: var(--ynov-primary-dark);
            margin-bottom: 1rem;
        }
        .warning-box {
            border: 1.5px solid var(--ynov-accent);
            background: var(--ynov-accent-50);
            border-radius: 8px;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            color: var(--ynov-accent-darker);
            margin-bottom: 1rem;
        }
        .danger-box {
            border: 1.5px solid #f1c3bc;
            background: #fdf1ef;
            border-radius: 8px;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            color: #8a2d24;
            margin-bottom: 1rem;
        }

        @media (max-width: 991.98px) {
            .ynov-sidebar { transform: translateX(-100%); }
            .ynov-sidebar.show { transform: translateX(0); }
            .ynov-main { margin-left: 0; padding: 1.5rem 1.5rem 3rem; }
            .sidebar-overlay.show { display: block; }
            .ynov-header .brand { font-size: 1rem; }
            .ynov-header .brand .badge-version { font-size: 0.55rem; }
        }

        @media (max-width: 575.98px) {
            .ynov-header { padding: 0 1rem; }
            .ynov-header .header-actions .env-selector { font-size: 0.75rem; padding: 4px 8px; max-width: 120px; }
            .ynov-header .header-actions .auth-status { font-size: 0.75rem; }
            .ynov-main { padding: 1rem 1rem 2rem; }
            .ynov-main .page-header h1 { font-size: 1.5rem; }
            .endpoint-card .endpoint-header { flex-direction: column; align-items: flex-start; }
            .endpoint-card .endpoint-header .endpoint-path { font-size: 0.85rem; word-break: break-all; }
        }
    </style>
</head>
<body>

<!-- ============ HEADER ============ -->
<header class="ynov-header">
    <div class="brand">
        <i class="fas fa-shield-halved"></i>
        <span>YNOV API</span>
        <span class="badge-version">v1.0</span>
        <button class="btn btn-sm btn-header d-lg-none ms-2" id="sidebarToggle" style="padding: 2px 10px;">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="header-actions">
        <select class="env-selector form-select-sm" id="envSelector">
            <option value="local">🌐 Local</option>
            <option value="dev">🖥️ Development</option>
            <option value="test">🧪 Test</option>
            <option value="staging">📦 Staging</option>
            <option value="production" disabled>🚀 Production (protégé)</option>
        </select>

        <div class="auth-status" id="authStatus">
            <span class="status-dot offline" id="statusDot"></span>
            <span id="authStatusText">Non authentifié</span>
        </div>

        <button class="btn-header" id="authBtn" data-bs-toggle="modal" data-bs-target="#authModal">
            <i class="fas fa-key"></i> <span id="authBtnText">Se connecter</span>
        </button>
    </div>
</header>

<!-- ============ OVERLAY MOBILE ============ -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============ SIDEBAR ============ -->
<nav class="ynov-sidebar" id="sidebar">
    <div class="sidebar-search position-relative">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Rechercher un endpoint..." class="form-control">
    </div>
    <div id="sidebarNav"></div>
</nav>

<!-- ============ MAIN CONTENT ============ -->
<main class="ynov-main" id="mainContent"></main>

<!-- ============ MODAL AUTHENTIFICATION ============ -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Authentification pour les tests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="authForm">
                    <div id="authFormStatus" class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Connectez-vous pour tester les endpoints protégés.
                    </div>

                    <div class="mb-3">
                        <label for="authLogin" class="form-label">Email / Login</label>
                        <input type="text" class="form-control" id="authLogin" placeholder="ex: admin@ynov.ci" required>
                    </div>
                    <div class="mb-3">
                        <label for="authPassword" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="authPassword" placeholder="••••••••" required>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary" id="authSubmitBtn">
                            <i class="fas fa-sign-in-alt me-1"></i> Se connecter
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="authLogoutBtn" style="display:none;">
                            <i class="fas fa-sign-out-alt me-1"></i> Se déconnecter
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>

                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Le token est stocké temporairement en mémoire pour les tests (jamais persisté, jamais loggé).
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============ TOAST ============ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">Message</div>
    </div>
</div>

<!-- ============ SCRIPTS ============ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-json.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>

<script>


/* ================================================================
   YNOV API DOCUMENTATION - JAVASCRIPT PRINCIPAL
   Version organisée par modules pour une meilleure maintenabilité
   ================================================================ */

console.warn = function() {
    const args = Array.from(arguments);
    if (args.some(arg => typeof arg === 'string' && arg.includes('Cookie'))) {
        return;
    }
    console.warn.apply(console, args);
};

    // ================================================================
    // 1. CONFIGURATION DES ENVIRONNEMENTS
    // ================================================================
    const API_DATA = {
        environments: {
            local: { url: 'http://localhost:8000/api/v1', label: 'Local' },
            dev: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Development' },
            test: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Test' },
            staging: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Staging' },
            production: { url: 'https://api.ynov.ci/api/v1', label: 'Production', protected: true }
        },

        // ================================================================
        // 2. MODULES DE LA SIDEBAR
        // ================================================================
        modules: {
            home: { label: 'Accueil', icon: 'fa-house' },
            auth: { label: 'Authentification', icon: 'fa-right-to-bracket' },
            password: { label: 'Mots de Passe', icon: 'fa-key' },
            '2fa': { label: 'Double Authentification', icon: 'fa-shield-halved' },
            security: { label: 'Questions de Sécurité', icon: 'fa-question-circle' },
            profile: { label: 'Profil & Sessions', icon: 'fa-user' },
            users: { label: 'Gestion des Utilisateurs', icon: 'fa-users' },
            freeze: { label: 'Gel / Dégel', icon: 'fa-snowflake' },
            roles: { label: 'Rôles', icon: 'fa-user-shield' },
            permissions: { label: 'Permissions', icon: 'fa-key' },
            permGroups: { label: 'Groupes de Permissions', icon: 'fa-layer-group' },
            ip: { label: 'Restrictions IP', icon: 'fa-network-wired' },
            audit: { label: 'Logs & Audit', icon: 'fa-clipboard-list' },
            partners: { label: 'Partenaires', icon: 'fa-handshake' },
            reseaux: { label: 'Réseaux', icon: 'fa-network-wired' },
            agences: { label: 'Agences', icon: 'fa-building' },
            contrats: { label: 'Contrats', icon: 'fa-file-contract' },
            faq: { label: 'FAQ', icon: 'fa-circle-question' },
            espaces_client: { label: 'Espace Client', icon: 'fa-user-tie' },
            errors: { label: 'Codes HTTP & Erreurs', icon: 'fa-bug' }
        },

        // ================================================================
        // 3. ENDPOINTS PAR MODULE
        // ================================================================
        endpoints: [
            // ============================================================
            // 3.1 ACCUEIL
            // ============================================================
            {
                id: 'home',
                module: 'home',
                name: 'Présentation de l\'API',
                description: 'Bienvenue sur la documentation interactive de l\'API YNOV.',
                isHome: true
            },

            // ============================================================
            // 3.2 AUTHENTIFICATION — PUBLIQUES
            // ============================================================
            {
                id: 'auth-login',
                module: 'auth',
                name: 'Connexion',
                description: 'Authentifie un utilisateur par email OU login. Gère IP restriction, gel de compte (3/4/5 tentatives échouées), blocage automatique (6e tentative), 2FA, changement de mot de passe. Message d\'erreur unique pour éviter l\'énumération des utilisateurs.',
                method: 'POST',
                path: '/auth/login',
                isProtected: false,
                rateLimit: 'throttle:login',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, max: 100, description: 'Email ou login de l\'utilisateur' },
                        password: { type: 'string', required: true, min: 8, description: 'Mot de passe' },
                        device_name: { type: 'string', required: false, max: 255, description: 'Nom de l\'appareil (défaut: "Appareil inconnu")' }
                    }
                },
                exampleRequest: { login: 'admin@ynov.ci', password: 'MonPassword123!', device_name: 'Documentation' },
                responses: [
                    { status: 200, description: 'Connexion réussie — token retourné dans le body ET le header Authorization', example: { success: true, data: { user: {}, access_token: '...', expires_at: '...', requires_2fa: false, must_change_password: false, trusted_device: true } } },
                    { status: 200, description: '2FA requise (code 2FA_REQUIRED)', example: { success: true, code: '2FA_REQUIRED', message: 'Vérification 2FA requise.', data: { two_factor_token: '...', user: {} } } },
                    { status: 200, description: 'Changement de mot de passe requis (code PASSWORD_CHANGE_REQUIRED)', example: { success: true, code: 'PASSWORD_CHANGE_REQUIRED', message: 'Vous devez changer votre mot de passe.', data: { change_password_token: '...', user: {} } } },
                    { status: 401, description: 'Identifiants incorrects', example: { success: false, code: 'AUTH_ERROR', message: 'Identifiants incorrects.' } },
                    { status: 403, description: 'IP bloquée, compte bloqué/inactif/suspendu', example: { success: false, code: 'AUTH_ERROR', message: 'Accès refusé depuis cette adresse IP.' } },
                    { status: 423, description: 'Compte temporairement gelé', example: { success: false, message: 'Compte temporairement gelé. Réessayez dans 3 min 0 s.', freeze_level: 2, remaining_seconds: 180 } },
                    { status: 500, description: 'Erreur interne', example: { success: false, code: 'SERVER_ERROR', message: 'Une erreur interne est survenue. Veuillez réessayer.' } }
                ]
            },

            {
                id: 'auth-get-register-data',
                module: 'auth',
                name: 'Vérifier un contrat avant inscription',
                description: 'Permet de vérifier les informations d\'un contrat avant l\'inscription d\'un client. Vérifie que le contrat existe, que la date de naissance correspond, et que le contrat n\'est pas arrêté. Retourne les informations complètes du contrat.',
                method: 'POST',
                path: '/auth/get-register-data',
                isProtected: false,
                rateLimit: 'throttle:6,1 (6 tentatives / minute)',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        idcontrat: { type: 'string', required: true, description: 'Identifiant du contrat (IdProposition)' },
                        datenaissance: { type: 'date', required: true, format: 'Y-m-d', description: 'Date de naissance du titulaire' }
                    }
                },
                exampleRequest: { idcontrat: 'PROP2024001', datenaissance: '1990-05-15' },
                responses: [
                    { status: 200, description: 'Contrat trouvé et valide', example: { success: true, message: 'Contrat trouvé.', data: {} } },
                    { status: 422, description: 'Contrat arrêté', example: { success: false, code: 'CONTRACT_FROZEN', message: 'Ce contrat est arreté.' } },
                    { status: 422, description: 'Date de naissance incorrecte', example: { success: false, code: 'DATE_OF_BIRTH_MISMATCH', message: 'La date de naissance saisie ne correspond pas à celle enregistrée dans le contrat.' } }
                ]
            },

            {
                id: 'auth-register-client',
                module: 'auth',
                name: 'Inscription client avec contrat',
                description: 'Permet à un client de s\'inscrire après avoir vérifié son contrat. Le mot de passe est généré automatiquement (12 caractères aléatoires) et envoyé par email ou SMS. Crée l\'utilisateur (rôle "client"), ses détails et associe les contrats.',
                method: 'POST',
                path: '/auth/register',
                isProtected: false,
                rateLimit: 'throttle:6,1 (6 tentatives / minute)',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        prenoms: { type: 'string', required: true, max: 255, description: 'Prénoms du client' },
                        nom: { type: 'string', required: true, max: 55, description: 'Nom du client' },
                        date_naissance: { type: 'date', required: false, format: 'Y-m-d', description: 'Date de naissance' },
                        lieu_naissance: { type: 'string', required: false, max: 55, description: 'Lieu de naissance' },
                        genre: { type: 'string', required: false, enum: ['M', 'F'], description: 'Genre' },
                        civilite: { type: 'string', required: false, max: 20, description: 'Civilité' },
                        nationalite: { type: 'string', required: false, max: 55, description: 'Nationalité' },
                        email: { type: 'email', required: true, max: 100, description: 'Email' },
                        login: { type: 'string', required: true, max: 100, description: 'Identifiant de connexion' },
                        mobile_1: { type: 'string', required: true, max: 25, description: 'Téléphone principal' },
                        ville: { type: 'string', required: false, max: 55, description: 'Ville' },
                        code_postal: { type: 'string', required: false, max: 20, description: 'Code postal' },
                        lieu_residence: { type: 'string', required: false, max: 55, description: 'Lieu de résidence' },
                        pays: { type: 'string', required: false, max: 55, description: 'Pays' },
                        fonction: { type: 'string', required: false, max: 55, description: 'Fonction professionnelle' },
                        contrats: { type: 'array', required: true, description: 'Liste des contrats à associer' },
                        numero_client: { type: 'string', required: false, description: 'Numéro client existant' },
                        client_number: { type: 'string', required: false, description: 'Numéro client pour les contrats' }
                    }
                },
                exampleRequest: {
                    prenoms: 'Jean', nom: 'Dupont', email: 'jean.dupont@example.com',
                    login: 'jdupont', mobile_1: '+2250708091011',
                    contrats: [{ IdProposition: 'PROP2024001', produit: 'Assurance Vie Premium' }]
                },
                responses: [
                    { status: 201, description: 'Inscription réussie', example: { success: true, code: 'USER_CREATED', message: 'Inscription réussie. Vos paramètres de connexion ont été envoyés.', data: {} } },
                    { status: 422, description: 'Erreur de validation', example: { success: false, message: 'Données invalides.', errors: {} } }
                ]
            },

            {
                id: 'auth-freeze-check',
                module: 'auth',
                name: 'Vérifier le gel d\'un compte',
                description: 'Endpoint public pour vérifier si un compte (par login) est actuellement gelé.',
                method: 'GET',
                path: '/auth/freeze-check/{login}',
                isProtected: false,
                rateLimit: 'throttle:30,1',
                headers: { 'Accept': 'application/json' },
                requestParams: {
                    path: { login: { type: 'string', required: true, description: 'Login de l\'utilisateur' } }
                },
                responses: [
                    { status: 200, description: 'Compte non gelé', example: { success: true, data: { is_frozen: false } } },
                    { status: 200, description: 'Compte gelé', example: { success: true, data: { is_frozen: true, remaining_seconds: 120, freeze_level: 2 } } }
                ]
            },

            // ============================================================
            // 3.3 MOTS DE PASSE
            // ============================================================
            {
                id: 'password-forgot',
                module: 'password',
                name: 'Mot de passe oublié',
                description: 'Demande de réinitialisation de mot de passe. **NOUVEAU :** Limitation de l\'envoi OTP par SMS à une fois toutes les 24 heures. Support de multiples canaux : sms, email, whatsapp, question_secrete.',
                method: 'POST',
                path: '/auth/forgot-password',
                isProtected: false,
                rateLimit: 'throttle:login',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur' },
                        option: { type: 'string', required: true, enum: ['sms', 'email', 'whatsapp', 'question_secrete'], description: 'Canal de récupération' }
                    }
                },
                exampleRequest: { login: 'jdupont', option: 'email' },
                responses: [
                    { status: 200, description: 'Questions de sécurité retournées', example: { success: true, data: { token: '...', method: 'question_secrete', questions: [] } } },
                    { status: 200, description: 'OTP envoyé avec succès', example: { success: true, message: 'Un code de vérification a été envoyé.', data: { token: '...', method: 'email', expires_in: 5 } } },
                    { status: 404, description: 'Utilisateur introuvable', example: { success: false, message: 'Utilisateur introuvable.' } },
                    { status: 429, description: 'Limite OTP SMS atteinte (24h)', example: { success: false, code: 'OTP_SMS_ALREADY_SENT', message: 'Vous avez déjà utilisé la vérification par SMS au cours des dernières 24 heures.', data: { available_options: [{ code: 'email', label: 'Recevoir un code par email' }, { code: 'question_secrete', label: 'Répondre aux questions de sécurité' }] } } }
                ]
            },

            {
                id: 'password-reset',
                module: 'password',
                name: 'Réinitialiser le mot de passe',
                description: 'Réinitialise le mot de passe à partir du token. Vérifie l\'historique (5 derniers mots de passe non réutilisables).',
                method: 'POST',
                path: '/auth/reset-password',
                isProtected: false,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur' },
                        token: { type: 'string', required: true, description: 'Token de réinitialisation' },
                        password: { type: 'string', required: true, min: 12, description: 'Nouveau mot de passe' },
                        password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
                    }
                },
                exampleRequest: { login: 'jean.dupont@example.com', token: 'abc123def456', password: 'NouveauMdp123!', password_confirmation: 'NouveauMdp123!' },
                responses: [
                    { status: 200, description: 'Mot de passe réinitialisé', example: { success: true, message: 'Mot de passe réinitialisé avec succès.' } },
                    { status: 422, description: 'Token invalide/expiré', example: { success: false, message: 'Token invalide ou expiré.' } }
                ]
            },

            {
                id: 'password-change',
                module: 'password',
                name: 'Changer le mot de passe',
                description: 'Change le mot de passe d\'un utilisateur authentifié après vérification du mot de passe actuel et de l\'historique.',
                method: 'POST',
                path: '/auth/change-password',
                isProtected: true,
                permissionsRequired: ['auth.change_password'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        current_password: { type: 'string', required: true, description: 'Mot de passe actuel' },
                        password: { type: 'string', required: true, min: 12, description: 'Nouveau mot de passe' },
                        password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
                    }
                },
                exampleRequest: { current_password: 'AncienMdp123!', password: 'NouveauMdp456!', password_confirmation: 'NouveauMdp456!' },
                responses: [
                    { status: 200, description: 'Mot de passe changé', example: { success: true, message: 'Mot de passe changé avec succès.' } },
                    { status: 422, description: 'Mot de passe actuel incorrect', example: { success: false, message: 'Mot de passe actuel incorrect.' } }
                ]
            },

            {
                id: 'password-first-login',
                module: 'password',
                name: 'Première connexion — définir le mot de passe',
                description: 'Définit le mot de passe initial lors de la première connexion. Nécessite le token temporaire avec ability password-change.',
                method: 'POST',
                path: '/auth/first-login',
                isProtected: true,
                abilityRequired: 'password-change',
                headers: { 'Authorization': 'Bearer {change_password_token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        password: { type: 'string', required: true, min: 12, description: 'Nouveau mot de passe' },
                        password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
                    }
                },
                exampleRequest: { password: 'MonPremierMdp123!', password_confirmation: 'MonPremierMdp123!' },
                responses: [
                    { status: 200, description: 'Mot de passe initialisé', example: { success: true, message: 'Mot de passe initialisé.', data: { access_token: '...', user: {} } } },
                    { status: 422, description: 'Changement non requis', example: { success: false, code: 'PASSWORD_CHANGE_NOT_REQUIRED', message: 'Le changement de mot de passe n\'est pas requis pour ce compte.' } }
                ]
            },

            // ============================================================
            // 3.4 DOUBLE AUTHENTIFICATION (2FA & OTP)
            // ============================================================
            {
                id: '2fa-enable',
                module: '2fa',
                name: 'Activer 2FA - QR Code',
                description: 'Génère un secret TOTP pour l\'authenticator et retourne un QR code SVG à scanner. Support de plusieurs méthodes de 2FA : authenticator (TOTP), OTP (email/SMS).',
                method: 'GET',
                path: '/auth/2fa/qrcode',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        method: { type: 'string', required: false, enum: ['authenticator', 'otp'], default: 'authenticator', description: 'Méthode de 2FA' }
                    }
                },
                responses: [
                    { status: 200, description: 'QR Code généré', example: { success: true, data: { secret: '...', qr_code_svg: '<svg>...</svg>', method: 'authenticator' } } },
                    { status: 200, description: 'OTP prêt', example: { success: true, data: { method: 'otp', message: 'Un code OTP de vérification a été envoyé.', expires_in: 5 } } },
                    { status: 422, description: '2FA déjà activé', example: { success: false, message: '2FA déjà activé.' } }
                ]
            },

            {
                id: '2fa-confirm',
                module: '2fa',
                name: 'Confirmer l\'activation 2FA',
                description: 'Confirme l\'activation de la 2FA. Supporte plusieurs méthodes de confirmation : code TOTP (authenticator) ou code OTP (email/SMS). Génère 8 codes de récupération.',
                method: 'POST',
                path: '/auth/2fa/confirm',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        method: { type: 'string', required: true, enum: ['authenticator', 'otp'], description: 'Méthode de confirmation' },
                        code: { type: 'string', required: true, size: 6, description: 'Code de confirmation' }
                    }
                },
                exampleRequest: { method: 'authenticator', code: '123456' },
                responses: [
                    { status: 200, description: '2FA activé', example: { success: true, message: '2FA activé avec succès.', data: { recovery_codes: ['abc123', '...'], method: 'authenticator' } } },
                    { status: 422, description: 'Code invalide', example: { success: false, message: 'Code invalide.' } }
                ]
            },

            {
                id: '2fa-disable',
                module: '2fa',
                name: 'Désactiver la 2FA',
                description: 'Désactive la 2FA après vérification du mot de passe.',
                method: 'POST',
                path: '/auth/2fa/disable',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: { password: { type: 'string', required: true, description: 'Mot de passe actuel' } }
                },
                exampleRequest: { password: 'MonPassword123!' },
                responses: [
                    { status: 200, description: '2FA désactivée', example: { success: true, message: '2FA désactivé.' } },
                    { status: 403, description: 'Mot de passe incorrect', example: { success: false, message: 'Mot de passe incorrect.' } }
                ]
            },

            {
                id: '2fa-methods',
                module: '2fa',
                name: 'Méthodes 2FA disponibles',
                description: 'Récupère la liste des méthodes de double authentification disponibles et configurées.',
                method: 'GET',
                path: '/auth/2fa/methods',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Méthodes disponibles', example: { success: true, data: { available_methods: ['authenticator', 'otp'], configured_methods: ['authenticator'], default_method: 'authenticator', is_enabled: true } } }
                ]
            },

            {
                id: '2fa-status',
                module: '2fa',
                name: 'Statut de la 2FA',
                description: 'Récupère le statut complet de la double authentification pour l\'utilisateur.',
                method: 'GET',
                path: '/auth/2fa/status',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Statut 2FA', example: { success: true, data: { enabled: true, method: 'authenticator', recovery_codes_count: 6 } } }
                ]
            },

            {
                id: '2fa-verify-login-public',
                module: '2fa',
                name: 'Vérifier 2FA (post-login)',
                description: 'Vérifie le code de double authentification après une connexion. Supporte les deux méthodes : authenticator (TOTP) et OTP (email/SMS). Gestion des tentatives : après 5 échecs, verrouillage 30 min.',
                method: 'POST',
                path: '/auth/2fa/verify-login',
                isProtected: true,
                abilityRequired: '2fa-verify',
                rateLimit: 'throttle:5,10',
                headers: { 'Authorization': 'Bearer {two_factor_token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        method: { type: 'string', required: false, enum: ['authenticator', 'otp'], default: 'authenticator', description: 'Méthode de vérification' },
                        code: { type: 'string', required: true, size: 6, description: 'Code de vérification' },
                        trust_device: { type: 'boolean', required: false, default: false, description: 'Marquer l\'appareil comme de confiance' }
                    }
                },
                exampleRequest: { method: 'authenticator', code: '123456', trust_device: true },
                responses: [
                    { status: 200, description: '2FA vérifiée', example: { success: true, data: { user: {}, access_token: '...', trusted_device: true } } },
                    { status: 401, description: 'Token invalide', example: { success: false, message: 'Token invalide.' } },
                    { status: 422, description: 'Code invalide', example: { success: false, message: 'Code 2FA invalide.' } },
                    { status: 423, description: 'Compte verrouillé', example: { success: false, message: 'Trop de tentatives. Réessayez dans 1800 secondes.', is_locked: true } }
                ]
            },

            {
                id: '2fa-recovery-codes',
                module: '2fa',
                name: 'Gérer les codes de récupération 2FA',
                description: 'Permet de régénérer ou de consulter les codes de récupération de la 2FA.',
                method: 'POST',
                path: '/auth/2fa/recovery-codes',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        action: { type: 'string', required: true, enum: ['regenerate', 'send'], description: 'Action : "regenerate" ou "send"' }
                    }
                },
                exampleRequest: { action: 'regenerate' },
                responses: [
                    { status: 200, description: 'Codes régénérés', example: { success: true, message: 'Nouveaux codes de récupération générés.', data: { recovery_codes: ['abc123', '...'], count: 8 } } },
                    { status: 200, description: 'Codes envoyés', example: { success: true, message: 'Codes de récupération envoyés par email.' } }
                ]
            },

            {
                id: '2fa-verify-recovery',
                module: '2fa',
                name: 'Vérifier un code de récupération 2FA',
                description: 'Permet de vérifier un code de récupération de la 2FA lors de la perte d\'accès à l\'authenticator.',
                method: 'POST',
                path: '/auth/2fa/verify-recovery',
                isProtected: false,
                rateLimit: 'throttle:5,30',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, description: 'Login de l\'utilisateur' },
                        code: { type: 'string', required: true, description: 'Code de récupération à 10 caractères' }
                    }
                },
                exampleRequest: { login: 'jdupont', code: 'abc123def4' },
                responses: [
                    { status: 200, description: 'Code de récupération valide', example: { success: true, message: 'Code de récupération valide.', data: { user_uuid: '...', reset_token: '...' } } },
                    { status: 422, description: 'Code invalide', example: { success: false, message: 'Code de récupération invalide ou déjà utilisé.' } },
                    { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives. Veuillez patienter.' } }
                ]
            },

            {
                id: 'otp-send',
                module: '2fa',
                name: 'Envoyer un code OTP',
                description: 'Génère et envoie un code OTP à 6 chiffres par email, SMS ou WhatsApp.',
                method: 'POST',
                path: '/auth/otp/send',
                isProtected: true,
                permissionsRequired: ['auth.2fa'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        channel: { type: 'string', required: true, enum: ['email', 'sms', 'whatsapp'], description: 'Canal d\'envoi' },
                        purpose: { type: 'string', required: true, enum: ['login', '2fa', 'reset'], description: 'Usage du code' },
                        login: { type: 'string', required: false, description: 'Login de l\'utilisateur' }
                    }
                },
                exampleRequest: { channel: 'email', purpose: '2fa' },
                responses: [
                    { status: 200, description: 'OTP envoyé', example: { success: true, code: 'OTP_SENT', message: 'Code OTP envoyé par email.', data: { channel: 'email', purpose: '2fa', expires_in: 5 } } },
                    { status: 422, description: 'Canal invalide', example: { success: false, code: 'CHANNEL_INVALID', message: 'Le canal WhatsApp n\'est pas encore configuré.' } },
                    { status: 422, description: 'Téléphone invalide', example: { success: false, code: 'TELEPHONE_INVALID', message: 'Numéro de téléphone invalide.' } }
                ]
            },

            {
                id: 'otp-verify-code',
                module: '2fa',
                name: 'Vérifier un OTP pour une opération',
                description: 'Vérifie un code OTP précédemment envoyé. Si le `purpose` est "reset", génère un token de réinitialisation.',
                method: 'POST',
                path: '/auth/otp/verify-code',
                isProtected: false,
                rateLimit: 'throttle:5,10',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, description: 'Login de l\'utilisateur' },
                        code: { type: 'string', required: true, size: 6, pattern: '^[0-9]{6}$', description: 'Code OTP' },
                        purpose: { type: 'string', required: true, enum: ['login', '2fa', 'reset'], description: 'Usage du code' }
                    }
                },
                exampleRequest: { login: 'jdupont', code: '123456', purpose: 'reset' },
                responses: [
                    { status: 200, description: 'OTP vérifié avec token', example: { success: true, code: 'OTP_VERIFIED', message: 'Code OTP vérifié.', data: { user_uuid: '...', reset_token: '...' } } },
                    { status: 422, description: 'OTP invalide', example: { success: false, code: 'OTP_INVALID', message: 'Code OTP invalide ou expiré.' } },
                    { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives. Veuillez patienter.' } }
                ]
            },

            // ============================================================
            // 3.5 QUESTIONS DE SÉCURITÉ
            // ============================================================
            {
                id: 'security-suggested',
                module: 'security',
                name: 'Questions suggérées (publiques)',
                description: 'Retourne une liste statique de questions de sécurité prédéfinies, groupées par catégorie.',
                method: 'GET',
                path: '/security/questions/suggested',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Questions suggérées', example: { success: true, code: 'QUESTIONS_SUGGESTED', data: [{ category: 'Personnelle', questions: ['...'] }] } }
                ]
            },

            {
                id: 'security-questions-available',
                module: 'security',
                name: 'Questions disponibles',
                description: 'Récupère toutes les questions de sécurité actives disponibles dans le système.',
                method: 'GET',
                path: '/security/questions',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Liste des questions', example: { success: true, data: [{ uuid: '...', question_text: '...', category: '...' }] } }
                ]
            },

            {
                id: 'security-verify-email',
                module: 'security',
                name: 'Vérifier les questions (par login)',
                description: 'Vérifie si un compte (par login) a configuré des questions de sécurité. Rate-limité par IP.',
                method: 'POST',
                path: '/security/verify-email',
                isProtected: false,
                rateLimit: 'throttle:5,15 + ThrottleService (5/300)',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: { login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur' } }
                },
                exampleRequest: { login: 'jdupont' },
                responses: [
                    { status: 200, description: 'Compte trouvé', example: { success: true, data: { user_uuid: '...', has_questions: true, questions: [] } } },
                    { status: 404, description: 'Login non trouvé', example: { success: false, message: 'Login non trouvé.' } },
                    { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives. Veuillez patienter.', code: 'TOO_MANY_ATTEMPTS' } }
                ]
            },

            {
                id: 'security-verify-answer',
                module: 'security',
                name: 'Vérifier les réponses (multi-questions)',
                description: 'Vérifie une ou plusieurs réponses aux questions de sécurité. Retourne un token de réinitialisation si toutes les réponses sont correctes.',
                method: 'POST',
                path: '/security/verify-answer',
                isProtected: false,
                rateLimit: 'throttle:5,15 + ThrottleService (5/300 par user)',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur' },
                        questions: { type: 'array', required: true, min: 1, description: 'Tableau des questions/réponses' },
                        'questions.*.question_uuid': { type: 'uuid', required: true, description: 'UUID de la question' },
                        'questions.*.answer': { type: 'string', required: true, min: 1, max: 255, description: 'Réponse à vérifier' }
                    }
                },
                exampleRequest: { login: 'jdupont', questions: [{ question_uuid: 'q1-uuid', answer: 'Rex' }] },
                responses: [
                    { status: 200, description: 'Réponses correctes', example: { success: true, message: 'Toutes les réponses sont correctes.', data: { verified: true, user_uuid: '...', reset_token: '...', results: [{ question_uuid: '...', verified: true }] } } },
                    { status: 422, description: 'Réponses incorrectes', example: { success: false, message: 'Une ou plusieurs réponses sont incorrectes.', remaining_attempts: 3 } },
                    { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives...', code: 'TOO_MANY_ATTEMPTS' } }
                ]
            },

            {
                id: 'security-user-questions-get',
                module: 'security',
                name: 'Mes questions configurées',
                description: 'Récupère les questions de sécurité déjà configurées par l\'utilisateur (sans les réponses).',
                method: 'GET',
                path: '/security/user-questions',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Questions configurées', example: { success: true, data: { user_uuid: '...', has_configured: true, questions: [] } } }
                ]
            },

            {
                id: 'security-user-questions-set',
                module: 'security',
                name: 'Configurer mes questions',
                description: 'Définit (remplace) les réponses de sécurité de l\'utilisateur. Entre 3 et 5 questions distinctes. Nécessite le mot de passe actuel.',
                method: 'POST',
                path: '/security/user-questions',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        answers: { type: 'array', required: true, min: 3, max: 5, description: 'Tableau { question_uuid, answer }' },
                        password: { type: 'string', required: true, description: 'Mot de passe actuel' }
                    }
                },
                exampleRequest: {
                    answers: [{ question_uuid: 'q1-uuid', answer: 'Rex' }, { question_uuid: 'q2-uuid', answer: 'Bouaké' }, { question_uuid: 'q3-uuid', answer: 'Aya' }],
                    password: 'MonPassword123!'
                },
                responses: [
                    { status: 200, description: 'Questions configurées', example: { success: true, message: 'Questions de sécurité configurées avec succès.' } },
                    { status: 403, description: 'Mot de passe incorrect', example: { success: false, message: 'Mot de passe incorrect.', code: 'INVALID_PASSWORD' } },
                    { status: 422, description: 'Validation échouée', example: { success: false, message: 'Vous ne pouvez pas sélectionner deux fois la même question.' } }
                ]
            },

            // ============================================================
            // 3.6 ADMIN — QUESTIONS DE SÉCURITÉ
            // ============================================================
            {
                id: 'security-questions-create-admin',
                module: 'security',
                name: '[Admin] Créer une question',
                description: 'Crée une nouvelle question de sécurité. Réservé aux utilisateurs avec la permission security_questions.gerer.',
                method: 'POST',
                path: '/admin/security/questions',
                isProtected: true,
                permissionsRequired: ['security_questions.gerer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        question_text: { type: 'string', required: true, max: 255, description: 'Texte de la question (unique)' },
                        category: { type: 'string', required: false, max: 50, description: 'Catégorie' },
                        is_active: { type: 'boolean', required: false, default: true, description: 'Question active' },
                        is_system: { type: 'boolean', required: false, default: false, description: 'Question système protégée' }
                    }
                },
                exampleRequest: { question_text: 'Quel est le nom de votre premier employeur ?', category: 'Professionnelle' },
                responses: [
                    { status: 201, description: 'Question créée', example: { success: true, message: 'Question de sécurité créée avec succès.', data: {} } },
                    { status: 422, description: 'Erreur de validation', example: { success: false, message: 'Données invalides.' } }
                ]
            },

            {
                id: 'security-questions-update-admin',
                module: 'security',
                name: '[Admin] Modifier une question',
                description: 'Met à jour une question existante. Les questions système sont protégées (sauf Super Admin).',
                method: 'PUT',
                path: '/admin/security/questions/{uuid}',
                isProtected: true,
                permissionsRequired: ['security_questions.gerer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid: { type: 'uuid', required: true, description: 'UUID de la question' } },
                    body: {
                        question_text: { type: 'string', required: false, max: 255, description: 'Texte de la question' },
                        category: { type: 'string', required: false, max: 50, description: 'Catégorie' },
                        is_active: { type: 'boolean', required: false, description: 'Question active' }
                    }
                },
                responses: [
                    { status: 200, description: 'Question mise à jour', example: { success: true, message: 'Question de sécurité mise à jour avec succès.', data: {} } },
                    { status: 403, description: 'Question système protégée', example: { success: false, message: 'Les questions système ne peuvent pas être modifiées.' } }
                ]
            },

            {
                id: 'security-questions-delete-admin',
                module: 'security',
                name: '[Admin] Supprimer une question',
                description: 'Supprime (soft delete) une question si elle n\'est pas utilisée et n\'est pas système.',
                method: 'DELETE',
                path: '/admin/security/questions/{uuid}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['security_questions.gerer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid: { type: 'uuid', required: true, description: 'UUID de la question' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Question supprimée', example: { success: true, message: 'Question de sécurité supprimée avec succès.' } },
                    { status: 422, description: 'Question utilisée ou protégée', example: { success: false, message: 'Cette question est utilisée par des utilisateurs et ne peut donc pas être supprimée.' } }
                ]
            },

            // ============================================================
            // 3.7 PROFIL, APPAREILS & SESSIONS
            // ============================================================
            {
                id: 'auth-me',
                module: 'profile',
                name: 'Utilisateur connecté',
                description: 'Récupère les informations complètes de l\'utilisateur authentifié (rôle, permissions, détails, partenaire, réseau, agences, contrats).',
                method: 'GET',
                path: '/auth/me',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Informations utilisateur', example: { success: true, data: { uuid_user: '...', email: '...', role: {}, permissions: [] } } }
                ]
            },

            {
                id: 'auth-logout',
                module: 'profile',
                name: 'Déconnexion',
                description: 'Révoque uniquement le token Sanctum de la session courante.',
                method: 'POST',
                path: '/auth/logout',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Déconnexion réussie', example: { success: true, message: 'Déconnexion réussie.' } }
                ]
            },

            {
                id: 'auth-logout-all',
                module: 'profile',
                name: 'Déconnexion de tous les appareils',
                description: 'Révoque tous les tokens Sanctum de l\'utilisateur et envoie un email de notification.',
                method: 'POST',
                path: '/auth/logout-all',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Toutes les sessions révoquées', example: { success: true, message: 'Déconnexion de tous les appareils.' } }
                ]
            },

            {
                id: 'auth-refresh',
                module: 'profile',
                name: 'Rafraîchir le token',
                description: 'Génère un nouveau token (24h) et supprime l\'ancien.',
                method: 'POST',
                path: '/auth/refresh',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'X-Device-Name': 'API Token', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Nouveau token généré', example: { success: true, data: { access_token: '...', token_type: 'Bearer', expires_at: '...' } } }
                ]
            },

            {
                id: 'profile-show',
                module: 'profile',
                name: 'Mon profil',
                description: 'Récupère les informations complètes du profil de l\'utilisateur connecté. Retourne les données utilisateur, ses détails (nom, prénoms, contact, photo, etc.), son rôle, ses permissions, ses partenaires, réseaux, agences et contrats.',
                method: 'GET',
                path: '/profile',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    {
                        status: 200,
                        description: 'Profil utilisateur récupéré avec succès',
                        example: {
                            success: true,
                            message: 'Profil récupéré avec succès.',
                            code: 'PROFILE_FOUND',
                            data: {
                                uuid_user: '550e8400-e29b-41d4-a716-446655440000',
                                login: 'jean.dupont',
                                email: 'jean.dupont@ynov.ci',
                                user_type: 'user_interne',
                                status: 'actif',
                                is_first_login: false,
                                is_online: true,
                                is_locked: false,
                                last_login_at: '2025-01-15T10:30:00.000000Z',
                                email_verified_at: '2025-01-15T10:00:00.000000Z',
                                two_factor_enabled: true,
                                role: {
                                    uuid_role: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'Administrateur',
                                    is_super_admin: false
                                },
                                details: {
                                    uuid_user_details: '550e8400-e29b-41d4-a716-446655440002',
                                    code_agent: 'AG2025001',
                                    matricule: 'MAT2025001',
                                    numero_client: 'CLT2025001',
                                    nom: 'Dupont',
                                    prenoms: 'Jean-Marc',
                                    full_name: 'Jean-Marc Dupont',
                                    fonction: 'Directeur Commercial',
                                    service: 'Commercial',
                                    departement: 'Ventes',
                                    mobile_1: '+2250708091011',
                                    mobile_2: '+2250708091012',
                                    telephone_fixe: '+2252720304050',
                                    email_pro: 'jean.dupont@yako.ci',
                                    photo: null,
                                    photo_path: 'profiles/550e8400-e29b-41d4-a716-446655440000/profile_550e8400-e29b-41d4-a716-446655440000_1698765432.jpg',
                                    photo_url: 'https://api.ynov.ci/storage/documents/profiles/550e8400-e29b-41d4-a716-446655440000/profile_550e8400-e29b-41d4-a716-446655440000_1698765432.jpg',
                                    date_naissance: '1985-06-15',
                                    lieu_naissance: 'Abidjan',
                                    lieu_residence: 'Cocody',
                                    nationalite: 'Ivoirienne',
                                    genre: 'M',
                                    civilite: 'M.',
                                    adresse_complete: 'Cocody, Abidjan',
                                    ville: 'Abidjan',
                                    code_postal: '01 BP 1234',
                                    pays: 'Côte d\'Ivoire',
                                    date_embauche: '2020-01-15',
                                    statut_employe: 'CDI',
                                    type_contrat: 'Permanent',
                                    created_at: '2025-01-15T10:00:00.000000Z',
                                    updated_at: '2025-01-15T14:30:00.000000Z'
                                },
                                partner: {
                                    uuid_partner: '...',
                                    designation: 'YAKO AFRICA'
                                },
                                reseau: {
                                    uuid_reseau: '...',
                                    libelle: 'Réseau Abidjan'
                                },
                                agences: [
                                    {
                                        uuid_agence: '...',
                                        libelle: 'YAKO Plateau',
                                        is_primary: true
                                    }
                                ],
                                permissions_grouped: {
                                    'Utilisateurs': ['users.afficher', 'users.creer', 'users.modifier'],
                                    'Rôles': ['roles.afficher', 'roles.creer']
                                }
                            }
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    }
                ]
            },

            {
                id: 'profile-update',
                module: 'profile',
                name: 'Mettre à jour mon profil',
                description: 'Modifie les informations personnelles de l\'utilisateur connecté. **NOUVEAU :** Support de l\'upload de photo de profil (image) et des URLs externes. Permet de mettre à jour le login, l\'email, les coordonnées, la photo, et les informations professionnelles.',
                method: 'PUT',
                path: '/profile',
                isProtected: true,
                headers: {
                    'Authorization': 'Bearer {token}',
                    'Content-Type': 'multipart/form-data (pour upload photo) ou application/json',
                    'Accept': 'application/json'
                },
                requestParams: {
                    body: {
                        login: { type: 'string', required: false, max: 100, description: 'Identifiant de connexion (unique)' },
                        email: { type: 'email', required: false, max: 100, description: 'Email principal (unique)' },
                        nom: { type: 'string', required: false, max: 55, description: 'Nom de famille' },
                        prenoms: { type: 'string', required: false, max: 255, description: 'Prénoms' },
                        civilite: { type: 'string', required: false, enum: ['M.', 'Mme', 'Mlle', 'Dr', 'Pr'], description: 'Civilité' },
                        genre: { type: 'string', required: false, enum: ['M', 'F'], description: 'Genre' },
                        date_naissance: { type: 'date', required: false, description: 'Date de naissance (avant aujourd\'hui)' },
                        lieu_naissance: { type: 'string', required: false, max: 55, description: 'Lieu de naissance' },
                        nationalite: { type: 'string', required: false, max: 55, description: 'Nationalité' },
                        mobile_1: { type: 'string', required: false, max: 25, description: 'Téléphone principal' },
                        mobile_2: { type: 'string', required: false, max: 25, description: 'Téléphone secondaire' },
                        telephone_fixe: { type: 'string', required: false, max: 25, description: 'Téléphone fixe' },
                        email_pro: { type: 'email', required: false, max: 100, description: 'Email professionnel (unique)' },
                        adresse_complete: { type: 'string', required: false, max: 255, description: 'Adresse complète' },
                        ville: { type: 'string', required: false, max: 100, description: 'Ville' },
                        pays: { type: 'string', required: false, max: 100, description: 'Pays' },
                        code_postal: { type: 'string', required: false, max: 20, description: 'Code postal' },
                        lieu_residence: { type: 'string', required: false, max: 55, description: 'Lieu de résidence' },
                        fonction: { type: 'string', required: false, max: 55, description: 'Fonction' },
                        service: { type: 'string', required: false, max: 55, description: 'Service' },
                        departement: { type: 'string', required: false, max: 55, description: 'Département' },
                        date_embauche: { type: 'date', required: false, description: 'Date d\'embauche' },
                        statut_employe: { type: 'string', required: false, max: 50, description: 'Statut employé' },
                        type_contrat: { type: 'string', required: false, max: 50, description: 'Type de contrat' },
                        code_agent: { type: 'string', required: false, max: 35, description: 'Code agent' },
                        matricule: { type: 'string', required: false, max: 35, description: 'Matricule' },
                        numero_client: { type: 'string', required: false, max: 35, description: 'Numéro client' },
                        photo: { type: 'file (image)', required: false, mimes: 'jpeg,png,jpg,gif,webp', max: '2048 Ko', description: 'Photo de profil (upload)' },
                        photo_url: { type: 'string (url)', required: false, max: 255, description: 'URL externe de la photo' },
                        remove_photo: { type: 'boolean', required: false, description: 'Supprimer la photo actuelle' },
                        preferences: { type: 'object', required: false, description: 'Préférences utilisateur (JSON)' }
                    }
                },
                exampleRequest: {
                    nom: 'Dupont',
                    prenoms: 'Jean-Marc',
                    fonction: 'Directeur Commercial',
                    mobile_1: '+2250708091011',
                    ville: 'Abidjan',
                    pays: 'Côte d\'Ivoire',
                    photo: '[Fichier image]'
                },
                exampleRequestJson: {
                    nom: 'Dupont',
                    prenoms: 'Jean-Marc',
                    fonction: 'Directeur Commercial',
                    mobile_1: '+2250708091011',
                    ville: 'Abidjan',
                    photo_url: 'https://example.com/photos/jean.jpg'
                },
                responses: [
                    {
                        status: 200,
                        description: 'Profil mis à jour avec succès',
                        example: {
                            success: true,
                            message: 'Profil mis à jour avec succès.',
                            code: 'PROFILE_UPDATED',
                            data: {}
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Données invalides.',
                            errors: {
                                email: ['Cet email est déjà utilisé.'],
                                photo: ['La photo ne doit pas dépasser 2 Mo.']
                            }
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    }
                ]
            },

            {
                id: 'profile-delete-photo',
                module: 'profile',
                name: 'Supprimer la photo de profil',
                description: 'Supprime la photo de profil de l\'utilisateur connecté. La photo est supprimée du serveur et les champs `photo` et `photo_path` sont vidés.',
                method: 'DELETE',
                path: '/profile/photo',
                isProtected: true,
                isDestructive: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    {
                        status: 200,
                        description: 'Photo supprimée avec succès',
                        example: {
                            success: true,
                            message: 'Photo de profil supprimée avec succès.',
                            code: 'PHOTO_DELETED'
                        }
                    },
                    {
                        status: 404,
                        description: 'Aucune photo à supprimer',
                        example: {
                            success: false,
                            message: 'Aucune photo de profil à supprimer.',
                            code: 'NO_PHOTO_FOUND'
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    }
                ]
            },

            {
                id: 'devices-list',
                module: 'profile',
                name: 'Liste des appareils',
                description: 'Récupère la liste des appareils enregistrés pour l\'utilisateur.',
                method: 'GET',
                path: '/auth/devices',
                isProtected: true,
                permissionsRequired: ['auth.devices'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Liste des appareils', example: { success: true, data: [{ uuid_device: '...', device_name: 'iPhone de Jean', is_trusted: false }] } }
                ]
            },

            {
                id: 'devices-trust',
                module: 'profile',
                name: 'Approuver un appareil',
                description: 'Marque un appareil comme "de confiance", évitant la 2FA lors des prochaines connexions.',
                method: 'POST',
                path: '/auth/devices/{uuidDevice}/trust',
                isProtected: true,
                permissionsRequired: ['auth.devices'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuidDevice: { type: 'uuid', required: true, description: 'UUID de l\'appareil' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Appareil approuvé', example: { success: true, message: 'Appareil approuvé.' } }
                ]
            },

            {
                id: 'devices-revoke',
                module: 'profile',
                name: 'Révoquer un appareil',
                description: 'Supprime un appareil de la liste des appareils connus.',
                method: 'DELETE',
                path: '/auth/devices/{uuidDevice}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['auth.devices'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuidDevice: { type: 'uuid', required: true, description: 'UUID de l\'appareil' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Appareil révoqué', example: { success: true, message: 'Appareil révoqué.' } },
                    { status: 404, description: 'Appareil non trouvé', example: { success: false, message: 'Appareil non trouvé.' } }
                ]
            },

            {
                id: 'sessions-list',
                module: 'profile',
                name: 'Liste des sessions',
                description: 'Récupère la liste des tokens Sanctum actifs (sessions) de l\'utilisateur.',
                method: 'GET',
                path: '/auth/sessions',
                isProtected: true,
                permissionsRequired: ['auth.sessions'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Liste des sessions', example: { success: true, data: [{ id: 1, name: 'API Token', last_used_at: '...' }] } }
                ]
            },

            {
                id: 'sessions-revoke',
                module: 'profile',
                name: 'Révoquer une session',
                description: 'Révoque un token spécifique (autre appareil).',
                method: 'DELETE',
                path: '/auth/sessions/{tokenId}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['auth.sessions'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { tokenId: { type: 'integer', required: true, description: 'ID du token Sanctum' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Session révoquée', example: { success: true, message: 'Session révoquée.' } },
                    { status: 404, description: 'Session non trouvée', example: { success: false, message: 'Session non trouvée.' } }
                ]
            },

            {
                id: 'login-attempts-list',
                module: 'profile',
                name: 'Historique de connexion',
                description: 'Liste paginée des tentatives de connexion (réussies et échouées) de l\'utilisateur.',
                method: 'GET',
                path: '/auth/login-attempts',
                isProtected: true,
                permissionsRequired: ['auth.login_attempts'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: { per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Historique des tentatives', example: { success: true, data: [{ login_attempted: '...', is_successful: true }] } }
                ]
            },

            // ============================================================
            // 3.8 GESTION DES UTILISATEURS
            // ============================================================
            {
                id: 'users-list',
                module: 'users',
                name: 'Liste des utilisateurs',
                description: 'Liste paginée des utilisateurs. Filtrée selon la portée de l\'utilisateur connecté.',
                method: 'GET',
                path: '/users',
                isProtected: true,
                permissionsRequired: ['users.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: { per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Liste des utilisateurs', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'users-show',
                module: 'users',
                name: 'Détails d\'un utilisateur',
                description: 'Récupère les informations complètes d\'un utilisateur (rôle, permissions, détails, partenaire, réseau, agences).',
                method: 'GET',
                path: '/users/{uuid_user}',
                isProtected: true,
                permissionsRequired: ['users.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } }
                },
                responses: [
                    { status: 200, description: 'Détails de l\'utilisateur', example: { success: true, data: {} } }
                ]
            },

            {
                id: 'users-create',
                module: 'users',
                name: 'Créer un utilisateur',
                description: 'Crée un nouvel utilisateur interne/partenaire/admin avec ses détails. Envoie un email de bienvenue.',
                method: 'POST',
                path: '/users',
                isProtected: true,
                permissionsRequired: ['users.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        email: { type: 'email', required: true, description: 'Email (unique)' },
                        login: { type: 'string', required: false, max: 100, description: 'Identifiant (unique)' },
                        password: { type: 'string', required: true, min: 12, description: 'Mot de passe' },
                        role_uuid: { type: 'uuid', required: true, description: 'UUID du rôle' },
                        user_type: { type: 'string', required: true, enum: ['client', 'user_interne', 'user_partner', 'admin'], description: 'Type' },
                        partner_uuid: { type: 'uuid', required: false, description: 'UUID du partenaire' },
                        reseau_uuid: { type: 'uuid', required: false, description: 'UUID du réseau' },
                        agence_uuid: { type: 'uuid', required: false, description: 'UUID de l\'agence' },
                        nom: { type: 'string', required: true, max: 55, description: 'Nom' },
                        prenoms: { type: 'string', required: true, max: 255, description: 'Prénoms' },
                        fonction: { type: 'string', required: false, max: 55, description: 'Fonction' },
                        mobile_1: { type: 'string', required: false, max: 25, description: 'Téléphone' }
                    }
                },
                exampleRequest: { email: 'nouveau@ynov.ci', login: 'nouveau', password: 'Password123!', role_uuid: 'role-uuid', user_type: 'user_interne', nom: 'Dupont', prenoms: 'Jean' },
                responses: [
                    { status: 201, description: 'Utilisateur créé', example: { success: true, message: 'Utilisateur créé.', data: {} } }
                ]
            },

            {
                id: 'users-update',
                module: 'users',
                name: 'Modifier un utilisateur',
                description: 'Met à jour les informations d\'un utilisateur.',
                method: 'PUT',
                path: '/users/{uuid_user}',
                isProtected: true,
                permissionsRequired: ['users.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: {
                        email: { type: 'email', required: false, description: 'Email (unique)' },
                        login: { type: 'string', required: false, max: 100, description: 'Identifiant' },
                        role_uuid: { type: 'uuid', required: false, description: 'UUID du rôle' },
                        user_type: { type: 'string', required: false, enum: ['client', 'user_interne', 'user_partner', 'admin'], description: 'Type' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif', 'gele', 'bloque'], description: 'Statut' },
                        nom: { type: 'string', required: false, max: 55, description: 'Nom' },
                        prenoms: { type: 'string', required: false, max: 255, description: 'Prénoms' }
                    }
                },
                exampleRequest: { email: 'jean.updated@ynov.ci', status: 'actif' },
                responses: [
                    { status: 200, description: 'Utilisateur mis à jour', example: { success: true, message: 'Utilisateur mis à jour.', data: {} } }
                ]
            },

            {
                id: 'users-destroy',
                module: 'users',
                name: 'Supprimer un utilisateur',
                description: 'Suppression logique (soft delete) : passe le statut à "inactif", supprime les tokens.',
                method: 'DELETE',
                path: '/users/{uuid_user}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['users.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Utilisateur supprimé', example: { success: true, message: 'Utilisateur supprimé.' } }
                ]
            },

            {
                id: 'users-block',
                module: 'users',
                name: 'Bloquer un utilisateur',
                description: 'Bloque manuellement un compte (statut = bloque, révocation des tokens). Envoie un email de notification.',
                method: 'POST',
                path: '/users/{uuid_user}/block',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['users.bloquer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: { reason: { type: 'string', required: true, max: 500, description: 'Motif du blocage' } }
                },
                exampleRequest: { reason: 'Activité suspecte détectée' },
                responses: [
                    { status: 200, description: 'Utilisateur bloqué', example: { success: true, message: 'Utilisateur bloqué.' } }
                ]
            },

            {
                id: 'users-unblock',
                module: 'users',
                name: 'Débloquer un utilisateur',
                description: 'Débloque un compte précédemment bloqué (statut = actif, réinitialise les compteurs).',
                method: 'POST',
                path: '/users/{uuid_user}/unblock',
                isProtected: true,
                permissionsRequired: ['users.bloquer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Utilisateur débloqué', example: { success: true, message: 'Utilisateur débloqué.' } }
                ]
            },

            // ============================================================
            // 3.9 GEL / DÉGEL
            // ============================================================
            {
                id: 'users-freeze',
                module: 'freeze',
                name: 'Geler un compte (manuel)',
                description: 'Gèle manuellement un compte pour une durée définie (10s à 24h), avec motif obligatoire. Impossible de geler son propre compte.',
                method: 'POST',
                path: '/users/{uuid}/freeze',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['users.geler'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: {
                        duration: { type: 'integer', required: true, min: 10, max: 86400, description: 'Durée en secondes' },
                        reason: { type: 'string', required: true, min: 3, max: 255, description: 'Motif du gel' }
                    }
                },
                exampleRequest: { duration: 300, reason: 'Comportement suspect détecté' },
                responses: [
                    { status: 200, description: 'Compte gelé', example: { message: 'Compte gelé avec succès.', data: { level: 4, is_frozen: true } } },
                    { status: 409, description: 'Compte non gelable', example: { message: 'Ce compte ne peut pas être gelé actuellement.' } },
                    { status: 422, description: 'Données invalides', example: { message: 'Données invalides.', errors: {} } }
                ]
            },

            {
                id: 'users-unfreeze',
                module: 'freeze',
                name: 'Dégeler un compte (manuel)',
                description: 'Dégèle manuellement un compte, réinitialise les compteurs de tentatives échouées et notifie l\'utilisateur.',
                method: 'POST',
                path: '/users/{uuid}/unfreeze',
                isProtected: true,
                permissionsRequired: ['users.degeler'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    body: { reason: { type: 'string', required: false, max: 255, description: 'Motif du dégel' } }
                },
                exampleRequest: { reason: 'Vérification effectuée' },
                responses: [
                    { status: 200, description: 'Compte dégelé', example: { message: 'Compte dégelé avec succès.', data: { level: 0, is_frozen: false } } },
                    { status: 409, description: 'Non gelé', example: { message: 'Ce compte n\'est pas gelé ou ne peut pas être dégelé manuellement.' } }
                ]
            },

            {
                id: 'users-freeze-status',
                module: 'freeze',
                name: 'Statut de gel',
                description: 'Récupère l\'état actuel de gel d\'un compte (niveau, durée restante, possibilité de geler/dégeler).',
                method: 'GET',
                path: '/users/{uuid}/freeze-status',
                isProtected: true,
                permissionsRequired: ['users.degeler'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } }
                },
                responses: [
                    { status: 200, description: 'Statut de gel', example: { data: { user: {}, freeze: { level: 2, is_frozen: true, remaining_seconds: 45 }, can_be_frozen: false, can_be_unfrozen: true } } }
                ]
            },

            // ============================================================
            // 3.10 RÔLES
            // ============================================================
            {
                id: 'roles-list',
                module: 'roles',
                name: 'Liste des rôles',
                description: 'Liste paginée des rôles avec leurs utilisateurs et permissions.',
                method: 'GET',
                path: '/roles',
                isProtected: true,
                permissionsRequired: ['roles.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], description: 'Filtrer par statut' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des rôles', example: { success: true, message: 'Liste des rôles récupérée.', code: 'ROLES_LISTED', data: [] } }
                ]
            },

            {
                id: 'roles-show',
                module: 'roles',
                name: 'Détails d\'un rôle',
                description: 'Récupère un rôle avec toutes ses permissions groupées par module.',
                method: 'GET',
                path: '/roles/{uuid_role}',
                isProtected: true,
                permissionsRequired: ['roles.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { path: { uuid_role: { type: 'uuid', required: true, description: 'UUID du rôle' } } },
                responses: [
                    { status: 200, description: 'Détails du rôle', example: { success: true, code: 'ROLE_FOUND', data: {} } }
                ]
            },

            {
                id: 'roles-users',
                module: 'roles',
                name: 'Utilisateurs d\'un rôle',
                description: 'Liste paginée des utilisateurs ayant ce rôle.',
                method: 'GET',
                path: '/roles/{uuid_role}/users',
                isProtected: true,
                permissionsRequired: ['roles.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_role: { type: 'uuid', required: true, description: 'UUID du rôle' } },
                    query: { per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Utilisateurs du rôle', example: { success: true, code: 'ROLE_USERS_LISTED', data: [] } }
                ]
            },

            {
                id: 'roles-create',
                module: 'roles',
                name: 'Créer un rôle',
                description: 'Crée un nouveau rôle personnalisé (non-système).',
                method: 'POST',
                path: '/roles',
                isProtected: true,
                permissionsRequired: ['roles.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        libelle: { type: 'string', required: true, max: 100, description: 'Libellé (unique)' },
                        description: { type: 'string', required: false, description: 'Description' },
                        level: { type: 'integer', required: false, min: 0, description: 'Niveau hiérarchique' },
                        priority: { type: 'integer', required: false, min: 0, description: 'Priorité' }
                    }
                },
                exampleRequest: { libelle: 'Gestionnaire Agence', description: 'Gère les opérations d\'une agence', level: 2 },
                responses: [
                    { status: 201, description: 'Rôle créé', example: { success: true, code: 'ROLE_CREATED', data: {} } }
                ]
            },

            {
                id: 'roles-update',
                module: 'roles',
                name: 'Modifier un rôle',
                description: 'Met à jour un rôle. Les rôles système (is_system = true) sont protégés.',
                method: 'PUT',
                path: '/roles/{uuid_role}',
                isProtected: true,
                permissionsRequired: ['roles.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_role: { type: 'uuid', required: true, description: 'UUID du rôle' } },
                    body: {
                        description: { type: 'string', required: false, description: 'Description' },
                        level: { type: 'integer', required: false, min: 0, description: 'Niveau' },
                        priority: { type: 'integer', required: false, min: 0, description: 'Priorité' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], description: 'Statut' }
                    }
                },
                exampleRequest: { description: 'Description mise à jour', priority: 10 },
                responses: [
                    { status: 200, description: 'Rôle mis à jour', example: { success: true, code: 'ROLE_UPDATED', data: {} } },
                    { status: 403, description: 'Rôle système protégé', example: { success: false, message: 'Rôle système protégé.', code: 'ROLE_PROTECTED' } }
                ]
            },

            {
                id: 'roles-destroy',
                module: 'roles',
                name: 'Supprimer un rôle',
                description: 'Suppression logique (soft delete) d\'un rôle. Les rôles système sont protégés.',
                method: 'DELETE',
                path: '/roles/{uuid_role}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['roles.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_role: { type: 'uuid', required: true, description: 'UUID du rôle' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Rôle supprimé', example: { success: true, message: 'Rôle supprimé.', code: 'ROLE_DELETED' } },
                    { status: 403, description: 'Rôle système protégé', example: { success: false, message: 'Rôle système protégé.', code: 'ROLE_PROTECTED' } }
                ]
            },

            {
                id: 'roles-assign-permissions',
                module: 'roles',
                name: 'Attribuer des permissions',
                description: 'Remplace (sync) l\'ensemble des permissions attribuées à un rôle. Le rôle Super Admin n\'est pas assignable.',
                method: 'POST',
                path: '/roles/{uuid_role}/permissions',
                isProtected: true,
                permissionsRequired: ['roles.gerer_permissions'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_role: { type: 'uuid', required: true, description: 'UUID du rôle' } },
                    body: { permission_uuids: { type: 'array', required: true, min: 1, description: 'Tableau d\'UUIDs de permissions' } }
                },
                exampleRequest: { permission_uuids: ['perm-uuid-1', 'perm-uuid-2'] },
                responses: [
                    { status: 200, description: 'Permissions attribuées', example: { success: true, code: 'ROLE_PERMISSIONS_ASSIGNED', data: {} } },
                    { status: 422, description: 'Super Admin non assignable', example: { success: false, code: 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE' } }
                ]
            },

            // ============================================================
            // 3.11 PERMISSIONS
            // ============================================================
            {
                id: 'permissions-suggested-actions',
                module: 'permissions',
                name: 'Actions suggérées',
                description: 'Liste des actions standards suggérées pour la création de permissions.',
                method: 'GET',
                path: '/permissions/suggested-actions',
                isProtected: true,
                permissionsRequired: ['permissions.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Actions suggérées', example: { success: true, code: 'ACTIONS_SUGGESTED', data: ['Créer', 'Afficher', 'Modifier', 'Supprimer', 'Geler', 'Dégeler', 'Bloquer', 'Débloquer'] } }
                ]
            },

            {
                id: 'permissions-list',
                module: 'permissions',
                name: 'Liste des permissions',
                description: 'Liste paginée des permissions, filtrable par groupe, statut, recherche.',
                method: 'GET',
                path: '/permissions',
                isProtected: true,
                permissionsRequired: ['permissions.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        permission_group_uuid: { type: 'uuid', required: false, description: 'Filtrer par groupe' },
                        status: { type: 'string', required: false, description: 'Filtrer par statut' },
                        search: { type: 'string', required: false, description: 'Recherche libre' },
                        per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des permissions', example: { success: true, code: 'PERMISSIONS_LISTED', data: [] } }
                ]
            },

            {
                id: 'permissions-show',
                module: 'permissions',
                name: 'Détails d\'une permission',
                description: 'Récupère une permission avec son groupe.',
                method: 'GET',
                path: '/permissions/{uuid_permission}',
                isProtected: true,
                permissionsRequired: ['permissions.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { path: { uuid_permission: { type: 'uuid', required: true, description: 'UUID de la permission' } } },
                responses: [
                    { status: 200, description: 'Détails de la permission', example: { success: true, code: 'PERMISSION_FOUND', data: {} } }
                ]
            },

            {
                id: 'permissions-create',
                module: 'permissions',
                name: 'Créer une permission',
                description: 'Crée une nouvelle permission. Le code est généré automatiquement (module.action).',
                method: 'POST',
                path: '/permissions',
                isProtected: true,
                permissionsRequired: ['permissions.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        permission_group_uuid: { type: 'uuid', required: true, description: 'UUID du groupe' },
                        libelle: { type: 'string', required: true, max: 100, description: 'Libellé (unique)' },
                        action: { type: 'string', required: true, max: 100, description: 'Action' },
                        description: { type: 'string', required: false, description: 'Description' },
                        category: { type: 'string', required: false, max: 100, description: 'Catégorie' }
                    }
                },
                exampleRequest: { permission_group_uuid: 'group-uuid', libelle: 'Créer un utilisateur', action: 'creer' },
                responses: [
                    { status: 201, description: 'Permission créée', example: { success: true, code: 'PERMISSION_CREATED', data: {} } }
                ]
            },

            {
                id: 'permissions-update',
                module: 'permissions',
                name: 'Modifier une permission',
                description: 'Met à jour une permission existante.',
                method: 'PUT',
                path: '/permissions/{uuid_permission}',
                isProtected: true,
                permissionsRequired: ['permissions.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_permission: { type: 'uuid', required: true, description: 'UUID de la permission' } },
                    body: {
                        permission_group_uuid: { type: 'uuid', required: true, description: 'UUID du groupe' },
                        libelle: { type: 'string', required: true, max: 100, description: 'Libellé' },
                        action: { type: 'string', required: true, max: 100, description: 'Action' },
                        description: { type: 'string', required: false, description: 'Description' },
                        category: { type: 'string', required: false, max: 100, description: 'Catégorie' }
                    }
                },
                exampleRequest: { permission_group_uuid: 'group-uuid', libelle: 'Créer un utilisateur (v2)', action: 'creer' },
                responses: [
                    { status: 200, description: 'Permission mise à jour', example: { success: true, code: 'PERMISSION_UPDATED', data: {} } }
                ]
            },

            {
                id: 'permissions-destroy',
                module: 'permissions',
                name: 'Supprimer une permission',
                description: 'Suppression logique d\'une permission, refusée si elle est encore attribuée à un rôle.',
                method: 'DELETE',
                path: '/permissions/{uuid_permission}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['permissions.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_permission: { type: 'uuid', required: true, description: 'UUID de la permission' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Permission supprimée', example: { success: true, message: 'Permission supprimée.', code: 'PERMISSION_DELETED' } },
                    { status: 422, description: 'Permission utilisée', example: { success: false, message: 'Cette permission est attribuée à un ou plusieurs rôles.', code: 'PERMISSION_IN_USE' } }
                ]
            },

            // ============================================================
            // 3.12 GROUPES DE PERMISSIONS
            // ============================================================
            {
                id: 'perm-groups-list',
                module: 'permGroups',
                name: 'Liste des groupes',
                description: 'Liste des groupes (modules) de permissions avec leurs permissions.',
                method: 'GET',
                path: '/permission-groups',
                isProtected: true,
                permissionsRequired: ['permission_groups.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        status: { type: 'string', required: false, description: 'Filtrer par statut' },
                        search: { type: 'string', required: false, description: 'Recherche libre' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des groupes', example: { success: true, code: 'PERMISSION_GROUPS_LISTED', data: [] } }
                ]
            },

            {
                id: 'perm-groups-show',
                module: 'permGroups',
                name: 'Détails d\'un groupe',
                description: 'Récupère un groupe avec toutes ses permissions.',
                method: 'GET',
                path: '/permission-groups/{uuid_permissionGroup}',
                isProtected: true,
                permissionsRequired: ['permission_groups.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { path: { uuid_permissionGroup: { type: 'uuid', required: true, description: 'UUID du groupe' } } },
                responses: [
                    { status: 200, description: 'Détails du groupe', example: { success: true, code: 'PERMISSION_GROUP_FOUND', data: {} } }
                ]
            },

            {
                id: 'perm-groups-create',
                module: 'permGroups',
                name: 'Créer un groupe',
                description: 'Crée un nouveau module/groupe de permissions.',
                method: 'POST',
                path: '/permission-groups',
                isProtected: true,
                permissionsRequired: ['permission_groups.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        libelle: { type: 'string', required: true, max: 100, description: 'Libellé (unique)' },
                        description: { type: 'string', required: false, description: 'Description' },
                        icone: { type: 'string', required: false, max: 100, description: 'Icône' },
                        color: { type: 'string', required: false, max: 50, description: 'Couleur' },
                        ordre_affichage: { type: 'integer', required: false, min: 0, description: 'Ordre d\'affichage' },
                        parent_uuid: { type: 'uuid', required: false, description: 'Groupe parent' },
                        route_prefix: { type: 'string', required: false, max: 100, description: 'Préfixe de route' }
                    }
                },
                exampleRequest: { libelle: 'Gestion des Sinistres', icone: 'fa-file-medical', ordre_affichage: 5 },
                responses: [
                    { status: 201, description: 'Groupe créé', example: { success: true, code: 'PERMISSION_GROUP_CREATED', data: {} } }
                ]
            },

            {
                id: 'perm-groups-update',
                module: 'permGroups',
                name: 'Modifier un groupe',
                description: 'Met à jour un groupe de permissions existant.',
                method: 'PUT',
                path: '/permission-groups/{uuid_permissionGroup}',
                isProtected: true,
                permissionsRequired: ['permission_groups.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_permissionGroup: { type: 'uuid', required: true, description: 'UUID du groupe' } },
                    body: {
                        libelle: { type: 'string', required: true, max: 100, description: 'Libellé' },
                        description: { type: 'string', required: false, description: 'Description' },
                        icone: { type: 'string', required: false, max: 100, description: 'Icône' },
                        color: { type: 'string', required: false, max: 50, description: 'Couleur' },
                        ordre_affichage: { type: 'integer', required: false, min: 0, description: 'Ordre d\'affichage' }
                    }
                },
                exampleRequest: { libelle: 'Gestion des Sinistres (v2)', color: '#F7A400' },
                responses: [
                    { status: 200, description: 'Groupe mis à jour', example: { success: true, code: 'PERMISSION_GROUP_UPDATED', data: {} } }
                ]
            },

            {
                id: 'perm-groups-destroy',
                module: 'permGroups',
                name: 'Supprimer un groupe',
                description: 'Suppression logique d\'un groupe, refusée s\'il contient encore des permissions.',
                method: 'DELETE',
                path: '/permission-groups/{uuid_permissionGroup}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['permission_groups.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_permissionGroup: { type: 'uuid', required: true, description: 'UUID du groupe' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Groupe supprimé', example: { success: true, message: 'Module supprimé.', code: 'PERMISSION_GROUP_DELETED' } },
                    { status: 422, description: 'Groupe non vide', example: { success: false, message: 'Le groupe contient des permissions.', code: 'PERMISSION_GROUP_NOT_EMPTY' } }
                ]
            },

            // ============================================================
            // 3.13 RESTRICTIONS IP
            // ============================================================
            {
                id: 'ip-restrictions-list',
                module: 'ip',
                name: 'Liste des restrictions IP',
                description: 'Récupère toutes les règles de restriction IP (whitelist/blacklist).',
                method: 'GET',
                path: '/ip-restrictions',
                isProtected: true,
                permissionsRequired: ['ip_restrictions.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Restrictions IP', example: { success: true, data: [{ ip_address: '10.0.0.5', type: 'blacklist' }] } }
                ]
            },

            {
                id: 'ip-restrictions-create',
                module: 'ip',
                name: 'Créer une restriction IP',
                description: 'Ajoute une règle de restriction IP (whitelist ou blacklist).',
                method: 'POST',
                path: '/ip-restrictions',
                isProtected: true,
                permissionsRequired: ['ip_restrictions.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        ip_address: { type: 'string', required: true, max: 45, description: 'Adresse IP' },
                        type: { type: 'string', required: true, enum: ['whitelist', 'blacklist'], description: 'Type' },
                        reason: { type: 'string', required: false, max: 255, description: 'Motif' },
                        expires_at: { type: 'date', required: false, description: 'Date d\'expiration' }
                    }
                },
                exampleRequest: { ip_address: '41.83.12.7', type: 'blacklist', reason: 'Tentatives de brute-force' },
                responses: [
                    { status: 201, description: 'Restriction créée', example: { success: true, data: {} } }
                ]
            },

            {
                id: 'ip-restrictions-destroy',
                module: 'ip',
                name: 'Supprimer une restriction IP',
                description: 'Supprime définitivement une règle de restriction IP.',
                method: 'DELETE',
                path: '/ip-restrictions/{uuid_restriction}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['ip_restrictions.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_restriction: { type: 'uuid', required: true, description: 'UUID de la restriction' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Restriction supprimée', example: { success: true, message: 'Restriction supprimée.' } }
                ]
            },

            // ============================================================
            // 3.14 LOGS & AUDIT
            // ============================================================
            {
                id: 'audit-my-activity',
                module: 'audit',
                name: 'Mes logs d\'activité',
                description: 'Récupère les logs d\'activité (ActivityLog) de l\'utilisateur connecté.',
                method: 'GET',
                path: '/audit/my-activity',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: { per_page: { type: 'integer', required: false, default: 10, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Logs d\'activité personnels', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'audit-my-activity-stats',
                module: 'audit',
                name: 'Mes statistiques d\'activité',
                description: 'Statistiques d\'activité (aujourd\'hui, cette semaine, ce mois, par action, par niveau).',
                method: 'GET',
                path: '/audit/my-activity/stats',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Statistiques', example: { success: true, data: { today: 4, this_week: 20, this_month: 87 } } }
                ]
            },

            {
                id: 'audit-all-logs',
                module: 'audit',
                name: '[Admin] Tous les logs',
                description: 'Liste paginée de tous les logs du système, avec filtres.',
                method: 'GET',
                path: '/audit/activity',
                isProtected: true,
                permissionsRequired: ['audit.consulter_les_logs'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        action: { type: 'string', required: false, description: 'Filtrer par action' },
                        level: { type: 'string', required: false, enum: ['info', 'warning', 'error', 'critical'], description: 'Niveau' },
                        module: { type: 'string', required: false, description: 'Filtrer par module' },
                        user_uuid: { type: 'uuid', required: false, description: 'Filtrer par utilisateur' },
                        per_page: { type: 'integer', required: false, default: 50, description: 'Nombre par page' }
                    }
                },
                responses: [
                    { status: 200, description: 'Tous les logs', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'audit-user-activity',
                module: 'audit',
                name: '[Admin] Logs d\'un utilisateur',
                description: 'Liste paginée des logs d\'activité d\'un utilisateur spécifique.',
                method: 'GET',
                path: '/audit/activity/user/{uuid_user}',
                isProtected: true,
                permissionsRequired: ['audit.consulter_les_logs'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' } },
                    query: { per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Logs de l\'utilisateur', example: { success: true, data: [] } },
                    { status: 403, description: 'Non autorisé', example: { success: false, message: 'Vous n\'avez pas le droit de consulter ces logs.' } }
                ]
            },

            {
                id: 'audit-freeze-logs',
                module: 'audit',
                name: '[Admin] Logs de gel/dégel',
                description: 'Historique paginé de tous les gels et dégels de comptes (table account_freezes).',
                method: 'GET',
                path: '/audit/freeze-logs',
                isProtected: true,
                permissionsRequired: ['audit.consulter_les_logs'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: { per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' } }
                },
                responses: [
                    { status: 200, description: 'Logs de gel/dégel', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'audit-stats',
                module: 'audit',
                name: '[Admin] Statistiques globales',
                description: 'Statistiques d\'activité globales du système.',
                method: 'GET',
                path: '/audit/stats',
                isProtected: true,
                permissionsRequired: ['audit.consulter_les_logs'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    { status: 200, description: 'Statistiques globales', example: { success: true, data: { today: 120, this_week: 640, this_month: 2500 } } }
                ]
            },

            // ============================================================
            // 3.15 PARTENAIRES
            // ============================================================
            {
                id: 'partners-list',
                module: 'partners',
                name: 'Liste des partenaires',
                description: 'Récupère la liste des partenaires avec filtres (statut, type, catégorie, recherche textuelle).',
                method: 'GET',
                path: '/partners',
                isProtected: true,
                permissionsRequired: ['partners.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif', 'suspendu'], description: 'Filtrer par statut' },
                        is_active: { type: 'boolean', required: false, description: 'Filtrer par statut actif/inactif' },
                        type: { type: 'string', required: false, description: 'Filtrer par type' },
                        categorie: { type: 'string', required: false, description: 'Filtrer par catégorie' },
                        code_branche: { type: 'string', required: false, description: 'Filtrer par code branche' },
                        search: { type: 'string', required: false, description: 'Recherche textuelle' },
                        not_expired: { type: 'boolean', required: false, description: 'Partenaires non expirés' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des partenaires', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'partners-create',
                module: 'partners',
                name: 'Créer un partenaire',
                description: 'Crée un nouveau partenaire avec toutes ses informations.',
                method: 'POST',
                path: '/partners',
                isProtected: true,
                permissionsRequired: ['partners.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        code: { type: 'string', required: true, max: 55, description: 'Code unique' },
                        designation: { type: 'string', required: true, max: 100, description: 'Nom' },
                        sigle: { type: 'string', required: false, max: 20, description: 'Sigle' },
                        description: { type: 'string', required: false, description: 'Description' },
                        logo: { type: 'string', required: false, max: 255, description: 'URL du logo' },
                        code_branche: { type: 'string', required: false, max: 35, description: 'Code branche' },
                        email: { type: 'email', required: false, max: 100, description: 'Email' },
                        email_2: { type: 'email', required: false, max: 100, description: 'Email secondaire' },
                        telephone: { type: 'string', required: false, max: 25, description: 'Téléphone' },
                        telephone_2: { type: 'string', required: false, max: 25, description: 'Téléphone secondaire' },
                        adresse: { type: 'string', required: false, max: 255, description: 'Adresse' },
                        ville: { type: 'string', required: false, max: 100, description: 'Ville' },
                        pays: { type: 'string', required: false, max: 100, description: 'Pays' },
                        site_web: { type: 'url', required: false, max: 255, description: 'Site web' },
                        latitude: { type: 'number', required: false, between: [-90, 90], description: 'Latitude' },
                        longitude: { type: 'number', required: false, between: [-180, 180], description: 'Longitude' },
                        type: { type: 'string', required: false, max: 50, description: 'Type de partenaire' },
                        secteur_activite: { type: 'string', required: false, max: 100, description: 'Secteur d\'activité' },
                        categorie: { type: 'string', required: false, max: 50, description: 'Catégorie' },
                        is_active: { type: 'boolean', required: false, default: true, description: 'Actif' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif', 'suspendu'], default: 'actif', description: 'Statut' },
                        date_agrement: { type: 'date', required: false, description: 'Date d\'agrément' },
                        date_expiration: { type: 'date', required: false, description: 'Date d\'expiration' }
                    }
                },
                exampleRequest: { code: 'PART001', designation: 'YAKO AFRICA Assurance', sigle: 'YAKO', email: 'contact@yako.ci', telephone: '+2252720304050', ville: 'Abidjan', pays: 'Côte d\'Ivoire', type: 'institutionnel', categorie: 'A' },
                responses: [
                    { status: 201, description: 'Partenaire créé', example: { success: true, message: 'Partenaire créé avec succès.', code: 'PARTNER_CREATED', data: {} } }
                ]
            },

            {
                id: 'partners-show',
                module: 'partners',
                name: 'Détails d\'un partenaire',
                description: 'Récupère les informations complètes d\'un partenaire avec ses réseaux et agences.',
                method: 'GET',
                path: '/partners/{uuid_partner}',
                isProtected: true,
                permissionsRequired: ['partners.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_partner: { type: 'uuid', required: true, description: 'UUID du partenaire' } }
                },
                responses: [
                    { status: 200, description: 'Détails du partenaire', example: { success: true, data: {} } }
                ]
            },

            {
                id: 'partners-update',
                module: 'partners',
                name: 'Mettre à jour un partenaire',
                description: 'Modifie les informations d\'un partenaire existant.',
                method: 'PUT',
                path: '/partners/{uuid_partner}',
                isProtected: true,
                permissionsRequired: ['partners.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_partner: { type: 'uuid', required: true, description: 'UUID du partenaire' } },
                    body: { /* Mêmes champs que la création, tous optionnels */ }
                },
                responses: [
                    { status: 200, description: 'Partenaire mis à jour', example: { success: true, message: 'Partenaire mis à jour avec succès.', code: 'PARTNER_UPDATED', data: {} } }
                ]
            },

            {
                id: 'partners-delete',
                module: 'partners',
                name: 'Supprimer un partenaire',
                description: 'Supprime un partenaire. Refusé s\'il a des réseaux associés.',
                method: 'DELETE',
                path: '/partners/{uuid_partner}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['partners.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_partner: { type: 'uuid', required: true, description: 'UUID du partenaire' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Partenaire supprimé', example: { success: true, message: 'Partenaire supprimé avec succès.', code: 'PARTNER_DELETED' } },
                    { status: 422, description: 'Partenaire a des réseaux', example: { success: false, message: 'Ce partenaire a des réseaux associés.', code: 'PARTNER_HAS_RESEAVX' } }
                ]
            },

            {
                id: 'partners-reseaux',
                module: 'partners',
                name: 'Réseaux d\'un partenaire',
                description: 'Récupère tous les réseaux associés à un partenaire.',
                method: 'GET',
                path: '/partners/{uuid_partner}/reseaux',
                isProtected: true,
                permissionsRequired: ['partners.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_partner: { type: 'uuid', required: true, description: 'UUID du partenaire' } }
                },
                responses: [
                    { status: 200, description: 'Réseaux du partenaire', example: { success: true, data: [] } }
                ]
            },

            // ============================================================
            // 3.16 RESEAUX
            // ============================================================
            {
                id: 'reseaux-list',
                module: 'reseaux',
                name: 'Liste des réseaux',
                description: 'Récupère la liste des réseaux avec filtres (statut, partenaire, recherche).',
                method: 'GET',
                path: '/reseaux',
                isProtected: true,
                permissionsRequired: ['reseaux.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], description: 'Filtrer par statut' },
                        partner_uuid: { type: 'uuid', required: false, description: 'Filtrer par partenaire' },
                        search: { type: 'string', required: false, description: 'Recherche textuelle' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des réseaux', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'reseaux-create',
                module: 'reseaux',
                name: 'Créer un réseau',
                description: 'Crée un nouveau réseau pour un partenaire.',
                method: 'POST',
                path: '/reseaux',
                isProtected: true,
                permissionsRequired: ['reseaux.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        code: { type: 'string', required: true, max: 55, description: 'Code unique' },
                        libelle: { type: 'string', required: true, max: 255, description: 'Nom du réseau' },
                        description: { type: 'string', required: false, description: 'Description' },
                        partner_uuid: { type: 'uuid', required: false, description: 'UUID du partenaire' },
                        email: { type: 'email', required: false, max: 100, description: 'Email' },
                        telephone: { type: 'string', required: false, max: 25, description: 'Téléphone' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], default: 'actif', description: 'Statut' }
                    }
                },
                exampleRequest: { code: 'RES001', libelle: 'Réseau Abidjan', partner_uuid: 'partner-uuid', email: 'abidjan@yako.ci' },
                responses: [
                    { status: 201, description: 'Réseau créé', example: { success: true, message: 'Réseau créé avec succès.', code: 'RESEAU_CREATED', data: {} } }
                ]
            },

            {
                id: 'reseaux-show',
                module: 'reseaux',
                name: 'Détails d\'un réseau',
                description: 'Récupère les informations complètes d\'un réseau avec ses agences.',
                method: 'GET',
                path: '/reseaux/{uuid_reseau}',
                isProtected: true,
                permissionsRequired: ['reseaux.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_reseau: { type: 'uuid', required: true, description: 'UUID du réseau' } }
                },
                responses: [
                    { status: 200, description: 'Détails du réseau', example: { success: true, data: {} } }
                ]
            },

            {
                id: 'reseaux-update',
                module: 'reseaux',
                name: 'Mettre à jour un réseau',
                description: 'Modifie les informations d\'un réseau existant.',
                method: 'PUT',
                path: '/reseaux/{uuid_reseau}',
                isProtected: true,
                permissionsRequired: ['reseaux.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_reseau: { type: 'uuid', required: true, description: 'UUID du réseau' } },
                    body: { /* Mêmes champs que la création, tous optionnels */ }
                },
                responses: [
                    { status: 200, description: 'Réseau mis à jour', example: { success: true, message: 'Réseau mis à jour avec succès.', code: 'RESEAU_UPDATED', data: {} } }
                ]
            },

            {
                id: 'reseaux-delete',
                module: 'reseaux',
                name: 'Supprimer un réseau',
                description: 'Supprime un réseau. Refusé s\'il a des agences associées.',
                method: 'DELETE',
                path: '/reseaux/{uuid_reseau}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['reseaux.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_reseau: { type: 'uuid', required: true, description: 'UUID du réseau' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Réseau supprimé', example: { success: true, message: 'Réseau supprimé avec succès.', code: 'RESEAU_DELETED' } },
                    { status: 422, description: 'Réseau a des agences', example: { success: false, message: 'Ce réseau a des agences associées.', code: 'RESEAU_HAS_AGENCES' } }
                ]
            },

            {
                id: 'reseaux-agences',
                module: 'reseaux',
                name: 'Agences d\'un réseau',
                description: 'Récupère toutes les agences associées à un réseau.',
                method: 'GET',
                path: '/reseaux/{uuid_reseau}/agences',
                isProtected: true,
                permissionsRequired: ['reseaux.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_reseau: { type: 'uuid', required: true, description: 'UUID du réseau' } }
                },
                responses: [
                    { status: 200, description: 'Agences du réseau', example: { success: true, data: [] } }
                ]
            },

            // ============================================================
            // 3.17 AGENCES
            // ============================================================
            {
                id: 'agences-list',
                module: 'agences',
                name: 'Liste des agences',
                description: 'Récupère la liste des agences avec filtres (ville, quartier, statut, réseau, recherche textuelle, agences ouvertes, géolocalisation).',
                method: 'GET',
                path: '/agences',
                isProtected: true,
                permissionsRequired: ['agences.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        per_page: { type: 'integer', required: false, default: 20, description: 'Nombre par page' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], description: 'Filtrer par statut' },
                        reseau_uuid: { type: 'uuid', required: false, description: 'Filtrer par réseau' },
                        ville: { type: 'string', required: false, description: 'Filtrer par ville' },
                        quartier: { type: 'string', required: false, description: 'Filtrer par quartier' },
                        search: { type: 'string', required: false, description: 'Recherche textuelle (libellé, description, adresse)' },
                        open_now: { type: 'boolean', required: false, description: 'Filtrer les agences ouvertes actuellement' },
                        latitude: { type: 'number', required: false, description: 'Latitude pour recherche à proximité' },
                        longitude: { type: 'number', required: false, description: 'Longitude pour recherche à proximité' },
                        radius: { type: 'number', required: false, default: 10, description: 'Rayon en kilomètres (1-100)' }
                    }
                },
                responses: [
                    { status: 200, description: 'Liste des agences', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'agences-create',
                module: 'agences',
                name: 'Créer une agence',
                description: 'Crée une nouvelle agence avec ses informations (contact, horaires, géolocalisation).',
                method: 'POST',
                path: '/agences',
                isProtected: true,
                permissionsRequired: ['agences.creer'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    body: {
                        code: { type: 'string', required: true, max: 55, description: 'Code unique de l\'agence' },
                        libelle: { type: 'string', required: true, max: 255, description: 'Nom de l\'agence' },
                        description: { type: 'string', required: false, description: 'Description' },
                        reseau_uuid: { type: 'uuid', required: false, description: 'UUID du réseau' },
                        email: { type: 'email', required: false, max: 100, description: 'Email' },
                        telephone: { type: 'string', required: false, max: 25, description: 'Téléphone principal' },
                        telephone_2: { type: 'string', required: false, max: 25, description: 'Téléphone secondaire' },
                        adresse: { type: 'string', required: false, max: 255, description: 'Adresse' },
                        ville: { type: 'string', required: false, max: 100, description: 'Ville' },
                        quartier: { type: 'string', required: false, max: 100, description: 'Quartier' },
                        code_postal: { type: 'string', required: false, max: 20, description: 'Code postal' },
                        pays: { type: 'string', required: false, max: 100, default: 'Côte d\'Ivoire', description: 'Pays' },
                        latitude: { type: 'number', required: false, between: [-90, 90], description: 'Latitude' },
                        longitude: { type: 'number', required: false, between: [-180, 180], description: 'Longitude' },
                        photo: { type: 'string', required: false, max: 255, description: 'Photo principale' },
                        photos: { type: 'array', required: false, description: 'Galerie photos' },
                        responsable: { type: 'string', required: false, max: 255, description: 'Nom du responsable' },
                        site_web: { type: 'url', required: false, max: 255, description: 'Site web' },
                        status: { type: 'string', required: false, enum: ['actif', 'inactif'], default: 'actif', description: 'Statut' },
                        horaires: { type: 'array', required: false, description: 'Horaires d\'ouverture par jour' },
                        'horaires.*.jour': { type: 'string', enum: ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'], description: 'Jour de la semaine' },
                        'horaires.*.heure_ouverture': { type: 'string', format: 'H:i', description: 'Heure d\'ouverture' },
                        'horaires.*.heure_fermeture': { type: 'string', format: 'H:i', description: 'Heure de fermeture' },
                        'horaires.*.heure_ouverture_midi': { type: 'string', format: 'H:i', description: 'Heure d\'ouverture après la pause midi' },
                        'horaires.*.heure_fermeture_midi': { type: 'string', format: 'H:i', description: 'Heure de fermeture pour la pause midi' },
                        'horaires.*.ferme': { type: 'boolean', default: false, description: 'Fermé ce jour' },
                        'horaires.*.commentaire': { type: 'string', required: false, max: 255, description: 'Commentaire' }
                    }
                },
                exampleRequest: {
                    code: 'AG001',
                    libelle: 'YAKO Plateau',
                    email: 'plateau@yako.ci',
                    telephone: '+2252720304050',
                    adresse: 'Av. Chardy, Imm. Alpha 2000',
                    ville: 'Abidjan',
                    quartier: 'Plateau',
                    latitude: 5.3364,
                    longitude: -4.0271,
                    horaires: [
                        { jour: 'lundi', heure_ouverture: '08:00', heure_fermeture: '17:30' },
                        { jour: 'samedi', heure_ouverture: '08:00', heure_fermeture: '12:00' },
                        { jour: 'dimanche', ferme: true }
                    ]
                },
                responses: [
                    { status: 201, description: 'Agence créée', example: { success: true, message: 'Agence créée avec succès.', code: 'AGENCE_CREATED', data: {} } },
                    { status: 422, description: 'Erreur de validation', example: { success: false, message: 'Données invalides.', errors: {} } }
                ]
            },

            {
                id: 'agences-show',
                module: 'agences',
                name: 'Détails d\'une agence',
                description: 'Récupère les informations complètes d\'une agence (avec les horaires, le réseau, les utilisateurs).',
                method: 'GET',
                path: '/agences/{uuid_agence}',
                isProtected: true,
                permissionsRequired: ['agences.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' } }
                },
                responses: [
                    { status: 200, description: 'Détails de l\'agence', example: { success: true, data: {} } },
                    { status: 404, description: 'Agence non trouvée' }
                ]
            },

            {
                id: 'agences-update',
                module: 'agences',
                name: 'Mettre à jour une agence',
                description: 'Modifie les informations d\'une agence existante.',
                method: 'PUT',
                path: '/agences/{uuid_agence}',
                isProtected: true,
                permissionsRequired: ['agences.modifier'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' } },
                    body: { /* Mêmes champs que la création, tous optionnels */ }
                },
                responses: [
                    { status: 200, description: 'Agence mise à jour', example: { success: true, message: 'Agence mise à jour avec succès.', code: 'AGENCE_UPDATED', data: {} } }
                ]
            },

            {
                id: 'agences-delete',
                module: 'agences',
                name: 'Supprimer une agence',
                description: 'Supprime une agence (soft delete). Refusée si des utilisateurs sont associés.',
                method: 'DELETE',
                path: '/agences/{uuid_agence}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['agences.supprimer'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' } },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Agence supprimée', example: { success: true, message: 'Agence supprimée avec succès.', code: 'AGENCE_DELETED' } },
                    { status: 422, description: 'Agence associée à des utilisateurs', example: { success: false, message: 'Cette agence est associée à des utilisateurs.', code: 'AGENCE_HAS_USERS' } }
                ]
            },

            {
                id: 'agences-nearby',
                module: 'agences',
                name: 'Agences à proximité',
                description: 'Récupère les agences les plus proches d\'une position géographique donnée.',
                method: 'GET',
                path: '/agences/nearby',
                isProtected: true,
                permissionsRequired: ['agences.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        latitude: { type: 'number', required: true, between: [-90, 90], description: 'Latitude' },
                        longitude: { type: 'number', required: true, between: [-180, 180], description: 'Longitude' },
                        radius: { type: 'number', required: false, min: 1, max: 100, default: 10, description: 'Rayon en kilomètres' },
                        limit: { type: 'integer', required: false, min: 1, max: 50, default: 20, description: 'Nombre d\'agences' }
                    }
                },
                exampleRequest: { latitude: 5.3364, longitude: -4.0271, radius: 10, limit: 10 },
                responses: [
                    { status: 200, description: 'Agences à proximité', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'agences-horaires',
                module: 'agences',
                name: 'Horaires d\'une agence',
                description: 'Récupère les horaires d\'ouverture d\'une agence.',
                method: 'GET',
                path: '/agences/{uuid_agence}/horaires',
                isProtected: true,
                permissionsRequired: ['agences.afficher'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' } }
                },
                responses: [
                    { status: 200, description: 'Horaires de l\'agence', example: { success: true, data: [] } }
                ]
            },

            {
                id: 'agences-assign-users',
                module: 'agences',
                name: 'Assigner des utilisateurs',
                description: 'Assigne un ou plusieurs utilisateurs à une agence.',
                method: 'POST',
                path: '/agences/{uuid_agence}/users',
                isProtected: true,
                permissionsRequired: ['agences.assigner_utilisateurs'],
                headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                requestParams: {
                    path: { uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' } },
                    body: {
                        user_uuids: { type: 'array', required: true, min: 1, description: 'UUIDs des utilisateurs' },
                        is_primary: { type: 'boolean', required: false, default: false, description: 'Utilisateur principal' }
                    }
                },
                exampleRequest: { user_uuids: ['uuid1', 'uuid2'], is_primary: true },
                responses: [
                    { status: 200, description: 'Utilisateurs assignés', example: { success: true, message: 'Utilisateurs assignés avec succès.', code: 'USERS_ASSIGNED' } }
                ]
            },

            {
                id: 'agences-remove-user',
                module: 'agences',
                name: 'Retirer un utilisateur',
                description: 'Retire un utilisateur d\'une agence.',
                method: 'DELETE',
                path: '/agences/{uuid_agence}/users/{uuid_user}',
                isProtected: true,
                permissionsRequired: ['agences.assigner_utilisateurs'],
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: {
                        uuid_agence: { type: 'uuid', required: true, description: 'UUID de l\'agence' },
                        uuid_user: { type: 'uuid', required: true, description: 'UUID de l\'utilisateur' }
                    },
                    body: {}
                },
                responses: [
                    { status: 200, description: 'Utilisateur retiré', example: { success: true, message: 'Utilisateur retiré de l\'agence avec succès.', code: 'USER_REMOVED' } }
                ]
            },

            // ============================================================
            // FAQ - Public
            // ============================================================

            {
                id: 'faq-list',
                module: 'faq',
                name: 'Liste des FAQs',
                description: 'Récupère la liste des FAQs actives avec possibilité de filtrage par catégorie, recherche textuelle, et tri. Accessible publiquement sans authentification.',
                method: 'GET',
                path: '/faq',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        faq_category_uuid: { 
                            type: 'uuid', 
                            required: false, 
                            description: 'Filtrer par catégorie (UUID de la catégorie)' 
                        },
                        category: { 
                            type: 'string', 
                            required: false, 
                            enum: ['compte', 'souscription', 'paiement', 'sinistre', 'securite', 'assistance', 'rendez-vous'], 
                            description: 'Filtrer par catégorie (legacy)' 
                        },
                        search: { 
                            type: 'string', 
                            required: false, 
                            description: 'Recherche textuelle dans les questions, réponses et tags' 
                        },
                        is_featured: { 
                            type: 'boolean', 
                            required: false, 
                            description: 'Filtrer les FAQs en vedette' 
                        },
                        per_page: { 
                            type: 'integer', 
                            required: false, 
                            default: 20, 
                            description: 'Nombre d\'éléments par page' 
                        }
                    }
                },
                exampleRequest: {
                    category: 'compte',
                    per_page: 10
                },
                responses: [
                    {
                        status: 200,
                        description: 'Liste des FAQs récupérée avec succès',
                        example: {
                            success: true,
                            message: 'Liste des FAQs récupérée.',
                            code: 'FAQS_LISTED',
                            data: [
                                {
                                    uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                    faq_category: {
                                        uuid: '550e8400-e29b-41d4-a716-446655440001',
                                        code: 'compte',
                                        label: 'Compte & connexion',
                                        icon: 'bi-person-circle',
                                        color: '#3490dc'
                                    },
                                    category: 'compte',
                                    category_label: 'Compte & connexion',
                                    question: 'Comment créer un compte sur YNOV ?',
                                    answer: '<p>Pour créer votre compte YNOV, suivez ces étapes...</p>',
                                    order: 1,
                                    is_active: true,
                                    is_featured: true,
                                    tags: ['inscription', 'compte', 'création'],
                                    views: 150,
                                    created_at: '2025-01-15T10:00:00.000000Z',
                                    updated_at: '2025-01-15T14:30:00.000000Z'
                                }
                            ],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 5,
                                last_page: 1
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-search',
                module: 'faq',
                name: 'Rechercher dans les FAQs',
                description: 'Effectue une recherche textuelle dans les questions, réponses et tags des FAQs actives. Accessible publiquement.',
                method: 'GET',
                path: '/faq/search',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        q: { 
                            type: 'string', 
                            required: true, 
                            min: 2, 
                            description: 'Terme de recherche (minimum 2 caractères)' 
                        }
                    }
                },
                exampleRequest: {
                    q: 'mot de passe oublié'
                },
                responses: [
                    {
                        status: 200,
                        description: 'Résultats de recherche',
                        example: {
                            success: true,
                            message: 'Résultats de recherche.',
                            code: 'FAQ_SEARCH_RESULTS',
                            data: [
                                {
                                    uuid_faq: '...',
                                    question: 'J\'ai oublié mon mot de passe. Que faire ?',
                                    answer: '<p>Si vous avez oublié votre mot de passe...</p>',
                                    views: 89
                                }
                            ]
                        }
                    },
                    {
                        status: 422,
                        description: 'Terme de recherche trop court',
                        example: {
                            success: false,
                            message: 'Le terme de recherche doit contenir au moins 2 caractères.',
                            errors: {
                                q: ['Le champ q doit contenir au moins 2 caractères.']
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-categories-list',
                module: 'faq',
                name: 'Liste des catégories de FAQ',
                description: 'Récupère toutes les catégories de FAQ avec leurs compteurs de questions. Accessible publiquement.',
                method: 'GET',
                path: '/faq/categories',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        only_active: { 
                            type: 'boolean', 
                            required: false, 
                            default: true, 
                            description: 'Récupérer uniquement les catégories actives' 
                        }
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégories récupérées avec succès',
                        example: {
                            success: true,
                            message: 'Catégories de FAQs récupérées.',
                            code: 'FAQ_CATEGORIES_LISTED',
                            data: [
                                {
                                    uuid: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'compte',
                                    label: 'Compte & connexion',
                                    icon: 'bi-person-circle',
                                    color: '#3490dc',
                                    description: 'Questions relatives à la création de compte, connexion, gestion du profil.',
                                    count: 5,
                                    is_default: true,
                                    is_active: true
                                },
                                {
                                    uuid: '550e8400-e29b-41d4-a716-446655440002',
                                    code: 'souscription',
                                    label: 'Souscription & contrats',
                                    icon: 'bi-file-earmark-text',
                                    color: '#2ecc71',
                                    description: 'Questions sur les souscriptions, les contrats et les garanties.',
                                    count: 8,
                                    is_default: true,
                                    is_active: true
                                }
                            ]
                        }
                    }
                ]
            },

            {
                id: 'faq-show',
                module: 'faq',
                name: 'Détails d\'une FAQ',
                description: 'Récupère les détails d\'une FAQ spécifique. Incrémente automatiquement le compteur de vues à chaque consultation.',
                method: 'GET',
                path: '/faq/{uuid_faq}',
                isProtected: false,
                headers: { 'Accept': 'application/json' },
                requestParams: {
                    path: { 
                        uuid_faq: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la FAQ à consulter' 
                        } 
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'Détails de la FAQ récupérés avec succès',
                        example: {
                            success: true,
                            message: 'Détails de la FAQ.',
                            code: 'FAQ_FOUND',
                            data: {
                                uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                faq_category: {
                                    uuid: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'compte',
                                    label: 'Compte & connexion',
                                    icon: 'bi-person-circle',
                                    color: '#3490dc'
                                },
                                category: 'compte',
                                category_label: 'Compte & connexion',
                                question: 'Comment créer un compte sur YNOV ?',
                                answer: '<p>Pour créer votre compte YNOV, suivez ces étapes :</p><ol><li>Rendez-vous sur la page d\'inscription</li><li>Remplissez le formulaire...</li></ol>',
                                order: 1,
                                is_active: true,
                                is_featured: true,
                                tags: ['inscription', 'compte', 'création'],
                                views: 151,
                                created_at: '2025-01-15T10:00:00.000000Z',
                                updated_at: '2025-01-15T14:30:00.000000Z'
                            }
                        }
                    },
                    {
                        status: 404,
                        description: 'FAQ non trouvée',
                        example: {
                            success: false,
                            message: 'FAQ non trouvée.'
                        }
                    }
                ]
            },

            // ============================================================
            // 2. FAQ ADMIN - GESTION DES FAQs
            // ============================================================

            {
                id: 'faq-create-admin',
                module: 'faq',
                name: '[Admin] Créer une FAQ',
                description: 'Crée une nouvelle question/réponse pour la FAQ. Nécessite la permission `faqs.creer`.',
                method: 'POST',
                path: '/admin/faq',
                isProtected: true,
                permissionsRequired: ['faqs.creer'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    body: {
                        faq_category_uuid: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie (exists:faq_categories,uuid_faq_category)' 
                        },
                        category: { 
                            type: 'string', 
                            required: false, 
                            max: 50, 
                            description: 'Catégorie (legacy - optionnel)' 
                        },
                        category_label: { 
                            type: 'string', 
                            required: false, 
                            max: 100, 
                            description: 'Libellé personnalisé de la catégorie' 
                        },
                        question: { 
                            type: 'string', 
                            required: true, 
                            max: 255, 
                            description: 'Question (titre de la FAQ)' 
                        },
                        answer: { 
                            type: 'string', 
                            required: true, 
                            description: 'Réponse (support HTML)' 
                        },
                        order: { 
                            type: 'integer', 
                            required: false, 
                            min: 0, 
                            default: 0, 
                            description: 'Ordre d\'affichage' 
                        },
                        is_active: { 
                            type: 'boolean', 
                            required: false, 
                            default: true, 
                            description: 'FAQ active (visible publiquement)' 
                        },
                        is_featured: { 
                            type: 'boolean', 
                            required: false, 
                            default: false, 
                            description: 'Mettre en avant dans la section "Questions fréquentes"' 
                        },
                        tags: { 
                            type: 'array', 
                            required: false, 
                            description: 'Tags pour la recherche' 
                        },
                        'tags.*': { 
                            type: 'string', 
                            max: 50, 
                            description: 'Tag individuel' 
                        }
                    }
                },
                exampleRequest: {
                    faq_category_uuid: '550e8400-e29b-41d4-a716-446655440001',
                    question: 'Comment créer un compte sur YNOV ?',
                    answer: '<p>Pour créer votre compte YNOV, suivez ces étapes :</p><ol><li>Rendez-vous sur la page d\'inscription</li><li>Remplissez le formulaire...</li></ol>',
                    order: 1,
                    is_active: true,
                    is_featured: true,
                    tags: ['inscription', 'compte', 'création']
                },
                responses: [
                    {
                        status: 201,
                        description: 'FAQ créée avec succès',
                        example: {
                            success: true,
                            message: 'FAQ créée avec succès.',
                            code: 'FAQ_CREATED',
                            data: {
                                uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                faq_category: {
                                    uuid: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'compte',
                                    label: 'Compte & connexion'
                                },
                                question: 'Comment créer un compte sur YNOV ?',
                                is_active: true,
                                is_featured: true
                            }
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Données invalides.',
                            errors: {
                                faq_category_uuid: ['La catégorie est obligatoire.'],
                                question: ['La question est obligatoire.'],
                                answer: ['La réponse est obligatoire.']
                            }
                        }
                    },
                    {
                        status: 403,
                        description: 'Permission manquante',
                        example: {
                            success: false,
                            message: 'Vous n\'avez pas la permission nécessaire pour effectuer cette action.',
                            code: 'PERMISSION_DENIED'
                        }
                    }
                ]
            },

            {
                id: 'faq-update-admin',
                module: 'faq',
                name: '[Admin] Mettre à jour une FAQ',
                description: 'Modifie une FAQ existante. Nécessite la permission `faqs.modifier`.',
                method: 'PUT',
                path: '/admin/faq/{uuid_faq}',
                isProtected: true,
                permissionsRequired: ['faqs.modifier'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la FAQ à modifier' 
                        } 
                    },
                    body: {
                        faq_category_uuid: { 
                            type: 'uuid', 
                            required: false, 
                            description: 'UUID de la catégorie' 
                        },
                        category: { 
                            type: 'string', 
                            required: false, 
                            max: 50, 
                            description: 'Catégorie (legacy)' 
                        },
                        category_label: { 
                            type: 'string', 
                            required: false, 
                            max: 100, 
                            description: 'Libellé personnalisé' 
                        },
                        question: { 
                            type: 'string', 
                            required: false, 
                            max: 255, 
                            description: 'Question' 
                        },
                        answer: { 
                            type: 'string', 
                            required: false, 
                            description: 'Réponse' 
                        },
                        order: { 
                            type: 'integer', 
                            required: false, 
                            min: 0, 
                            description: 'Ordre d\'affichage' 
                        },
                        is_active: { 
                            type: 'boolean', 
                            required: false, 
                            description: 'FAQ active' 
                        },
                        is_featured: { 
                            type: 'boolean', 
                            required: false, 
                            description: 'Mettre en avant' 
                        },
                        tags: { 
                            type: 'array', 
                            required: false, 
                            description: 'Tags pour la recherche' 
                        }
                    }
                },
                exampleRequest: {
                    question: 'Comment créer un compte sur YNOV ? (mis à jour)',
                    answer: '<p>Pour créer votre compte YNOV, suivez ces étapes mises à jour...</p>',
                    is_featured: true
                },
                responses: [
                    {
                        status: 200,
                        description: 'FAQ mise à jour avec succès',
                        example: {
                            success: true,
                            message: 'FAQ mise à jour avec succès.',
                            code: 'FAQ_UPDATED',
                            data: {}
                        }
                    },
                    {
                        status: 404,
                        description: 'FAQ non trouvée',
                        example: {
                            success: false,
                            message: 'FAQ non trouvée.'
                        }
                    }
                ]
            },

            {
                id: 'faq-delete-admin',
                module: 'faq',
                name: '[Admin] Supprimer une FAQ',
                description: 'Supprime une FAQ (soft delete). Nécessite la permission `faqs.supprimer`.',
                method: 'DELETE',
                path: '/admin/faq/{uuid_faq}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['faqs.supprimer'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la FAQ à supprimer' 
                        } 
                    },
                    body: {}
                },
                responses: [
                    {
                        status: 200,
                        description: 'FAQ supprimée avec succès',
                        example: {
                            success: true,
                            message: 'FAQ supprimée avec succès.',
                            code: 'FAQ_DELETED'
                        }
                    },
                    {
                        status: 404,
                        description: 'FAQ non trouvée',
                        example: {
                            success: false,
                            message: 'FAQ non trouvée.'
                        }
                    }
                ]
            },

            {
                id: 'faq-toggle-admin',
                module: 'faq',
                name: '[Admin] Activer/Désactiver une FAQ',
                description: 'Active ou désactive une FAQ. Les FAQs désactivées ne sont pas visibles publiquement. Nécessite la permission `faqs.modifier`.',
                method: 'POST',
                path: '/admin/faq/{uuid_faq}/toggle',
                isProtected: true,
                permissionsRequired: ['faqs.modifier'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la FAQ' 
                        } 
                    },
                    body: {}
                },
                responses: [
                    {
                        status: 200,
                        description: 'FAQ activée',
                        example: {
                            success: true,
                            message: 'FAQ activée.',
                            code: 'FAQ_TOGGLED',
                            data: {
                                uuid_faq: '...',
                                is_active: true
                            }
                        }
                    },
                    {
                        status: 200,
                        description: 'FAQ désactivée',
                        example: {
                            success: true,
                            message: 'FAQ désactivée.',
                            code: 'FAQ_TOGGLED',
                            data: {
                                uuid_faq: '...',
                                is_active: false
                            }
                        }
                    }
                ]
            },

            // ============================================================
            // 3. FAQ ADMIN - GESTION DES CATÉGORIES
            // ============================================================

            {
                id: 'faq-category-create-admin',
                module: 'faq',
                name: '[Admin] Créer une catégorie de FAQ',
                description: 'Crée une nouvelle catégorie de FAQ personnalisée. Nécessite la permission `faq_categories.creer`. Les catégories par défaut sont protégées.',
                method: 'POST',
                path: '/admin/faq/categories',
                isProtected: true,
                permissionsRequired: ['faq_categories.creer'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    body: {
                        label: { 
                            type: 'string', 
                            required: true, 
                            max: 100, 
                            description: 'Libellé de la catégorie' 
                        },
                        code: { 
                            type: 'string', 
                            required: false, 
                            max: 50, 
                            description: 'Code unique (généré automatiquement si non fourni)' 
                        },
                        icon: { 
                            type: 'string', 
                            required: false, 
                            max: 50, 
                            description: 'Icône (Bootstrap Icons, FontAwesome)' 
                        },
                        color: { 
                            type: 'string', 
                            required: false, 
                            max: 20, 
                            description: 'Couleur (hexadécimal ou nom CSS)' 
                        },
                        description: { 
                            type: 'string', 
                            required: false, 
                            max: 500, 
                            description: 'Description de la catégorie' 
                        },
                        order: { 
                            type: 'integer', 
                            required: false, 
                            min: 0, 
                            description: 'Ordre d\'affichage (auto si non fourni)' 
                        },
                        is_active: { 
                            type: 'boolean', 
                            required: false, 
                            default: true, 
                            description: 'Catégorie active' 
                        },
                        metadata: { 
                            type: 'object', 
                            required: false, 
                            description: 'Métadonnées supplémentaires' 
                        }
                    }
                },
                exampleRequest: {
                    label: 'Questions générales',
                    icon: 'bi-question-circle',
                    color: '#6c757d',
                    description: 'Questions générales sur la plateforme',
                    order: 8,
                    is_active: true
                },
                responses: [
                    {
                        status: 201,
                        description: 'Catégorie créée avec succès',
                        example: {
                            success: true,
                            message: 'Catégorie créée avec succès.',
                            code: 'FAQ_CATEGORY_CREATED',
                            data: {
                                uuid_faq_category: '...',
                                code: 'questions_generales',
                                label: 'Questions générales',
                                icon: 'bi-question-circle',
                                color: '#6c757d',
                                is_active: true,
                                is_default: false
                            }
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            errors: {
                                label: ['Le libellé est obligatoire.'],
                                code: ['Ce code est déjà utilisé.']
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-update-admin',
                module: 'faq',
                name: '[Admin] Mettre à jour une catégorie de FAQ',
                description: 'Modifie une catégorie de FAQ existante. Les catégories par défaut ne peuvent pas être modifiées. Nécessite la permission `faq_categories.modifier`.',
                method: 'PUT',
                path: '/admin/faq/categories/{uuid_faq_category}',
                isProtected: true,
                permissionsRequired: ['faq_categories.modifier'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq_category: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie à modifier' 
                        } 
                    },
                    body: {
                        label: { 
                            type: 'string', 
                            required: false, 
                            max: 100, 
                            description: 'Libellé' 
                        },
                        icon: { 
                            type: 'string', 
                            required: false, 
                            max: 50, 
                            description: 'Icône' 
                        },
                        color: { 
                            type: 'string', 
                            required: false, 
                            max: 20, 
                            description: 'Couleur' 
                        },
                        description: { 
                            type: 'string', 
                            required: false, 
                            max: 500, 
                            description: 'Description' 
                        },
                        order: { 
                            type: 'integer', 
                            required: false, 
                            min: 0, 
                            description: 'Ordre d\'affichage' 
                        },
                        is_active: { 
                            type: 'boolean', 
                            required: false, 
                            description: 'Catégorie active' 
                        },
                        metadata: { 
                            type: 'object', 
                            required: false, 
                            description: 'Métadonnées' 
                        }
                    }
                },
                exampleRequest: {
                    label: 'Questions générales (mise à jour)',
                    icon: 'bi-question-circle-fill',
                    color: '#6c757d',
                    description: 'Questions générales mises à jour',
                    is_active: true
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégorie mise à jour avec succès',
                        example: {
                            success: true,
                            message: 'Catégorie mise à jour avec succès.',
                            code: 'FAQ_CATEGORY_UPDATED',
                            data: {}
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            errors: {
                                category: ['Les catégories par défaut ne peuvent pas être modifiées.']
                            }
                        }
                    },
                    {
                        status: 404,
                        description: 'Catégorie non trouvée',
                        example: {
                            success: false,
                            message: 'Catégorie non trouvée.',
                            code: 'FAQ_CATEGORY_NOT_FOUND'
                        }
                    }
                ]
            },

            {
                id: 'faq-category-delete-admin',
                module: 'faq',
                name: '[Admin] Supprimer une catégorie de FAQ',
                description: 'Supprime une catégorie de FAQ. Les catégories par défaut ne peuvent pas être supprimées. Une catégorie contenant des FAQs ne peut pas être supprimée. Nécessite la permission `faq_categories.supprimer`.',
                method: 'DELETE',
                path: '/admin/faq/categories/{uuid_faq_category}',
                isProtected: true,
                isDestructive: true,
                permissionsRequired: ['faq_categories.supprimer'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq_category: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie à supprimer' 
                        } 
                    },
                    body: {}
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégorie supprimée avec succès',
                        example: {
                            success: true,
                            message: 'Catégorie supprimée avec succès.',
                            code: 'FAQ_CATEGORY_DELETED'
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            errors: {
                                category: ['Les catégories par défaut ne peuvent pas être supprimées.']
                            }
                        }
                    },
                    {
                        status: 422,
                        description: 'Catégorie contient des FAQs',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            errors: {
                                category: ['Cette catégorie contient des FAQs et ne peut pas être supprimée.']
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-toggle-admin',
                module: 'faq',
                name: '[Admin] Activer/Désactiver une catégorie de FAQ',
                description: 'Active ou désactive une catégorie de FAQ. Les catégories désactivées ne sont pas affichées publiquement. Nécessite la permission `faq_categories.modifier`.',
                method: 'POST',
                path: '/admin/faq/categories/{uuid_faq_category}/toggle',
                isProtected: true,
                permissionsRequired: ['faq_categories.modifier'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq_category: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie' 
                        } 
                    },
                    body: {}
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégorie activée/désactivée',
                        example: {
                            success: true,
                            message: 'Catégorie activée avec succès.',
                            code: 'FAQ_CATEGORY_TOGGLED',
                            data: {
                                uuid_faq_category: '...',
                                is_active: true
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-reorder-admin',
                module: 'faq',
                name: '[Admin] Réordonner les catégories',
                description: 'Réordonne les catégories de FAQ selon l\'ordre souhaité. Nécessite la permission `faq_categories.modifier`.',
                method: 'POST',
                path: '/admin/faq/categories/reorder',
                isProtected: true,
                permissionsRequired: ['faq_categories.modifier'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    body: {
                        uuids: { 
                            type: 'array', 
                            required: true, 
                            description: 'Liste des UUIDs dans l\'ordre souhaité' 
                        },
                        'uuids.*': { 
                            type: 'uuid', 
                            description: 'UUID d\'une catégorie (exists:faq_categories,uuid_faq_category)' 
                        }
                    }
                },
                exampleRequest: {
                    uuids: [
                        'uuid_categorie_1',
                        'uuid_categorie_2',
                        'uuid_categorie_3'
                    ]
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégories réordonnées avec succès',
                        example: {
                            success: true,
                            message: 'Catégories réordonnées avec succès.',
                            code: 'FAQ_CATEGORIES_REORDERED'
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Données invalides.',
                            errors: {
                                uuids: ['Le champ uuids est obligatoire.']
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-duplicate-admin',
                module: 'faq',
                name: '[Admin] Dupliquer une catégorie de FAQ',
                description: 'Crée une copie d\'une catégorie existante. La nouvelle catégorie est créée en mode inactif. Nécessite la permission `faq_categories.creer`.',
                method: 'POST',
                path: '/admin/faq/categories/{uuid_faq_category}/duplicate',
                isProtected: true,
                permissionsRequired: ['faq_categories.creer'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq_category: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie à dupliquer' 
                        } 
                    },
                    body: {}
                },
                responses: [
                    {
                        status: 201,
                        description: 'Catégorie dupliquée avec succès',
                        example: {
                            success: true,
                            message: 'Catégorie dupliquée avec succès.',
                            code: 'FAQ_CATEGORY_DUPLICATED',
                            data: {
                                uuid_faq_category: '...',
                                label: 'Questions générales (copie)',
                                is_active: false
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-stats-admin',
                module: 'faq',
                name: '[Admin] Statistiques des catégories',
                description: 'Récupère les statistiques des catégories de FAQ (total, actives, inactives, par défaut, personnalisées). Nécessite la permission `faq_categories.afficher`.',
                method: 'GET',
                path: '/admin/faq/categories/stats',
                isProtected: true,
                permissionsRequired: ['faq_categories.afficher'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: { body: {} },
                responses: [
                    {
                        status: 200,
                        description: 'Statistiques récupérées avec succès',
                        example: {
                            success: true,
                            message: 'Statistiques des catégories récupérées.',
                            code: 'FAQ_CATEGORY_STATS',
                            data: {
                                total: 10,
                                active: 8,
                                inactive: 2,
                                default: 7,
                                custom: 3
                            }
                        }
                    }
                ]
            },

            {
                id: 'faq-category-show-admin',
                module: 'faq',
                name: '[Admin] Détails d\'une catégorie de FAQ',
                description: 'Récupère les détails d\'une catégorie avec ses FAQs associées. Nécessite la permission `faq_categories.afficher`.',
                method: 'GET',
                path: '/admin/faq/categories/{uuid_faq_category}',
                isProtected: true,
                permissionsRequired: ['faq_categories.afficher'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    path: { 
                        uuid_faq_category: { 
                            type: 'uuid', 
                            required: true, 
                            description: 'UUID de la catégorie' 
                        } 
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'Détails de la catégorie',
                        example: {
                            success: true,
                            message: 'Catégorie récupérée avec succès.',
                            code: 'FAQ_CATEGORY_FOUND',
                            data: {
                                uuid_faq_category: '...',
                                code: 'compte',
                                label: 'Compte & connexion',
                                icon: 'bi-person-circle',
                                color: '#3490dc',
                                description: 'Questions relatives à la création de compte',
                                is_active: true,
                                is_default: true,
                                faqs_count: 5,
                                active_faqs_count: 5,
                                faqs: [
                                    {
                                        uuid_faq: '...',
                                        question: 'Comment créer un compte ?',
                                        answer: '...',
                                        is_active: true,
                                        views: 150
                                    }
                                ]
                            }
                        }
                    },
                    {
                        status: 404,
                        description: 'Catégorie non trouvée',
                        example: {
                            success: false,
                            message: 'Catégorie non trouvée.',
                            code: 'FAQ_CATEGORY_NOT_FOUND'
                        }
                    }
                ]
            },

            {
                id: 'faq-category-select-admin',
                module: 'faq',
                name: '[Admin] Catégories pour sélection',
                description: 'Récupère les catégories formatées pour un dropdown/select. Nécessite la permission `faq_categories.afficher`.',
                method: 'GET',
                path: '/admin/faq/categories/select',
                isProtected: true,
                permissionsRequired: ['faq_categories.afficher'],
                headers: { 
                    'Authorization': 'Bearer {token}', 
                    'Accept': 'application/json' 
                },
                requestParams: {
                    query: {
                        only_active: { 
                            type: 'boolean', 
                            required: false, 
                            default: true, 
                            description: 'Récupérer uniquement les catégories actives' 
                        }
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'Catégories pour sélection',
                        example: {
                            success: true,
                            message: 'Catégories pour sélection récupérées.',
                            code: 'FAQ_CATEGORIES_SELECT',
                            data: [
                                { value: 'uuid_categorie_1', label: 'Compte & connexion', code: 'compte' },
                                { value: 'uuid_categorie_2', label: 'Souscription & contrats', code: 'souscription' }
                            ]
                        }
                    }
                ]
            },

            // ============================================================
            // ESPACE CLIENT - TABLEAU DE BORD
            // ============================================================
            {
                id: 'customer-dashboard',
                module: 'espaces_client',
                name: 'Tableau de bord client',
                description: 'Récupère les informations de synthèse du client : nombre de contrats, capital total, primes totales, statut global, etc.',
                method: 'GET',
                path: '/espaces-client/dashboard',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: { body: {} },
                responses: [
                    {
                        status: 200,
                        description: 'Tableau de bord récupéré',
                        example: {
                            success: true,
                            code: 'DASHBOARD_FOUND',
                            message: 'Tableau de bord récupéré avec succès.',
                            data: {
                                total_contrats: 6,
                                total_capital: 45000000,
                                total_prime: 18000000,
                                total_encaisse: 14200000,
                                taux_moyen_paiement: 78.9,
                                contrats_actifs: 4,
                                contrats_en_retard: 2,
                                dernier_contrat: {
                                    IdProposition: 'PROP2024006',
                                    produit: 'PERFORMA Individuel',
                                    date: '2023-06-15'
                                }
                            }
                        }
                    }
                ]
            },
            // ============================================================
            // ESPACE CLIENT - CONTRATS
            // ============================================================
            {
                id: 'customer-contrats-list',
                module: 'espaces_client',
                name: 'Liste des contrats du client',
                description: 'Récupère la liste de tous les contrats actifs du client authentifié avec pagination. Exclut les contrats arrêtés (OnStdbyOff = 3). Retourne les informations détaillées de chaque contrat : capital, primes, taux de paiement, statut, ancienneté.',
                method: 'GET',
                path: '/espaces-client/contrats',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    query: {
                        per_page: {
                            type: 'integer',
                            required: false,
                            default: 10,
                            min: 1,
                            max: 100,
                            description: 'Nombre d\'éléments par page (1-100)'
                        },
                        page: {
                            type: 'integer',
                            required: false,
                            default: 1,
                            min: 1,
                            description: 'Numéro de la page'
                        }
                    }
                },
                exampleRequest: {
                    per_page: 5,
                    page: 2
                },
                responses: [
                    {
                        status: 200,
                        description: 'Liste des contrats récupérée avec succès',
                        example: {
                            success: true,
                            code: 'GET_ALL_CONTRAT_SUCCESS',
                            message: 'Contrats récupérés avec succès.',
                            data: [
                                {
                                    IdProposition: 'PROP2024001',
                                    CapitalSouscrit: 15000000,
                                    TotalPrime: 5400000,
                                    NbreImpayes: 0,
                                    produit: 'PERFORMA Individuel',
                                    EtatAvancementCotisation: 42 + 'En %',
                                }
                            ],
                            meta: {
                                total: 6,
                                per_page: 10,
                                current_page: 1,
                                last_page: 1,
                                has_errors: false,
                                errors: []
                            }
                        }
                    },
                    {
                        status: 200,
                        description: 'Aucun contrat actif trouvé',
                        example: {
                            success: true,
                            code: 'NO_CONTRAT_FOUND',
                            message: 'Aucun contrat actif trouvé.',
                            data: [],
                            meta: {
                                total: 0,
                                per_page: 10,
                                current_page: 1,
                                last_page: 1,
                                has_errors: false,
                                errors: []
                            }
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    },
                    {
                        status: 422,
                        description: 'Erreur de récupération',
                        example: {
                            success: false,
                            code: 'GET_CONTRAT_ERROR',
                            message: 'Une erreur est survenue lors de la récupération des contrats.'
                        }
                    }
                ]
            },

            // ============================================================
            // ESPACE CLIENT - DÉTAILS D'UN CONTRAT
            // ============================================================
            {
                id: 'customer-contrat-detail',
                module: 'espaces_client',
                name: 'Détails d\'un contrat',
                description: 'Récupère les informations détaillées d\'un contrat spécifique du client. Inclut les informations personnelles (assurés, bénéficiaires), les garanties, les documents contractuels (CP, CG, avenants) et l\'état d\'avancement.',
                method: 'GET',
                path: '/espaces-client/contrat-details/{contrat_id}',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: {
                        contrat_id: {
                            type: 'integer',
                            required: true,
                            description: 'ID du contrat (identifiant numérique)'
                        }
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'Détails du contrat récupérés avec succès',
                        example: {
                            success: true,
                            code: 'CONTRAT_DETAILS_FOUND',
                            message: 'Détails du contrat récupérés avec succès.',
                            data: {
                                details: {
                                    IdProposition: 'PROP2024001',
                                    NumBulletin: 'BUL-2024-001',
                                    CodeProposition: 'PROP-2024-001',
                                    CapitalSouscrit: 15000000,
                                    TotalPrime: 5400000,
                                    NbreImpayes: 0,
                                    produit: 'PERFORMA Individuel',
                                    EtatAvancementCotisation: '78.5',
                                    Periodicite: 'Mensuel',
                                    ModePaiement: 'Prélèvement automatique',
                                    DateFinAdhesion: '31/12/2040',
                                    DateEffetAdhesion: '15/01/2021',
                                    Conseiller: 'C12345 - KOFFI Serge',
                                    Adherent: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                    Status: 'En cours'
                                },
                                Assures: [
                                    {
                                        CodePersonne: 'P001',
                                        Nom: 'YAPO',
                                        Prenoms: 'BRUCE BERNADIN EVRARD JUNIOR',
                                        NomComplet: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        DateNaissance: '2000-11-20',
                                        LieuNaissance: 'Grand-Lahou',
                                        Profession: 'Informaticien',
                                        CodeFiliation: 'FIL001',
                                        Filiation: 'Fils'
                                    }
                                ],
                                Beneficiaires: [
                                    {
                                        CodePersonne: 'P002',
                                        Nom: 'YAPO',
                                        Prenoms: 'MARIE CLAIRE',
                                        NomComplet: 'YAPO MARIE CLAIRE',
                                        DateNaissance: '1975-03-15',
                                        LieuNaissance: 'Abidjan',
                                        Profession: 'Enseignante',
                                        CodeFiliation: 'FIL002',
                                        Filiation: 'Conjoint'
                                    }
                                ],
                                Garanties: [
                                    {
                                        CodeGarantie: 'G001',
                                        Libelle: 'Décès',
                                        Capital: 15000000,
                                        Prime: 5400000,
                                        PrimePrincipale: 5400000,
                                        DateEffet: '2021-01-15',
                                        DateEcheance: '2040-12-31',
                                        DureeCouvAns: 20,
                                        DureePrimeAns: 20,
                                        Periodicite: 'M'
                                    }
                                ],
                                Documents: {
                                    CP: 'https://apidev.yakoafricassur.com/get-document-contrat/A2025/M11/DocumentsContractuels_3593104/CP-CG_3593104.pdf',
                                    avenantsUrls: [
                                        'https://apidev.yakoafricassur.com/get-document-contrat/A2025/M11/DocumentsContractuels_3593104/AVT_3593104_001.pdf'
                                    ]
                                },
                                Anciennete: {
                                    date_premier_contrat: '2021-01-15',
                                    date_aujourdhui: '2025-08-26',
                                    annees: 4,
                                    mois: 7,
                                    jours: 11,
                                    total_mois: 55,
                                    total_jours: 1685
                                }
                            }
                        }
                    },
                    {
                        status: 404,
                        description: 'Contrat non trouvé',
                        example: {
                            success: false,
                            code: 'CONTRAT_NOT_FOUND',
                            message: 'Contrat non trouvé ou non associé à cet utilisateur.'
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de récupération des détails',
                        example: {
                            success: false,
                            code: 'CONTRACT_DETAILS_ERROR',
                            message: 'Une erreur est survenue lors de la récupération des détails du contrat.'
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    }
                ]
            },

            // ============================================================
            // ESPACE CLIENT - ÉTAT DE COTISATION D'UN CONTRAT
            // ============================================================
            {
                id: 'customer-contrat-etat-cotisation',
                module: 'espaces_client',
                name: 'État de cotisation d\'un contrat',
                description: 'Récupère l\'état détaillé des cotisations d\'un contrat spécifique. Retourne les informations financières complètes : primes payées, impayées, encaissements, et le détail des paiements. Inclut également les informations des assurés, bénéficiaires, payeurs et garanties.',
                method: 'GET',
                path: '/espaces-client/contrat-etat-cotisation/{contrat_id}',
                isProtected: true,
                headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
                requestParams: {
                    path: {
                        contrat_id: {
                            type: 'integer',
                            required: true,
                            description: 'ID du contrat (identifiant numérique)'
                        }
                    }
                },
                responses: [
                    {
                        status: 200,
                        description: 'État de cotisation récupéré avec succès',
                        example: {
                            success: true,
                            code: 'GET_ALL_CONTRAT_SUCCESS',
                            message: 'Etats de cotisation du contrat récupérés avec succès.',
                            data: {
                                details: {
                                    IdProposition: 'PROP2024001',
                                    NumBulletin: 'BUL-2024-001',
                                    NumPolice: 'POL-2024-001',
                                    CodeProposition: 'PROP-2024-001',
                                    CapitalSouscrit: 15000000,
                                    TotalPrime: 5400000,
                                    NbreImpayes: 0,
                                    NbreEmission: 12,
                                    NbreEncaissment: 10,
                                    NbrencPartielle: 0,
                                    TotalEncaissement: 4200000,
                                    TotalEncaissementPartielle: 0,
                                    TotalImpayes: 1200000,
                                    produit: 'PERFORMA Individuel',
                                    EtatAvancementCotisation: 78.5,
                                    DureeCotisationAns: 20,
                                    Periodicite: 'Mensuel',
                                    ModePaiement: 'Prélèvement automatique',
                                    DateFinAdhesion: '31/12/2040',
                                    DateEffetAdhesion: '15/01/2021',
                                    Conseiller: 'C12345 - KOFFI Serge',
                                    Adherent: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                    Status: 'En cours'
                                },
                                Assures: [
                                    {
                                        CodePersonne: 'P001',
                                        Nom: 'YAPO',
                                        Prenoms: 'BRUCE BERNADIN EVRARD JUNIOR',
                                        NomComplet: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        DateNaissance: '2000-11-20',
                                        LieuNaissance: 'Grand-Lahou',
                                        Profession: 'Informaticien',
                                        CodeFiliation: 'FIL001',
                                        Filiation: 'Fils'
                                    }
                                ],
                                AssuresGaranties: [
                                    {
                                        NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        CodeGarantie: 'G001',
                                        Libelle: 'Décès',
                                        Capital: 15000000,
                                        Prime: 450000,
                                        PrimePrincipale: 450000,
                                        FraisAccessoires: 0,
                                        DateEffet: '2021-01-15',
                                        DateEcheance: '2040-12-31',
                                        DureeCouvAns: 20,
                                        DureePrimeAns: 20,
                                        Periodicite: 'M'
                                    },
                                    {
                                        NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        CodeGarantie: 'G002',
                                        Libelle: 'Invalidité',
                                        Capital: 5000000,
                                        Prime: 150000,
                                        PrimePrincipale: 150000,
                                        FraisAccessoires: 0,
                                        DateEffet: '2021-01-15',
                                        DateEcheance: '2040-12-31',
                                        DureeCouvAns: 20,
                                        DureePrimeAns: 20,
                                        Periodicite: 'M'
                                    }
                                ],
                                Beneficiaires: [
                                    {
                                        CodePersonne: 'P002',
                                        Nom: 'YAPO',
                                        Prenoms: 'MARIE CLAIRE',
                                        NomComplet: 'YAPO MARIE CLAIRE',
                                        DateNaissance: '1975-03-15',
                                        LieuNaissance: 'Abidjan',
                                        Profession: 'Enseignante',
                                        CodeFiliation: 'FIL002',
                                        Filiation: 'Conjoint'
                                    }
                                ],
                                PayeurPrime: [
                                    {
                                        CodePersonne: 'P001',
                                        NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        ModePaiement: 'PRE',
                                        Organisme: 'BICICI',
                                        NumCompte: '12345678901'
                                    }
                                ],
                                PrimeNonRegles: [
                                    {
                                        DateEcheance: '2024-12-15',
                                        MontantNet: 450000,
                                        NumEcheance: 'ECH-2024-12',
                                        Statut: 'En attente'
                                    },
                                    {
                                        DateEcheance: '2024-11-15',
                                        MontantNet: 450000,
                                        NumEcheance: 'ECH-2024-11',
                                        Statut: 'En attente'
                                    }
                                ],
                                PrimeRegles: [
                                    {
                                        DateReglement: '2024-10-20',
                                        Montant: 450000,
                                        NumEcheance: 'ECH-2024-10',
                                        ModePaiement: 'PRE',
                                        Reference: 'PAY-2024-10-001'
                                    },
                                    {
                                        DateReglement: '2024-09-18',
                                        Montant: 450000,
                                        NumEcheance: 'ECH-2024-09',
                                        ModePaiement: 'PRE',
                                        Reference: 'PAY-2024-09-001'
                                    }
                                ],
                                PrimeReglesPartielle: []
                            }
                        }
                    },
                    {
                        status: 404,
                        description: 'Contrat non trouvé',
                        example: {
                            success: false,
                            code: 'CONTRAT_NOT_FOUND',
                            message: 'Contrat non trouvé ou non associé à cet utilisateur.'
                        }
                    },
                    {
                        status: 422,
                        description: 'Erreur de récupération',
                        example: {
                            success: false,
                            code: 'CONTRACT_DETAILS_ERROR',
                            message: 'Une erreur est survenue lors de la récupération des détails du contrat.'
                        }
                    },
                    {
                        status: 401,
                        description: 'Non authentifié',
                        example: { success: false, message: 'Non authentifié.' }
                    }
                ]
            }


            // ============================================================
            // ESPACE CLIENT - HISTORIQUE DES PAIEMENTS
            // ============================================================
            // {
            //     id: 'customer-paiements',
            //     module: 'espaces_client',
            //     name: 'Historique des paiements',
            //     description: 'Récupère l\'historique complet des paiements effectués par le client pour tous ses contrats.',
            //     method: 'GET',
            //     path: '/espaces-client/paiements',
            //     isProtected: true,
            //     headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            //     requestParams: {
            //         query: {
            //             contrat_id: {
            //                 type: 'integer',
            //                 required: false,
            //                 description: 'Filtrer par ID de contrat'
            //             },
            //             per_page: {
            //                 type: 'integer',
            //                 required: false,
            //                 default: 20,
            //                 min: 1,
            //                 max: 100,
            //                 description: 'Nombre d\'éléments par page'
            //             },
            //             page: {
            //                 type: 'integer',
            //                 required: false,
            //                 default: 1,
            //                 min: 1,
            //                 description: 'Numéro de la page'
            //             }
            //         }
            //     },
            //     responses: [
            //         {
            //             status: 200,
            //             description: 'Historique des paiements',
            //             example: {
            //                 success: true,
            //                 code: 'PAIEMENTS_LISTED',
            //                 message: 'Historique des paiements récupéré.',
            //                 data: [
            //                     {
            //                         contrat_id: 3593104,
            //                         produit: 'PERFORMA Individuel',
            //                         date: '2023-12-15',
            //                         montant: 350000,
            //                         mode: 'Carte bancaire',
            //                         statut: 'payé',
            //                         reference: 'PAY-2023-12-15-001'
            //                     }
            //                 ],
            //                 meta: {
            //                     total: 24,
            //                     per_page: 20,
            //                     current_page: 1,
            //                     last_page: 2
            //                 }
            //             }
            //         }
            //     ]
            // },

            // // ============================================================
            // // ESPACE CLIENT - PROCHAINES ÉCHÉANCES
            // // ============================================================
            // {
            //     id: 'customer-echeances',
            //     module: 'espaces_client',
            //     name: 'Prochaines échéances',
            //     description: 'Récupère les prochaines échéances de paiement pour les contrats du client.',
            //     method: 'GET',
            //     path: '/espaces-client/echeances',
            //     isProtected: true,
            //     headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            //     requestParams: {
            //         query: {
            //             limite: {
            //                 type: 'integer',
            //                 required: false,
            //                 default: 5,
            //                 min: 1,
            //                 max: 20,
            //                 description: 'Nombre d\'échéances à afficher'
            //             }
            //         }
            //     },
            //     responses: [
            //         {
            //             status: 200,
            //             description: 'Prochaines échéances',
            //             example: {
            //                 success: true,
            //                 code: 'ECHEANCES_LISTED',
            //                 message: 'Prochaines échéances récupérées.',
            //                 data: [
            //                     {
            //                         contrat_id: 3588730,
            //                         produit: 'YAKO Éternité 2018',
            //                         date_echeance: '2024-01-15',
            //                         montant: 400000,
            //                         statut: 'à venir'
            //                     }
            //                 ]
            //             }
            //         }
            //     ]
            // },

            // // ============================================================
            // // ESPACE CLIENT - STATISTIQUES CLIENT
            // // ============================================================
            // {
            //     id: 'customer-statistiques',
            //     module: 'espaces_client',
            //     name: 'Statistiques client',
            //     description: 'Récupère les statistiques détaillées du client : ancienneté, taux de paiement, répartition des contrats, etc.',
            //     method: 'GET',
            //     path: '/espaces-client/statistiques',
            //     isProtected: true,
            //     headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            //     requestParams: { body: {} },
            //     responses: [
            //         {
            //             status: 200,
            //             description: 'Statistiques client',
            //             example: {
            //                 success: true,
            //                 code: 'STATISTIQUES_FOUND',
            //                 message: 'Statistiques récupérées avec succès.',
            //                 data: {
            //                     anciennete: {
            //                         date_premier_contrat: '2021-01-15',
            //                         annees: '3 ans',
            //                         mois: '6 mois',
            //                         jours: '12 jours',
            //                         total_mois: 42,
            //                         total_jours: 1278
            //                     },
            //                     taux_paiement: {
            //                         global: 78.9,
            //                         par_contrat: {
            //                             'PERFORMA Individuel': 85.2,
            //                             'YAKO Éternité 2018': 45.0,
            //                             'CADENCE Éducation Plus': 62.5
            //                         }
            //                     },
            //                     repartition: {
            //                         type: {
            //                             'Vie': 4,
            //                             'Santé': 1,
            //                             'Éducation': 1
            //                         },
            //                         statut: {
            //                             'actif': 4,
            //                             'en_retard': 2
            //                         }
            //                     }
            //                 }
            //             }
            //         }
            //     ]
            // },
   
        ],

        // ================================================================
        // 4. CODES HTTP
        // ================================================================
        httpCodes: [
            { code: 200, name: 'OK', desc: 'Requête traitée avec succès.' },
            { code: 201, name: 'Created', desc: 'Ressource créée avec succès.' },
            { code: 401, name: 'Unauthorized', desc: 'Authentification manquante ou invalide.' },
            { code: 403, name: 'Forbidden', desc: 'Accès refusé (permission manquante, compte bloqué/inactif, IP restreinte).' },
            { code: 404, name: 'Not Found', desc: 'Ressource introuvable.' },
            { code: 409, name: 'Conflict', desc: 'Conflit d\'état métier (compte déjà gelé/non gelable).' },
            { code: 422, name: 'Unprocessable Entity', desc: 'Erreur de validation des données.' },
            { code: 423, name: 'Locked', desc: 'Compte temporairement gelé (AccountFrozenException).' },
            { code: 429, name: 'Too Many Requests', desc: 'Limite de tentatives dépassée.' },
            { code: 500, name: 'Internal Server Error', desc: 'Erreur interne inattendue.' }
        ],

        // ================================================================
        // 5. ERREURS MÉTIER
        // ================================================================
        businessErrors: [
            { code: 'AUTH_ERROR', message: 'Identifiants incorrects.', cause: 'Login/mot de passe invalide ou IP bloquée.', endpoint: 'POST /auth/login', action: 'Vérifier les identifiants.' },
            { code: 'SERVER_ERROR', message: 'Une erreur interne est survenue.', cause: 'Exception non prévue.', endpoint: 'POST /auth/login', action: 'Réessayer plus tard.' },
            { code: '2FA_REQUIRED', message: 'Vérification 2FA requise.', cause: '2FA activée, appareil non de confiance.', endpoint: 'POST /auth/login', action: 'Appeler /auth/2fa/verify-login.' },
            { code: 'PASSWORD_CHANGE_REQUIRED', message: 'Vous devez changer votre mot de passe.', cause: 'Première connexion ou mot de passe expiré.', endpoint: 'POST /auth/login', action: 'Appeler /auth/first-login.' },
            { code: 'ACCOUNT_FROZEN', message: 'Compte temporairement gelé.', cause: 'Trop de tentatives ou gel manuel.', endpoint: 'Middleware CheckAccountStatus', action: 'Attendre l\'expiration ou contacter un admin.' },
            { code: 'ACCOUNT_BLOCKED', message: 'Compte bloqué.', cause: '6 tentatives échouées ou blocage manuel.', endpoint: 'Middleware CheckAccountStatus', action: 'Contacter un administrateur.' },
            { code: 'IP_BLOCKED', message: 'Accès refusé depuis cette adresse IP.', cause: 'IP blacklistée ou non whitelistée.', endpoint: 'Middleware IpRestriction', action: 'Utiliser une IP autorisée.' },
            { code: 'PASSWORD_EXPIRED', message: 'Votre mot de passe a expiré.', cause: 'password_expires_at dépassé.', endpoint: 'Middleware CheckPasswordExpiration', action: 'Appeler /auth/change-password.' },
            { code: 'PASSWORD_CHANGE_NOT_REQUIRED', message: 'Le changement n\'est pas requis.', cause: 'Appel de /auth/first-login sans nécessité.', endpoint: 'POST /auth/first-login', action: 'Utiliser /auth/change-password.' },
            { code: 'PERMISSION_DENIED', message: 'Vous n\'avez pas la/les permission(s) nécessaire(s).', cause: 'Permission manquante sur le rôle.', endpoint: 'Middleware permission:*', action: 'Demander l\'attribution de la permission.' },
            { code: 'ROLE_PROTECTED', message: 'Rôle système protégé.', cause: 'Tentative de modification/suppression d\'un rôle système.', endpoint: 'PUT/DELETE /roles', action: 'Créer un rôle personnalisé.' },
            { code: 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE', message: 'Super Admin déjà tous droits.', cause: 'Tentative d\'attribution de permissions explicites au Super Admin.', endpoint: 'POST /roles/{uuid}/permissions', action: 'Ne rien faire.' },
            { code: 'PERMISSION_IN_USE', message: 'Permission attribuée à un rôle.', cause: 'Permission encore liée à un rôle.', endpoint: 'DELETE /permissions', action: 'Retirer la permission de tous les rôles.' },
            { code: 'PERMISSION_GROUP_NOT_EMPTY', message: 'Le groupe contient des permissions.', cause: 'Groupe non vide.', endpoint: 'DELETE /permission-groups', action: 'Supprimer ou déplacer les permissions.' },
            { code: 'INVALID_PASSWORD', message: 'Mot de passe incorrect.', cause: 'Vérification échouée.', endpoint: 'POST /security/user-questions', action: 'Ressaisir le mot de passe.' },
            { code: 'TOO_MANY_ATTEMPTS', message: 'Trop de tentatives.', cause: 'Rate limiting dépassé.', endpoint: 'Security endpoints', action: 'Attendre le délai indiqué.' },
            { code: 'OTP_INVALID', message: 'Code OTP invalide ou expiré.', cause: 'Le code OTP fourni est incorrect, a déjà été utilisé ou a expiré.', endpoint: 'POST /auth/otp/verify-code', action: 'Demander un nouveau code OTP.' },
            { code: 'OTP_SMS_ALREADY_SENT', message: 'Vous avez déjà utilisé la vérification par SMS au cours des dernières 24 heures.', cause: 'L\'utilisateur a déjà reçu un OTP SMS pour une réinitialisation dans les dernières 24 heures.', endpoint: 'POST /auth/forgot-password (option=sms)', action: 'Utiliser un autre canal de récupération.' },
            { code: 'EMAIL_INVALID', message: 'Aucune adresse email disponible pour l\'envoi de l\'OTP.', cause: 'L\'utilisateur n\'a pas d\'email associé à son compte.', endpoint: 'POST /auth/forgot-password (option=email)', action: 'Utiliser un autre canal.' },
            { code: 'TELEPHONE_INVALID', message: 'Numéro de téléphone invalide pour l\'envoi SMS.', cause: 'Le numéro de téléphone est manquant ou ne comporte pas 10 chiffres.', endpoint: 'POST /auth/forgot-password (option=sms)', action: 'Utiliser un autre canal.' },
            { code: 'WHATSAPP_NOT_CONFIGURED', message: 'Le canal WhatsApp n\'est pas encore configuré.', cause: 'Le service WhatsApp n\'est pas encore implémenté côté serveur.', endpoint: 'POST /auth/forgot-password (option=whatsapp)', action: 'Utiliser un autre canal.' },
            { code: 'CHANNEL_INVALID', message: 'Canal d\'envoi OTP invalide.', cause: 'Le canal demandé n\'est pas supporté.', endpoint: 'POST /auth/otp/send ou /auth/forgot-password', action: 'Utiliser un canal valide.' },
            { code: 'RECOVERY_CODE_INVALID', message: 'Code de récupération invalide ou déjà utilisé.', cause: 'Le code de récupération fourni est incorrect ou a déjà été utilisé.', endpoint: 'POST /auth/2fa/verify-recovery', action: 'Utiliser un autre code de récupération.' },
            { code: 'RECOVERY_CODES_EXHAUSTED', message: 'Plus de codes de récupération disponibles.', cause: 'Tous les codes de récupération ont été utilisés.', endpoint: 'POST /auth/2fa/recovery-codes', action: 'Régénérer de nouveaux codes.' },
            { code: '2FA_ALREADY_ENABLED', message: 'La 2FA est déjà activée pour ce compte.', cause: 'Tentative d\'activation de la 2FA alors qu\'elle est déjà active.', endpoint: 'GET /auth/2fa/qrcode', action: 'Désactiver la 2FA avant de la réactiver.' },
            { code: '2FA_NOT_ENABLED', message: 'La 2FA n\'est pas activée pour ce compte.', cause: 'Tentative d\'utilisation de la 2FA alors qu\'elle n\'est pas activée.', endpoint: 'POST /auth/2fa/verify', action: 'Activer la 2FA via /auth/2fa/qrcode et /auth/2fa/confirm.' },
            { code: 'OTP_SEND_FAILED', message: 'Impossible d\'envoyer le code OTP.', cause: 'Erreur lors de l\'envoi du code OTP (service indisponible).', endpoint: 'POST /auth/otp/send', action: 'Réessayer plus tard ou contacter le support.' },
            { code: 'PARTNER_HAS_RESEAVX', message: 'Ce partenaire a des réseaux associés et ne peut pas être supprimé.', cause: 'Le partenaire est référencé dans la table reseaux.', endpoint: 'DELETE /partners/{uuid_partner}', action: 'Supprimer les réseaux associés avant de supprimer le partenaire.' },
            { code: 'RESEAU_HAS_AGENCES', message: 'Ce réseau a des agences associées et ne peut pas être supprimé.', cause: 'Le réseau est référencé dans la table agences.', endpoint: 'DELETE /reseaux/{uuid_reseau}', action: 'Supprimer les agences associées avant de supprimer le réseau.' },
            { code: 'AGENCE_HAS_USERS', message: 'Cette agence est associée à des utilisateurs et ne peut pas être supprimée.', cause: 'L\'agence est référencée dans la table user_agences.', endpoint: 'DELETE /agences/{uuid_agence}', action: 'Retirer les utilisateurs associés avant de supprimer l\'agence.' },
            {
                code: 'GET_ALL_CONTRAT_SUCCESS',
                message: 'Contrats récupérés avec succès.',
                cause: 'La liste des contrats du client a été récupérée avec succès.',
                endpoint: 'GET /espaces-client/contrats',
                action: 'Aucune action requise.'
            },
            {
                code: 'NO_CONTRAT_FOUND',
                message: 'Aucun contrat actif trouvé.',
                cause: 'Le client n\'a pas de contrats actifs ou tous ses contrats sont arrêtés (OnStdbyOff = 3).',
                endpoint: 'GET /espaces-client/contrats',
                action: 'Aucune action requise.'
            },
            {
                code: 'GET_CONTRAT_ERROR',
                message: 'Une erreur est survenue lors de la récupération des contrats.',
                cause: 'Erreur technique lors de l\'appel au service externe (encaissement-bis).',
                endpoint: 'GET /espaces-client/contrats',
                action: 'Réessayer plus tard ou contacter le support.'
            },
            {
                code: 'CONTRAT_DETAILS_FOUND',
                message: 'Détails du contrat récupérés avec succès.',
                cause: 'Les détails du contrat ont été récupérés avec succès.',
                endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                action: 'Aucune action requise.'
            },
            {
                code: 'CONTRAT_NOT_FOUND',
                message: 'Contrat non trouvé ou non associé à cet utilisateur.',
                cause: 'Le contrat n\'existe pas ou n\'appartient pas à l\'utilisateur authentifié.',
                endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                action: 'Vérifier l\'ID du contrat ou contacter le support.'
            },
            {
                code: 'CONTRACT_DETAILS_ERROR',
                message: 'Une erreur est survenue lors de la récupération des détails du contrat.',
                cause: 'Erreur technique lors de la récupération des détails du contrat (service externe indisponible).',
                endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                action: 'Réessayer plus tard ou contacter le support.'
            },
            {
                code: 'CONTRACT_NO_DETAILS',
                message: 'Aucun détail trouvé pour ce contrat.',
                cause: 'Le contrat existe mais n\'a pas de détails disponibles.',
                endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                action: 'Contacter le support pour vérifier l\'intégrité du contrat.'
            }
        ]
    };

    // ================================================================
    // 6. ÉTAT DE L'APPLICATION
    // ================================================================
    const AppState = {
        currentEnv: 'local',
        authToken: null,
        currentUser: null,
        isAuthenticated: false,
        selectedEndpoint: 'home',
        searchQuery: '',
        tryItOpen: {}
    };

    // ================================================================
    // 7. UTILITAIRES, CLIENT API, AUTH MANAGER, RENDERER, SEARCH, INIT
    // ================================================================

    const Utils = {
        sanitizeForDisplay(data) {
            if (!data) return data;
            try {
                const str = JSON.stringify(data);
                return str
                    .replace(/"token":"[^"]*"/g, '"token":"••••••••"')
                    .replace(/"password":"[^"]*"/g, '"password":"••••••••"')
                    .replace(/"secret":"[^"]*"/g, '"secret":"••••••••"')
                    .replace(/"code_plain":"[^"]*"/g, '"code_plain":"••••••••"')
                    .replace(/"access_token":"[^"]*"/g, '"access_token":"••••••••"')
                    .replace(/"reset_token":"[^"]*"/g, '"reset_token":"••••••••"');
            } catch (e) {
                return data;
            }
        },

        generateId() {
            return Math.random().toString(36).substring(2, 10);
        },

        formatDate(date) {
            return new Date(date).toLocaleString('fr-FR');
        },

        getMethodBadgeClass(method) {
            const classes = {
                'GET': 'get',
                'POST': 'post',
                'PUT': 'put',
                'PATCH': 'patch',
                'DELETE': 'delete'
            };
            return classes[method] || '';
        },

        getStatusBadgeClass(status) {
            if (status >= 200 && status < 300) return 'success';
            if (status >= 400 && status < 500) return 'warning';
            if (status >= 500) return 'error';
            return 'info';
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        truncate(text, maxLength = 60) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        },

        showToast(message, type = 'info') {
            const toast = document.getElementById('liveToast');
            const title = document.getElementById('toastTitle');
            const body = document.getElementById('toastMessage');

            const icons = {
                success: '✅',
                danger: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            title.textContent = icons[type] || 'ℹ️';
            body.textContent = message;
            toast.className = `toast bg-${type} text-white`;

            const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
            bsToast.show();
        }
    };

    // ================================================================
    // 8. CLIENT API
    // ================================================================
    const ApiClient = {
        getBaseUrl() {
            const env = API_DATA.environments[AppState.currentEnv];
            return env ? env.url : 'http://localhost:8000/api/v1';
        },

        getHeaders(extraHeaders = {}) {
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...extraHeaders
            };

            if (AppState.isAuthenticated && AppState.authToken) {
                headers['Authorization'] = `Bearer ${AppState.authToken}`;
            }

            return headers;
        },

        async request(method, path, data = null, extraHeaders = {}) {
            const url = this.getBaseUrl() + path;
            const headers = this.getHeaders(extraHeaders);
            const options = {
                method: method.toUpperCase(),
                headers: headers
            };

            if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
                options.body = JSON.stringify(data);
            }

            const startTime = performance.now();

            try {
                const response = await fetch(url, options);
                const endTime = performance.now();
                const responseTime = Math.round(endTime - startTime);

                let responseData;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    responseData = await response.json();
                } else {
                    responseData = await response.text();
                }

                return {
                    ok: response.ok,
                    status: response.status,
                    statusText: response.statusText,
                    headers: response.headers,
                    data: responseData,
                    responseTime: responseTime
                };
            } catch (error) {
                const endTime = performance.now();
                return {
                    ok: false,
                    status: 0,
                    statusText: 'Network Error',
                    data: { error: error.message },
                    responseTime: Math.round(endTime - startTime),
                    isNetworkError: true
                };
            }
        },

        async login(login, password) {
            return this.request('POST', '/auth/login', { login, password });
        },

        async testEndpoint(endpoint, params) {
            let path = endpoint.path;

            if (params.path) {
                for (const [key, value] of Object.entries(params.path)) {
                    path = path.replace(`{${key}}`, encodeURIComponent(value));
                }
            }

            const queryParams = new URLSearchParams();
            if (params.query) {
                for (const [key, value] of Object.entries(params.query)) {
                    if (value !== undefined && value !== null && value !== '') {
                        queryParams.append(key, value);
                    }
                }
            }
            const queryString = queryParams.toString();
            if (queryString) {
                path += '?' + queryString;
            }

            return this.request(
                endpoint.method,
                path,
                params.body || null
            );
        }
    };

    // ================================================================
    // 9. GESTION DE L'AUTHENTIFICATION
    // ================================================================
    const AuthManager = {
        async login(login, password) {
            try {
                const result = await ApiClient.login(login, password);

                if (result.ok && result.data && result.data.data && result.data.data.access_token) {
                    this.setToken(result.data.data.access_token);
                    this.setUser(result.data.data.user || null);
                    return { success: true, data: result.data };
                }

                if (result.data && result.data.code === '2FA_REQUIRED') {
                    return { success: true, data: result.data, requires2fa: true };
                }
                if (result.data && result.data.code === 'PASSWORD_CHANGE_REQUIRED') {
                    return { success: true, data: result.data, requiresPasswordChange: true };
                }

                return {
                    success: false,
                    message: result.data?.message || 'Erreur de connexion',
                    status: result.status
                };
            } catch (error) {
                return { success: false, message: error.message };
            }
        },

        setToken(token) {
            AppState.authToken = token;
            AppState.isAuthenticated = true;
            this.updateUI();
        },

        setUser(user) {
            AppState.currentUser = user;
            this.updateUI();
        },

        logout() {
            AppState.authToken = null;
            AppState.currentUser = null;
            AppState.isAuthenticated = false;
            this.updateUI();
            Utils.showToast('Déconnexion réussie', 'info');
        },

        updateUI() {
            const statusText = document.getElementById('authStatusText');
            const statusDot = document.getElementById('statusDot');
            const authBtnText = document.getElementById('authBtnText');
            const logoutBtn = document.getElementById('authLogoutBtn');
            const submitBtn = document.getElementById('authSubmitBtn');

            if (AppState.isAuthenticated) {
                statusText.textContent = AppState.currentUser?.email || 'Authentifié';
                statusDot.className = 'status-dot online';
                authBtnText.textContent = 'Connecté';
                if (logoutBtn) logoutBtn.style.display = 'inline-block';
                if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sync me-1"></i> Rafraîchir';
            } else {
                statusText.textContent = 'Non authentifié';
                statusDot.className = 'status-dot offline';
                authBtnText.textContent = 'Se connecter';
                if (logoutBtn) logoutBtn.style.display = 'none';
                if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i> Se connecter';
            }
        }
    };

    // ================================================================
    // 10. RENDU DE LA DOCUMENTATION
    // ================================================================
    const Renderer = {
        renderSidebar() {
            const nav = document.getElementById('sidebarNav');
            const modules = API_DATA.modules;
            const endpoints = API_DATA.endpoints;

            const grouped = {};
            for (const ep of endpoints) {
                if (!grouped[ep.module]) grouped[ep.module] = [];
                grouped[ep.module].push(ep);
            }

            let html = '';

            html += `
                <a class="nav-item ${!AppState.selectedEndpoint || AppState.selectedEndpoint === 'home' ? 'active' : ''}" data-endpoint="home">
                    <i class="fas fa-house nav-icon"></i>
                    Accueil
                </a>
            `;

            for (const [moduleKey, moduleData] of Object.entries(modules)) {
                const eps = grouped[moduleKey] || [];
                if (eps.length === 0 || moduleKey === 'home') continue;

                html += `<div class="nav-module">${moduleData.label}</div>`;

                for (const ep of eps) {
                    if (ep.isHome) continue;
                    const isActive = AppState.selectedEndpoint === ep.id;
                    const methodClass = Utils.getMethodBadgeClass(ep.method);
                    const protectedIcon = ep.isProtected ? ' 🔒' : '';

                    html += `
                        <a class="nav-item ${isActive ? 'active' : ''}" data-endpoint="${ep.id}">
                            <i class="fas ${moduleData.icon} nav-icon"></i>
                            <span class="method-badge ${methodClass}">${ep.method || 'GET'}</span>
                            <span class="flex-grow-1">${ep.name}${protectedIcon}</span>
                        </a>
                    `;
                }
            }

            nav.innerHTML = html;

            nav.querySelectorAll('.nav-item').forEach(el => {
                el.addEventListener('click', function() {
                    const endpointId = this.dataset.endpoint;
                    AppState.selectedEndpoint = endpointId;
                    Renderer.renderContent();
                    Renderer.updateSidebarActive();
                    if (window.innerWidth < 992) {
                        document.getElementById('sidebar').classList.remove('show');
                        document.getElementById('sidebarOverlay').classList.remove('show');
                    }
                });
            });
        },

        updateSidebarActive() {
            document.querySelectorAll('.nav-item').forEach(el => {
                el.classList.toggle('active', el.dataset.endpoint === AppState.selectedEndpoint);
            });
        },

        renderContent() {
            const main = document.getElementById('mainContent');

            if (!AppState.selectedEndpoint || AppState.selectedEndpoint === 'home') {
                main.innerHTML = this.renderHome();
                return;
            }

            const endpoint = API_DATA.endpoints.find(e => e.id === AppState.selectedEndpoint);
            if (!endpoint) {
                main.innerHTML = `<div class="alert alert-warning">Endpoint non trouvé.</div>`;
                return;
            }

            if (endpoint.isHome) {
                main.innerHTML = this.renderHome();
                return;
            }

            main.innerHTML = this.renderEndpoint(endpoint);
        },

        renderHome() {
            const endpoints = API_DATA.endpoints.filter(e => !e.isHome);
            const protectedCount = endpoints.filter(e => e.isProtected).length;
            const totalCount = endpoints.length;

            return `
                <div class="fade-in">
                    <div class="home-hero">
                        <h1>YNOV API Documentation</h1>
                        <p>
                            Documentation technique officielle de l'API REST utilisée par l'application 
                            Front-Office YNOV de <strong>YAKO AFRICA Assurances Vie Côte d'Ivoire</strong>.
                        </p>
                        <div class="quick-links">
                            <button class="btn btn-light" onclick="Renderer.selectEndpoint('auth-login')">
                                <i class="fas fa-right-to-bracket me-1"></i> Authentification
                            </button>
                            <button class="btn btn-outline-light" onclick="Renderer.selectEndpoint('users-list')">
                                <i class="fas fa-users me-1"></i> Utilisateurs
                            </button>
                            <button class="btn btn-outline-light" onclick="Renderer.selectEndpoint('security-suggested')">
                                <i class="fas fa-question-circle me-1"></i> Questions de sécurité
                            </button>
                            <button class="btn btn-outline-light" onclick="document.getElementById('searchInput').focus()">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                        </div>
                    </div>

                    <div class="home-stats">
                        <div class="stat-card">
                            <div class="stat-number">${totalCount}</div>
                            <div class="stat-label">Endpoints documentés</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${protectedCount}</div>
                            <div class="stat-label">Endpoints protégés</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${Object.keys(API_DATA.modules).length}</div>
                            <div class="stat-label">Modules</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">v1.0</div>
                            <div class="stat-label">Version API</div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-rocket text-primary me-2"></i>Quick Start</h5>
                                    <p class="card-text">
                                        Pour commencer à utiliser l'API, consultez le guide d'authentification 
                                        et testez vos premiers endpoints.
                                    </p>
                                    <button class="btn btn-primary btn-sm" onclick="Renderer.selectEndpoint('auth-login')">
                                        <i class="fas fa-right-to-bracket me-1"></i> Tester la connexion
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-shield-halved text-danger me-2"></i>Sécurité</h5>
                                    <p class="card-text">
                                        L'API intègre des mécanismes de sécurité avancés : 
                                        gel de compte, 2FA, OTP, questions de sécurité et restrictions IP.
                                    </p>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="Renderer.selectEndpoint('2fa-enable')">
                                        <i class="fas fa-shield-halved me-1"></i> Découvrir la 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Environnement actif :</strong> ${API_DATA.environments[AppState.currentEnv]?.label || 'Local'} 
                            (${ApiClient.getBaseUrl()})
                            ${AppState.isAuthenticated ? ' — <span class="text-success"><i class="fas fa-check-circle"></i> Authentifié</span>' : ' — <span class="text-warning"><i class="fas fa-circle"></i> Non authentifié</span>'}
                        </div>
                    </div>
                </div>
            `;
        },

        selectEndpoint(id) {
            AppState.selectedEndpoint = id;
            this.renderContent();
            this.updateSidebarActive();
            document.getElementById('mainContent').scrollIntoView({ behavior: 'smooth' });
        },

        renderEndpoint(endpoint) {
            const methodClass = Utils.getMethodBadgeClass(endpoint.method);
            const baseUrl = ApiClient.getBaseUrl();

            let pathParamsHtml = '';
            if (endpoint.requestParams?.path && Object.keys(endpoint.requestParams.path).length > 0) {
                pathParamsHtml = this.renderParamTable(endpoint.requestParams.path, 'path');
            }

            let queryParamsHtml = '';
            if (endpoint.requestParams?.query && Object.keys(endpoint.requestParams.query).length > 0) {
                queryParamsHtml = this.renderParamTable(endpoint.requestParams.query, 'query');
            }

            let bodyParamsHtml = '';
            let exampleRequestHtml = '';
            let invalidExampleHtml = '';
            if (endpoint.requestParams?.body && Object.keys(endpoint.requestParams.body).length > 0) {
                bodyParamsHtml = this.renderParamTable(endpoint.requestParams.body, 'body');

                if (endpoint.exampleRequest) {
                    exampleRequestHtml = `
                        <div class="section-title"><i class="fas fa-check-circle text-success"></i> Exemple de requête valide</div>
                        <div class="code-block">
                            <pre><code class="language-json">${JSON.stringify(endpoint.exampleRequest, null, 2)}</code></pre>
                            <button class="copy-btn" onclick="Renderer.copyCode(this)">Copier</button>
                        </div>
                    `;
                }

                if (endpoint.invalidExample) {
                    invalidExampleHtml = `
                        <div class="section-title"><i class="fas fa-times-circle text-danger"></i> Exemple invalide</div>
                        <div class="warning-box">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Pourquoi cet exemple est invalide :</strong> ${endpoint.invalidReason || 'Non spécifié'}
                        </div>
                        <div class="code-block" style="border-color: #f1c3bc;">
                            <pre><code class="language-json">${JSON.stringify(endpoint.invalidExample, null, 2)}</code></pre>
                        </div>
                    `;
                }
            }

            let responsesHtml = '';
            if (endpoint.responses && endpoint.responses.length > 0) {
                responsesHtml = `
                    <div class="section-title"><i class="fas fa-reply"></i> Réponses</div>
                    <div class="table-responsive">
                        <table class="table doc-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Exemple</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${endpoint.responses.map(r => `
                                    <tr>
                                        <td><span class="badge bg-${Utils.getStatusBadgeClass(r.status)}">${r.status}</span></td>
                                        <td>${r.description}</td>
                                        <td>
                                            <div class="code-block" style="max-height:200px; overflow-y:auto;">
                                                <pre><code class="language-json">${JSON.stringify(r.example, null, 2)}</code></pre>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            const authBadges = [];
            if (endpoint.isProtected) {
                authBadges.push(`<span class="badge bg-warning text-dark"><i class="fas fa-lock me-1"></i>Protégé</span>`);
            } else {
                authBadges.push(`<span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Public</span>`);
            }
            if (endpoint.permissionsRequired && endpoint.permissionsRequired.length > 0) {
                authBadges.push(`<span class="badge bg-danger">Permission: ${endpoint.permissionsRequired.join(', ')}</span>`);
            }
            if (endpoint.abilityRequired) {
                authBadges.push(`<span class="badge bg-info">Ability: ${endpoint.abilityRequired}</span>`);
            }
            if (endpoint.rateLimit) {
                authBadges.push(`<span class="badge bg-secondary"><i class="fas fa-gauge-high me-1"></i>${endpoint.rateLimit}</span>`);
            }
            if (endpoint.isDestructive) {
                authBadges.push(`<span class="badge bg-danger"><i class="fas fa-triangle-exclamation me-1"></i>Destructive</span>`);
            }

            const html = `
                <div class="fade-in">
                    <div class="page-header">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#" onclick="Renderer.selectEndpoint('home'); return false;">Accueil</a></li>
                                <li class="breadcrumb-item active">${API_DATA.modules[endpoint.module]?.label || endpoint.module}</li>
                                <li class="breadcrumb-item active">${endpoint.name}</li>
                            </ol>
                        </nav>
                        <h1>${endpoint.name}</h1>
                    </div>

                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="endpoint-method ${methodClass}">${endpoint.method || 'GET'}</span>
                                <span class="endpoint-path">
                                    <span class="base-url">${baseUrl}</span>${endpoint.path}
                                </span>
                            </div>
                            <div class="endpoint-badges">
                                ${authBadges.join(' ')}
                            </div>
                        </div>
                        <div class="endpoint-body">
                            <div class="description">
                                <p>${endpoint.description}</p>
                            </div>

                            ${pathParamsHtml ? `
                                <div class="section-title"><i class="fas fa-link"></i> Paramètres du chemin</div>
                                ${pathParamsHtml}
                            ` : ''}

                            ${queryParamsHtml ? `
                                <div class="section-title"><i class="fas fa-search"></i> Paramètres de requête</div>
                                ${queryParamsHtml}
                            ` : ''}

                            ${bodyParamsHtml ? `
                                <div class="section-title"><i class="fas fa-file"></i> Corps de la requête</div>
                                ${bodyParamsHtml}
                            ` : ''}

                            ${exampleRequestHtml}
                            ${invalidExampleHtml}

                            ${responsesHtml}

                            <div class="try-it-section">
                                <button class="try-it-toggle" onclick="Renderer.toggleTryIt('${endpoint.id}')">
                                    <i class="fas fa-play"></i> Tester l'endpoint
                                </button>

                                <div class="try-it-panel ${AppState.tryItOpen[endpoint.id] ? 'show' : ''}" id="tryItPanel_${endpoint.id}">
                                    ${this.renderTryItForm(endpoint)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            setTimeout(() => {
                if (typeof Prism !== 'undefined') {
                    Prism.highlightAll();
                }
            }, 100);

            return html;
        },

        renderParamTable(params, type) {
            let html = `
                <div class="table-responsive">
                    <table class="table doc-table">
                        <thead>
                            <tr>
                                <th>Paramètre</th>
                                <th>Type</th>
                                <th>Requis</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            for (const [key, value] of Object.entries(params)) {
                const required = value.required ? '<span class="required-badge">Obligatoire</span>' : '<span class="optional-badge">Optionnel</span>';
                const typeInfo = value.type || 'string';
                let desc = value.description || '';
                if (value.enum) {
                    desc += ` (valeurs: ${value.enum.join(', ')})`;
                }
                if (value.default !== undefined) {
                    desc += ` (défaut: ${value.default})`;
                }
                if (value.min !== undefined) {
                    desc += ` (min: ${value.min})`;
                }
                if (value.max !== undefined) {
                    desc += ` (max: ${value.max})`;
                }
                if (value.size !== undefined) {
                    desc += ` (taille: ${value.size})`;
                }

                html += `
                    <tr>
                        <td><code>${key}</code></td>
                        <td><code>${typeInfo}</code></td>
                        <td>${required}</td>
                        <td>${desc}</td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            return html;
        },

        renderTryItForm(endpoint) {
            const endpointId = endpoint.id;

            let pathFields = '';
            if (endpoint.requestParams?.path) {
                for (const [key, value] of Object.entries(endpoint.requestParams.path)) {
                    pathFields += `
                        <div class="row mb-2">
                            <div class="col-md-3"><label class="form-label"><code>{${key}}</code></label></div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="try_${endpointId}_path_${key}" 
                                    placeholder="${value.description || key}" 
                                    ${value.required ? 'required' : ''}>
                            </div>
                        </div>
                    `;
                }
            }

            let queryFields = '';
            if (endpoint.requestParams?.query) {
                for (const [key, value] of Object.entries(endpoint.requestParams.query)) {
                    queryFields += `
                        <div class="row mb-2">
                            <div class="col-md-3"><label class="form-label">${key}</label></div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="try_${endpointId}_query_${key}" 
                                    placeholder="${value.description || key}"
                                    ${value.required ? 'required' : ''}>
                            </div>
                        </div>
                    `;
                }
            }

            let bodyField = '';
            if (endpoint.requestParams?.body && Object.keys(endpoint.requestParams.body).length > 0) {
                let defaultBody = '{}';
                if (endpoint.exampleRequest) {
                    defaultBody = JSON.stringify(endpoint.exampleRequest, null, 2);
                } else {
                    const sampleBody = {};
                    for (const [key, value] of Object.entries(endpoint.requestParams.body)) {
                        sampleBody[key] = value.type === 'string' ? '' : null;
                    }
                    defaultBody = JSON.stringify(sampleBody, null, 2);
                }
                bodyField = `
                    <div class="form-group">
                        <label class="form-label">JSON Body</label>
                        <textarea class="form-control json-editor" id="try_${endpointId}_body" 
                                rows="6" spellcheck="false">${defaultBody}</textarea>
                        <small class="text-muted">Format JSON valide requis.</small>
                    </div>
                `;
            }

            let headersHtml = '';
            if (endpoint.headers) {
                let headersObj = { ...endpoint.headers };
                if (AppState.isAuthenticated && AppState.authToken) {
                    headersObj['Authorization'] = 'Bearer {token}';
                }
                headersHtml = `
                    <div class="form-group">
                        <label class="form-label">Headers</label>
                        <pre style="background:#f8f9fa; padding:8px 12px; border-radius:6px; font-size:0.85rem; margin:0;">${JSON.stringify(headersObj, null, 2)}</pre>
                    </div>
                `;
            }

            // Avertissement pour les actions destructives
            let destructiveWarning = '';
            if (endpoint.isDestructive) {
                destructiveWarning = `
                    <div class="danger-box">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>⚠️ Action destructive :</strong> Cette opération peut modifier ou supprimer des données de manière irréversible.
                        Vérifiez l'environnement sélectionné avant de continuer.
                    </div>
                `;
            }

            return `
                <div class="try-it-form">
                    ${destructiveWarning}
                    ${headersHtml}

                    ${pathFields ? `
                        <div class="form-group">
                            <label class="form-label">Paramètres du chemin</label>
                            ${pathFields}
                        </div>
                    ` : ''}

                    ${queryFields ? `
                        <div class="form-group">
                            <label class="form-label">Paramètres de requête</label>
                            ${queryFields}
                        </div>
                    ` : ''}

                    ${bodyField}

                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <button class="btn btn-primary send-btn" onclick="Renderer.sendTryIt('${endpointId}')">
                            <i class="fas fa-paper-plane me-1"></i> Envoyer
                        </button>
                        <button class="btn btn-outline-secondary" onclick="Renderer.clearTryIt('${endpointId}')">
                            <i class="fas fa-undo me-1"></i> Réinitialiser
                        </button>
                        ${endpoint.exampleRequest ? `
                            <button class="btn btn-outline-info" onclick="Renderer.loadExample('${endpointId}')">
                                <i class="fas fa-file me-1"></i> Charger l'exemple
                            </button>
                        ` : ''}
                    </div>

                    <div class="response-viewer" id="responseViewer_${endpointId}">
                        <div class="response-meta" id="responseMeta_${endpointId}"></div>
                        <div class="response-body" id="responseBody_${endpointId}">
                            <pre><code class="language-json">${JSON.stringify({ message: 'En attente de requête...' }, null, 2)}</code></pre>
                        </div>
                    </div>

                    <div class="spinner-overlay" id="spinner_${endpointId}">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            `;
        },

        toggleTryIt(endpointId) {
            AppState.tryItOpen[endpointId] = !AppState.tryItOpen[endpointId];
            const panel = document.getElementById(`tryItPanel_${endpointId}`);
            if (panel) {
                panel.classList.toggle('show');
            }
            this.renderContent();
        },

        async sendTryIt(endpointId) {
            const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
            if (!endpoint) return;

            const spinner = document.getElementById(`spinner_${endpointId}`);
            const viewer = document.getElementById(`responseViewer_${endpointId}`);
            const meta = document.getElementById(`responseMeta_${endpointId}`);
            const body = document.getElementById(`responseBody_${endpointId}`);

            if (spinner) spinner.classList.add('show');
            if (viewer) viewer.classList.remove('show');

            const params = { path: {}, query: {}, body: null };

            if (endpoint.requestParams?.path) {
                for (const [key] of Object.entries(endpoint.requestParams.path)) {
                    const input = document.getElementById(`try_${endpointId}_path_${key}`);
                    if (input && input.value) {
                        params.path[key] = input.value;
                    }
                }
            }

            if (endpoint.requestParams?.query) {
                for (const [key] of Object.entries(endpoint.requestParams.query)) {
                    const input = document.getElementById(`try_${endpointId}_query_${key}`);
                    if (input && input.value) {
                        params.query[key] = input.value;
                    }
                }
            }

            if (endpoint.requestParams?.body) {
                const bodyInput = document.getElementById(`try_${endpointId}_body`);
                if (bodyInput && bodyInput.value) {
                    try {
                        params.body = JSON.parse(bodyInput.value);
                    } catch (e) {
                        if (spinner) spinner.classList.remove('show');
                        if (viewer) viewer.classList.add('show');
                        if (meta) {
                            meta.innerHTML = `
                                <span class="meta-item">
                                    <span class="label">Erreur:</span>
                                    <span class="text-danger">JSON invalide</span>
                                </span>
                            `;
                        }
                        if (body) {
                            body.innerHTML = `
                                <pre><code>${JSON.stringify({ error: 'Format JSON invalide', details: e.message }, null, 2)}</code></pre>
                            `;
                        }
                        return;
                    }
                }
            }

            try {
                const result = await ApiClient.testEndpoint(endpoint, params);

                if (spinner) spinner.classList.remove('show');
                if (viewer) viewer.classList.add('show');

                const statusClass = result.ok ? 'success' : (result.isNetworkError ? 'warning' : 'error');
                const statusText = result.ok ? `${result.status} ${result.statusText}` : (result.isNetworkError ? '⚠️ Erreur réseau' : `${result.status} ${result.statusText}`);

                if (meta) {
                    meta.innerHTML = `
                        <span class="meta-item">
                            <span class="label">Statut:</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">Temps:</span>
                            <span>${result.responseTime} ms</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">URL:</span>
                            <code style="font-size:0.8rem; word-break:break-all;">${ApiClient.getBaseUrl() + endpoint.path}</code>
                        </span>
                        <span class="meta-item">
                            <span class="label">Méthode:</span>
                            <strong>${endpoint.method}</strong>
                        </span>
                    `;
                }

                if (body) {
                    const sanitized = Utils.sanitizeForDisplay(result.data);
                    let displayData = sanitized;
                    try {
                        const parsed = JSON.parse(sanitized);
                        displayData = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        displayData = sanitized;
                    }

                    body.innerHTML = `<pre><code class="language-json">${displayData}</code></pre>`;

                    setTimeout(() => {
                        if (typeof Prism !== 'undefined') {
                            Prism.highlightElement(body.querySelector('code'));
                        }
                    }, 50);
                }

                if (!result.ok) {
                    const msg = result.data?.message || result.statusText || 'Erreur lors de la requête';
                    Utils.showToast(`❌ ${msg}`, 'danger');
                } else {
                    Utils.showToast('✅ Requête réussie', 'success');
                }

            } catch (error) {
                if (spinner) spinner.classList.remove('show');
                if (viewer) viewer.classList.add('show');
                if (body) {
                    body.innerHTML = `
                        <pre><code>${JSON.stringify({ error: 'Erreur lors de la requête', details: error.message }, null, 2)}</code></pre>
                    `;
                }
                Utils.showToast('❌ Erreur: ' + error.message, 'danger');
            }
        },

        clearTryIt(endpointId) {
            const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
            if (!endpoint) return;

            if (endpoint.requestParams?.path) {
                for (const [key] of Object.entries(endpoint.requestParams.path)) {
                    const input = document.getElementById(`try_${endpointId}_path_${key}`);
                    if (input) input.value = '';
                }
            }
            if (endpoint.requestParams?.query) {
                for (const [key] of Object.entries(endpoint.requestParams.query)) {
                    const input = document.getElementById(`try_${endpointId}_query_${key}`);
                    if (input) input.value = '';
                }
            }
            if (endpoint.requestParams?.body) {
                const input = document.getElementById(`try_${endpointId}_body`);
                if (input) {
                    let defaultBody = '{}';
                    if (endpoint.exampleRequest) {
                        defaultBody = JSON.stringify(endpoint.exampleRequest, null, 2);
                    }
                    input.value = defaultBody;
                }
            }

            const viewer = document.getElementById(`responseViewer_${endpointId}`);
            if (viewer) viewer.classList.remove('show');

            Utils.showToast('Formulaire réinitialisé', 'info');
        },

        loadExample(endpointId) {
            const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
            if (!endpoint || !endpoint.exampleRequest) return;

            if (endpoint.requestParams?.body) {
                const input = document.getElementById(`try_${endpointId}_body`);
                if (input) {
                    input.value = JSON.stringify(endpoint.exampleRequest, null, 2);
                }
            }

            Utils.showToast('Exemple chargé', 'success');
        },

        copyCode(btn) {
            const pre = btn.parentElement.querySelector('pre');
            const code = pre ? pre.textContent : '';
            navigator.clipboard.writeText(code).then(() => {
                btn.textContent = 'Copié !';
                setTimeout(() => { btn.textContent = 'Copier'; }, 2000);
            });
        }
    };

    // ================================================================
    // 11. RECHERCHE
    // ================================================================
    const SearchManager = {
        filter(query) {
            AppState.searchQuery = query.toLowerCase().trim();

            const navItems = document.querySelectorAll('.nav-item[data-endpoint]');
            const endpoints = API_DATA.endpoints;

            navItems.forEach(el => {
                const epId = el.dataset.endpoint;
                const ep = endpoints.find(e => e.id === epId);
                if (!ep || ep.isHome) {
                    el.style.display = '';
                    return;
                }

                const searchable = [
                    ep.name,
                    ep.path,
                    ep.method,
                    ep.description,
                    ep.module,
                    ...(ep.permissionsRequired || [])
                ].join(' ').toLowerCase();

                const match = !AppState.searchQuery || searchable.includes(AppState.searchQuery);
                el.style.display = match ? '' : 'none';
            });
        }
    };

    // ================================================================
    // 12. INITIALISATION
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        AppState.selectedEndpoint = 'home';

        Renderer.renderSidebar();
        Renderer.renderContent();

        document.getElementById('envSelector').addEventListener('change', function() {
            AppState.currentEnv = this.value;
            Renderer.renderContent();
            Utils.showToast(`Environnement : ${API_DATA.environments[this.value]?.label || this.value}`, 'info');
        });

        document.getElementById('searchInput').addEventListener('input', function() {
            SearchManager.filter(this.value);
        });

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });

        document.getElementById('authForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const login = document.getElementById('authLogin').value;
            const password = document.getElementById('authPassword').value;
            const statusDiv = document.getElementById('authFormStatus');

            if (!login || !password) {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Veuillez remplir tous les champs.';
                return;
            }

            statusDiv.className = 'alert alert-info';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connexion en cours...';

            const result = await AuthManager.login(login, password);

            if (result.success) {
                if (result.requires2fa) {
                    statusDiv.className = 'alert alert-warning';
                    statusDiv.innerHTML = `
                        <i class="fas fa-shield-halved me-2"></i>
                        Une vérification 2FA est requise. Utilisez l'endpoint /auth/2fa/verify-login.
                    `;
                    if (result.data?.data?.two_factor_token) {
                        AuthManager.setToken(result.data.data.two_factor_token);
                    }
                } else if (result.requiresPasswordChange) {
                    statusDiv.className = 'alert alert-warning';
                    statusDiv.innerHTML = `
                        <i class="fas fa-key me-2"></i>
                        Un changement de mot de passe est requis. Utilisez l'endpoint /auth/first-login.
                    `;
                    if (result.data?.data?.change_password_token) {
                        AuthManager.setToken(result.data.data.change_password_token);
                    }
                } else {
                    statusDiv.className = 'alert alert-success';
                    statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Authentification réussie !';
                    AuthManager.updateUI();
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                        if (modal) modal.hide();
                        Renderer.renderContent();
                    }, 1500);
                }
            } else {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${result.message || 'Erreur de connexion'}`;
            }
        });

        document.getElementById('authLogoutBtn').addEventListener('click', function() {
            AuthManager.logout();
            Renderer.renderContent();
            const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
            if (modal) modal.hide();
        });

        document.getElementById('authBtn').addEventListener('click', function(e) {
            if (AppState.isAuthenticated) {
                e.preventDefault();
                document.getElementById('authFormStatus').className = 'alert alert-success';
                document.getElementById('authFormStatus').innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Connecté en tant que ${AppState.currentUser?.email || 'utilisateur'}
                `;
                document.getElementById('authLogin').style.display = 'none';
                document.getElementById('authPassword').style.display = 'none';
                document.querySelector('#authForm label[for="authLogin"]').style.display = 'none';
                document.querySelector('#authForm label[for="authPassword"]').style.display = 'none';
                document.getElementById('authSubmitBtn').style.display = 'none';
                document.getElementById('authLogoutBtn').style.display = 'inline-block';
            } else {
                document.getElementById('authLogin').style.display = '';
                document.getElementById('authPassword').style.display = '';
                document.querySelector('#authForm label[for="authLogin"]').style.display = '';
                document.querySelector('#authForm label[for="authPassword"]').style.display = '';
                document.getElementById('authSubmitBtn').style.display = '';
                document.getElementById('authLogoutBtn').style.display = 'none';
                document.getElementById('authFormStatus').className = 'alert alert-info';
                document.getElementById('authFormStatus').innerHTML = `
                    <i class="fas fa-info-circle me-2"></i>
                    Connectez-vous pour tester les endpoints protégés.
                `;
                document.getElementById('authLogin').value = '';
                document.getElementById('authPassword').value = '';
            }
        });

        AuthManager.updateUI();

        setTimeout(() => {
            Utils.showToast('Bienvenue sur la documentation interactive YNOV API 🚀', 'info');
        }, 500);
    });

    window.Renderer = Renderer;
    window.Utils = Utils;
    window.AuthManager = AuthManager;
    window.AppState = AppState;
</script>

</body>
</html>
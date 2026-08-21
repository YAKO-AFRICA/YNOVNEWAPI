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
   Version adaptée aux modifications du code (ForgotPassword avec login,
   SecurityQuestions avec vérification multiple, etc.)
   ================================================================ */

console.warn = function() {
    const args = Array.from(arguments);
    if (args.some(arg => typeof arg === 'string' && arg.includes('Cookie'))) {
        return;
    }
    console.warn.apply(console, args);
};

// ================================================================
// 1. DONNÉES DES ENDPOINTS — COMPLÈTES ET COHÉRENTES
// ================================================================
const API_DATA = {
    environments: {
        local: { url: 'http://localhost:8000/api/v1', label: 'Local' },
        dev: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Development' },
        test: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Test' },
        staging: { url: 'https://apidev.yakoafricassur.com/api/v1', label: 'Staging' },
        production: { url: 'https://api.ynov.ci/api/v1', label: 'Production', protected: true }
    },

    modules: {
        home: { label: 'Accueil', icon: 'fa-house' },
        auth: { label: 'Authentification', icon: 'fa-right-to-bracket' },
        password: { label: 'Mots de Passe', icon: 'fa-key' },
        '2fa': { label: 'Double Authentification', icon: 'fa-shield-halved' },
        contrats: { label: 'Contrats', icon: 'fa-file-contract' },
        profile: { label: 'Profil & Sessions', icon: 'fa-user' },
        security: { label: 'Questions de Sécurité', icon: 'fa-question-circle' },
        users: { label: 'Gestion des Utilisateurs', icon: 'fa-users' },
        freeze: { label: 'Gel / Dégel', icon: 'fa-snowflake' },
        roles: { label: 'Rôles', icon: 'fa-user-shield' },
        permissions: { label: 'Permissions', icon: 'fa-key' },
        permGroups: { label: 'Groupes de Permissions', icon: 'fa-layer-group' },
        ip: { label: 'Restrictions IP', icon: 'fa-network-wired' },
        audit: { label: 'Logs & Audit', icon: 'fa-clipboard-list' },
        errors: { label: 'Codes HTTP & Erreurs', icon: 'fa-bug' }
    },

    endpoints: [
        // ============================================================
        // ACCUEIL
        // ============================================================
        {
            id: 'home',
            module: 'home',
            name: 'Présentation de l\'API',
            description: 'Bienvenue sur la documentation interactive de l\'API YNOV.',
            isHome: true
        },


        // ============================================================
        // AUTHENTIFICATION — INSCRIPTION CLIENT AVEC CONTRAT
        // ============================================================
        {
            id: 'auth-get-register-data',
            module: 'auth',
            name: 'Vérifier un contrat avant inscription',
            description: 'Permet de vérifier les informations d\'un contrat avant l\'inscription d\'un client. Vérifie que le contrat existe, que la date de naissance correspond, et que le contrat n\'est pas arrêté (OnStdbyOff != "3"). Retourne les informations complètes du contrat (détails, encaissements, ancienneté client).',
            method: 'POST',
            path: '/auth/get-register-data',
            isProtected: false,
            rateLimit: 'throttle:6,1 (6 tentatives / minute)',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    idcontrat: { type: 'string', required: true, description: 'Identifiant du contrat (IdProposition)' },
                    datenaissance: { type: 'date', required: true, format: 'Y-m-d', description: 'Date de naissance du titulaire (doit correspondre à celle du contrat)' }
                }
            },
            exampleRequest: {
                idcontrat: 'PROP2024001',
                datenaissance: '1990-05-15'
            },
            invalidExample: {
                idcontrat: 'PROP2024001',
                datenaissance: '1991-05-15'
            },
            invalidReason: 'La date de naissance saisie ne correspond pas à celle enregistrée dans le contrat.',
            responses: [
                { 
                    status: 200, 
                    description: 'Contrat trouvé et valide', 
                    example: {
                        success: true,
                        message: 'Contrat trouvé.',
                        data: {
                            details: [{
                                IdProposition: 'PROP2024001',
                                DateNaissance: '1990-05-15',
                                OnStdbyOff: '0',
                                TotalPrime: 150000.00,
                                CapitalSouscrit: 5000000.00,
                                NbreEncaissment: 12,
                                TotalEncaissement: 1800000.00,
                                NbreImpayes: 0,
                                TotalImpayes: 0
                            }],
                            autreContrat: [
                                {
                                    IdProposition: 'PROP2024002',
                                    DateNaissance: '1990-05-15',
                                    OnStdbyOff: '0',
                                    TotalPrime: 150000.00,
                                    CapitalSouscrit: 5000000.00,
                                    NbreEncaissment: 12,
                                    TotalEncaissement: 1800000.00,
                                    NbreImpayes: 0,
                                    TotalImpayes: 0
                                }
                            ],
                            contactsPersonne: [],
                            InfoPiecePersonson: []
                        }
                    }
                },
                { 
                    status: 422, 
                    description: 'Contrat arrêté (OnStdbyOff = "3")', 
                    example: {
                        type: 'error',
                        urlback: '',
                        message: 'Ce contrat est arreté.'
                    }
                },
                { 
                    status: 422, 
                    description: 'Date de naissance incorrecte', 
                    example: {
                        success: false,
                        message: 'La date de naissance saisie ne correspond pas à celle enregistrée dans le contrat.'
                    }
                },
                { 
                    status: 422, 
                    description: 'Contrat non trouvé ou service indisponible', 
                    example: {
                        success: false,
                        message: 'Service de recuperation des informations du contrat indisponible.'
                    }
                }
            ]
        },

        {
            id: 'auth-register-client',
            module: 'auth',
            name: 'Inscription client avec contrat',
            description: 'Permet à un client de s\'inscrire après avoir vérifié son contrat. Le mot de passe est généré automatiquement (12 caractères aléatoires) et envoyé par email ou SMS selon les informations disponibles. Crée l\'utilisateur (rôle "client"), ses détails et associe les contrats.',
            method: 'POST',
            path: '/auth/register',
            isProtected: false,
            rateLimit: 'throttle:6,1 (6 tentatives / minute)',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    // Identité
                    prenoms: { type: 'string', required: true, max: 255, description: 'Prénoms du client' },
                    nom: { type: 'string', required: true, max: 55, description: 'Nom du client' },
                    date_naissance: { type: 'date', required: false, format: 'Y-m-d', description: 'Date de naissance' },
                    lieu_naissance: { type: 'string', required: false, max: 55, description: 'Lieu de naissance' },
                    genre: { type: 'string', required: false, enum: ['M', 'F'], description: 'Genre' },
                    civilite: { type: 'string', required: false, max: 20, description: 'Civilité (M., Mme, Mlle, Dr, Pr)' },
                    nationalite: { type: 'string', required: false, max: 55, description: 'Nationalité' },
                    
                    // Coordonnées
                    email: { type: 'email', required: true, max: 100, description: 'Email (utilisé pour l\'envoi des identifiants si présent)' },
                    login: { type: 'string', required: true, max: 100, description: 'Identifiant de connexion (unique:users,login)' },
                    mobile_1: { type: 'string', required: true, max: 25, description: 'Téléphone principal (utilisé pour l\'envoi des identifiants si email absent)' },
                    
                    // Adresse
                    ville: { type: 'string', required: false, max: 55, description: 'Ville' },
                    code_postal: { type: 'string', required: false, max: 20, description: 'Code postal' },
                    lieu_residence: { type: 'string', required: false, max: 55, description: 'Lieu de résidence' },
                    pays: { type: 'string', required: false, max: 55, description: 'Pays' },
                    
                    // Profession
                    fonction: { type: 'string', required: false, max: 55, description: 'Fonction professionnelle' },
                    
                    // Contrats (obtenus de get-register-data)
                    contrats: { type: 'array', required: true, description: 'Liste des contrats à associer au client' },
                    'contrats.*.IdProposition': { type: 'string', description: 'Identifiant du contrat' },
                    'contrats.*.produit': { type: 'string', description: 'Libellé du produit' },
                    'contrats.*.codeProduit': { type: 'string', description: 'Code du produit' },
                    'contrats.*.CodeProduitFormule': { type: 'string', description: 'Code de la formule' },
                    'contrats.*.ProduitFormule': { type: 'string', description: 'Libellé de la formule' },
                    
                    // Informations client
                    numero_client: { type: 'string', required: false, description: 'Numéro client existant' },
                    client_number: { type: 'string', required: false, description: 'Numéro client pour les contrats' }
                }
            },
            exampleRequest: {
                prenoms: 'Jean',
                nom: 'Dupont',
                date_naissance: '1990-05-15',
                lieu_naissance: 'Abidjan',
                genre: 'M',
                civilite: 'M.',
                nationalite: 'Ivoirienne',
                email: 'jean.dupont@example.com',
                login: 'jdupont',
                mobile_1: '+2250708091011',
                ville: 'Abidjan',
                code_postal: '01 BP 1234',
                lieu_residence: 'Cocody',
                pays: 'Côte d\'Ivoire',
                fonction: 'Ingénieur',
                numero_client: 'CLT2024001',
                client_number: 'CLT2024001',
                contrats: [
                    {
                        IdProposition: 'PROP2024001',
                        produit: 'Assurance Vie Premium',
                        codeProduit: 'AVP001',
                        CodeProduitFormule: 'AVPF001',
                        ProduitFormule: 'Formule Excellence'
                    }
                ]
            },
            invalidExample: {
                prenoms: 'Jean',
                nom: 'Dupont',
                email: 'jean.dupont@example.com',
                login: 'jdupont',
                contrats: []
            },
            invalidReason: 'Le tableau "contrats" doit contenir au moins un contrat, et les champs email/login/mobile sont obligatoires.',
            responses: [
                { 
                    status: 201, 
                    description: 'Inscription réussie — identifiants envoyés', 
                    example: {
                        success: true,
                        message: 'Inscription réussie. Vos paramètres de connexion ont été envoyés.',
                        data: {
                            uuid_user: '...',
                            email: 'jean.dupont@example.com',
                            login: 'jdupont',
                            user_type: 'client',
                            status: 'actif'
                        }
                    }
                },
                { 
                    status: 422, 
                    description: 'Aucun rôle client configuré', 
                    example: {
                        success: false,
                        message: 'Aucun rôle client configuré.'
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
                            login: ['Cet identifiant est déjà utilisé.']
                        }
                    }
                }
            ]
        },
        
        // {
        //     id: 'auth-register',
        //     module: 'auth',
        //     name: 'Inscription',
        //     description: 'Permet à un nouvel utilisateur de créer un compte client. Le rôle par défaut (is_default = true) doit exister dans la base.',
        //     method: 'POST',
        //     path: '/auth/register',
        //     isProtected: false,
        //     rateLimit: 'throttle:6,1 (6 tentatives / minute)',
        //     headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        //     requestParams: {
        //         body: {
        //             prenoms: { type: 'string', required: true, max: 255, description: 'Prénoms' },
        //             nom: { type: 'string', required: true, max: 55, description: 'Nom' },
        //             email: { type: 'email', required: true, max: 100, description: 'Email (unique:users,email)' },
        //             login: { type: 'string', required: false, max: 100, description: 'Identifiant (unique:users,login)' },
        //             mobile_1: { type: 'string', required: false, max: 25, description: 'Téléphone' },
        //             password: { type: 'string', required: true, min: 8, description: 'Mot de passe (confirmed)' },
        //             password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
        //         }
        //     },
        //     exampleRequest: {
        //         prenoms: 'Jean', nom: 'Dupont', email: 'jean.dupont@example.com',
        //         login: 'jdupont', mobile_1: '+2250708091011',
        //         password: 'MonPassword123!', password_confirmation: 'MonPassword123!'
        //     },
        //     responses: [
        //         { status: 201, description: 'Inscription réussie', example: { success: true, message: 'Inscription réussie. Veuillez vérifier votre email.', data: { uuid_user: '...' } } },
        //         { status: 422, description: 'Erreur de validation (email/login déjà utilisé, mot de passe trop court...)', example: { success: false, message: 'Aucun rôle par défaut configuré.' } }
        //     ]
        // },

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
                { status: 401, description: 'Identifiants incorrects (même message pour userNotFound ou mauvais mot de passe)', example: { success: false, code: 'AUTH_ERROR', message: 'Identifiants incorrects.' } },
                { status: 403, description: 'IP bloquée, compte bloqué/inactif/suspendu', example: { success: false, code: 'AUTH_ERROR', message: 'Accès refusé depuis cette adresse IP.' } },
                { status: 423, description: 'Compte temporairement gelé (AccountFrozenException)', example: { success: false, message: 'Compte temporairement gelé. Réessayez dans 3 min 0 s.', freeze_level: 2, remaining_seconds: 180 } },
                { status: 500, description: 'Erreur interne', example: { success: false, code: 'SERVER_ERROR', message: 'Une erreur interne est survenue. Veuillez réessayer.' } }
            ]
        },

        {
            id: 'auth-freeze-check',
            module: 'auth',
            name: 'Vérifier le gel d\'un compte',
            description: 'Endpoint public pour vérifier si un compte (par login ou email) est actuellement gelé, avant d\'afficher le formulaire de connexion.',
            method: 'GET',
            path: '/auth/freeze-check/{login}',
            isProtected: false,
            rateLimit: 'throttle:30,1',
            headers: { 'Accept': 'application/json' },
            requestParams: {
                path: { login: { type: 'string', required: true, description: 'Email ou login de l\'utilisateur' } }
            },
            responses: [
                { status: 200, description: 'Compte non gelé', example: { success: true, data: { is_frozen: false } } },
                { status: 200, description: 'Compte gelé', example: { success: true, data: { is_frozen: true, remaining_seconds: 120, freeze_level: 2 } } }
            ]
        },

        // ============================================================
        // MOTS DE PASSE — PUBLIQUES
        // ============================================================
        {
            id: 'password-forgot',
            module: 'password',
            name: 'Mot de passe oublié',
            description: 'Demande de réinitialisation de mot de passe. **IMPORTANT :** utilise désormais `login` (email OU login) au lieu de `email`. Pour les utilisateurs de type `client` sans email, retourne directement les questions de sécurité. Protection anti-timing attack : opération factice même si l\'utilisateur n\'existe pas.',
            method: 'POST',
            path: '/auth/forgot-password',
            isProtected: false,
            rateLimit: 'throttle:login',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    login: { type: 'string', required: true, max: 100, description: 'Email OU login de l\'utilisateur (exists:users,login)' }
                }
            },
            exampleRequest: { login: 'admin@ynov.ci' },
            responses: [
                { status: 200, description: 'Requête traitée (utilisateur client sans email → questions de sécurité retournées)', example: { success: true, data: { token: '...', user_uuid: '...', has_configured: true, questions: [] } } },
                { status: 200, description: 'Requête traitée (email envoyé ou utilisateur inexistant — même message)', example: { success: true, message: 'Un lien a été envoyé vers votre adresse email pour réinitialiser votre mot de passe.' } }
            ]
        },

        {
            id: 'password-reset',
            module: 'password',
            name: 'Réinitialiser le mot de passe',
            description: 'Réinitialise le mot de passe à partir du token. Vérifie l\'historique (5 derniers mots de passe non réutilisables). Utilise désormais `login` (email OU login).',
            method: 'POST',
            path: '/auth/reset-password',
            isProtected: false,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    login: { type: 'string', required: true, max: 100, description: 'Email OU login de l\'utilisateur' },
                    token: { type: 'string', required: true, description: 'Token de réinitialisation reçu par email' },
                    password: { type: 'string', required: true, min: 12, description: 'Nouveau mot de passe (majuscule, minuscule, chiffre, symbole)' },
                    password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
                }
            },
            exampleRequest: { login: 'jean.dupont@example.com', token: 'abc123def456', password: 'NouveauMdp123!', password_confirmation: 'NouveauMdp123!' },
            responses: [
                { status: 200, description: 'Mot de passe réinitialisé', example: { success: true, message: 'Mot de passe réinitialisé avec succès.' } },
                { status: 422, description: 'Token invalide/expiré ou mot de passe déjà utilisé', example: { success: false, message: 'Token invalide ou expiré.' } },
                { status: 422, description: 'Ancien mot de passe réutilisé', example: { success: false, message: 'Vous ne pouvez pas réutiliser un ancien mot de passe.' } }
            ]
        },

        // ============================================================
        // QUESTIONS DE SÉCURITÉ — PUBLIQUES
        // ============================================================
        {
            id: 'security-suggested',
            module: 'security',
            name: 'Questions suggérées (publiques)',
            description: 'Retourne une liste statique de questions de sécurité prédéfinies, groupées par catégorie. Utile pour le front-end lors de la configuration.',
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
            id: 'security-verify-email',
            module: 'security',
            name: 'Vérifier les questions (par login)',
            description: 'Vérifie si un compte (par login) a configuré des questions de sécurité. **ATTENTION :** endpoint public qui retourne les questions configurées — risque d\'énumération de comptes (à sécuriser en production). Rate-limité par IP (5 tentatives / 5 min).',
            method: 'POST',
            path: '/security/verify-email',
            isProtected: false,
            rateLimit: 'throttle:5,15 + ThrottleService (5/300)',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: { login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur (exists:users,login)' } }
            },
            exampleRequest: { login: 'jdupont' },
            responses: [
                { status: 200, description: 'Compte trouvé — questions retournées', example: { success: true, data: { user_uuid: '...', has_questions: true, questions: [] } } },
                { status: 404, description: 'Login non trouvé', example: { success: false, message: 'Login non trouvé.' } },
                { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives. Veuillez patienter.', code: 'TOO_MANY_ATTEMPTS' } }
            ]
        },

        {
            id: 'security-verify-answer',
            module: 'security',
            name: 'Vérifier les réponses (multi-questions)',
            description: 'Vérifie une ou plusieurs réponses aux questions de sécurité. **NOUVEAU :** accepte un tableau de questions/réponses. Retourne un token de réinitialisation si toutes les réponses sont correctes. Rate-limité par utilisateur (5 tentatives / 5 min).',
            method: 'POST',
            path: '/security/verify-answer',
            isProtected: false,
            rateLimit: 'throttle:5,15 + ThrottleService (5/300 par user)',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    login: { type: 'string', required: true, max: 100, description: 'Login de l\'utilisateur' },
                    questions: { type: 'array', required: true, min: 1, description: 'Tableau des questions/réponses à vérifier' },
                    'questions.*.question_uuid': { type: 'uuid', required: true, description: 'UUID de la question (exists:security_questions,uuid)' },
                    'questions.*.answer': { type: 'string', required: true, min: 1, max: 255, description: 'Réponse à vérifier' }
                }
            },
            exampleRequest: {
                login: 'jdupont',
                questions: [
                    { question_uuid: 'q1-uuid', answer: 'Rex' },
                    { question_uuid: 'q2-uuid', answer: 'Bouaké' }
                ]
            },
            responses: [
                { status: 200, description: 'Toutes les réponses correctes — token retourné', example: { success: true, message: 'Toutes les réponses sont correctes.', data: { verified: true, user_uuid: '...', reset_token: '...', results: [{ question_uuid: '...', verified: true }] } } },
                { status: 422, description: 'Une ou plusieurs réponses incorrectes', example: { success: false, message: 'Une ou plusieurs réponses sont incorrectes.', remaining_attempts: 3, data: { verified: false, results: [{ question_uuid: '...', verified: false }] } } },
                { status: 429, description: 'Trop de tentatives', example: { success: false, message: 'Trop de tentatives...', code: 'TOO_MANY_ATTEMPTS' } }
            ]
        },

        // ============================================================
        // 2FA — PUBLIQUES (avec token temporaire)
        // ============================================================
        {
            id: '2fa-verify-login-public',
            module: '2fa',
            name: 'Vérifier 2FA (post-login)',
            description: 'Vérifie le code TOTP à 6 chiffres après une connexion nécessitant la 2FA. Utilise le token temporaire (ability 2fa-verify) reçu au login. Permet de marquer l\'appareil comme "de confiance".',
            method: 'POST',
            path: '/auth/2fa/verify-login',
            isProtected: true,
            abilityRequired: '2fa-verify',
            rateLimit: 'throttle:5,10 (5 tentatives / 10 min)',
            headers: { 'Authorization': 'Bearer {two_factor_token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    code: { type: 'string', required: true, size: 6, description: 'Code TOTP à 6 chiffres' },
                    trust_device: { type: 'boolean', required: false, default: false, description: 'Marquer cet appareil comme de confiance' }
                }
            },
            exampleRequest: { code: '123456', trust_device: true },
            responses: [
                { status: 200, description: '2FA vérifiée, nouveau token complet', example: { success: true, data: { user: {}, access_token: '...', expires_at: '...', trusted_device: true } } },
                { status: 401, description: 'Token invalide', example: { success: false, message: 'Token invalide.' } },
                { status: 422, description: 'Code 2FA invalide', example: { success: false, message: 'Code 2FA invalide.' } }
            ]
        },

        {
            id: 'otp-verify-login-public',
            module: '2fa',
            name: 'Vérifier un OTP (post-login)',
            description: 'Vérifie un code OTP (email/SMS) après une connexion, avec un token temporaire.',
            method: 'POST',
            path: '/auth/otp/verify-login',
            isProtected: true,
            rateLimit: 'throttle:5,10',
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    code: { type: 'string', required: true, size: 6, description: 'Code OTP' },
                    purpose: { type: 'string', required: true, description: 'Usage du code (login, 2fa, reset)' }
                }
            },
            exampleRequest: { code: '654321', purpose: 'login' },
            responses: [
                { status: 200, description: 'OTP vérifié', example: { success: true, message: 'Code OTP vérifié.' } },
                { status: 422, description: 'OTP invalide ou expiré', example: { success: false, message: 'Code OTP invalide ou expiré.' } }
            ]
        },

        // ============================================================
        // AUTHENTIFICATION — PROTÉGÉES
        // ============================================================
        {
            id: 'auth-me',
            module: 'auth',
            name: 'Utilisateur connecté',
            description: 'Récupère les informations complètes de l\'utilisateur authentifié (rôle, permissions, détails, partenaire, réseau, agences, groupes de notification).',
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
            module: 'auth',
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
            module: 'auth',
            name: 'Déconnexion de tous les appareils',
            description: 'Révoque tous les tokens Sanctum de l\'utilisateur et envoie un email de notification (SessionRevokedMail).',
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
            module: 'auth',
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
            id: 'password-change',
            module: 'password',
            name: 'Changer le mot de passe',
            description: 'Change le mot de passe d\'un utilisateur authentifié après vérification du mot de passe actuel et de l\'historique (5 derniers mots de passe interdits).',
            method: 'POST',
            path: '/auth/change-password',
            isProtected: true,
            permissionsRequired: ['auth.change_password'],
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    current_password: { type: 'string', required: true, description: 'Mot de passe actuel' },
                    password: { type: 'string', required: true, min: 12, description: 'Nouveau mot de passe (majuscule, minuscule, chiffre, symbole)' },
                    password_confirmation: { type: 'string', required: true, description: 'Confirmation' }
                }
            },
            exampleRequest: { current_password: 'AncienMdp123!', password: 'NouveauMdp456!', password_confirmation: 'NouveauMdp456!' },
            responses: [
                { status: 200, description: 'Mot de passe changé', example: { success: true, message: 'Mot de passe changé avec succès.' } },
                { status: 422, description: 'Mot de passe actuel incorrect ou réutilisation', example: { success: false, message: 'Mot de passe actuel incorrect.' } }
            ]
        },

        {
            id: 'password-first-login',
            module: 'password',
            name: 'Première connexion — définir le mot de passe',
            description: 'Définit le mot de passe initial lors de la première connexion, ou lors de son expiration. Nécessite le token temporaire avec ability password-change reçu au login.',
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
                { status: 200, description: 'Mot de passe initialisé, nouveau token complet', example: { success: true, message: 'Mot de passe initialisé.', data: { access_token: '...', expires_at: '...', user: {} } } },
                { status: 422, description: 'Changement non requis pour ce compte', example: { success: false, code: 'PASSWORD_CHANGE_NOT_REQUIRED', message: 'Le changement de mot de passe n\'est pas requis pour ce compte.' } }
            ]
        },

        // ============================================================
        // 2FA — PROTÉGÉES
        // ============================================================
        {
            id: '2fa-enable',
            module: '2fa',
            name: 'Activer 2FA — QR Code',
            description: 'Génère un secret TOTP et retourne l\'URL du QR Code au format SVG.',
            method: 'GET',
            path: '/auth/2fa/qrcode',
            isProtected: true,
            permissionsRequired: ['auth.2fa'],
            headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            requestParams: { body: {} },
            responses: [
                { status: 200, description: 'QR Code généré', example: { success: true, data: { secret: '...', qr_code_svg: '<svg>...</svg>' } } },
                { status: 422, description: '2FA déjà activé', example: { success: false, message: '2FA déjà activé.' } }
            ]
        },

        {
            id: '2fa-confirm',
            module: '2fa',
            name: 'Confirmer l\'activation 2FA',
            description: 'Confirme l\'activation de la 2FA en vérifiant le premier code TOTP. Génère 8 codes de récupération à usage unique.',
            method: 'POST',
            path: '/auth/2fa/confirm',
            isProtected: true,
            permissionsRequired: ['auth.2fa'],
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: { code: { type: 'string', required: true, size: 6, description: 'Code TOTP' } }
            },
            exampleRequest: { code: '123456' },
            responses: [
                { status: 200, description: '2FA activé', example: { success: true, message: '2FA activé avec succès.', data: { recovery_codes: ['abc123', '...'] } } },
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
            id: 'otp-send',
            module: '2fa',
            name: 'Envoyer un code OTP',
            description: 'Génère et envoie un code OTP à 6 chiffres par email ou SMS (via Infobip), valable 2 minutes.',
            method: 'POST',
            path: '/auth/otp/send',
            isProtected: true,
            permissionsRequired: ['auth.2fa'],
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    channel: { type: 'string', required: true, enum: ['email', 'sms'], description: 'Canal d\'envoi' },
                    purpose: { type: 'string', required: true, enum: ['login', '2fa', 'reset'], description: 'Usage du code' }
                }
            },
            exampleRequest: { channel: 'email', purpose: '2fa' },
            responses: [
                { status: 200, description: 'OTP envoyé', example: { success: true, message: 'Code OTP envoyé avec succès.', data: { channel: 'email', purpose: '2fa', expires_in: 2 } } },
                { status: 422, description: 'Numéro invalide (SMS)', example: { success: false, message: 'Numéro de téléphone invalide pour l\'envoi SMS.' } }
            ]
        },

        {
            id: 'otp-verify',
            module: '2fa',
            name: 'Vérifier un OTP (authentifié)',
            description: 'Vérifie un code OTP précédemment envoyé, pour un utilisateur déjà authentifié.',
            method: 'POST',
            path: '/auth/otp/verify',
            isProtected: true,
            permissionsRequired: ['auth.2fa'],
            rateLimit: 'throttle:5,10',
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    code: { type: 'string', required: true, size: 6, description: 'Code OTP' },
                    purpose: { type: 'string', required: true, description: 'Usage du code' }
                }
            },
            exampleRequest: { code: '123456', purpose: '2fa' },
            responses: [
                { status: 200, description: 'OTP vérifié', example: { success: true, message: 'Code OTP vérifié.' } },
                { status: 422, description: 'OTP invalide ou expiré', example: { success: false, message: 'Code OTP invalide ou expiré.' } }
            ]
        },

        // ============================================================
        // PROFIL, APPAREILS & SESSIONS
        // ============================================================
        {
            id: 'profile-show',
            module: 'profile',
            name: 'Mon profil',
            description: 'Récupère les informations complètes du profil de l\'utilisateur connecté.',
            method: 'GET',
            path: '/profile',
            isProtected: true,
            headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            requestParams: { body: {} },
            responses: [
                { status: 200, description: 'Profil utilisateur', example: { success: true, data: { uuid_user: '...', details: {} } } }
            ]
        },

        {
            id: 'profile-update',
            module: 'profile',
            name: 'Mettre à jour mon profil',
            description: 'Modifie les informations personnelles (login, nom, prénoms, fonction, mobiles, photo, ville, pays).',
            method: 'PUT',
            path: '/profile',
            isProtected: true,
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    login: { type: 'string', required: false, max: 100, description: 'Identifiant (unique)' },
                    nom: { type: 'string', required: false, max: 55, description: 'Nom' },
                    prenoms: { type: 'string', required: false, max: 255, description: 'Prénoms' },
                    fonction: { type: 'string', required: false, max: 55, description: 'Fonction' },
                    mobile_1: { type: 'string', required: false, max: 25, description: 'Téléphone principal' },
                    mobile_2: { type: 'string', required: false, max: 25, description: 'Téléphone secondaire' },
                    photo: { type: 'string', required: false, max: 255, description: 'Chemin de la photo' },
                    ville: { type: 'string', required: false, max: 100, description: 'Ville' },
                    pays: { type: 'string', required: false, max: 100, description: 'Pays' }
                }
            },
            exampleRequest: { nom: 'Dupont', prenoms: 'Jean', mobile_1: '+2250708091011' },
            responses: [
                { status: 200, description: 'Profil mis à jour', example: { success: true, message: 'Profil mis à jour.', data: {} } }
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
        // QUESTIONS DE SÉCURITÉ — PROTÉGÉES
        // ============================================================
        {
            id: 'security-questions-available',
            module: 'security',
            name: 'Questions disponibles',
            description: 'Récupère toutes les questions de sécurité actives disponibles dans le système.',
            method: 'GET',
            path: '/security/questions',
            isProtected: true,
            headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            requestParams: { body: {} },
            responses: [
                { status: 200, description: 'Liste des questions', example: { success: true, data: [{ uuid: '...', question_text: '...', category: '...' }] } }
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
            description: 'Définit (remplace) les réponses de sécurité de l\'utilisateur. Entre 3 et 5 questions distinctes. **Nécessite le mot de passe actuel** (défense en profondeur).',
            method: 'POST',
            path: '/security/user-questions',
            isProtected: true,
            headers: { 'Authorization': 'Bearer {token}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            requestParams: {
                body: {
                    answers: { type: 'array', required: true, min: 3, max: 5, description: 'Tableau { question_uuid, answer }' },
                    password: { type: 'string', required: true, description: 'Mot de passe actuel (vérification obligatoire)' }
                }
            },
            exampleRequest: {
                answers: [
                    { question_uuid: 'q1-uuid', answer: 'Rex' },
                    { question_uuid: 'q2-uuid', answer: 'Bouaké' },
                    { question_uuid: 'q3-uuid', answer: 'Aya' }
                ],
                password: 'MonPassword123!'
            },
            responses: [
                { status: 200, description: 'Questions configurées', example: { success: true, message: 'Questions de sécurité configurées avec succès.' } },
                { status: 403, description: 'Mot de passe incorrect', example: { success: false, message: 'Mot de passe incorrect.', code: 'INVALID_PASSWORD' } },
                { status: 422, description: 'Validation échouée', example: { success: false, message: 'Vous ne pouvez pas sélectionner deux fois la même question.' } }
            ]
        },

        // ============================================================
        // ADMIN — QUESTIONS DE SÉCURITÉ
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
            exampleRequest: { question_text: 'Quel est le nom de votre premier employeur (màj) ?' },
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
        // GESTION DES UTILISATEURS
        // ============================================================
        {
            id: 'users-list',
            module: 'users',
            name: 'Liste des utilisateurs',
            description: 'Liste paginée des utilisateurs. Filtrée selon la portée de l\'utilisateur connecté (Super Admin = tout, sinon restriction par partenaire/réseau/agence).',
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
            exampleRequest: {
                email: 'nouveau@ynov.ci', login: 'nouveau', password: 'Password123!',
                role_uuid: 'role-uuid', user_type: 'user_interne', nom: 'Dupont', prenoms: 'Jean'
            },
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
        // GEL / DÉGEL
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
        // RÔLES
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
        // PERMISSIONS
        // ============================================================
        {
            id: 'permissions-suggested-actions',
            module: 'permissions',
            name: 'Actions suggérées',
            description: 'Liste des actions standards suggérées pour la création de permissions (Créer, Afficher, Modifier, Supprimer, Geler, Dégeler...).',
            method: 'GET',
            path: '/permissions/suggested-actions',
            isProtected: true,
            permissionsRequired: ['permissions.afficher'],
            headers: { 'Authorization': 'Bearer {token}', 'Accept': 'application/json' },
            requestParams: { body: {} },
            responses: [
                { status: 200, description: 'Actions suggérées', example: { success: true, code: 'ACTIONS_SUGGESTED', data: ['Créer', 'Afficher', 'Modifier', 'Supprimer'] } }
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
        // GROUPES DE PERMISSIONS
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
        // RESTRICTIONS IP
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
        // LOGS & AUDIT
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
        }
    ],

    // ============================================================
    // CODES HTTP — observés dans le code
    // ============================================================
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

    // ============================================================
    // ERREURS MÉTIER — codes applicatifs
    // ============================================================
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
        { code: 'TOO_MANY_ATTEMPTS', message: 'Trop de tentatives.', cause: 'Rate limiting dépassé.', endpoint: 'Security endpoints', action: 'Attendre le délai indiqué.' }
    ]
};

// ================================================================
// 2. ÉTAT DE L'APPLICATION
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
// 3. UTILITAIRES
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
// 4. CLIENT API
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
// 5. GESTION DE L'AUTHENTIFICATION
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
// 6. RENDU DE LA DOCUMENTATION
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
// 7. RECHERCHE
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
// 8. INITIALISATION
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
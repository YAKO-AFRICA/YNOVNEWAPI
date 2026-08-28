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

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-display);
            background: var(--ynov-light);
            color: var(--ynov-dark);
            padding-top: var(--header-height);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: var(--ynov-primary);
        }

        a:hover {
            color: var(--ynov-primary-dark);
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #eef3ef;
        }

        ::-webkit-scrollbar-thumb {
            background: #b7c9bd;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9ab0a1;
        }

        /* ============ HEADER ============ */
        .ynov-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
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

        .ynov-header .brand i {
            color: var(--ynov-accent);
        }

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
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .ynov-header .env-selector:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .ynov-header .env-selector option {
            color: var(--ynov-dark);
            background: white;
        }

        .ynov-header .auth-status {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 0.85rem;
        }

        .ynov-header .auth-status .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .ynov-header .auth-status .status-dot.online {
            background: var(--ynov-accent);
            box-shadow: 0 0 0 3px rgba(247, 164, 0, 0.25);
        }

        .ynov-header .auth-status .status-dot.offline {
            background: #e57373;
        }

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

        .btn-header:hover {
            background: var(--ynov-accent-dark);
            color: var(--ynov-primary-darker);
        }

        .btn-header i {
            margin-right: 6px;
        }

        /* ============ SIDEBAR ============ */
        .ynov-sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
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
            box-shadow: 0 0 0 3px rgba(9, 104, 53, 0.12);
        }

        .ynov-sidebar .sidebar-search .search-icon {
            position: absolute;
            left: 22px;
            top: 50%;
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

        .ynov-sidebar .nav-item .method-badge.get {
            background: var(--ynov-primary-50);
            color: var(--ynov-primary);
        }

        .ynov-sidebar .nav-item .method-badge.post {
            background: var(--ynov-accent-50);
            color: var(--ynov-accent-darker);
        }

        .ynov-sidebar .nav-item .method-badge.put {
            background: #fff3cd;
            color: #8a6300;
        }

        .ynov-sidebar .nav-item .method-badge.patch {
            background: #ffe3d1;
            color: #9a4b12;
        }

        .ynov-sidebar .nav-item .method-badge.delete {
            background: #fbdede;
            color: #a12b23;
        }

        .ynov-sidebar .nav-item .nav-icon {
            width: 20px;
            text-align: center;
            color: var(--ynov-secondary);
            font-size: 0.8rem;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(6, 20, 12, 0.4);
            z-index: 1035;
        }

        .sidebar-overlay.show {
            display: block;
        }

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

        .ynov-main .page-header .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }

        .ynov-main .page-header .breadcrumb-item a {
            color: var(--ynov-secondary);
        }

        .ynov-main .page-header .breadcrumb-item.active {
            color: var(--ynov-primary);
        }

        /* ============ ENDPOINT CARD ============ */
        .endpoint-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--ynov-border);
            box-shadow: 0 2px 10px rgba(9, 104, 53, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .endpoint-card:hover {
            box-shadow: 0 6px 24px rgba(9, 104, 53, 0.1);
        }

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

        .endpoint-card .endpoint-header .endpoint-method.get {
            background: #d3ecdd;
            color: var(--ynov-primary-dark);
        }

        .endpoint-card .endpoint-header .endpoint-method.post {
            background: var(--ynov-accent-100);
            color: var(--ynov-accent-darker);
        }

        .endpoint-card .endpoint-header .endpoint-method.put {
            background: #fff3cd;
            color: #8a6300;
        }

        .endpoint-card .endpoint-header .endpoint-method.patch {
            background: #ffe3d1;
            color: #9a4b12;
        }

        .endpoint-card .endpoint-header .endpoint-method.delete {
            background: #fbdede;
            color: #a12b23;
        }

        .endpoint-card .endpoint-header .endpoint-path {
            font-family: var(--font-mono);
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--ynov-dark);
            flex: 1;
        }

        .endpoint-card .endpoint-header .endpoint-path .base-url {
            color: var(--ynov-secondary);
            font-weight: 400;
            font-size: 0.8rem;
        }

        .endpoint-card .endpoint-header .endpoint-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .endpoint-card .endpoint-header .endpoint-badges .badge {
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 10px;
        }

        .endpoint-card .endpoint-body {
            padding: 1.5rem;
        }

        .endpoint-card .endpoint-body .section-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--ynov-dark);
            margin: 1.5rem 0 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .endpoint-card .endpoint-body .section-title:first-child {
            margin-top: 0;
        }

        .endpoint-card .endpoint-body .section-title i {
            color: var(--ynov-primary);
            font-size: 0.9rem;
        }

        .endpoint-card .endpoint-body .description {
            color: #445a4c;
            line-height: 1.7;
        }

        .endpoint-card .endpoint-body .description code {
            background: var(--ynov-light);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--ynov-accent-darker);
        }

        /* ============ TABLES ============ */
        .doc-table {
            font-size: 0.9rem;
            border-radius: 8px;
            overflow: hidden;
        }

        .doc-table thead {
            background: var(--ynov-light);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--ynov-secondary);
        }

        .doc-table thead th {
            border-bottom: 2px solid var(--ynov-border);
            padding: 10px 14px;
        }

        .doc-table tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef3ef;
            vertical-align: middle;
        }

        .doc-table tbody tr:last-child td {
            border-bottom: none;
        }

        .doc-table .required-badge {
            background: var(--ynov-accent-100);
            color: var(--ynov-accent-darker);
            font-size: 0.65rem;
            font-weight: 800;
            padding: 1px 8px;
            border-radius: 4px;
        }

        .doc-table .optional-badge {
            background: #e9ede9;
            color: #56655c;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 8px;
            border-radius: 4px;
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

        .code-block pre {
            margin: 0;
            color: var(--code-color);
            font-family: var(--font-mono);
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .code-block .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(247, 164, 0, 0.15);
            border: 1px solid rgba(247, 164, 0, 0.3);
            color: var(--ynov-accent);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .code-block .copy-btn:hover {
            background: rgba(247, 164, 0, 0.3);
            color: white;
        }

        /* ============ TRY IT ============ */
        .try-it-section {
            margin-top: 1.5rem;
            border-top: 1px solid var(--ynov-border);
            padding-top: 1.5rem;
        }

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

        .try-it-section .try-it-toggle:hover {
            background: var(--ynov-primary);
            color: white;
        }

        .try-it-panel {
            display: none;
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--ynov-light);
            border-radius: 8px;
            border: 1px solid var(--ynov-border);
        }

        .try-it-panel.show {
            display: block;
        }

        .try-it-panel .form-group {
            margin-bottom: 1rem;
        }

        .try-it-panel .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #445a4c;
        }

        .try-it-panel .form-group .form-control,
        .try-it-panel .form-group .form-select {
            font-size: 0.9rem;
            border-radius: 6px;
            border: 1px solid #ced9d0;
        }

        .try-it-panel .form-group .form-control:focus,
        .try-it-panel .form-group .form-select:focus {
            border-color: var(--ynov-primary);
            box-shadow: 0 0 0 3px rgba(9, 104, 53, 0.12);
        }

        .try-it-panel .json-editor {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            min-height: 120px;
            resize: vertical;
        }

        .try-it-panel .send-btn {
            padding: 8px 30px;
            font-weight: 700;
            border-radius: 8px;
            background: var(--ynov-accent);
            border-color: var(--ynov-accent);
            color: var(--ynov-primary-darker);
        }

        .try-it-panel .send-btn:hover {
            background: var(--ynov-accent-dark);
            border-color: var(--ynov-accent-dark);
            color: white;
        }

        /* ============ RESPONSE VIEWER ============ */
        .response-viewer {
            margin-top: 1rem;
            display: none;
        }

        .response-viewer.show {
            display: block;
        }

        .response-viewer .response-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px 14px;
            background: white;
            border-radius: 8px 8px 0 0;
            border: 1px solid var(--ynov-border);
            border-bottom: none;
        }

        .response-viewer .response-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }

        .response-viewer .response-meta .meta-item .label {
            color: var(--ynov-secondary);
        }

        .response-viewer .response-meta .status-badge {
            font-weight: 800;
            padding: 2px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .response-viewer .response-meta .status-badge.success {
            background: #d3ecdd;
            color: var(--ynov-primary-dark);
        }

        .response-viewer .response-meta .status-badge.error {
            background: #fbdede;
            color: #a12b23;
        }

        .response-viewer .response-meta .status-badge.warning {
            background: var(--ynov-accent-100);
            color: var(--ynov-accent-darker);
        }

        .response-viewer .response-meta .status-badge.info {
            background: #d9edf1;
            color: #0f7a8c;
        }

        .response-viewer .response-body {
            background: var(--code-bg);
            border-radius: 0 0 8px 8px;
            padding: 1.25rem;
            overflow-x: auto;
            border: 1px solid #123a24;
            border-top: none;
        }

        .response-viewer .response-body pre {
            margin: 0;
            color: var(--code-color);
            font-family: var(--font-mono);
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .spinner-overlay {
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .spinner-overlay.show {
            display: flex;
        }

        .spinner-overlay .spinner-border {
            color: var(--ynov-primary);
        }

        .toast-container {
            z-index: 1060;
        }

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
            top: -60px;
            right: -60px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(247, 164, 0, 0.35) 0%, rgba(247, 164, 0, 0) 70%);
        }

        .home-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .home-hero p {
            font-size: 1.1rem;
            opacity: 0.92;
            max-width: 700px;
            position: relative;
            z-index: 1;
        }

        .home-hero .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .home-hero .quick-links .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
        }

        .home-hero .quick-links .btn-light {
            background: var(--ynov-accent);
            border-color: var(--ynov-accent);
            color: var(--ynov-primary-darker);
        }

        .home-hero .quick-links .btn-light:hover {
            background: var(--ynov-accent-dark);
        }

        .home-hero .quick-links .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.4);
        }

        .home-hero .quick-links .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .home-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .home-stats .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            border: 1px solid var(--ynov-border);
            border-top: 3px solid var(--ynov-accent);
        }

        .home-stats .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--ynov-primary);
        }

        .home-stats .stat-card .stat-label {
            font-size: 0.85rem;
            color: var(--ynov-secondary);
        }

        .permission-badge {
            font-size: 0.7rem;
            background: #e9ede9;
            color: #56655c;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .permission-badge.required {
            background: var(--ynov-accent-100);
            color: var(--ynov-accent-darker);
        }

        /* Bootstrap overrides */
        .btn-primary {
            background-color: var(--ynov-primary);
            border-color: var(--ynov-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--ynov-primary-dark);
            border-color: var(--ynov-primary-dark);
        }

        .btn-outline-primary {
            color: var(--ynov-primary);
            border-color: var(--ynov-primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--ynov-primary);
            border-color: var(--ynov-primary);
        }

        .text-primary {
            color: var(--ynov-primary) !important;
        }

        .bg-primary {
            background-color: var(--ynov-primary) !important;
        }

        .bg-warning {
            background-color: var(--ynov-accent) !important;
            color: var(--ynov-primary-darker) !important;
        }

        .badge.bg-warning {
            color: var(--ynov-primary-darker) !important;
        }

        .badge.bg-success {
            background-color: var(--ynov-primary) !important;
        }

        .badge.bg-danger {
            background-color: #c9372c !important;
        }

        .badge.bg-info {
            background-color: #0f7a8c !important;
        }

        .form-check-input:checked {
            background-color: var(--ynov-primary);
            border-color: var(--ynov-primary);
        }

        .accordion-button:not(.collapsed) {
            color: var(--ynov-primary);
        }

        .modal-header {
            border-bottom-color: var(--ynov-border);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        .text-muted-small {
            color: var(--ynov-secondary);
            font-size: 0.8rem;
        }

        .gap-1 {
            gap: 0.25rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .gap-3 {
            gap: 1rem;
        }

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
            .ynov-sidebar {
                transform: translateX(-100%);
            }

            .ynov-sidebar.show {
                transform: translateX(0);
            }

            .ynov-main {
                margin-left: 0;
                padding: 1.5rem 1.5rem 3rem;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .ynov-header .brand {
                font-size: 1rem;
            }

            .ynov-header .brand .badge-version {
                font-size: 0.55rem;
            }
        }

        @media (max-width: 575.98px) {
            .ynov-header {
                padding: 0 1rem;
            }

            .ynov-header .header-actions .env-selector {
                font-size: 0.75rem;
                padding: 4px 8px;
                max-width: 120px;
            }

            .ynov-header .header-actions .auth-status {
                font-size: 0.75rem;
            }

            .ynov-main {
                padding: 1rem 1rem 2rem;
            }

            .ynov-main .page-header h1 {
                font-size: 1.5rem;
            }

            .endpoint-card .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .endpoint-card .endpoint-header .endpoint-path {
                font-size: 0.85rem;
                word-break: break-all;
            }
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
    <script src="{{ asset('assets/js/documentation.js') }}"></script>
    

</body>

</html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Docs — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #1a1a2e;
            --surface: #16213e;
            --border: #2a3a5c;
            --text: #e8eaf0;
            --muted: #8b95a8;
            --accent: #4fc3f7;
            --get: #61affe;
            --post: #49cc90;
            --put: #fca130;
            --patch: #50e3c2;
            --delete: #f93e3e;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        /* —— Sidebar (trái) —— */
        .sidebar {
            width: 280px;
            flex-shrink: 0;
            background: #121a2f;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.1rem 1.15rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .sidebar-brand h1 {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.2rem;
        }

        .sidebar-brand p {
            color: var(--muted);
            font-size: 0.72rem;
        }

        .sidebar-user {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .sidebar-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #2979ff);
            color: #0b1220;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-user-meta {
            min-width: 0;
            flex: 1;
        }

        .sidebar-user-name {
            font-size: 0.88rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-email {
            font-size: 0.72rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-section {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-section:last-of-type {
            border-bottom: none;
            flex: 1;
        }

        .sidebar-label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.45rem;
        }

        .sidebar-actions {
            padding: 1rem 1.15rem;
            margin-top: auto;
            border-top: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover { border-color: var(--accent); }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #0b1220;
        }

        .btn-danger {
            border-color: #f93e3e55;
            color: #ff8a8a;
            width: 100%;
        }

        .btn-block { width: 100%; }

        .search,
        .token-input,
        .field-input,
        .body-input,
        .query-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            font-size: 0.875rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            width: 100%;
        }

        .search {
            font-family: inherit;
        }

        .search:focus,
        .token-input:focus,
        .field-input:focus,
        .body-input:focus,
        .query-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .stats {
            display: block;
            margin-top: 0.45rem;
            font-size: 0.75rem;
            color: var(--muted);
        }

        /* —— Content (phải) —— */
        .content-wrapper {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .content-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .content-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
        }

        .content-header p {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .mobile-toggle {
            display: none;
            margin-bottom: 0.65rem;
        }

        main {
            flex: 1;
            padding: 1.5rem 1.75rem 3rem;
            max-width: 1100px;
            width: 100%;
        }

        .sidebar-backdrop {
            display: none;
        }

        .group {
            margin-bottom: 2rem;
        }

        .group-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 0.75rem;
            text-transform: capitalize;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .endpoint {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
            transition: border-color 0.15s;
        }

        .endpoint:hover,
        .endpoint.open {
            border-color: var(--accent);
        }

        .endpoint.hidden { display: none; }

        .endpoint-summary {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            user-select: none;
        }

        .method {
            display: inline-block;
            min-width: 64px;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            color: #fff;
            flex-shrink: 0;
            margin-top: 0.15rem;
        }

        .method-GET { background: var(--get); }
        .method-POST { background: var(--post); }
        .method-PUT { background: var(--put); }
        .method-PATCH { background: var(--patch); }
        .method-DELETE { background: var(--delete); }

        .endpoint-body { flex: 1; min-width: 0; }

        .path {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.4rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            background: var(--bg);
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .badge-auth {
            color: #ffc107;
            border-color: #ffc10755;
            background: #ffc10715;
        }

        .badge-public {
            color: var(--post);
            border-color: #49cc9055;
            background: #49cc9015;
        }

        .chevron {
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 0.25rem;
            transition: transform 0.15s;
        }

        .endpoint.open .chevron { transform: rotate(90deg); }

        .try-panel {
            display: none;
            border-top: 1px solid var(--border);
            padding: 1rem;
            background: #121a2f;
        }

        .endpoint.open .try-panel { display: block; }

        .try-section {
            margin-bottom: 1rem;
        }

        .try-section:last-child { margin-bottom: 0; }

        .try-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.45rem;
        }

        .endpoint-desc {
            margin-top: 0.35rem;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .param-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 0.5rem;
            margin-bottom: 0.45rem;
            align-items: center;
        }

        .param-name {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8rem;
            color: var(--accent);
        }

        .field-input,
        .query-input {
            width: 100%;
        }

        .body-input {
            width: 100%;
            min-height: 140px;
            resize: vertical;
            line-height: 1.45;
        }

        .schema-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
            margin-bottom: 0.75rem;
        }

        .schema-table th,
        .schema-table td {
            text-align: left;
            padding: 0.45rem 0.55rem;
            border: 1px solid var(--border);
            vertical-align: top;
        }

        .schema-table th {
            background: var(--surface);
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.68rem;
        }

        .schema-table td.code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--accent);
            white-space: nowrap;
        }

        .schema-table .rules {
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.72rem;
            word-break: break-word;
        }

        .req-pill,
        .opt-pill {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .req-pill {
            color: #ff8a8a;
            background: #f93e3e18;
            border: 1px solid #f93e3e44;
        }

        .opt-pill {
            color: var(--muted);
            background: var(--bg);
            border: 1px solid var(--border);
        }

        .type-pill {
            display: inline-block;
            font-size: 0.68rem;
            color: var(--post);
            border: 1px solid #49cc9044;
            background: #49cc9012;
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .schema-empty {
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }

        .try-hint {
            font-size: 0.72rem;
            color: var(--muted);
            margin-bottom: 0.45rem;
        }

        .try-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .response-box {
            display: none;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
        }

        .response-box.visible { display: block; }

        .response-head {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.55rem 0.75rem;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            font-size: 0.8rem;
        }

        .status-ok { color: var(--post); }
        .status-err { color: #ff8a8a; }

        .response-body {
            margin: 0;
            padding: 0.85rem;
            max-height: 360px;
            overflow: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.78rem;
            white-space: pre-wrap;
            word-break: break-word;
            background: #0d1324;
        }

        .empty {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }

        /* —— Toast —— */
        .toast-stack {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            width: min(380px, calc(100vw - 2rem));
            pointer-events: none;
        }

        .toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.75rem 0.85rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            color: var(--text);
            font-size: 0.85rem;
            line-height: 1.4;
            animation: toast-in 0.22s ease;
        }

        .toast.leaving {
            animation: toast-out 0.2s ease forwards;
        }

        .toast-error {
            border-color: #f93e3e66;
            background: linear-gradient(90deg, #f93e3e22, var(--surface) 28%);
        }

        .toast-success {
            border-color: #49cc9066;
            background: linear-gradient(90deg, #49cc9022, var(--surface) 28%);
        }

        .toast-info {
            border-color: #4fc3f766;
            background: linear-gradient(90deg, #4fc3f722, var(--surface) 28%);
        }

        .toast-icon {
            flex-shrink: 0;
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0.05rem;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1.15rem;
            text-align: center;
        }

        .toast-error .toast-icon { color: #ff8a8a; }
        .toast-success .toast-icon { color: var(--post); }
        .toast-info .toast-icon { color: var(--accent); }

        .toast-body {
            flex: 1;
            min-width: 0;
            word-break: break-word;
        }

        .toast-close {
            flex-shrink: 0;
            border: none;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            padding: 0.1rem 0.2rem;
        }

        .toast-close:hover { color: var(--text); }

        @keyframes toast-in {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes toast-out {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-6px); }
        }

        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform 0.2s ease;
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                z-index: 30;
            }

            body.sidebar-open .sidebar-backdrop {
                display: block;
            }

            .mobile-toggle { display: inline-flex; }

            main { padding: 1rem 1rem 2.5rem; }
            .content-header { padding: 0.9rem 1rem; }
            .param-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .param-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="toast-stack" id="toast-stack" aria-live="polite" aria-relevant="additions"></div>
    <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()"></div>

    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h1>{{ config('app.name') }}</h1>
                <p>API Documentation · prefix <code>/api</code></p>
            </div>

            <div class="sidebar-user">
                <div class="sidebar-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'), 'UTF-8') }}</div>
                <div class="sidebar-user-meta">
                    <div class="sidebar-user-name">{{ $user->name }}</div>
                    <div class="sidebar-user-email">{{ $user->email ?? $user->phone }}</div>
                </div>
            </div>

            <div class="sidebar-section">
                <label class="sidebar-label" for="search">Tìm kiếm API</label>
                <input
                    type="search"
                    class="search"
                    id="search"
                    placeholder="Title, path, method..."
                    autofocus
                >
                <span class="stats" id="stats">{{ $total }} endpoints</span>
            </div>

            <div class="sidebar-section">
                <label class="sidebar-label" for="bearer-token">Bearer token</label>
                <input
                    id="bearer-token"
                    class="token-input"
                    type="text"
                    value="{{ $apiToken }}"
                    placeholder="token Sanctum"
                    spellcheck="false"
                >
            </div>

            <div class="sidebar-actions">
                <form method="POST" action="{{ route('api-docs.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <button type="button" class="btn mobile-toggle" onclick="toggleSidebar()">☰ Menu</button>
                <h2>Danh sách API</h2>
                <p>Chọn endpoint để xem query / body và thử gọi trực tiếp</p>
            </div>

            <main id="content">
                @foreach ($grouped as $group => $items)
                    <section class="group" data-group="{{ $group }}">
                        <h2 class="group-title">{{ str_replace('-', ' ', $group) }}</h2>
                        @foreach ($items as $route)
                            @php
                                $endpointId = 'ep-'.md5($route['method'].$route['uri']);
                            @endphp
                            <article
                                class="endpoint"
                                id="{{ $endpointId }}"
                                data-search="{{ mb_strtolower($route['method'].' '.$route['uri'].' '.($route['action'] ?? '').' '.($route['description'] ?? '').' '.str_replace('-', ' ', $group).' '.implode(' ', $route['middleware']).' '.implode(' ', array_column($route['query_params'], 'name')).' '.implode(' ', array_column($route['body_params'], 'name')), 'UTF-8') }}"
                                data-title="{{ mb_strtolower($route['description'] ?? '', 'UTF-8') }}"
                                data-method="{{ $route['method'] }}"
                                data-uri="{{ $route['uri'] }}"
                                data-auth="{{ $route['auth_required'] ? '1' : '0' }}"
                                data-has-body="{{ $route['has_body'] ? '1' : '0' }}"
                            >
                        <div class="endpoint-summary" onclick="toggleEndpoint('{{ $endpointId }}')">
                            <span class="method method-{{ $route['method'] }}">{{ $route['method'] }}</span>
                            <div class="endpoint-body">
                                <div class="path">{{ $route['uri'] }}</div>
                                @if (!empty($route['description']))
                                    <p class="endpoint-desc">{{ $route['description'] }}</p>
                                @endif
                                <div class="meta">
                                    @if ($route['auth_required'])
                                        <span class="badge badge-auth">auth:sanctum</span>
                                    @else
                                        <span class="badge badge-public">public</span>
                                    @endif
                                    @if (count($route['query_params']))
                                        <span class="badge">{{ count($route['query_params']) }} query</span>
                                    @endif
                                    @if (count($route['body_params']))
                                        <span class="badge">{{ count($route['body_params']) }} body</span>
                                    @endif
                                    @if ($route['action'])
                                        <span class="badge">{{ $route['action'] }}</span>
                                    @endif
                                    @if ($route['name'])
                                        <span class="badge">{{ $route['name'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="chevron">›</span>
                        </div>

                        <div class="try-panel">
                            @if (count($route['path_params']))
                                <div class="try-section">
                                    <span class="try-label">Path parameters</span>
                                    <table class="schema-table">
                                        <thead>
                                            <tr>
                                                <th>Tên</th>
                                                <th>Bắt buộc</th>
                                                <th>Mô tả</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($route['path_params'] as $param)
                                                <tr>
                                                    <td class="code">{{ $param }}</td>
                                                    <td><span class="req-pill">required</span></td>
                                                    <td>Giá trị thay thế <code>{{ '{'.$param.'}' }}</code> trên URL</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @foreach ($route['path_params'] as $param)
                                        <div class="param-row">
                                            <span class="param-name">{{ $param }}</span>
                                            <input
                                                class="field-input path-param"
                                                data-param="{{ $param }}"
                                                type="text"
                                                placeholder="{{ $param }}"
                                            >
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="try-section">
                                <span class="try-label">Query string</span>
                                @if (count($route['query_params']))
                                    <table class="schema-table">
                                        <thead>
                                            <tr>
                                                <th>Tên</th>
                                                <th>Kiểu</th>
                                                <th>Bắt buộc</th>
                                                <th>Validation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($route['query_params'] as $param)
                                                <tr>
                                                    <td class="code">{{ $param['name'] }}</td>
                                                    <td><span class="type-pill">{{ $param['type'] }}</span></td>
                                                    <td>
                                                        @if ($param['required'])
                                                            <span class="req-pill">required</span>
                                                        @else
                                                            <span class="opt-pill">optional</span>
                                                        @endif
                                                    </td>
                                                    <td class="rules">{{ implode(' · ', $param['rules']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <p class="try-hint">Nhập giá trị từng field (để trống = bỏ qua). Hoặc dùng JSON bên dưới.</p>
                                    @foreach ($route['query_params'] as $param)
                                        <div class="param-row">
                                            <span class="param-name">
                                                {{ $param['name'] }}
                                                @if ($param['required'])
                                                    <span class="req-pill">req</span>
                                                @endif
                                            </span>
                                            <input
                                                class="field-input query-param"
                                                data-param="{{ $param['name'] }}"
                                                data-type="{{ $param['type'] }}"
                                                type="text"
                                                placeholder="{{ $param['type'] }}{{ $param['example'] !== '' && $param['example'] !== null ? ' · vd: '.$param['example'] : '' }}"
                                                value=""
                                            >
                                        </div>
                                    @endforeach
                                @else
                                    <p class="schema-empty">Không có query parameter được khai báo (hoặc endpoint không nhận query).</p>
                                @endif
                                <textarea
                                    class="query-input"
                                    rows="3"
                                    placeholder='JSON override (tùy chọn), vd: {"page":1,"keyword":"abc"}'
                                ></textarea>
                            </div>

                            @if ($route['has_body'])
                                <div class="try-section">
                                    <span class="try-label">Request body</span>
                                    @if (count($route['body_params']))
                                        <table class="schema-table">
                                            <thead>
                                                <tr>
                                                    <th>Tên</th>
                                                    <th>Kiểu</th>
                                                    <th>Bắt buộc</th>
                                                    <th>Validation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($route['body_params'] as $param)
                                                    <tr>
                                                        <td class="code">{{ $param['name'] }}</td>
                                                        <td><span class="type-pill">{{ $param['type'] }}</span></td>
                                                        <td>
                                                            @if ($param['required'])
                                                                <span class="req-pill">required</span>
                                                            @else
                                                                <span class="opt-pill">optional</span>
                                                            @endif
                                                        </td>
                                                        <td class="rules">{{ implode(' · ', $param['rules']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <p class="try-hint">Sửa JSON bên dưới rồi bấm Try it out. Field <code>file</code> cần gửi qua form-data ngoài trang này.</p>
                                    @else
                                        <p class="schema-empty">Chưa trích xuất được schema body từ controller (có thể không validate hoặc dùng logic đặc biệt).</p>
                                    @endif
                                    <textarea class="body-input" rows="10" placeholder='{"field":"value"}'>@if (!empty($route['body_example'])){{ json_encode($route['body_example'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}@endif</textarea>
                                </div>
                            @endif

                            <div class="try-actions">
                                <button type="button" class="btn btn-primary" onclick="executeRequest('{{ $endpointId }}')">
                                    Try it out
                                </button>
                                <button type="button" class="btn" onclick="clearResponse('{{ $endpointId }}')">
                                    Clear
                                </button>
                            </div>

                            <div class="response-box" data-response>
                                <div class="response-head">
                                    <span data-status></span>
                                    <span data-time></span>
                                </div>
                                <pre class="response-body" data-body></pre>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endforeach

        <p class="empty" id="empty" style="display:none">Không tìm thấy endpoint nào.</p>
            </main>
        </div>
    </div>

    <script>
        const search = document.getElementById('search');
        const endpoints = document.querySelectorAll('.endpoint');
        const groups = document.querySelectorAll('.group');
        const empty = document.getElementById('empty');
        const stats = document.getElementById('stats');
        const bearerInput = document.getElementById('bearer-token');

        function showToast(message, type = 'error', duration = 3500) {
            const stack = document.getElementById('toast-stack');
            if (!stack) return;

            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            const icons = { error: '!', success: '✓', info: 'i' };
            toast.innerHTML =
                '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
                '<div class="toast-body"></div>' +
                '<button type="button" class="toast-close" aria-label="Đóng">×</button>';

            toast.querySelector('.toast-body').textContent = message;

            const remove = () => {
                if (toast.classList.contains('leaving')) return;
                toast.classList.add('leaving');
                setTimeout(() => toast.remove(), 200);
            };

            toast.querySelector('.toast-close').addEventListener('click', remove);
            stack.appendChild(toast);

            if (duration > 0) {
                setTimeout(remove, duration);
            }
        }

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        search.addEventListener('input', () => {
            const q = search.value.trim().toLocaleLowerCase('vi');
            let visible = 0;

            endpoints.forEach(el => {
                const haystack = (el.dataset.search || '') + ' ' + (el.dataset.title || '');
                const match = !q || haystack.includes(q);
                el.classList.toggle('hidden', !match);
                if (match) visible++;
            });

            groups.forEach(group => {
                const hasVisible = group.querySelector('.endpoint:not(.hidden)');
                group.style.display = hasVisible ? '' : 'none';
            });

            empty.style.display = visible === 0 ? '' : 'none';
            stats.textContent = visible + ' / {{ $total }} endpoints';
        });

        document.querySelectorAll('.query-param').forEach(input => {
            input.addEventListener('input', () => {
                input.dataset.touched = '1';
            });
        });

        function toggleEndpoint(id) {
            const el = document.getElementById(id);
            el.classList.toggle('open');
        }

        function clearResponse(id) {
            const el = document.getElementById(id);
            const box = el.querySelector('[data-response]');
            box.classList.remove('visible');
            box.querySelector('[data-status]').textContent = '';
            box.querySelector('[data-time]').textContent = '';
            box.querySelector('[data-body]').textContent = '';
        }

        function coerceQueryValue(raw, type) {
            if (type === 'integer') {
                const n = parseInt(raw, 10);
                return Number.isNaN(n) ? raw : n;
            }
            if (type === 'number') {
                const n = Number(raw);
                return Number.isNaN(n) ? raw : n;
            }
            if (type === 'boolean') {
                if (raw === '1' || raw.toLowerCase() === 'true') return true;
                if (raw === '0' || raw.toLowerCase() === 'false') return false;
                return raw;
            }
            return raw;
        }

        function buildUrl(uriTemplate, pathParams, queryObj) {
            let path = uriTemplate;
            Object.entries(pathParams).forEach(([key, value]) => {
                path = path.replace(new RegExp('\\{' + key + '\\}', 'g'), encodeURIComponent(value));
            });

            const qs = new URLSearchParams();
            Object.entries(queryObj || {}).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') return;
                if (Array.isArray(value)) {
                    value.forEach(v => qs.append(key + '[]', v));
                } else if (typeof value === 'object') {
                    qs.append(key, JSON.stringify(value));
                } else {
                    qs.append(key, value);
                }
            });

            const query = qs.toString();
            return query ? path + '?' + query : path;
        }

        async function executeRequest(id) {
            const el = document.getElementById(id);
            const method = el.dataset.method;
            const uriTemplate = el.dataset.uri;
            const needsAuth = el.dataset.auth === '1';
            const hasBody = el.dataset.hasBody === '1';

            const pathParams = {};
            el.querySelectorAll('.path-param').forEach(input => {
                pathParams[input.dataset.param] = input.value.trim();
            });

            for (const [key, value] of Object.entries(pathParams)) {
                if (!value) {
                    showToast('Vui lòng nhập path param: ' + key, 'error');
                    return;
                }
            }

            let queryObj = {};
            const queryText = (el.querySelector('.query-input')?.value || '').trim();
            if (queryText) {
                try {
                    queryObj = JSON.parse(queryText);
                    if (typeof queryObj !== 'object' || Array.isArray(queryObj) || queryObj === null) {
                        throw new Error('Query phải là JSON object');
                    }
                } catch (e) {
                    showToast('Query JSON không hợp lệ: ' + e.message, 'error');
                    return;
                }
            }

            // Field inputs ghi đè / bổ sung lên JSON (để trống = không gửi key đó)
            el.querySelectorAll('.query-param').forEach(input => {
                const key = input.dataset.param;
                const raw = input.value.trim();
                if (!raw) {
                    // Nếu user xóa field input, bỏ key khỏi query (kể cả trong JSON mẫu)
                    if (Object.prototype.hasOwnProperty.call(queryObj, key) && input.dataset.touched === '1') {
                        delete queryObj[key];
                    }
                    return;
                }
                queryObj[key] = coerceQueryValue(raw, input.dataset.type);
            });

            // Bỏ các key rỗng trong query mẫu để tránh gửi "" vô ích
            Object.keys(queryObj).forEach(key => {
                if (queryObj[key] === '' || queryObj[key] === null || queryObj[key] === undefined) {
                    delete queryObj[key];
                }
            });

            let bodyObj = null;
            if (hasBody) {
                const bodyText = (el.querySelector('.body-input')?.value || '').trim();
                if (bodyText) {
                    try {
                        bodyObj = JSON.parse(bodyText);
                    } catch (e) {
                        showToast('Body JSON không hợp lệ: ' + e.message, 'error');
                        return;
                    }
                } else {
                    bodyObj = {};
                }
            }

            const url = buildUrl(uriTemplate, pathParams, queryObj);
            const headers = {
                'Accept': 'application/json',
            };

            if (hasBody) {
                headers['Content-Type'] = 'application/json';
            }

            const token = bearerInput.value.trim();
            if (needsAuth) {
                if (!token) {
                    showToast('Endpoint này cần Bearer token. Đăng nhập lại hoặc dán token vào ô bên sidebar.', 'error');
                    return;
                }
                headers['Authorization'] = 'Bearer ' + token;
            } else if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            const box = el.querySelector('[data-response]');
            const statusEl = box.querySelector('[data-status]');
            const timeEl = box.querySelector('[data-time]');
            const bodyEl = box.querySelector('[data-body]');

            box.classList.add('visible');
            statusEl.textContent = 'Đang gửi...';
            statusEl.className = '';
            timeEl.textContent = '';
            bodyEl.textContent = '';

            const started = performance.now();

            try {
                const options = { method, headers };
                if (hasBody) {
                    options.body = JSON.stringify(bodyObj);
                }

                const res = await fetch(url, options);
                const elapsed = Math.round(performance.now() - started);
                const text = await res.text();

                let pretty = text;
                try {
                    pretty = JSON.stringify(JSON.parse(text), null, 2);
                } catch (_) {}

                statusEl.textContent = res.status + ' ' + res.statusText;
                statusEl.className = res.ok ? 'status-ok' : 'status-err';
                timeEl.textContent = elapsed + ' ms';
                bodyEl.textContent = pretty || '(empty body)';
            } catch (err) {
                const elapsed = Math.round(performance.now() - started);
                statusEl.textContent = 'Network error';
                statusEl.className = 'status-err';
                timeEl.textContent = elapsed + ' ms';
                bodyEl.textContent = String(err);
            }
        }
    </script>
</body>
</html>

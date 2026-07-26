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

        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        header p {
            color: var(--muted);
            font-size: 0.875rem;
        }

        .user-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .user-bar span {
            font-size: 0.85rem;
            color: var(--muted);
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
        }

        .toolbar {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .token-bar {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.85rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .token-bar label {
            font-size: 0.75rem;
            color: var(--muted);
            white-space: nowrap;
        }

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
        }

        .search {
            flex: 1;
            min-width: 200px;
            font-family: inherit;
        }

        .token-input {
            flex: 1;
            min-width: 220px;
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
            font-size: 0.8rem;
            color: var(--muted);
            white-space: nowrap;
        }

        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem 2rem 3rem;
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

        @media (max-width: 640px) {
            header, main { padding-left: 1rem; padding-right: 1rem; }
            .param-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-top">
            <div>
                <h1>{{ config('app.name') }} — API Documentation</h1>
                <p>Đăng nhập rồi thử gọi API trực tiếp (prefix <code>/api</code>)</p>
            </div>
            <div class="user-bar">
                <span>{{ $user->name }} · {{ $user->email ?? $user->phone }}</span>
                <form method="POST" action="{{ route('api-docs.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">Đăng xuất</button>
                </form>
            </div>
        </div>

        <div class="toolbar">
            <input type="search" class="search" id="search" placeholder="Tìm theo path, method, controller..." autofocus>
            <span class="stats" id="stats">{{ $total }} endpoints</span>
        </div>

        <div class="token-bar">
            <label for="bearer-token">Bearer token</label>
            <input
                id="bearer-token"
                class="token-input"
                type="text"
                value="{{ $apiToken }}"
                placeholder="token Sanctum — tự điền sau khi đăng nhập"
                spellcheck="false"
            >
        </div>
    </header>

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
                        data-search="{{ strtolower($route['method'].' '.$route['uri'].' '.($route['action'] ?? '').' '.implode(' ', $route['middleware'])) }}"
                        data-method="{{ $route['method'] }}"
                        data-uri="{{ $route['uri'] }}"
                        data-auth="{{ $route['auth_required'] ? '1' : '0' }}"
                        data-has-body="{{ $route['has_body'] ? '1' : '0' }}"
                    >
                        <div class="endpoint-summary" onclick="toggleEndpoint('{{ $endpointId }}')">
                            <span class="method method-{{ $route['method'] }}">{{ $route['method'] }}</span>
                            <div class="endpoint-body">
                                <div class="path">{{ $route['uri'] }}</div>
                                <div class="meta">
                                    @if ($route['auth_required'])
                                        <span class="badge badge-auth">auth:sanctum</span>
                                    @else
                                        <span class="badge badge-public">public</span>
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
                                <span class="try-label">Query string (JSON object, tùy chọn)</span>
                                <textarea class="query-input" rows="3" placeholder='{"page":1,"per_page":10,"keyword":""}'></textarea>
                            </div>

                            @if ($route['has_body'])
                                <div class="try-section">
                                    <span class="try-label">Request body (JSON)</span>
                                    <textarea class="body-input" rows="8" placeholder='{"field":"value"}'></textarea>
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

    <script>
        const search = document.getElementById('search');
        const endpoints = document.querySelectorAll('.endpoint');
        const groups = document.querySelectorAll('.group');
        const empty = document.getElementById('empty');
        const stats = document.getElementById('stats');
        const bearerInput = document.getElementById('bearer-token');

        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let visible = 0;

            endpoints.forEach(el => {
                const match = !q || el.dataset.search.includes(q);
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
                    alert('Vui lòng nhập path param: ' + key);
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
                    alert('Query JSON không hợp lệ: ' + e.message);
                    return;
                }
            }

            let bodyObj = null;
            if (hasBody) {
                const bodyText = (el.querySelector('.body-input')?.value || '').trim();
                if (bodyText) {
                    try {
                        bodyObj = JSON.parse(bodyText);
                    } catch (e) {
                        alert('Body JSON không hợp lệ: ' + e.message);
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
                    alert('Endpoint này cần Bearer token. Đăng nhập lại hoặc dán token vào ô phía trên.');
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

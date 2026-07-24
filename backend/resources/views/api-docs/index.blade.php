<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

        header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        header p {
            color: var(--muted);
            font-size: 0.875rem;
        }

        .toolbar {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            font-size: 0.875rem;
        }

        .search:focus {
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
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: border-color 0.15s;
        }

        .endpoint:hover {
            border-color: var(--accent);
        }

        .endpoint.hidden { display: none; }

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

        .empty {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ config('app.name') }} — API Documentation</h1>
        <p>Danh sách tự động từ route đăng ký trong Laravel (prefix <code>/api</code>)</p>
        <div class="toolbar">
            <input type="search" class="search" id="search" placeholder="Tìm theo path, method, controller..." autofocus>
            <span class="stats" id="stats">{{ $total }} endpoints</span>
        </div>
    </header>

    <main id="content">
        @foreach ($grouped as $group => $items)
            <section class="group" data-group="{{ $group }}">
                <h2 class="group-title">{{ str_replace('-', ' ', $group) }}</h2>
                @foreach ($items as $route)
                    <article
                        class="endpoint"
                        data-search="{{ strtolower($route['method'].' '.$route['uri'].' '.($route['action'] ?? '').' '.implode(' ', $route['middleware'])) }}"
                    >
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
    </script>
</body>
</html>

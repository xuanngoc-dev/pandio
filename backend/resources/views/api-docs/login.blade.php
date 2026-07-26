<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập API Docs — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #1a1a2e;
            --surface: #16213e;
            --border: #2a3a5c;
            --text: #e8eaf0;
            --muted: #8b95a8;
            --accent: #4fc3f7;
            --danger: #f93e3e;
            --post: #49cc90;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
        }

        h1 {
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 0.35rem;
        }

        .field { margin-bottom: 1rem; }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            font-size: 0.95rem;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 1.25rem;
        }

        .error {
            background: #f93e3e18;
            border: 1px solid #f93e3e55;
            color: #ff8a8a;
            border-radius: 6px;
            padding: 0.65rem 0.75rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        button {
            width: 100%;
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 6px;
            background: var(--accent);
            color: #0b1220;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
        }

        button:hover { filter: brightness(1.08); }

        .hint {
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.45;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>API Documentation</h1>
        <p class="subtitle">Đăng nhập bằng tài khoản hệ thống để xem và thử API.</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('api-docs.login.submit') }}">
            @csrf
            <div class="field">
                <label for="login">Email hoặc số điện thoại</label>
                <input
                    id="login"
                    type="text"
                    name="login"
                    value="{{ old('login') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>
            <div class="field">
                <label for="password">Mật khẩu</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Ghi nhớ đăng nhập
            </label>
            <button type="submit">Đăng nhập</button>
        </form>

        <p class="hint">
            Sau khi đăng nhập, bạn có thể nhập body / query và gọi thử các API đã được bảo vệ bằng token Sanctum.
        </p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LK Edad')</title>
    <style>
        :root {
            --bg: #f4f1ea;
            --ink: #1c1915;
            --muted: #6b645b;
            --card: #fffdf8;
            --line: #e4ddd2;
            --brand: #0f6e56;
            --brand-dark: #0b5340;
            --danger: #9f2d2d;
            --bar: #d7efe6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, #efe6d4 0%, transparent 55%),
                var(--bg);
        }
        a { color: var(--brand-dark); }
        .shell { width: min(680px, calc(100% - 32px)); margin: 0 auto; padding: 28px 0 48px; }
        header.top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .mark { font-weight: 700; letter-spacing: -0.03em; font-size: 1.15rem; text-decoration: none; color: var(--ink); }
        .mark span { color: var(--brand); }
        button, .btn {
            font-family: inherit;
            border: 0;
            border-radius: 999px;
            background: var(--brand);
            color: #fff;
            padding: 12px 18px;
            font-size: 1rem;
            cursor: pointer;
        }
        button:disabled { opacity: 0.6; cursor: wait; }
        .btn.ghost, button.ghost {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--line);
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 16px 40px rgba(60, 40, 10, 0.05);
        }
        h1 { font-size: 1.8rem; line-height: 1.15; letter-spacing: -0.03em; margin: 0 0 8px; }
        p.lead { color: var(--muted); margin: 0 0 18px; line-height: 1.45; }
        label { display: block; font-size: 0.92rem; margin-bottom: 6px; }
        input[type="email"], input[type="text"], input[type="file"] {
            width: 100%;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 14px;
            padding: 12px 14px;
            font: inherit;
            margin-bottom: 14px;
        }
        .error {
            background: #fdecec;
            color: var(--danger);
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .option {
            display: block;
            width: 100%;
            text-align: left;
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 10px;
        }
        .option:hover { border-color: var(--brand); }
        .bar-row { margin: 14px 0; }
        .bar-meta { display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 6px; }
        .track { height: 12px; background: var(--bar); border-radius: 999px; overflow: hidden; }
        .fill { height: 100%; background: var(--brand); border-radius: inherit; }
        .overall { font-size: 2.6rem; letter-spacing: -0.04em; margin: 0; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        form.inline { display: inline; }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .processing:not([hidden]) { display: flex; gap: 12px; align-items: center; }
        .spinner {
            width: 22px; height: 22px; border-radius: 50%;
            border: 3px solid var(--bar); border-top-color: var(--brand);
            animation: spin 0.8s linear infinite; flex: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="top">
            <a class="mark" href="{{ route('home') }}">LK <span>Edad</span></a>
            @isset($edadUser)
                <form class="inline" method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="ghost" type="submit">Sign out</button>
                </form>
            @endisset
        </header>
        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</body>
</html>

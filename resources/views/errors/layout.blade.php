<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>@yield('code') — Tikmos</title>
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 24px;
            background: #f6f6f7; color: #0b0b0c; text-align: center; position: relative; overflow: hidden;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        /* taches pastel décoratives */
        body::before, body::after {
            content: ''; position: absolute; width: 460px; height: 460px; border-radius: 50%;
            filter: blur(10px); opacity: .55; z-index: 0; pointer-events: none;
        }
        body::before { background: radial-gradient(circle, #e9f1fe 0%, transparent 70%); top: -160px; left: -120px; }
        body::after  { background: radial-gradient(circle, #f1ebfb 0%, transparent 70%); bottom: -180px; right: -140px; }
        main {
            position: relative; z-index: 1; max-width: 460px; width: 100%;
            background: #fff; border: 1px solid #ededf0; border-radius: 24px;
            padding: 40px 28px; box-shadow: 0 18px 50px -20px rgba(11,11,12,.18);
            animation: rise .45s cubic-bezier(.2,.7,.3,1) both;
        }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { main { animation: none; } }
        .logo { height: 34px; width: auto; margin: 0 auto 22px; display: block; }
        .code {
            font-size: 72px; font-weight: 900; letter-spacing: -0.04em; line-height: 1; margin: 0;
            background: linear-gradient(135deg, #0b0b0c, #6b6b73); -webkit-background-clip: text;
            background-clip: text; color: transparent;
        }
        h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; margin: 10px 0 8px; }
        p { color: #71717a; margin: 0 auto 26px; line-height: 1.6; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a { display: inline-flex; align-items: center; gap: 7px; text-decoration: none; font-weight: 600;
            font-size: 15px; padding: 12px 22px; border-radius: 12px; transition: all .15s; }
        a.primary { background: #0b0b0c; color: #fff; }
        a.primary:hover { background: #26262a; transform: translateY(-1px); }
        a.ghost { background: #fff; color: #0b0b0c; border: 1px solid #d4d4d8; }
        a.ghost:hover { border-color: #0b0b0c; }
    </style>
</head>
<body>
    <main>
        <img class="logo" src="{{ asset('img/tikmos-logo.png') }}" alt="{{ config('app.name', 'Tikmos') }}">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="primary" href="{{ url('/') }}">← Retour à l'accueil</a>
            <a class="ghost" href="{{ url('/contact') }}">Nous contacter</a>
        </div>
    </main>
</body>
</html>

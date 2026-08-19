<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We're Building Something Great — CADEBECK HR</title>
    <meta name="description" content="CADEBECK HR is evolving. A smarter way to manage your people is on the way. Join the launch list to be notified first.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #022c22;
            --text: #ecfdf5;
            --muted: #a7f3d0;
            --accent: #34d399;
            --accent-deep: #10b981;
            --ink: #064e3b;
            --card: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ---------- Aurora background ---------- */
        .aurora {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: radial-gradient(120% 120% at 50% 0%, #053f30 0%, #022c22 55%, #011a12 100%);
        }
        .aurora::before,
        .aurora::after {
            content: "";
            position: absolute;
            width: 60vw;
            height: 60vw;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
        }
        .aurora::before {
            top: -20%;
            left: -10%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.55) 0%, rgba(5, 150, 105, 0) 70%);
            animation: drift 18s ease-in-out infinite alternate;
        }
        .aurora::after {
            bottom: -25%;
            right: -12%;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.5) 0%, rgba(6, 95, 70, 0) 70%);
            animation: drift 22s ease-in-out infinite alternate-reverse;
        }
        @keyframes drift {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(6vw, 4vh) scale(1.15); }
        }

        .grain {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            opacity: 0.5;
            background-image: radial-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(110, 231, 183, 0.5), rgba(16, 185, 129, 0.06) 70%);
            filter: blur(6px);
        }
        .orb-a { width: 220px; height: 220px; top: 12%; left: 8%; animation: float 14s ease-in-out infinite; }
        .orb-b { width: 140px; height: 140px; bottom: 18%; left: 22%; animation: float 16s ease-in-out 1s infinite; }
        .orb-c { width: 180px; height: 180px; top: 40%; right: 6%; animation: float 12s ease-in-out 0.5s infinite; }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-24px); }
        }

        /* ---------- Layout ---------- */
        .wrap {
            position: relative;
            z-index: 2;
            max-width: 1160px;
            margin: 0 auto;
            padding: 24px 24px 48px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 32px;
        }
        .brand { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #e7fdf3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.35);
        }
        .brand-mark svg { width: 26px; height: 26px; }
        .brand-name { line-height: 1.15; }
        .brand-name strong { font-size: 19px; font-weight: 800; letter-spacing: 0.02em; display: block; }
        .brand-name span { font-size: 12px; font-weight: 600; color: var(--muted); letter-spacing: 0.04em; }

        .nav-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ---------- Hero ---------- */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid var(--border);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 32px;
            backdrop-filter: blur(8px);
        }
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.55); }
            70% { box-shadow: 0 0 0 10px rgba(52, 211, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
        }

        h1 {
            font-size: clamp(40px, 6vw, 68px);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.02em;
            margin-bottom: 24px;
            max-width: 800px;
        }
        .grad {
            background: linear-gradient(92deg, #6ee7b7 0%, #34d399 45%, #a7f3d0 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .lede {
            font-size: 18px;
            line-height: 1.7;
            color: var(--muted);
            max-width: 560px;
            margin-bottom: 40px;
        }

        /* ---------- Email capture ---------- */
        .form-card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        }
        .form-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .form-row input[type="email"] {
            flex: 1;
            min-width: 0;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .form-row input[type="email"]::placeholder { color: rgba(167, 243, 208, 0.6); }
        .form-row input[type="email"]:focus {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.12);
        }
        .form-row button {
            padding: 16px 26px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            color: var(--ink);
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .form-row button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
            filter: brightness(1.05);
        }
        .form-note {
            text-align: center;
            font-size: 13px;
            color: rgba(167, 243, 208, 0.7);
            margin-top: 14px;
            margin-bottom: 2px;
        }

        /* Success state */
        .success {
            display: none;
            padding: 16px 18px;
            text-align: center;
        }
        .success svg { width: 44px; height: 44px; margin: 0 auto 12px; color: var(--accent); }
        .success strong { font-size: 17px; font-weight: 700; display: block; margin-bottom: 6px; }
        .success p { font-size: 14px; color: var(--muted); line-height: 1.6; }

        .form-card.submitted .form-row,
        .form-card.submitted .form-note { display: none; }
        .form-card.submitted .success { display: block; }

        /* ---------- Product teaser ---------- */
        .teaser {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            width: 100%;
            max-width: 720px;
            margin-top: 56px;
        }
        .teaser-card {
            background: var(--card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px 18px;
            text-align: center;
            transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }
        .teaser-card:hover {
            transform: translateY(-4px);
            border-color: rgba(52, 211, 153, 0.4);
            background: rgba(255, 255, 255, 0.09);
        }
        .teaser-card svg { width: 26px; height: 26px; margin: 0 auto 12px; color: var(--accent); }
        .teaser-card span { font-size: 13px; font-weight: 600; color: var(--text); }

        /* ---------- Footer ---------- */
        footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 40px;
            color: rgba(167, 243, 208, 0.6);
            font-size: 13px;
            font-weight: 500;
        }
        .contact a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .contact a:hover { color: #ffffff; }
        .contact svg { width: 16px; height: 16px; }

        /* ---------- Reduced motion ---------- */
        @media (prefers-reduced-motion: reduce) {
            .aurora::before, .aurora::after, .orb, .badge-dot { animation: none !important; }
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 640px) {
            header { padding-bottom: 20px; }
            .nav-link { display: none; }
            .form-row { flex-direction: column; }
            .form-row button { width: 100%; }
            .teaser { grid-template-columns: 1fr; max-width: 380px; }
            footer { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="aurora">
        <div class="orb orb-a"></div>
        <div class="orb orb-b"></div>
        <div class="orb orb-c"></div>
    </div>
    <div class="grain"></div>

    <div class="wrap">
        <header>
            <a class="brand" href="/">
                <div class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#065f46" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="brand-name">
                    <strong>CADEBECK</strong>
                    <span>HR Management</span>
                </div>
            </a>
            <a class="nav-link" href="mailto:info@cadebeckhr.com">Contact Us</a>
        </header>

        <main class="hero">
            <div class="badge">
                <span class="badge-dot"></span>
                A new version is on the way
            </div>

            <h1>We're building something <span class="grad">great for your people</span></h1>
            <p class="lede">
                CADEBECK HR is being redesigned to make managing your team simpler, faster and smarter.
                Join the launch list and be the first to know when we open the doors.
            </p>

            <div class="form-card" id="notifyForm">
                <form class="form-row" id="notifyFormEl" novalidate>
                    <input type="email" id="emailInput" placeholder="Enter your work email" autocomplete="email" aria-label="Work email">
                    <button type="submit">Notify Me</button>
                </form>
                <p class="form-note">No spam. Just one email when we launch.</p>
                <div class="success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong>You're on the list!</strong>
                    <p>Thanks for your interest in CADEBECK HR. We'll let you know the moment we launch.</p>
                </div>
            </div>

            <div class="teaser">
                <div class="teaser-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span>People Management</span>
                </div>
                <div class="teaser-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>Payroll &amp; Attendance</span>
                </div>
                <div class="teaser-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                    <span>Recruitment &amp; Onboarding</span>
                </div>
            </div>
        </main>

        <footer>
            <div class="contact">
                <a href="mailto:info@cadebeckhr.com">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    info@cadebeckhr.com
                </a>
            </div>
            <div>© <span id="year"></span> CADEBECK HR. All rights reserved.</div>
        </footer>
    </div>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        const form = document.getElementById('notifyFormEl');
        const card = document.getElementById('notifyForm');
        const input = document.getElementById('emailInput');
        const button = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const email = input.value.trim();
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!valid) {
                input.style.borderColor = '#f87171';
                input.focus();
                return;
            }
            input.style.borderColor = '';

            const original = button.textContent;
            button.disabled = true;
            button.textContent = 'Subscribing...';

            try {
                const response = await fetch('/under-construction/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ email: email }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                card.classList.add('submitted');
            } catch (err) {
                input.style.borderColor = '#f87171';
                button.disabled = false;
                button.textContent = original;
            }
        });

        input.addEventListener('input', function () {
            input.style.borderColor = '';
        });
    </script>
</body>
</html>
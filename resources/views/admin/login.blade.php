<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Staff - RestoApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --ink: #172033;
            --muted: #667085;
            --line: #d9e0ea;
            --panel: #ffffff;
            --surface: #f5f7fb;
            --primary: #e85e10;
            --primary-dark: #c84b0b;
            --teal: #158f85;
            --danger: #b42318;
            --danger-bg: #fff1f0;
            --danger-line: #fecdca;
            --shadow: 0 24px 70px rgba(23, 32, 51, 0.14);
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(232, 94, 16, 0.08), transparent 32%),
                linear-gradient(315deg, rgba(21, 143, 133, 0.08), transparent 34%),
                var(--surface);
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .shell {
            width: min(980px, 100%);
            min-height: 590px;
            display: grid;
            grid-template-columns: minmax(320px, 0.95fr) minmax(360px, 1.05fr);
            overflow: hidden;
            background: var(--panel);
            border: 1px solid rgba(217, 224, 234, 0.9);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 34px;
            color: #fff;
            background:
                linear-gradient(160deg, rgba(23, 32, 51, 0.2), rgba(23, 32, 51, 0.88)),
                linear-gradient(135deg, #e85e10 0%, #b44313 52%, #158f85 100%);
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 18px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: inline-grid;
            place-items: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-icon svg {
            width: 23px;
            height: 23px;
        }

        .brand-copy {
            max-width: 360px;
        }

        .brand-copy h1 {
            margin: 0 0 14px;
            font-size: 38px;
            line-height: 1.05;
            letter-spacing: 0;
        }

        .brand-copy p {
            margin: 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 15px;
            line-height: 1.7;
        }

        .status-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .status-item {
            min-width: 0;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
        }

        .status-item strong {
            display: block;
            font-size: 17px;
            line-height: 1.1;
        }

        .status-item span {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.72);
            white-space: nowrap;
        }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 44px;
        }

        .form-wrap {
            width: min(100%, 390px);
        }

        .form-header {
            margin-bottom: 28px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: var(--teal);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--teal);
        }

        .form-header h2 {
            margin: 0;
            font-size: 30px;
            line-height: 1.15;
            letter-spacing: 0;
        }

        .form-header p {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            padding: 13px 14px;
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.45;
        }

        .alert-error svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            margin-top: 1px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 13px;
            font-weight: 800;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            width: 19px;
            height: 19px;
            color: #98a2b3;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 44px;
            color: var(--ink);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            outline: none;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .form-input::placeholder {
            color: #98a2b3;
            font-weight: 500;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(232, 94, 16, 0.12);
        }

        .form-input:focus ~ .input-icon {
            color: var(--primary);
        }

        .password-input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            width: 34px;
            height: 34px;
            display: inline-grid;
            place-items: center;
            color: #667085;
            background: transparent;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            color: var(--ink);
            background: #f2f4f7;
            outline: none;
        }

        .password-toggle svg {
            width: 19px;
            height: 19px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 6px 0 24px;
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
        }

        .remember-label input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: var(--primary);
        }

        .btn-login {
            width: 100%;
            height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #fff;
            background: var(--primary);
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: background 160ms ease, transform 160ms ease, box-shadow 160ms ease;
            box-shadow: 0 12px 24px rgba(232, 94, 16, 0.24);
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(232, 94, 16, 0.28);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.78;
            cursor: wait;
            transform: none;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.38);
            border-top-color: #fff;
            border-radius: 999px;
            animation: spin 700ms linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-footer {
            margin-top: 22px;
            color: #98a2b3;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 820px) {
            .page {
                padding: 18px;
                place-items: stretch;
            }

            .shell {
                min-height: calc(100vh - 36px);
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 230px;
                padding: 24px;
            }

            .brand-copy h1 {
                font-size: 30px;
            }

            .brand-copy p {
                max-width: 560px;
            }

            .status-strip {
                display: none;
            }

            .form-panel {
                align-items: flex-start;
                padding: 30px 24px 28px;
            }
        }

        @media (max-width: 480px) {
            body {
                background: var(--surface);
            }

            .page {
                padding: 0;
            }

            .shell {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .brand-panel {
                min-height: 190px;
                padding: 22px;
            }

            .brand-copy h1 {
                font-size: 26px;
            }

            .form-panel {
                padding: 28px 20px;
            }

            .form-header h2 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="shell" aria-label="Login staff RestoApp">
            <aside class="brand-panel">
                <div class="brand-mark">
                    <span class="brand-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M6 10v7a3 3 0 003 3h6a3 3 0 003-3v-7M8 10V7a4 4 0 118 0v3"></path>
                        </svg>
                    </span>
                    <span>RestoApp</span>
                </div>

                <div class="brand-copy">
                    <h1>Panel operasional restoran</h1>
                    <p>Kelola pesanan, menu, stok, reservasi, dan laporan harian dari satu dashboard yang ringkas.</p>
                </div>

                <div class="status-strip" aria-hidden="true">
                    <div class="status-item">
                        <strong>Live</strong>
                        <span>Pesanan</span>
                    </div>
                    <div class="status-item">
                        <strong>KDS</strong>
                        <span>Dapur</span>
                    </div>
                    <div class="status-item">
                        <strong>POS</strong>
                        <span>Kasir</span>
                    </div>
                </div>
            </aside>

            <section class="form-panel">
                <div class="form-wrap">
                    <header class="form-header">
                        <div class="eyebrow">Staff</div>
                        <h2>Masuk panel</h2>
                        <p>Gunakan akun admin, waiter, atau chef sesuai akses operasional Anda.</p>
                    </header>

                    @if($errors->any())
                        <div class="alert-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" class="form-input" placeholder="admin@restoapp.com" value="{{ old('email') }}" required autocomplete="username" autofocus>
                                <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.2a2.8 2.8 0 005.6 0V12a9.6 9.6 0 10-4.1 7.86"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-input password-input" placeholder="Masukkan password" required autocomplete="current-password">
                                <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10V7a5 5 0 0110 0v3m-9 0h8a2 2 0 012 2v7a2 2 0 01-2 2H8a2 2 0 01-2-2v-7a2 2 0 012-2z"></path>
                                </svg>
                                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Tampilkan password" title="Tampilkan password">
                                    <svg id="eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="remember-row">
                            <label class="remember-label">
                                <input type="checkbox" name="remember" value="1">
                                Ingat saya
                            </label>
                        </div>

                        <button type="submit" class="btn-login" id="btnLogin">
                            Masuk
                        </button>
                    </form>

                    <p class="form-footer">&copy; {{ date('Y') }} RestoApp</p>
                </div>
            </section>
        </section>
    </main>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            const button = document.querySelector('.password-toggle');
            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
            button.setAttribute('title', showing ? 'Tampilkan password' : 'Sembunyikan password');

            icon.innerHTML = showing
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"></path>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.18A10.4 10.4 0 0112 5c6 0 9.5 7 9.5 7a13.2 13.2 0 01-2.21 3.08M6.61 6.61C3.92 8.24 2.5 12 2.5 12s3.5 7 9.5 7c1.22 0 2.34-.29 3.35-.76"></path>';
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Memproses</span>';
        });
    </script>
</body>
</html>

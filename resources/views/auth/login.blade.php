<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WebGIS Alumni</title>
    <style>
        :root {
            --pilkom-blue: #004a87;
            --pilkom-blue-dark: #063b68;
            --pilkom-yellow: #fdb813;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --line: #dbe4ef;
            --surface: rgba(255, 255, 255, 0.92);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text-main);
            background:
                linear-gradient(135deg, rgba(0, 74, 135, 0.88), rgba(14, 165, 233, 0.72)),
                url("{{ asset('img/ilustrasi.png') }}") center/cover no-repeat;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .login-shell {
            width: min(430px, 100%);
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            border-radius: 18px;
            padding: 28px;
            backdrop-filter: blur(14px);
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 26px;
        }

        .login-brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: var(--pilkom-blue);
            color: var(--pilkom-yellow);
            display: grid;
            place-items: center;
            font-weight: 900;
            font-size: 20px;
            box-shadow: 0 12px 26px rgba(0, 74, 135, 0.25);
        }

        .login-brand-mark svg {
            width: 28px;
            height: 28px;
        }

        .login-brand h1 {
            margin: 0;
            font-size: 23px;
            line-height: 1.15;
            color: var(--pilkom-blue-dark);
        }

        .login-brand p {
            margin: 4px 0 0 0;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 13px 14px;
            font: inherit;
            color: var(--text-main);
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .field input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.16);
        }

        .password-input-wrap {
            position: relative;
        }

        .password-input-wrap input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            width: 36px;
            height: 36px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #64748b;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .password-toggle:hover {
            background: rgba(14, 165, 233, 0.1);
            color: var(--pilkom-blue);
        }

        .password-toggle svg {
            width: 19px;
            height: 19px;
        }

        .password-toggle .icon-eye-off {
            display: none;
        }

        .password-toggle.is-visible .icon-eye {
            display: none;
        }

        .password-toggle.is-visible .icon-eye-off {
            display: block;
        }

        .field-error {
            margin-top: 7px;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 6px 0 20px 0;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--pilkom-blue);
        }

        .login-button {
            width: 100%;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--pilkom-blue), #0ea5e9);
            color: #ffffff;
            font: inherit;
            font-weight: 900;
            padding: 14px 16px;
            cursor: pointer;
            box-shadow: 0 16px 30px rgba(0, 74, 135, 0.22);
        }

        .login-button:hover {
            filter: brightness(1.04);
        }

        .back-link {
            display: flex;
            justify-content: center;
            margin-top: 16px;
            color: var(--pilkom-blue-dark);
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .back-link:hover {
            color: var(--pilkom-blue);
        }

        @media (max-width: 480px) {
            .login-shell {
                padding: 22px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <div class="login-brand">
            <div class="login-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div>
                <h1>Login WebGIS Alumni</h1>
                <p>Pendidikan Komputer ULM</p>
            </div>
        </div>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    required
                >
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-input-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        id="toggle-password"
                        aria-label="Tampilkan password"
                        aria-pressed="false"
                    >
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                            <path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 8 10 8a18.5 18.5 0 0 1-3.1 4.3"></path>
                            <path d="M6.6 6.6A18.2 18.2 0 0 0 2 12s3.5 8 10 8a10.6 10.6 0 0 0 4.1-.8"></path>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="login-options">
                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="login-button">Masuk</button>
        </form>

        <a href="{{ route('peta') }}" class="back-link">Kembali ke peta</a>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');

            if (!passwordInput || !toggleButton) {
                return;
            }

            toggleButton.addEventListener('click', function () {
                const nextType = passwordInput.type === 'password' ? 'text' : 'password';
                const isVisible = nextType === 'text';

                passwordInput.type = nextType;
                toggleButton.classList.toggle('is-visible', isVisible);
                toggleButton.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });
    </script>
</body>
</html>

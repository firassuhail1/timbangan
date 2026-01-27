<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

    <style>
        /* Reset & Base */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f172a;
            color: #fff;
            min-height: 100vh;
            display: grid;
            place-items: center;
            overflow-x: hidden;
        }

        /* Background Effects */
        .background {
            position: fixed;
            inset: 0;
            z-index: -1;
        }

        .noise {
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><filter id=%22n%22><feTurbulence type=%22fractalNoise%22 baseFrequency=%22.8%22 numOctaves=%224%22 stitchTiles=%22stitch%22/></filter><rect width=%22100%22 height=%22100%22 filter=%22url(%23n)%22 opacity=%220.1%22/></svg>') repeat;
        }

        .grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .gradient-sphere {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.5;
        }

        .sphere-1 {
            width: 550px;
            height: 600px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            top: -150px;
            left: -150px;
        }

        .sphere-2 {
            width: 600px;
            height: 550px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            bottom: -100px;
            right: -100px;
        }

        /* Main Layout */
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1400px;
            min-height: 100vh;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            border-radius: 28px;
            overflow: hidden;
        }

        /* Left Panel */
        .left-panel {
            flex: 1;
            padding: 3rem 2.5rem;
            background: rgba(15, 15, 26, 0.85);
            backdrop-filter: blur(16px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100vh;
        }

        .brand .logo {
            font-size: 4.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .logo-text {
            font-size: 1.1rem;
            margin-top: 0.5rem;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        .intro-text h1 {
            font-size: 2.1rem;
            margin: 2rem 0 1rem;
            line-height: 1.3;
        }

        .intro-text p {
            font-size: 1rem;
            opacity: 0.8;
            line-height: 1.7;
        }

        .features {
            margin: 3rem 0;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0;
            font-size: 1.15rem;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .footer {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Right Panel - Form */
        .right-panel {
            flex: 1;
            background: rgba(20, 20, 40, 0.92);
            backdrop-filter: blur(16px);
            display: grid;
            place-items: center;
            padding: 2rem;
            min-height: 100vh;
        }

        .remember-me input[type="checkbox"] {
            vertical-align: middle;
            margin-top: -7px;
            /* angkat dikit biar sejajar */
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .forgot-password {
            display: flex;
            align-items: center;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            background: rgba(30, 30, 50, 0.4);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-header h2 {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .login-header p {
            text-align: center;
            opacity: 0.8;
            margin-bottom: 2.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .input-with-icon {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-field {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        /* Dropdown select */
        select.input-field {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            /* teks input */
            font-size: 1rem;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Teks option */
        select.input-field option {
            color: #000;
            /* hitam agar terlihat di dropdown */
            background-color: #fff;
            /* putih background dropdown */
        }

        /* Focus */
        select.input-field:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.7;
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #ccc;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
            width: 100%;
            flex-wrap: nowrap;
        }

        .login-button {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .signup-link a {
            color: #a8a8ff;
            text-decoration: none;
        }

        /* RESPONSIVE - INI YANG PALING PENTING */
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: row;
                /* Tetap 2 kolom di tablet */
                min-height: 100vh;
            }

            .left-panel,
            .right-panel {
                padding: 2.5rem 2rem;
            }

            .brand .logo {
                font-size: 3.8rem;
            }

            .intro-text h1 {
                font-size: 1.9rem;
            }

            .login-header h2 {
                font-size: 2.1rem;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                border-radius: 0;
                min-height: 100vh;
            }

            .left-panel {
                border-radius: 0;
                padding: 3rem 2rem;
                text-align: center;
                min-height: auto;
            }

            .right-panel {
                border-radius: 0;
                padding: 2rem;
            }

            .brand .logo {
                font-size: 3.5rem;
            }

            .login-container {
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {

            .left-panel,
            .right-panel {
                padding: 2rem 1.5rem;
            }

            .form-extras {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }

            .forgot-password {
                margin-top: 0 !important;
            }
        }

        /* Animasi Fade In */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s forwards;
        }

        .fade-in-1 {
            animation-delay: 0.2s;
        }

        .fade-in-2 {
            animation-delay: 0.4s;
        }

        .fade-in-3 {
            animation-delay: 0.6s;
        }

        .fade-in-4 {
            animation-delay: 0.8s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: none;
            }
        }

        .select-arrow {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.7;
            font-size: 1rem;
            pointer-events: none;
            /* biar tidak ganggu klik select */
            transition: transform 0.3s ease;
        }

        select:focus+.select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        /* Hilangkan panah default browser */
        #esp_id {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 3rem !important;
            /* beri ruang untuk panah custom */
        }

        select::-ms-expand {
            display: none;
        }
    </style>
</head>

<body>

    <div class="background">
        <div class="noise"></div>
        <div class="grid"></div>
        <div class="gradient-sphere sphere-1"></div>
        <div class="gradient-sphere sphere-2"></div>
    </div>

    <div class="login-wrapper">
        <!-- LEFT PANEL -->
        <div class="left-panel">
            <div class="brand fade-in fade-in-1">
                <div class="logo">KMJ</div>
                <div class="logo-text">#duniakanindomakmurjaya</div>
            </div>

            <div class="intro-text fade-in fade-in-2">
                <h1>PT. Kanindo Makmur Jaya</h1>
                <p>Jl. Raya Jepara - Kudus, Pendosawalan, Kec. Kalinyamatan,<br>Kabupaten Jepara, Jawa Tengah 59462</p>
            </div>

            <div class="features fade-in fade-in-3">
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <div class="feature-text">Perusahaan Tas Merk Ternama</div>
                </div>
            </div>

            <div class="footer fade-in fade-in-4">
                <span>© <?= date('Y') ?> . All rights reserved.</span>
            </div>
        </div>

        <!-- RIGHT PANEL - FORM -->
        <div class="right-panel">
            <div class="login-container fade-in fade-in-3">
                <div class="login-header">
                    <h2>Selamat Datang!</h2>
                    <p>Silahkan masukkan username dan password anda!</p>
                </div>

                <form action="{{ route('login.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <input type="text" name="username" id="username" class="input-field"
                                value="{{ old('username') ?: Cookie::get('username') ?? '' }}" placeholder="Username"
                                required />
                            <i class="fa-regular fa-user form-icon"></i>
                        </div>
                        @error('username')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <input type="password" name="password" id="password" class="input-field"
                                placeholder="Enter your password" required />
                            <i class="fa-solid fa-lock form-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="esp_id">Pilih Timbangan (ESP)</label>
                        <div class="input-with-icon">
                            <select name="esp_id" id="esp_id" class="input-field">
                                <option value="">Pilih timbangan...</option>
                                @php
                                    $selectedEsp = $autoSelectedEspId ?? null;
                                @endphp

                                @foreach ($availableDevices as $device)
                                    <option value="{{ $device->esp_id }}"
                                        {{ $selectedEsp === $device->esp_id ? 'selected' : '' }}>
                                        {{ $device->name ?? $device->esp_id }}
                                    </option>
                                @endforeach
                            </select>

                            <i class="fa-solid fa-scale-balanced form-icon"></i>
                            <!-- Panah dropdown custom -->
                            <i class="fa-solid fa-chevron-down select-arrow"></i>
                        </div>
                        @error('esp_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <input type="hidden" name="esp_id_final" id="esp_id_final">

                    <div class="form-extras">
                        <div class="remember-me">
                            <input type="checkbox" name="remember" id="remember" checked />
                            <label for="remember">Remember me</label>
                        </div>
                        {{-- <div class="forgot-password">
                            <a href="#">Forgot password?</a>
                        </div> --}}
                    </div>

                    <button type="submit" class="login-button">Sign In</button>

                    {{-- <div class="signup-link">
                        Don't have an account? <a href="#">Create account</a>
                    </div> --}}
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Pastikan ini dipanggil setelah user login dan halaman dashboard terbuka
            async function initSanctum() {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include'
                });

                function getCookie(name) {
                    let value = "; " + document.cookie;
                    let parts = value.split("; " + name + "=");
                    if (parts.length === 2) return parts.pop().split(";").shift();
                }

                // Override fetch agar selalu sertakan token
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    options.credentials = 'include';
                    options.headers = {
                        ...(options.headers || {}),
                        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                        'Accept': 'application/json'
                    };
                    return originalFetch(url, options);
                };
            }

            await initSanctum();
        });

        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const selectEl = document.getElementById('esp_id');
            const formEl = selectEl.closest('form');
            const usernameInput = document.getElementById('username')

            let isSubmitting = false;
            let lastDataHash = null;

            formEl.addEventListener('submit', () => {
                isSubmitting = true;
            });


            async function fetchDevices() {
                const username = usernameInput.value.trim();
                if (!username) return;

                const res = await fetch(`/devices/list?username=${encodeURIComponent(username)}`);
                if (!res.ok) return;

                const devices = await res.json();

                selectEl.innerHTML = `<option value="">Pilih timbangan...</option>`;
                devices.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.esp_id;
                    opt.textContent = d.name ?? d.esp_id;
                    selectEl.appendChild(opt);
                });
            }

            usernameInput.addEventListener('blur', fetchDevices);

            fetchDevices();
            setInterval(fetchDevices, 5000);
        });

        // function renderTable(data, currentPage) {
        //     let rows = '';
        //     data.forEach((item, i) => {
        //         const no = (i + 1) + (currentPage - 1) * 10;
        //         rows += `
    //                 <tr>
    //                     <td>${no}</td>
    //                     <td>${item.Buyer || '-'}</td>
    //                     <td>${item.PurchaseOrderNumber || '-'}</td>
    //                     <td>${item.ProductName || '-'}</td>
    //                     <td>${item.Qty || 0}</td>
    //                     <td>${item.ActualFOB || '-'}</td>
    //                     <td>${item.DocumentDate || '-'}</td>
    //                     <td>
    //                         <button class="btn btn-sm btn-outline-primary btn-timbang" 
    //                                 data-item="${encodeURIComponent(JSON.stringify(item))}">
    //                             <i class="fa-solid fa-weight-scale"></i> Timbang
    //                         </button>
    //                     </td>
    //                 </tr>`;
        //     });
        //     tableBody.innerHTML = rows;
        // }
    </script>
</body>

</html>

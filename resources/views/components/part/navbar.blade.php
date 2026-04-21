<header class="mb-3 shadow-sm p-3 rounded">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

        <!-- Kiri: Burger (mobile/tablet) + Search (desktop) -->
        <div class="d-flex align-items-center flex-shrink-0">
            <!-- Burger hanya muncul di < xl -->
            <a href="#" class="burger-btn d-block d-xl-none me-3 text-decoration-none">
                <i class="fa-solid fa-bars fs-3" style="color: #435ebe"></i>
            </a>

            <!-- Search hanya muncul di desktop (lg ke atas) -->
            <form action="" class="d-none d-lg-flex align-items-center mb-0">
                <div class="input-group" style="min-width: 280px;">
                    <input type="text" class="form-control" placeholder="Search ..." aria-label="Search" />
                    <button type="button" class="btn btn-primary">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tengah: Nama device (flex-grow-1 supaya push ke tengah jika ada ruang) -->
        @php
            $deviceName = 'Timbangan tidak terhubung';
            $deviceType = null;
            if (Auth::check()) {
                $device = \App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first();
                if ($device) {
                    if (preg_match('/Timbangan-([OP])\d+/', $device->esp_id, $matches)) {
                        $deviceType = $matches[1];
                    }
                    $deviceName = $device->name ?: $device->esp_id;
                }
            }
        @endphp

        @if ($deviceType === 'O' || $deviceType === 'P')
            <div class="flex-grow-1 text-center order-last order-lg-0">
                <h5 class="mb-0 text-truncate" style="max-width: 100%;">
                    {{ $deviceName }}
                </h5>
            </div>
        @endif

        <!-- Kanan: Notifikasi + Profile (selalu di paling kanan) -->
        <div class="d-flex align-items-center gap-3 flex-shrink-0 ms-auto">
            <!-- NOTIFIKASI -->
            @if (Auth::check())
                @if (Auth::user()->role == 'user')
                    <div class="position-relative">
                        <a href="#" id="notifBtn" class="text-decoration-none" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-bell fs-4" style="color: #435ebe"></i>
                            <span id="notifBadge"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow animated fadeIn" aria-labelledby="notifBtn">
                            <li class="px-3 py-2 text-center">
                                <h6 class="mb-0">Pemberitahuan Firmware</h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li id="notifList" class="list-unstyled">
                                <!-- Isi notif via JS -->
                            </li>
                        </ul>
                    </div>
                @endif
            @endif

            <!-- PROFILE -->
            <div class="dropdown">
                <a href="#" id="dropdownProfile" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ asset('assets/images/profile.png') }}" alt="Profile" class="rounded-circle"
                        width="42" height="42" style="cursor:pointer; object-fit: cover;">
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow animated fadeIn" aria-labelledby="dropdownProfile">
                    <li class="px-3 py-2 text-center">
                        <img src="{{ asset('assets/images/profile.png') }}" alt="Avatar" class="rounded-circle mb-2"
                            width="70" height="70" style="object-fit: cover;">
                        <h6 class="fw-bold mb-0">{{ Auth::user()->username ?? '-' }}</h6>
                        <p class="text-muted small mb-2">{{ Auth::user()->line ?? '-' }}</p>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="{{ route('setting.profile') }}"><i
                                class="fa-solid fa-user me-2"></i> My Profile</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</header>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const notifBadge = document.getElementById('notifBadge');
            const notifList = document.getElementById('notifList');
            const checkNotifUrl = "{{ route('firmware.check-notification') }}";
            const pollInterval = 15000; // 15 detik - jangan terlalu pendek agar server tidak overload

            let pollingTimer = null;

            async function checkFirmwareNotification() {
                try {
                    const response = await fetch(checkNotifUrl, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Network error');

                    const data = await response.json();

                    if (data.has_notification) {
                        // Tampilkan badge
                        notifBadge.textContent = data.count || 1;
                        notifBadge.classList.remove('d-none');

                        // Update isi dropdown
                        notifList.innerHTML = `
                            <a href="${data.link || '#'}" class="dropdown-item d-flex align-items-center py-3 border-bottom">
                                <div class="me-3">
                                    <i class="fa-solid fa-cloud-arrow-up fs-4 text-primary"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold">${data.message}</p>
                                    <small class="text-muted">Dirilis: ${data.firmware.released_at}</small>
                                    ${data.firmware.notes ? `<small class="d-block text-muted mt-1">${data.firmware.notes}</small>` : ''}
                                </div>
                            </a>
                        `;
                    } else {
                        // Sembunyikan badge & kosongkan list
                        notifBadge.classList.add('d-none');
                        notifBadge.textContent = '0';
                        notifList.innerHTML =
                            '<p class="text-center text-muted py-3 mb-0">Tidak ada pemberitahuan baru</p>';
                    }

                } catch (err) {
                    console.warn('Polling notifikasi gagal:', err);
                    // Opsional: tampilkan fallback atau retry
                } finally {
                    // Jadwalkan polling berikutnya
                    pollingTimer = setTimeout(checkFirmwareNotification, pollInterval);
                }
            }

            // Mulai polling
            checkFirmwareNotification(); // Cek pertama langsung

            // Cleanup
            window.addEventListener('beforeunload', () => {
                if (pollingTimer) clearTimeout(pollingTimer);
            });
        });
    </script>
@endpush

<header class="mb-3 shadow-sm p-3 rounded d-flex justify-content-between align-items-center flex-wrap">

    <!-- Kiri: Burger + Search (hanya desktop) -->
    <div class="d-flex align-items-center order-1">
        <!-- Burger muncul di tablet & HP -->
        <a href="#" class="burger-btn d-block d-xl-none me-3">
            <i class="fa-solid fa-bars fs-3" style="color: #435ebe"></i>
        </a>

        <!-- Search hanya muncul di desktop (lg ke atas) -->
        <form action="" class="d-none d-lg-flex align-items-center mb-0">
            <div class="input-group flex-grow-1">
                <input type="text" class="form-control flex-grow-1" placeholder="Search ..." aria-label="Search" />
                <button type="button" class="btn btn-primary">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Tengah: Nama device -->
    @php
        $deviceName = 'Timbangan tidak terhubung';
        $deviceType = null;

        if (Auth::check()) {
            // Ambil device aktif milik user ini
            $device = \App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first();

            if ($device) {
                // ambil huruf pertama setelah "Timbangan-" → O atau P
                if (preg_match('/Timbangan-([OP])\d+-/', $device->esp_id, $matches)) {
                    $deviceType = $matches[1];
                }

                // gunakan nama device jika ada, fallback ke esp_id
                $deviceName = $device->name ?: $device->esp_id;
            }
        }
    @endphp

    @if ($deviceType === 'O' || $deviceType === 'P')
        <div class="nama-esp text-center flex-grow-1 order-2 d-flex justify-content-center align-items-center">
            <h5 class="mb-0 text-truncate" style="max-width: 100%;">
                {{ $deviceName }}
            </h5>
        </div>
    @endif

    <!-- Kanan: User -->
    <div class="d-flex align-items-center gap-3 order-3 justify-content-end">
        <div class="dropdown">
            <a href="#" id="dropdownProfile" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ asset('assets/images/profile.png') }}" alt="Profile" class="rounded-circle" width="42"
                    height="42" style="cursor:pointer;">
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow animated fadeIn" aria-labelledby="dropdownProfile">
                <li class="px-3 py-2 text-center">
                    <img src="{{ asset('assets/images/profile.png') }}" alt="Avatar" class="rounded-circle mb-2"
                        width="70" height="70">
                    <h6 class="fw-bold mb-0">{{ Auth::user()->username ?? '-' }}</h6>
                    <p class="text-muted small mb-2">{{ Auth::user()->line ?? '-' }}</p>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="{{ route('setting.profile') }}"><i class="fa-solid fa-user me-2"></i>
                        My
                        Profile</a></li>
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

</header>

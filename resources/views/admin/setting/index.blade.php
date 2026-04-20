<x-layout.home title="My Profie">

    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Account Settings /</span> Account</h4>

            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i>
                                Account</a>
                        </li>
                        {{-- <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bx bx-bell me-1"></i> Notifications</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bx bx-link-alt me-1"></i> Connections</a>
                            </li> --}}
                    </ul>
                    <div class="card mb-4">
                        <h5 class="card-header">Profile Details</h5>
                        <div class="card-body">
                            <div class="dropdown mb-2">
                                <button class="btn btn-info dropdown-toggle device-btn" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-microchip"></i>
                                    <span id="currentDeviceName">Memuat Device...</span>
                                </button>

                                <ul class="dropdown-menu device-dropdown-menu" id="deviceList">
                                    <li>
                                        <span class="dropdown-item text-center disabled">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                            Memuat...
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <hr>
                            <form id="formAccountSettings" method="POST"
                                action="{{ route('profile.update', $profile->id) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="d-flex align-items-start align-items-sm-center gap-4">

                                    <!-- Kondisi foto -->
                                    <div class="photo-upload-box">
                                        <img src="{{ $profile->foto ? asset('storage/' . $profile->foto) : asset('assets/images/profile.png') }}"
                                            data-default="{{ asset('assets/images/profile.png') }}"
                                            data-current="{{ $profile->foto ? asset('storage/' . $profile->foto) : asset('assets/images/profile.png') }}"
                                            alt="user-avatar" class="d-block rounded" height="100" width="100"
                                            id="uploadedAvatar" />

                                        <div>
                                            <label class="btn btn-primary custom-file-btn mb-2">
                                                <span>Upload New Photo</span>
                                                <input type="file" id="upload" name="foto"
                                                    accept="image/png, image/jpeg" />
                                            </label>

                                            <button type="button" class="btn btn-outline-secondary mb-2"
                                                id="resetImage">
                                                Reset
                                            </button>

                                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                                Allowed JPG, GIF or PNG. Max size 800 KB.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-0" />

                                <div class="card-body">
                                    <div class="row">
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">Username</label>
                                            <input class="form-control" type="text" name="username"
                                                value="{{ $profile->username }}" />
                                        </div>

                                        <div class="mb-3 col-md-6">
                                            <label class="form-label">Line</label>
                                            <input class="form-control" type="text" name="line"
                                                value="{{ $profile->line }}" />
                                        </div>

                                        {{-- <div class="mb-3 col-md-6">
                                        <label class="form-label">Password</label>

                                        <div class="position-relative">
                                            <input type="password" id="password" name="password" class="form-control"
                                                placeholder="Kosongkan jika tidak mengubah"
                                                value="{{ $profile->password }}" />

                                            <!-- Eye icon -->
                                            <i class="fa-solid fa-eye-slash" id="togglePassword"
                                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d;">
                                            </i>
                                        </div>
                                    </div> --}}

                                    </div>

                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-save"></i> Save changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Konfirmasi Pindah Device -->
                    <div class="modal fade" id="confirmSwitchModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content shadow-lg">
                                <div class="modal-header text-dark">
                                    <h5 class="modal-title">
                                        <i class="fa-solid fa-arrow-right-arrow-left me-2"></i> Konfirmasi Pindah
                                        Device
                                    </h5>
                                    <button type="button" class="btn-close btn-close-dark"
                                        data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p>Anda akan berpindah ke device:</p>
                                    <h5 class="fw-bold" id="targetDeviceName"></h5>
                                    <small class="text-muted d-block mt-1" id="targetDeviceId"></small>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fa-solid fa-xmark"></i> Batal
                                    </button>
                                    <button type="button" class="btn btn-primary" id="confirmSwitchBtn">
                                        <i class="fa-solid fa-check"></i> Ya, Pindah Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .photo-upload-box {
                display: flex;
                align-items: center;
                gap: 20px;
                padding: 20px;
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                background: #f8fafc;
                transition: 0.2s ease-in-out;
            }

            .photo-upload-box:hover {
                border-color: #0d6efd;
                background: #f1f5f9;
            }

            .photo-preview {
                width: 90px;
                height: 90px;
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .photo-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .custom-file-btn input {
                display: none;
            }
        </style>
    @endpush

    @push('js')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.getElementById('upload').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('uploadedAvatar').src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('resetImage').addEventListener('click', function() {
                const img = document.getElementById('uploadedAvatar');
                img.src = img.dataset.current; // atau img.dataset.default
                document.getElementById('upload').value = "";
            });

            // password
            document.getElementById('togglePassword').addEventListener('click', function() {
                const input = document.getElementById('password');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                // Toggle icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // Device
            let currentDeviceId = null;

            async function loadAvailableDevices() {
                try {
                    const res = await fetch('/user/devices/available'); // BENAR
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status} ${res.statusText}`);
                    }
                    const devices = await res.json();

                    const list = document.getElementById('deviceList');
                    list.innerHTML = '';

                    const currentUserDevice = devices.find(d => d.status === 'in_use');

                    if (currentUserDevice) {
                        currentDeviceId = currentUserDevice.id;
                        document.getElementById('currentDeviceName').textContent =
                            currentUserDevice.name || currentUserDevice.esp_id;
                    }

                    if (devices.length === 0) {
                        list.innerHTML =
                            '<li><a class="dropdown-item text-center" href="#">Tidak ada device aktif</a></li>';
                        return;
                    }

                    devices.forEach(device => {
                        const isCurrent = device.id === currentDeviceId;
                        const statusBadge = device.status === 'in_use' ?
                            '<span class="badge bg-success ms-2"> Sedang Dipakai</span>' :
                            '<span class="badge bg-primary ms-2"> Online</span>';

                        const item = document.createElement('li');
                        item.innerHTML = `
                            <a class="dropdown-item d-flex justify-content-between align-items-center ${isCurrent ? 'active' : ''}" 
                            href="javascript:void(0)" 
                            onclick="prepareSwitch(${device.id}, '${(device.name || device.esp_id).replace(/'/g, "\\'")}', '${device.esp_id}')">
                                <div>
                                    <div><strong>${device.name || device.esp_id}</strong></div>
                                    <small class="text-muted">ID: ${device.esp_id}</small>
                                </div>
                                ${statusBadge}
                            </a>
                        `;
                        list.appendChild(item);
                    });
                } catch (err) {
                    console.error("Gagal load device:", err);
                    document.getElementById('deviceList').innerHTML =
                        '<li><a class="dropdown-item text-danger text-center" href="#">Error loading devices</a></li>';
                }
            }

            function prepareSwitch(id, name, esp_id) {
                if (id == currentDeviceId) {
                    alert("Kamu sudah menggunakan device ini.");
                    return;
                }

                document.getElementById('targetDeviceName').textContent = name || esp_id;
                document.getElementById('targetDeviceId').textContent = esp_id;

                const modal = new bootstrap.Modal(document.getElementById('confirmSwitchModal'));
                modal.show();

                document.getElementById('confirmSwitchBtn').onclick = () => switchDevice(id);
            }

            async function switchDevice(deviceId) {
                try {
                    const res = await fetch('/user/devices/switch', { // BENAR
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            device_id: deviceId
                        })
                    });

                    if (!res.ok) {
                        const text = await res.text();
                        console.error("Response:", text);
                        throw new Error("Server error");
                    }

                    const data = await res.json();

                    if (data.success) {
                        Swal.fire('Sukses!', 'Berhasil pindah devicer!', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Tidak dapat terhubung ke server', 'error');
                }
            }

            // Load saat halaman dibuka & refresh tiap 15 detik
            loadAvailableDevices();
            setInterval(loadAvailableDevices, 15000);
        </script>
    @endpush

</x-layout.home>

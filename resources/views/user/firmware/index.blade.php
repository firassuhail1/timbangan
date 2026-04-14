<x-layout.home title="Update Firmware">

    <div class="page-heading d-flex justify-content-between align-items-center">
        @php
            $deviceType = null;
            if (isset($device) && $device) {
                // Ambil huruf pertama setelah "Timbangan-" → O atau P
                if (preg_match('/Timbangan-([OP])\d+-/', $device->esp_id, $matches)) {
                    $deviceType = $matches[1];
                }
            }
        @endphp

        @if ($deviceType === 'O')
            <h5 class="welcome-message">Timbangan Ordersheet</h5>
        @elseif ($deviceType === 'P')
            <h5 class="welcome-message">Timbangan Package</h5>
        @endif

        <div class="text-end">
            <h6 id="current-day" class="mb-0 fw-bold"></h6>
            <small id="current-time" class="text-muted"></small>
        </div>
    </div>

    <hr>

    <div class="page-content">
        <h2 class="mb-4">Update Firmware Device</h2>

        @if (isset($noDevice))
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> Tidak ada device yang terhubung. Hubungkan device terlebih
                dahulu.
            </div>
        @else
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">

                    {{-- Header Device --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1">
                                <i class="fa fa-microchip text-primary me-2"></i>
                                {{ $device->name ?? $device->esp_id }}
                            </h4>
                            <small class="text-muted fw-bold">
                                Firmware Saat Ini :
                                <span class="badge text-muted">{{ $currentVersion }}</span>
                            </small>
                        </div>

                        @if ($firmware)
                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-circle-info"></i> Update Tersedia
                            </span>
                        @else
                            <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-circle-check"></i> Sudah Versi Terbaru
                            </span>
                        @endif
                    </div>

                    <hr>

                    @if ($firmware)

                        <div class="row g-4 align-items-center">

                            {{-- Info Update --}}
                            <div class="col-md-8">

                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-1">
                                        Versi Baru :
                                        <span class="text-success">*{{ $firmware->version }}</span>
                                    </h5>

                                    <div class="mb-2">
                                        @if ($firmware->device_type == 'P')
                                            <span class="badge bg-primary rounded-pill px-3">
                                                Timbangan Package
                                            </span>
                                        @elseif($firmware->device_type == 'O')
                                            <span class="badge bg-secondary rounded-pill px-3">
                                                Timbangan Ordersheet
                                            </span>
                                        @endif
                                    </div>

                                    <small class="text-muted">
                                        Dirilis :
                                        {{ $firmware->created_at ? \Carbon\Carbon::parse($firmware->created_at)->format('d M Y H:i') : '-' }}
                                    </small>
                                </div>

                                @if ($firmware->notes)
                                    <div class="bg-light rounded-3 p-3 mb-3 border">
                                        <strong class="d-block mb-1 text-muted">
                                            <i class="fa fa-sticky-note text-warning me-1"></i>
                                            Catatan Update
                                        </strong>
                                        <small class="text-muted">
                                            {{ $firmware->notes }}
                                        </small>
                                    </div>
                                @endif

                                <button id="updateOtaBtn" class="btn btn-success btn-lg rounded-pill px-4 shadow-sm"
                                    data-firmware-id="{{ $firmware->id }}" data-device-id="{{ $device->id }}">
                                    <i class="fa fa-cloud-download-alt me-2"></i>
                                    Update OTA Sekarang
                                </button>

                            </div>

                            {{-- Progress Section --}}
                            <div class="col-md-4">
                                <div id="progressContainer" class="d-none">
                                    <div class="card border-0 bg-light rounded-4 p-3 shadow-sm">
                                        <small class="text-muted d-block mb-2">
                                            Proses Update
                                        </small>

                                        <div class="progress rounded-pill" style="height: 18px;">
                                            <div id="progressBar"
                                                class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: 0%">
                                            </div>
                                        </div>

                                        <small id="progressText" class="mt-2 text-muted">
                                            Memulai update...
                                        </small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="fw-semibold">Device Sudah Menggunakan Versi Terbaru</h5>
                            <small class="text-muted">
                                Tidak ada pembaruan firmware saat ini.
                            </small>
                        </div>

                    @endif

                </div>
            </div>

        @endif
    </div>

    @push('js')
        <!-- SweetAlert2 CDN untuk notif -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const deviceId = "{{ $device->id ?? '' }}"; // Wajib ada, pastikan $device ada di controller
                const checkUrl = "{{ route('device.check-firmware-update', $device->id ?? '') }}";
                const pollInterval = 10000; // 10 detik - sesuaikan jika perlu

                let currentHasUpdate = {{ $firmware ? 'true' : 'false' }};
                let pollingTimer = null;

                // Fungsi update UI berdasarkan data polling
                function updateUI(data) {
                    console.log('Polling response:', data); // Debug: lihat apa yang dikembalikan server

                    if (!data.has_update) {
                        if (currentHasUpdate) {
                            console.log('Update hilang → reload untuk refresh tampilan');
                            location.reload(); // Aman untuk kasus ini, atau custom tanpa reload jika mau
                        }
                        return;
                    }

                    // Ada update baru → update seluruh card-body dinamis
                    currentHasUpdate = true;
                    const firmware = data.firmware;
                    const currentVersion = data.current_version || '{{ $currentVersion }}'; // Fallback ke nilai awal
                    const deviceName = "{{ $device->name ?? $device->esp_id }}"; // Ambil dari Blade
                    const releasedDate = firmware.released_at ||
                        (firmware.created_at ? new Date(firmware.created_at).toLocaleString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }) : '-');

                    const cardBody = document.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    <i class="fa fa-microchip text-primary me-2"></i>
                                    ${deviceName}
                                </h4>
                                <small class="text-muted fw-bold">
                                    Firmware Saat Ini :
                                    <span class="badge text-muted">${currentVersion}</span>
                                </small>
                            </div>
                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-circle-info"></i> Update Tersedia
                            </span>
                        </div>

                        <hr>

                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-1">
                                        Versi Baru :
                                        <span class="text-success">${firmware.version}</span>
                                    </h5>
                                    <div class="mb-2">
                                        ${firmware.device_type === 'P' ? 
                                            '<span class="badge bg-primary rounded-pill px-3">Timbangan Package</span>' : 
                                            '<span class="badge bg-secondary rounded-pill px-3">Timbangan Ordersheet</span>'}
                                    </div>
                                    <small class="text-muted">
                                        Dirilis : ${releasedDate}
                                    </small>
                                </div>

                                ${firmware.notes ? `
                                                    <div class="bg-light rounded-3 p-3 mb-3 border">
                                                        <strong class="d-block mb-1 text-muted">
                                                            <i class="fa fa-sticky-note text-warning me-1"></i>
                                                            Catatan Update
                                                        </strong>
                                                        <small class="text-muted">${firmware.notes}</small>
                                                    </div>
                                                ` : ''}

                                <button id="updateOtaBtn" class="btn btn-success btn-lg rounded-pill px-4 shadow-sm"
                                    data-firmware-id="${firmware.id}" data-device-id="${deviceId}">
                                    <i class="fa fa-cloud-download-alt me-2"></i>
                                    Update OTA Sekarang
                                </button>
                            </div>

                            <div class="col-md-4">
                                <div id="progressContainer" class="d-none">
                                    <div class="card border-0 bg-light rounded-4 p-3 shadow-sm">
                                        <small class="text-muted d-block mb-2">
                                            Proses Update
                                        </small>
                                        <div class="progress rounded-pill" style="height: 18px;">
                                            <div id="progressBar"
                                                class="progress-bar progress-bar-striped progress-bar-animated"
                                                role="progressbar" style="width: 0%">
                                            </div>
                                        </div>
                                        <small id="progressText" class="mt-2 text-muted">
                                            Memulai update...
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                        // Re-attach listener untuk tombol OTA setelah DOM di-update
                        const updateOtaBtn = document.getElementById('updateOtaBtn');
                        if (updateOtaBtn) {
                            updateOtaBtn.addEventListener('click', async function() {
                                const btn = this;
                                const firmwareId = btn.dataset.firmwareId;
                                const deviceId = btn.dataset.deviceId;

                                btn.disabled = true;
                                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
                                document.getElementById('progressContainer')?.classList.remove('d-none');

                                try {
                                    const response = await fetch('{{ route('firmware.ota') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').getAttribute(
                                                'content')
                                        },
                                        body: JSON.stringify({
                                            firmware_id: firmwareId,
                                            device_id: deviceId
                                        })
                                    });

                                    const data = await response.json();
                                    if (data.success) {
                                        Swal.fire('Sukses!', data.message, 'success');
                                        // Reload page setelah 3 detik
                                        setTimeout(() => location.reload(), 3000);
                                    } else {
                                        throw new Error(data.error || 'Update gagal');
                                    }
                                } catch (error) {
                                    Swal.fire('Error!', error.message, 'error');
                                } finally {
                                    btn.disabled = false;
                                    btn.innerHTML =
                                        '<i class="fa fa-cloud-download-alt me-2"></i> Update OTA Sekarang';
                                    document.getElementById('progressContainer')?.classList.add('d-none');
                                }
                            });
                        }
                    } else {
                        console.warn('Card body tidak ditemukan untuk update UI');
                    }
                }

                // Fungsi polling
                async function checkForUpdate() {
                    console.log('Mulai polling ke:', checkUrl); // Debug: lihat URL

                    try {
                        const response = await fetch(checkUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error: ${response.status}`);
                        }

                        const data = await response.json();
                        updateUI(data);

                    } catch (err) {
                        console.warn('Polling gagal:', err.message);
                    } finally {
                        // Jadwalkan polling selanjutnya (recursive)
                        if (pollingTimer) clearTimeout(pollingTimer);
                        pollingTimer = setTimeout(checkForUpdate, pollInterval);
                    }
                }

                // Mulai polling jika deviceId & checkUrl valid
                if (deviceId && checkUrl) {
                    checkForUpdate(); // Cek pertama langsung
                } else {
                    console.error('Device ID atau URL polling tidak valid');
                }

                // Cleanup saat halaman unload
                window.addEventListener('beforeunload', () => {
                    if (pollingTimer) clearTimeout(pollingTimer);
                });
            });
        </script>
    @endpush

</x-layout.home>

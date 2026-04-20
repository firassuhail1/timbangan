<div class="modal fade" id="tambah" tabindex="-1" aria-labelledby="tambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">

            <!-- HEADER -->
            <div class="modal-header border-bottom text-dark">
                <h5 class="modal-title fw-bold" id="tambahLabel">
                    Tambah Ordersheet Package
                </h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="formOrdersheet" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="weight" id="weight">

                <!-- BODY — Scrollable & Responsive -->
                <div class="modal-body p-3 p-md-4" style="max-height: 75vh; overflow-y: auto;">

                    <!-- CARD: Informasi Package -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-4 text-primary fw-bold border-bottom pb-2">Informasi Package</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Package <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="Contoh: Wallet Kulit Sapi" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Leather Type</label>
                                    <input type="text" name="leather_type" id="leather_type" class="form-control"
                                        placeholder="Kulit Sapi, Domba, dll">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Color</label>
                                    <input type="text" name="color" id="color" class="form-control"
                                        placeholder="Hitam, Coklat, dll">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Size</label>
                                    <input type="text" name="size" id="size" class="form-control"
                                        placeholder="20x10 cm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Stitching Type</label>
                                    <input type="text" name="stitching_type" id="stitching_type" class="form-control"
                                        placeholder="Hand Stitch, Mesin">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Lining Material</label>
                                    <input type="text" name="lining_material" id="lining_material"
                                        class="form-control" placeholder="Kain, Kulit Tipis">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Deskripsi (Opsional)</label>
                                    <textarea name="description" rows="3" class="form-control" placeholder="Catatan tambahan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD: Berat Barang -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-4 text-primary fw-bold border-bottom pb-2">Berat Barang</h5>

                            <div class="row g-4 align-items-stretch">

                                <!-- KIRI: Input Manual -->
                                <div class="col-12 col-lg-6">
                                    <div class="h-80 d-flex flex-column gap-3">

                                        <div>
                                            <label for="no_package" class="form-label fw-semibold small text-muted">
                                                No. Package <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm"
                                                    name="no_package" id="no_package" placeholder="PKG-001" required>
                                                <button class="btn btn-outline-warning btn-sm" type="button"
                                                    id="btnScanBarcode">
                                                    <i class="fa-solid fa-barcode"></i>
                                                    <span class="d-none d-sm-inline"> Scan</span>
                                                </button>
                                            </div>
                                            <small class="text-muted">Tekan tombol scan atau ketik
                                                manual</small>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">Batas Min
                                                    (g)</label>
                                                <input type="number" step="0.01" name="rasio_batas_beban_min"
                                                    id="rasio_batas_beban_min" class="form-control text-center"
                                                    required placeholder="100">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">Batas Max
                                                    (g)</label>
                                                <input type="number" step="0.01" name="rasio_batas_beban_max"
                                                    id="rasio_batas_beban_max" class="form-control text-center"
                                                    required placeholder="150">
                                            </div>
                                        </div>

                                        <div class="text-center mt-auto">
                                            <label class="form-label fw-semibold text-success small d-block mb-1">Rasio
                                                Lost Weight</label>
                                            <input type="text" name="lost_weight" id="lost_weight" readonly
                                                class="form-control form-control-lg text-center bg-light fw-bold shadow-sm"
                                                style="max-width: 220px; margin: 0 auto;" value=""
                                                placeholder="0.00 kg (0%)">
                                        </div>
                                    </div>
                                </div>

                                <!-- KANAN: Timbangan Live -->
                                <div class="col-12 col-lg-6">
                                    <div class="h-60 d-flex flex-column">

                                        <div class="alert alert-success py-2 small text-center mb-3 rounded-pill">
                                            Timbangan Aktif & Siap
                                        </div>

                                        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                                            <div class="text-center p-5 rounded-4 shadow-lg weight-box">
                                                <h1 id="currentWeight" class="display-3 fw-bold text-primary mb-0">0
                                                </h1>
                                                <p class="text-muted fs-4 mb-2">Kg</p>
                                                <div id="previewStatus"
                                                    class="badge bg-warning text-dark fw-bold px-3 py-2">
                                                    Menunggu data...
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-center mt-4">
                                            <small class="text-muted d-block mb-3">Pastikan timbangan stabil sebelum
                                                menyimpan</small>
                                            <button type="button" id="tare"
                                                class="btn btn-outline-primary rounded-pill px-4">
                                                <i class="fa-solid fa-thumbtack"></i> Stabilkan (Tare)
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- AKHIR modal-body -->

                <!-- FOOTER -->
                <div class="modal-footer justify-content-center gap-3 border-top py-4">
                    <button type="submit" id="btnSimpanTimbang" class="btn btn-success btn-lg px-5 shadow" disabled>
                        <i class="fa-solid fa-floppy-disk"></i> Simpan
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">
                        <i class="fa-solid fa-circle-xmark"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Scan barcode --}}
<div class="modal fade" id="scannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header text-dark bg-light">
                <h6 class="modal-title fw-bold">
                    <i class="fa-solid fa-camera me-2"></i> Scan Barcode Carton
                </h6>
                <button type="button" class="btn-close btn-close-dark" id="forceCloseBtn"></button>
            </div>
            <div class="modal-body text-center p-0 bg-dark position-relative">
                <!-- Video akan mengisi full -->
                <div id="reader" class="w-100" style="height: 70vh; max-height: 600px;"></div>

                <div class="position-absolute bottom-0 inset-s-0 inset-e-0 p-3 bg-gradient"
                    style="background: linear-gradient(transparent, #000000ee);">
                    <p class="text-white mb-2 fw-semibold" id="scanStatus">Memuat kamera...</p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning btn-sm px-4 d-none" id="torchToggle">
                            <i class="fa-solid fa-lightbulb me-1"></i> Nyalakan Lampu
                        </button>
                        <button type="button" class="btn btn-info btn-sm px-4 d-none" id="switchCamera">
                            <i class="fa-solid fa-camera-rotate me-1"></i> Ganti Kamera
                        </button>
                        <button type="button" class="btn btn-danger btn-sm px-4" id="batalScan">
                            <i class="fa-solid fa-xmark me-1"></i> Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        /* FIX SCROLL BOCOR KE BACKGROUND */
        .modal-dialog-scrollable {
            overflow: hidden !important;
        }

        .modal-dialog-scrollable .modal-body {
            max-height: 60vh !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            /* smooth scroll di iPhone */
        }

        /* Fullscreen di HP tanpa bug */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0 !important;
                max-width: 100% !important;
                height: 100% !important;
            }

            .modal-content {
                height: 100% !important;
                border-radius: 0 !important;
            }

            .modal-body {
                max-height: 80vh !important;
            }
        }

        @media (max-width: 576px) {
            #currentWeight {
                font-size: 4rem !important;
            }

            .weight-box {
                padding: 2rem !important;
                min-width: 200px;
            }
        }
    </style>
@endpush

@push('js')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    {{-- Expose espId ke JS --}}
    <script>
        window.APP = {
            espId: "{{ optional(\App\Models\Update\Device::where('user_id', Auth::id())->where('status', 'in_use')->first())->esp_id }}"
        };
    </script>
    @vite(['resources/js/app.js'])

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const display = document.getElementById("currentWeight");
            const hiddenInput = document.getElementById("weight");
            const statusText = document.getElementById("previewStatus");
            const btnSimpan = document.getElementById("btnSimpanTimbang");
            const form = document.getElementById("formOrdersheet");
            const minInput = document.getElementById('rasio_batas_beban_min');
            const maxInput = document.getElementById('rasio_batas_beban_max');
            const lostWeightField = document.getElementById('lost_weight');

            // ── State stabil (ganti polling)
            let lastStableWeight = null;
            let stableStartTime = null;
            const STABLE_THRESHOLD = 0.5; // gram — toleransi getaran
            const STABLE_DURATION = 800; // ms — waktu diam sebelum "stabil"
            let hasPlayedStableBeepForThisItem = false;
            let stableTimer = null;
            let lastBeratSebelumStabil = null;

            function formatBerat(value) {
                const num = parseFloat(value);
                if (isNaN(num)) return "0";
                return Number.isInteger(num) ? num.toString() : num.toFixed(2);
            }

            function playStableBeep() {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 800;
                gain.gain.value = 0.3;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 1.2);
            }

            function playSuccessBeep() {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();

                function beep(freq, duration, delay = 0) {
                    setTimeout(() => {
                        const osc = ctx.createOscillator(),
                            gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = freq;
                        gain.gain.value = 0.4;
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + duration);
                    }, delay);
                }
                beep(523, 0.12, 0);
                beep(659, 0.12, 120);
                beep(784, 0.25, 240);
            }

            function hitungLossWeight(berat) {
                const min = parseFloat(minInput.value) || 0;
                const max = parseFloat(maxInput.value) || 0;

                if (!min || !max || berat <= 0) {
                    lostWeightField.value = '';
                    return;
                }

                const loss = (max - berat).toFixed(2);
                const ratio = ((berat - min) / (max - min) * 100).toFixed(1);
                lostWeightField.value = `${loss} g (${ratio}%)`;
            }

            // ── WebSocket listener
            function startListening(espId) {
                stopListening();
                window._currentEspId = espId;
                window.Echo.channel(`timbangan.${espId}`)
                    .listen('.berat.updated', (data) => {

                        const isPackage = data.espId?.includes('Timbangan-P');

                        // GUNAKAN toFixed(3) untuk mendapatkan 3 angka di belakang koma
                        // parseFloat digunakan agar hasilnya tetap angka jika diperlukan kalkulasi
                        const beratDisplay = isPackage ? (data.berat / 1000).toFixed(2) : data.berat;
                        const satuan = isPackage ? 'kg' : 'g';

                        // Kirim variabel lokal, bukan data.beratDisplay
                        updateBeratUI(beratDisplay, parseFloat(data.berat), satuan);
                    });
            }

            function stopListening() {
                if (window._currentEspId) {
                    window.Echo.leaveChannel(`timbangan.${window._currentEspId}`);
                    window._currentEspId = null;
                }
            }

            function updateBeratUI(beratDisplay, beratGram, satuan) {
                display.innerText = beratDisplay;
                hiddenInput.value = beratGram; // simpan gram mentah
                hitungLossWeight(beratGram);

                // Update label satuan di UI
                const satuanLabel = document.getElementById('satuanLabel');
                if (satuanLabel) satuanLabel.textContent = satuan;

                clearTimeout(stableTimer);

                if (beratGram <= 0.5) {
                    statusText.textContent = 'Timbangan Kosong';
                    statusText.className = 'badge bg-warning text-dark fw-bold px-3 py-2';
                    btnSimpan.disabled = true;
                    hasPlayedStableBeepForThisItem = false;
                    lastBeratSebelumStabil = null;
                    return;
                }

                if (lastBeratSebelumStabil !== null && Math.abs(beratGram - lastBeratSebelumStabil) > 5) {
                    hasPlayedStableBeepForThisItem = false;
                }

                statusText.textContent = 'Stabil';
                statusText.className = 'badge bg-success fw-bold px-3 py-2';
                btnSimpan.disabled = false;
                lastBeratSebelumStabil = beratGram;

                if (!hasPlayedStableBeepForThisItem) {
                    playStableBeep();
                    hasPlayedStableBeepForThisItem = true;
                }
            }

            // ── Tombol TARE
            document.getElementById("tare")?.addEventListener("click", async () => {
                statusText.textContent = 'Tare dikirim...';
                statusText.className = 'badge bg-info text-dark fw-bold px-3 py-2';
                display.innerText = "0";
                hiddenInput.value = "0";
                lastStableWeight = null;
                stableStartTime = null;
                hasPlayedStableBeepForThisItem = false;
                btnSimpan.disabled = true;

                try {
                    const res = await fetch('/api/package/timbangan/tare', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content
                        }
                    });
                    const json = await res.json();

                    if (json.success) {
                        statusText.textContent = 'Tare berhasil!';
                        statusText.className = 'badge bg-success fw-bold px-3 py-2';
                        setTimeout(() => {
                            statusText.textContent = 'Menunggu timbangan...';
                            statusText.className =
                                'badge bg-warning text-dark fw-bold px-3 py-2';
                        }, 2000);
                    } else {
                        throw new Error(json.message || 'Tare gagal');
                    }
                } catch (err) {
                    statusText.textContent = 'Tare gagal!';
                    statusText.className = 'badge bg-danger fw-bold px-3 py-2';
                }
            });

            // ── Form submit
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (btnSimpan.disabled) {
                    Swal.fire('Belum Stabil', 'Tunggu timbangan stabil!', 'warning');
                    return;
                }

                btnSimpan.disabled = true;
                btnSimpan.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Menyimpan...';

                try {
                    const res = await fetch('/api/package/store', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    });
                    const data = await res.json();

                    if (data.success) {
                        playSuccessBeep();
                        if (navigator.vibrate) navigator.vibrate([100, 80, 100]);

                        // Reset state untuk barang berikutnya
                        hasPlayedStableBeepForThisItem = false;
                        lastStableWeight = null;
                        stableStartTime = null;

                        stopListening(); // tutup channel sebelum redirect

                        Swal.fire({
                            icon: 'success',
                            title: 'Sukses!',
                            text: data.message,
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '/user/package-view';
                        });
                    } else {
                        throw new Error(data.message || 'Gagal');
                    }
                } catch (err) {
                    Swal.fire('Error', err.message || 'Gagal menyimpan!', 'error');
                } finally {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> Simpan';
                }
            });

            // ── Barcode scanner (tidak berubah)
            initBarcodeScanner();

            // ── Mulai WebSocket
            const espId = window.APP?.espId ?? null;
            if (espId) {
                startListening(espId);
            } else {
                statusText.textContent = 'Device tidak ditemukan';
                statusText.className = 'badge bg-danger fw-bold px-3 py-2';
            }
        });

        // ── Fungsi initBarcodeScanner (sama persis dengan kode lama)
        function initBarcodeScanner() {
            const scanButton = document.getElementById('btnScanBarcode');
            if (!scanButton) return;

            scanButton.addEventListener('click', startScanner);

            function startScanner() {
                const modalEl = document.getElementById('scannerModal');
                if (!modalEl) return alert('Modal scanner tidak ditemukan!');

                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });

                const statusEl = document.getElementById('scanStatus');
                const torchBtn = document.getElementById('torchToggle');
                const switchBtn = document.getElementById('switchCamera');
                const forceCloseBtn = document.getElementById('forceCloseBtn');
                const batalBtn = document.getElementById('batalScan');

                let scannerInstance = null;
                let currentCamera = 'environment';
                let torchOn = false;

                // Fungsi untuk MATIKAN scanner & TUTUP modal dengan paksa
                const destroyAndClose = () => {
                    if (scannerInstance) {
                        scannerInstance.stop().catch(() => {});
                        scannerInstance.clear().catch(() => {});
                        scannerInstance = null;
                    }
                    modal.hide();
                };

                // Event ketika modal benar-benar tertutup → bersihkan
                modalEl.addEventListener('hidden.bs.modal', () => {
                    destroyAndClose();
                }, {
                    once: true
                });

                // Tombol X dan Batal → paksa tutup
                forceCloseBtn.onclick = destroyAndClose;
                batalBtn.onclick = destroyAndClose;

                modal.show();

                modalEl.addEventListener('shown.bs.modal', () => {
                    statusEl.textContent = 'Memuat kamera...';

                    const html5QrCode = new Html5Qrcode("reader");
                    const config = {
                        fps: 15,
                        qrbox: {
                            width: 280,
                            height: 280
                        },
                        aspectRatio: 1,
                        disableFlip: false,
                        formatsToSupport: [
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.EAN_8,
                            Html5QrcodeSupportedFormats.UPC_A,
                            Html5QrcodeSupportedFormats.UPC_E
                        ]
                    };

                    const onSuccess = (decodedText) => {
                        const text = decodedText.trim();
                        if (!text) return;

                        const noBoxInput = document.getElementById('no_package');
                        if (noBoxInput) {
                            noBoxInput.value = text;
                            noBoxInput.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }

                        statusEl.innerHTML =
                            `<span class="text-success fw-bold">Berhasil!</span><br><small>${text}</small>`;

                        setTimeout(() => {
                            destroyAndClose();
                            Swal.fire({
                                icon: 'success',
                                title: 'Scan Berhasil!',
                                text: text,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }, 800);
                    };

                    html5QrCode.start({
                            facingMode: currentCamera
                        },
                        config,
                        onSuccess,
                        () => {} // error callback (noise)
                    ).then(() => {
                        scannerInstance = html5QrCode;
                        statusEl.innerHTML =
                            '<span class="text-info">Arahkan ke barcode...</span>';

                        // Torch
                        if (/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
                            torchBtn.classList.remove('d-none');
                            torchBtn.onclick = () => {
                                if (!scannerInstance) return;
                                torchOn = !torchOn;
                                scannerInstance.applyVideoConstraints({
                                    advanced: [{
                                        torch: torchOn
                                    }]
                                }).then(() => {
                                    torchBtn.innerHTML = torchOn ?
                                        '<i class="fa-solid fa-lightbulb-on me-1"></i> Matikan Lampu' :
                                        '<i class="fa-solid fa-lightbulb me-1"></i> Nyalakan Lampu';
                                    torchBtn.classList.toggle('btn-danger',
                                        torchOn);
                                    torchBtn.classList.toggle('btn-warning', !
                                        torchOn);
                                }).catch(() => {
                                    torchBtn.innerHTML = 'Lampu tidak didukung';
                                    torchBtn.disabled = true;
                                });
                            };
                        }

                        // Ganti kamera
                        Html5Qrcode.getCameras().then(cameras => {
                            if (cameras && cameras.length > 1) {
                                switchBtn.classList.remove('d-none');
                                switchBtn.onclick = () => {
                                    currentCamera = currentCamera ===
                                        'environment' ? 'user' : 'environment';
                                    scannerInstance.stop().then(() => {
                                        html5QrCode.start({
                                                    facingMode: currentCamera
                                                }, config, onSuccess,
                                                () => {})
                                            .then(() => scannerInstance =
                                                html5QrCode);
                                    });
                                };
                            }
                        }).catch(() => {});

                    }).catch(err => {
                        statusEl.innerHTML =
                            `<span class="text-danger">Gagal akses kamera:<br><small>${err.message || err}</small></span>`;
                        forceCloseBtn.disabled = false;
                        batalBtn.disabled = false;
                    });
                });
            }
        }
    </script>
@endpush

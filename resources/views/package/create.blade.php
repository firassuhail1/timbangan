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
                                                <p class="text-muted fs-4 mb-2">Gram</p>
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

                <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-gradient"
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

            let lastValidWeight = 0;
            let lastWeight = null; // ← akan diisi saat polling pertama
            let stableTimer = null;
            let isTareMode = false;
            let hasPlayedStableBeepForThisItem = false;
            let isFirstLoad = true; // ← TAMBAHAN: deteksi load pertama

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
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
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

            function resetStableBeepFlag() {
                hasPlayedStableBeepForThisItem = false;
            }

            function hitungLossWeight(current) {
                const min = parseFloat(minInput.value) || 0;
                const max = parseFloat(maxInput.value) || 0;

                if (!min || !max || current <= 0) {
                    lostWeightField.value = '';
                    if (current <= 0 && !isTareMode) {
                        statusText.innerText = "Timbangan Kosong";
                        statusText.className = "text-warning fw-bold";
                    }
                    return;
                }

                const loss = (max - current).toFixed(2);
                const ratio = ((current - min) / (max - min) * 100).toFixed(1);
                lostWeightField.value = `${loss} g (${ratio}%)`;

                if (current < min) {
                    statusText.textContent = "Berat di bawah batas!";
                    statusText.className = "text-danger fw-bold";
                } else if (current > max) {
                    statusText.textContent = "Berat melebihi batas!";
                    statusText.className = "text-danger fw-bold";
                } else {
                    statusText.textContent = "Berat dalam batas normal";
                    statusText.className = "text-success fw-bold";
                }
            }

            // POLLING — DIPERBAIKI SUPAYA TIDAK BUNYI SAAT BUKA MODAL
            async function ambilBeratLiveLoop() {
                try {
                    const res = await fetch('/api/package/timbangan/live', {
                        credentials: 'include',
                        cache: 'no-cache'
                    });

                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    if (!data.success || data.berat === null) return;

                    const berat = parseFloat(data.berat);
                    if (isNaN(berat)) return;

                    lastValidWeight = berat;
                    display.innerText = formatBerat(berat);
                    hiddenInput.value = berat;
                    hitungLossWeight(berat);

                    if (berat > 0) isTareMode = false;

                    // === KOSONG → RESET & JANGAN BUNYI ===
                    if (berat <= 0.5) {
                        statusText.innerText = "Timbangan Kosong";
                        statusText.className = "text-warning fw-bold";
                        btnSimpan.disabled = true;
                        resetStableBeepFlag();
                        isFirstLoad = false; // sudah lewati fase inisialisasi
                        lastWeight = berat; // penting: set agar tidak trigger perubahan
                        return;
                    }

                    // === PERTAMA KALI DAPAT BERAT NYATA (bukan 0) ===
                    if (isFirstLoad) {
                        lastWeight = berat; // anggap ini "normal", bukan "barang baru"
                        isFirstLoad = false;
                        btnSimpan.disabled = true;
                        statusText.innerText = "Menunggu stabil...";
                        statusText.className = "text-info fw-bold";
                        return;
                    }

                    // === ADA BARANG BARU? (dari kosong → ada beban) ===
                    if (lastWeight <= 0.5 && berat > 0.5) {
                        resetStableBeepFlag(); // ini barang baru → siap beep saat stabil
                    }

                    // === DETEKSI PERUBAHAN BERAT ===
                    if (Math.abs(lastWeight - berat) > 0.01) { // toleransi kecil biar ga noise
                        lastWeight = berat;
                        btnSimpan.disabled = true;

                        if (stableTimer) clearTimeout(stableTimer);

                        stableTimer = setTimeout(() => {
                            btnSimpan.disabled = false;
                            statusText.innerText = "Stabil";
                            statusText.className = "text-success fw-bold fs-3";

                            // HANYA BUNYI JIKA BELUM PERNAH BUNYI UNTUK BARANG INI
                            if (!hasPlayedStableBeepForThisItem) {
                                playStableBeep();
                                hasPlayedStableBeepForThisItem = true;
                            }
                        }, 800);
                    }

                } catch (e) {
                    console.error("Polling error:", e);
                    statusText.innerText = "Koneksi bermasalah...";
                    statusText.className = "text-danger fw-bold";
                } finally {
                    setTimeout(ambilBeratLiveLoop, 150);
                }
            }

            // TOMBOL TARE
            const btnTare = document.getElementById("tare");
            if (btnTare) {
                btnTare.addEventListener("click", async () => {
                    statusText.innerText = "Tare dikirim...";
                    statusText.className = "text-info fw-bold";
                    display.innerText = "0";
                    hiddenInput.value = "0";
                    isTareMode = true;
                    resetStableBeepFlag();
                    lastWeight = 0;

                    try {
                        await fetch('/api/package/timbangan/tare', {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                tare: true
                            })
                        });
                        statusText.innerText = "Tare berhasil!";
                        statusText.className = "text-success fw-bold";
                    } catch (err) {
                        statusText.innerText = "Tare gagal (UI direset)";
                        statusText.className = "text-warning fw-bold";
                    }
                });
            }

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

            // SUBMIT — reset beep untuk barang berikutnya
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
                        resetStableBeepFlag();

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

            // MULAI
            display.innerText = "0";
            statusText.innerText = "Menunggu data...";
            statusText.className = "text-muted";

            initBarcodeScanner();
            ambilBeratLiveLoop();
        });
    </script>
@endpush

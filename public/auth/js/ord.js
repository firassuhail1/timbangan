let lastSearchParams = {
    search: '',
    start_date: '',
    end_date: '',
    page: 1
}

let currentSearchData = []

document.addEventListener('DOMContentLoaded', () => {
    initDateTime()
    initSearch()
    initTimbangModal()
    initBarcodeScanner()
    initManualMode()
    initTareButton()
    initLossWeightCalculation()
    initSaveButton()
})

function initDateTime() {
    const days = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu'
    ]

    function updateDateTime() {
        const now = new Date()
        const dayName = days[now.getDay()]
        const date = now.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        })
        const time = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        })

        document.getElementById('current-day').textContent =
            `${dayName}, ${date}`
        document.getElementById('current-time').textContent = time
    }

    updateDateTime() // jalankan sekali saat load
    setInterval(updateDateTime, 1000) // update tiap detik
}

function initSearch() {
    const searchBtn = document.getElementById('searchBtn')
    searchBtn.addEventListener('click', () => fetchData(1))
}

async function fetchData(page = 1) {
    const spinner = document.getElementById('loadingSpinner')
    const tableBody = document.querySelector('#resultTable tbody')

    const search = document.getElementById('search')?.value.trim() || ''
    const start = document.getElementById('start_date')?.value || ''
    const end = document.getElementById('end_date')?.value || ''

    lastSearchParams = { search, start_date: start, end_date: end, page }

    spinner.style.display = 'inline-block'
    tableBody.innerHTML = `<tr><td colspan="9">Memuat...</td></tr>`

    const params = new URLSearchParams(lastSearchParams)

    try {
        const res = await fetch(`/api/ordersheet?${params}`)
        if (!res.ok) throw new Error(`HTTP ${res.status}`)

        const json = await res.json()
        spinner.style.display = 'none'

        if (json.success && json.data.length) {
            currentSearchData = json.data
            renderTable(currentSearchData, json.current_page)
            renderPagination(json.current_page, json.last_page)
        } else {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center">Tidak ditemukan</td></tr>`
        }
    } catch (err) {
        spinner.style.display = 'none'
        tableBody.innerHTML = `<tr><td colspan="9" class="text-danger text-center">Server error</td></tr>`
        console.error(err)
    }
}

function renderTable(data, currentPage) {
    const tableBody = document.querySelector('#resultTable tbody')
    let rows = ''

    data.forEach((item, i) => {
        const no = i + 1 + (currentPage - 1) * 10
        rows += `
            <tr>
                <td>${no}</td>
                <td>${item.Buyer || '-'}</td>
                <td>${item.PurchaseOrderNumber || '-'}</td>
                <td>${item.ProductName || '-'}</td>
                <td>${item.Qty || 0}</td>
                <td>${item.ActualFOB || '-'}</td>
                <td>${item.DocumentDate || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-timbang"
                        data-item='${JSON.stringify(item)}'>
                        <i class="fa-solid fa-weight-scale"></i> Timbang
                    </button>
                </td>
            </tr>`
    })

    tableBody.innerHTML = rows
}

function renderPagination(currentPage, lastPage) {
    const pagination = document.getElementById('pagination')
    if (lastPage <= 1) {
        pagination.innerHTML = ''
        return
    }

    let html = `<ul class="pagination">`

    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${
                currentPage - 1
            }">Previous</a>
        </li>`

    for (let i = 1; i <= lastPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`
    }

    html += `<li class="page-item ${
        currentPage === lastPage ? 'disabled' : ''
    }">
            <a class="page-link" href="#" data-page="${
                currentPage + 1
            }">Next</a>
        </li></ul>`

    pagination.innerHTML = html

    pagination.querySelectorAll('a[data-page]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault()
            const page = parseInt(link.dataset.page)
            if (page > 0 && page <= lastPage) fetchData(page)
        })
    })
}

let currentId = null
let pollingInterval = null
let latestPreview = null
let currentDeviceId = null

function initTimbangModal() {
    const modalElement = document.getElementById('timbangModal')
    const btnSimpan = document.getElementById('btnSimpanTimbang')

    // Klik tombol "Timbang" di tabel
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-timbang')
        if (!btn) return

        let item
        try {
            item = JSON.parse(btn.dataset.item)
        } catch {
            return
        }

        currentId = item.id
        fillModalFields(item)
        resetPreviewUI()

        const modal = new bootstrap.Modal(modalElement)

        // Stop polling saat modal ditutup
        modalElement.addEventListener('hidden.bs.modal', stopPolling, {
            once: true
        })

        // Jalankan setelah modal terbuka
        modalElement.addEventListener(
            'shown.bs.modal',
            async () => {
                try {
                    const res = await fetch('/user/order/set-id', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content
                        },
                        body: JSON.stringify({
                            id: currentId
                        })
                    })

                    const json = await res.json()
                    console.log('Set ID response:', json)

                    if (json.success) {
                        console.log(
                            `File current_id/${json.user_id}.txt telah dibuat dengan isi: ${json.current_id}`
                        )
                        console.log('Link debug:', json.file)
                    }
                } catch (err) {
                    console.warn('Gagal set ID:', err)
                }

                await loadPreview()
                hitungLossWeight()

                startPolling()
            },
            {
                once: true
            }
        )

        modal.show()
    })
}

function fillModalFields(item) {
    const fields = {
        info_buyer: 'Buyer',
        info_order_code: 'Order_code',
        info_purchaseordernumber: 'PurchaseOrderNumber',
        info_style: 'ProductName',
        info_qty_order: 'Qty',
        info_pcs: 'Pcs',
        info_ctn: 'Ctn',
        info_less_ctn: 'Less_ctn',
        info_pcs_less_ctn: 'Pcs_less_ctn',
        info_carton_weight: 'Carton_weight_std',
        info_pcs_weight: 'Pcs_weight_std',
        info_GAC: 'GAC',
        info_FinalDestination: 'FinalDestination'
    }

    Object.keys(fields).forEach((id) => {
        const el = document.getElementById(id)
        if (!el) return

        const key = fields[id]
        let value = item[key] ?? ''

        if (id === 'info_GAC' && value) {
            value = formatDateForInput(value)
        }
        el.value = value
    })

    // Rasio & lost weight
    document.getElementById('rasio_batas_beban_min').value =
        item.rasio_min ?? ''
    document.getElementById('rasio_batas_beban_max').value =
        item.rasio_max ?? ''
    document.getElementById('lost_weight').value = ''
}

function formatDateForInput(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return isNaN(date) ? '' : date.toISOString().split('T')[0]
}

function resetPreviewUI() {
    document.getElementById('currentWeight').textContent = '0.00 kg'
    const status = document.getElementById('previewStatus')
    status.textContent = 'Menunggu timbangan...'
    status.className = 'text-warning fw-bold'
    document.getElementById('lost_weight').value = ''
    document.getElementById('btnSimpanTimbang').disabled = true
    latestPreview = null
}

function initManualMode() {
    const toggle = document.getElementById('manualMode')
    const input = document.getElementById('manualWeight')

    toggle.addEventListener('change', () => {
        isManualMode = toggle.checked

        if (isManualMode) {
            stopPolling() // MATIKAN ESP
            input.disabled = false
            document.getElementById('previewStatus').textContent =
                'Mode Manual Aktif'
            document.getElementById('previewStatus').className =
                'text-info fw-bold'
        } else {
            input.disabled = true
            input.value = ''
            resetPreviewUI()
            startPolling() // BALIK KE ESP
        }
    })

    input.addEventListener('input', () => {
        if (!isManualMode) return

        const berat = parseFloat(input.value) || 0
        applyManualWeight(berat)
    })
}

function applyManualWeight(berat) {
    const weightEl = document.getElementById('currentWeight')
    const statusEl = document.getElementById('previewStatus')

    weightEl.textContent = berat.toFixed(2)

    if (berat < 0.05) {
        statusEl.textContent = 'Timbangan kosong'
        statusEl.className = 'text-muted'
        document.getElementById('btnSimpanTimbang').disabled = true
        return
    }

    if (berat < 0.5) {
        statusEl.textContent = 'Ada beban kecil...'
        statusEl.className = 'text-warning fw-bold'
        document.getElementById('btnSimpanTimbang').disabled = true
        return
    }

    statusEl.textContent = 'STABIL (Manual)'
    statusEl.className = 'text-success fw-bold'

    latestPreview = {
        berat: berat.toFixed(2)
    }

    document.getElementById('btnSimpanTimbang').disabled = false
    hitungLossWeight()
}

// Polling
function startPolling() {
    stopPolling()
    pollingInterval = setInterval(() => {
        loadPreview()
    }, 1500)
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval)
        pollingInterval = null
    }
}

const THRESHOLD = 0.002 // 2 gram
let lastBerat = 0

let lastStableWeight = null // berat terakhir yang dianggap stabil
let stableStartTime = null // waktu pertama kali berat sama
const STABLE_THRESHOLD = 0.02 // toleransi perubahan (bisa disesuaikan, misal 20 gram)
const STABLE_DURATION = 3000 // harus sama selama 3 detik baru dianggap stabil

async function loadPreview() {
    if (!currentId) return

    try {
        const res = await fetch(`/user/order/preview/${currentId}`, {
            headers: {
                Accept: 'application/json'
            }
        })

        if (!res.ok) {
            document.getElementById('previewStatus').textContent =
                'Koneksi gagal'
            document.getElementById('previewStatus').className =
                'text-danger fw-bold'
            return
        }

        const json = await res.json()
        if (!json.success) return

        const berat = parseFloat(json.berat) || 0
        const weightEl = document.getElementById('currentWeight')
        const statusEl = document.getElementById('previewStatus')

        const newText = berat.toFixed(2)

        // Animasi hanya saat angka benar-benar berubah
        if (weightEl.textContent !== newText) {
            weightEl.textContent = newText

            weightEl.style.transition = 'all 0.4s ease'
            weightEl.style.transform = 'scale(1.25)'
            weightEl.style.color = '#e91e63'
            setTimeout(() => {
                weightEl.style.transform = 'scale(1)'
                weightEl.style.color = '#0d6efd'
            }, 400)

            // Reset status stabil jika berat berubah
            lastStableWeight = null
            stableStartTime = null
        }

        // === LOGIKA DETEKSI STABIL ===
        const sekarang = Date.now()

        if (lastStableWeight === null) {
            // Pertama kali dapat nilai yang cukup besar
            if (berat >= 0.5) {
                lastStableWeight = berat
                stableStartTime = sekarang
            }
        } else {
            // Cek apakah berat masih dalam toleransi
            if (Math.abs(berat - lastStableWeight) <= STABLE_THRESHOLD) {
                // Masih sama dalam batas toleransi
                if (sekarang - stableStartTime >= STABLE_DURATION) {
                    // SUDAH STABIL LAMA!
                    statusEl.textContent = 'STABIL'
                    statusEl.className = 'text-success fw-bold fs-4 blink' // optional: blink

                    // Beep panjang hanya sekali (tidak berulang setiap polling)
                    if (!statusEl.dataset.beeped) {
                        playStableBeep() // fungsi beep panjang
                        statusEl.dataset.beeped = 'true' // tandai sudah beep
                    }
                } else {
                    // Belum cukup lama, masih "menunggu stabil"
                    statusEl.textContent = 'Menunggu stabil...'
                    statusEl.className = 'text-warning fw-bold'
                    statusEl.dataset.beeped = '' // reset beep jika berat bergerak lagi
                }
            } else {
                // Berat berubah di luar toleransi → reset
                lastStableWeight = berat
                stableStartTime = sekarang
                statusEl.dataset.beeped = '' // siap beep lagi nanti
            }
        }

        // Status default jika belum cukup berat
        if (berat < 0.05) {
            statusEl.textContent = 'Timbangan kosong'
            statusEl.className = 'text-muted'
            lastStableWeight = null
            stableStartTime = null
            statusEl.dataset.beeped = ''
        } else if (berat < 0.5) {
            statusEl.textContent = 'Ada beban kecil...'
            statusEl.className = 'text-info fw-bold'
            lastStableWeight = null
            stableStartTime = null
            statusEl.dataset.beeped = ''
        }

        // Aktifkan tombol simpan
        document.getElementById('btnSimpanTimbang').disabled = berat < 0.5

        latestPreview = {
            berat: berat.toFixed(2)
        }
        hitungLossWeight()
    } catch (err) {
        console.error('Polling error:', err)
        document.getElementById('previewStatus').textContent =
            'Terputus dari server'
        document.getElementById('previewStatus').className =
            'text-danger fw-bold'
    }
}

// ============== FUNGSI BEEP PANJANG ==============
function playStableBeep() {
    const ctx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()

    osc.type = 'sine'
    osc.frequency.value = 800 // tinggi
    gain.gain.value = 0.3

    osc.connect(gain)
    gain.connect(ctx.destination)

    osc.start()
    osc.stop(ctx.currentTime + 1.2) // 1.2 detik → terasa "panjang"
}

// BEEP SUKSES (2x beep pendek + ceria, nada naik)
function playSuccessBeep() {
    const ctx = new (window.AudioContext || window.webkitAudioContext)()

    function beep(freq, duration, delay = 0) {
        setTimeout(() => {
            const osc = ctx.createOscillator()
            const gain = ctx.createGain()

            osc.type = 'sine'
            osc.frequency.value = freq
            gain.gain.value = 0.4

            osc.connect(gain)
            gain.connect(ctx.destination)

            osc.start()
            osc.stop(ctx.currentTime + duration)
        }, delay)
    }

    // Nada ceria: Do → Mi → Sol (seperti "ding-dong" sukses)
    beep(523, 0.12, 0) // C5
    beep(659, 0.12, 120) // E5
    beep(784, 0.25, 240) // G5 (lebih panjang biar endingnya manis)
}

function hitungLossWeight() {
    const minEl = document.getElementById('rasio_batas_beban_min')
    const maxEl = document.getElementById('rasio_batas_beban_max')
    const lostEl = document.getElementById('lost_weight')
    const statusEl = document.getElementById('previewStatus')

    const current =
        parseFloat(document.getElementById('currentWeight').textContent) || 0
    const min = parseFloat(minEl?.value) || 0
    const max = parseFloat(maxEl?.value) || 0

    if (!min || !max || current === 0) {
        lostEl.value = ''
        return
    }

    const loss = (max - current).toFixed(2)
    const ratio = ((current - min) / (max - min)).toFixed(3)
    lostEl.value = `${loss} kg (${ratio})`

    if (current < min) {
        statusEl.textContent = 'Berat di bawah batas minimal!'
        statusEl.className = 'text-danger fw-bold'
    } else if (current > max) {
        statusEl.textContent = 'Berat melebihi batas maksimal!'
        statusEl.className = 'text-danger fw-bold'
    } else {
        statusEl.textContent = 'Berat dalam batas normal'
        statusEl.className = 'text-success fw-bold'
    }
}

function initLossWeightCalculation() {
    ;['rasio_batas_beban_min', 'rasio_batas_beban_max'].forEach((id) => {
        const el = document.getElementById(id)
        if (el) el.addEventListener('input', hitungLossWeight)
    })
}

function initTareButton() {
    const tareBtn = document.getElementById('tare')
    if (!tareBtn) return

    tareBtn.addEventListener('click', async () => {
        const statusEl = document.getElementById('previewStatus')
        statusEl.textContent = 'Mengirim perintah tare...'
        statusEl.className = 'text-info fw-bold'

        try {
            const res = await fetch('/user/order/tare', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content
                }
            })
            const json = await res.json()

            if (json.success) {
                statusEl.textContent = 'Tare berhasil!'
                setTimeout(
                    () => (statusEl.textContent = 'Menunggu timbangan...'),
                    2000
                )
            } else {
                throw new Error(json.message || 'Tare gagal')
            }
        } catch (err) {
            statusEl.textContent = 'Tare gagal!'
            statusEl.className = 'text-danger fw-bold'
        }
    })
}

function initBarcodeScanner() {
    const scanButton = document.getElementById('btnScanBarcode')
    if (!scanButton) return

    scanButton.addEventListener('click', startScanner)

    function startScanner() {
        const modalEl = document.getElementById('scannerModal')
        if (!modalEl) return alert('Modal scanner tidak ditemukan!')

        const modal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        })
        const statusEl = document.getElementById('scanStatus')
        const torchBtn = document.getElementById('torchToggle')
        const switchBtn = document.getElementById('switchCamera')

        let scannerInstance = null
        let currentCamera = 'environment'
        let torchOn = false
        const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(
            navigator.userAgent
        )

        modal.show()

        const onSuccess = (decodedText) => {
            const text = decodedText.trim()
            if (!text) return

            const noBoxInput = document.getElementById('no_box')
            if (noBoxInput) {
                noBoxInput.value = text
                noBoxInput.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                )
            }

            statusEl.innerHTML = `<span class="text-success fw-bold">Berhasil Scan!</span><br><small class="text-light">${text}</small>`
            setTimeout(() => {
                stopScanner()
                modal.hide()
                Swal.fire({
                    icon: 'success',
                    title: 'Scan Berhasil!',
                    text: text,
                    timer: 1500,
                    showConfirmButton: false
                })
            }, 800)
        }

        const stopScanner = () => {
            if (scannerInstance) {
                scannerInstance.stop().catch(() => {})
                scannerInstance = null
            }
            torchOn = false
        }

        modalEl.addEventListener('shown.bs.modal', () => {
            statusEl.textContent = 'Memuat kamera...'
            torchBtn.disabled = true
            torchBtn.classList.add('d-none')

            const html5QrCode = new Html5Qrcode('reader')
            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1,
                disableFlip: false,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A
                ]
            }

            html5QrCode
                .start(
                    {
                        facingMode: currentCamera
                    },
                    config,
                    onSuccess,
                    () => {}
                )
                .then(() => {
                    scannerInstance = html5QrCode
                    statusEl.innerHTML =
                        '<span class="text-info">Arahkan kamera ke barcode...</span>'

                    if (isMobile) {
                        torchBtn.classList.remove('d-none')
                        torchBtn.disabled = false
                        setupTorch()
                    }

                    Html5Qrcode.getCameras().then((cameras) => {
                        if (cameras?.length > 1)
                            switchBtn.classList.remove('d-none')
                    })

                    switchBtn.onclick = () => {
                        currentCamera =
                            currentCamera === 'environment'
                                ? 'user'
                                : 'environment'
                        stopScanner()
                        setTimeout(() => {
                            html5QrCode
                                .start(
                                    {
                                        facingMode: currentCamera
                                    },
                                    config,
                                    onSuccess,
                                    () => {}
                                )
                                .then(() => (scannerInstance = html5QrCode))
                        }, 500)
                    }
                })
                .catch((err) => {
                    statusEl.innerHTML = `<span class="text-danger">Gagal akses kamera:<br><small>${
                        err.message || err
                    }</small></span>`
                })
        })

        function setupTorch() {
            torchBtn.onclick = () => {
                if (!scannerInstance) return
                torchOn = !torchOn
                scannerInstance
                    .applyVideoConstraints({
                        advanced: [
                            {
                                torch: torchOn
                            }
                        ]
                    })
                    .then(() => {
                        torchBtn.innerHTML = torchOn
                            ? 'Matikan Lampu'
                            : 'Nyalakan Lampu'
                        torchBtn.classList.toggle('btn-danger', torchOn)
                        torchBtn.classList.toggle('btn-warning', !torchOn)
                    })
                    .catch(() => {
                        torchOn = false
                        torchBtn.innerHTML = 'Lampu Tidak Didukung'
                        torchBtn.className = 'btn btn-secondary btn-sm px-3'
                        torchBtn.disabled = true
                    })
            }
        }

        modalEl.addEventListener('hidden.bs.modal', stopScanner, {
            once: true
        })
    }
}

function initSaveButton() {
    const btnSimpan = document.getElementById('btnSimpanTimbang')
    if (!btnSimpan) return

    btnSimpan.addEventListener('click', async () => {
        if (!latestPreview || parseFloat(latestPreview.berat) < 0.05) {
            Swal.fire(
                'Peringatan',
                'Berat terlalu kecil atau belum terdeteksi!',
                'warning'
            )
            return
        }

        const form = document.getElementById('formOrdersheet')
        const formData = new FormData(form)
        formData.set('berat', latestPreview.berat)
        formData.set('id', currentId)

        btnSimpan.disabled = true
        btnSimpan.innerHTML = 'Menyimpan...'

        try {
            const res = await fetch('/user/order/simpan', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            const json = await res.json()

            if (res.ok && json.success) {
                playSuccessBeep()

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: json.message,
                    timer: 1200,
                    showConfirmButton: false
                })

                bootstrap.Modal.getInstance(
                    document.getElementById('timbangModal')
                ).hide()

                // 🔥 HAPUS ITEM DARI TABEL TANPA REFRESH
                removeRowFromSearch(currentId)

                // 🔄 UPDATE REPORT
                loadReport()
            } else {
                throw new Error(json.message || 'Gagal menyimpan')
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error')
        } finally {
            btnSimpan.disabled = false
            btnSimpan.innerHTML = 'Simpan'
        }
    })
}

async function reloadLastSearch() {
    if (
        !lastSearchParams.search &&
        !lastSearchParams.start_date &&
        !lastSearchParams.end_date
    ) {
        return
    }

    const params = new URLSearchParams(lastSearchParams)
    const tableBody = document.querySelector('#resultTable tbody')
    const spinner = document.getElementById('loadingSpinner')

    spinner.style.display = 'inline-block'

    try {
        const res = await fetch(`/api/ordersheet?${params}`)
        const json = await res.json()

        spinner.style.display = 'none'

        if (json.success && json.data.length > 0) {
            renderTable(json.data, json.current_page)
            renderPagination(json.current_page, json.last_page)
        }
    } catch (e) {
        spinner.style.display = 'none'
        console.error(e)
    }
}

// Ambil user id dari Blade
// const userId = {{ Auth::check() ? Auth::id() : 'null' }};
const userId = window.APP?.userId ?? null
const isAuth = window.APP?.isAuth ?? false

async function loadAvailableDevices() {
    try {
        const res = await fetch('/user/devices/available')
        if (!res.ok) throw new Error(`HTTP ${res.status}`)

        const devices = await res.json()
        const list = document.getElementById('deviceList')
        list.innerHTML = ''

        // Ambil device user login (in_use)
        const currentUserDevice = devices.find(
            (d) => d.status === 'in_use' && d.user_id === parseInt(userId)
        )
        if (currentUserDevice) {
            currentDeviceId = currentUserDevice.id
            document.getElementById('currentDeviceName').textContent =
                currentUserDevice.name || currentUserDevice.esp_id
        } else {
            currentDeviceId = null
            document.getElementById('currentDeviceName').textContent =
                'Pilih Device...'
        }

        devices.forEach((device) => {
            const isCurrent = device.id === currentDeviceId
            const statusBadge =
                device.status === 'in_use'
                    ? 'Sedang Dipakai'
                    : device.status === 'online'
                      ? 'Online'
                      : 'Offline'
            const bgClass =
                device.status === 'in_use'
                    ? 'bg-success text-white'
                    : device.status === 'online'
                      ? 'bg-light text-dark'
                      : 'bg-danger text-white'

            const item = document.createElement('li')
            item.innerHTML = `
                <a class="dropdown-item d-flex justify-content-between align-items-center ${
                    isCurrent ? 'active' : ''
                }" 
                href="javascript:void(0)" 
                onclick="prepareSwitch(${device.id}, '${(
                    device.name || device.esp_id
                ).replace(/'/g, "\\'")}', '${device.esp_id}')"
                style="background-color: ${
                    bgClass.includes('bg-light') ? '#f8f9fa' : ''
                }; color: ${bgClass.includes('text-white') ? '#fff' : '#000'};">
                    <div>
                        <div><strong>${
                            device.name || device.esp_id
                        }</strong></div>
                        <small class="text-muted">ID: ${device.esp_id}</small>
                    </div>
                    <span class="badge ${bgClass} ms-2">${statusBadge}</span>
                </a>
            `
            list.appendChild(item)
        })
    } catch (err) {
        console.error('Gagal load device:', err)
        document.getElementById('deviceList').innerHTML =
            '<li><a class="dropdown-item text-danger text-center" href="#">Error loading devices</a></li>'
    }
}

function prepareSwitch(id, name, esp_id) {
    if (id == currentDeviceId) {
        alert('Kamu sudah menggunakan device ini.')
        return
    }

    document.getElementById('targetDeviceName').textContent = name || esp_id
    document.getElementById('targetDeviceId').textContent = esp_id

    const modal = new bootstrap.Modal(
        document.getElementById('confirmSwitchModal')
    )
    modal.show()

    document.getElementById('confirmSwitchBtn').onclick = () => switchDevice(id)
}

async function switchDevice(deviceId) {
    try {
        const res = await fetch('/user/devices/switch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]'
                ).content
            },
            body: JSON.stringify({
                device_id: deviceId
            })
        })

        if (!res.ok) {
            const text = await res.text()
            console.error('Response:', text)
            throw new Error('Server error')
        }

        const data = await res.json()

        if (data.success) {
            Swal.fire('Sukses!', 'Berhasil pindah device!', 'success').then(
                () => {
                    // Redirect sesuai tipe device
                    const type = data.device_type
                    if (type === 'O') {
                        window.location.href = '/user/ordersheet-view'
                    } else if (type === 'P') {
                        window.location.href = '/user/package-view'
                    } else {
                        location.reload()
                    }
                }
            )
        } else {
            Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error')
        }
    } catch (err) {
        console.error(err)
        Swal.fire('Error', 'Tidak dapat terhubung ke server', 'error')
    }
}

// Load saat halaman dibuka & refresh tiap 10 detik
loadAvailableDevices()
setInterval(loadAvailableDevices, 10000)

function renderReportHTML(data) {
    if (!Array.isArray(data) || data.length === 0) {
        return `
            <div class="text-center text-muted py-3">
                Belum ada data timbang
            </div>
        `
    }

    return data
        .map(
            (item, i) => `
            <div class="card mb-2 shadow-sm">
                <div class="card-body p-2">
                    <strong>${i + 1}. ${item.ProductName ?? '-'}</strong><br>
                    PO: ${item.PurchaseOrderNumber ?? '-'}<br>
                    Berat: <strong>${item.berat ?? '-'} kg</strong>
                </div>
            </div>
        `
        )
        .join('')
}

async function loadReport() {
    try {
        const res = await fetch('/user/order/report')
        if (!res.ok) throw new Error(`HTTP ${res.status}`)

        const json = await res.json()
        const container = document.getElementById('reportContainer')

        if (!json.data || Object.keys(json.data).length === 0) {
            container.innerHTML = `
                <div class="alert alert-info text-center">
                    Belum Ada Data
                </div>`
            return
        }

        container.innerHTML = ''

        // 🔥 JIKA DATA OBJECT (grouped)
        if (!Array.isArray(json.data)) {
            Object.entries(json.data).forEach(([group, items]) => {
                container.innerHTML += `
                    <h6 class="mt-3">${group}</h6>
                    ${renderReportHTML(items)}
                `
            })
            return
        }

        // 🔁 JIKA DATA ARRAY BIASA
        container.innerHTML = renderReportHTML(json.data)
    } catch (err) {
        console.error('Gagal load report:', err)
    }
}

setInterval(loadReport, 5000) // 5 detik
loadReport() // pertama kali

function removeRowFromSearch(id) {
    const tableBody = document.querySelector('#resultTable tbody')
    const rows = tableBody.querySelectorAll('tr')
    rows.forEach((row) => {
        const btn = row.querySelector('.btn-timbang')
        if (btn) {
            try {
                const item = JSON.parse(btn.dataset.item)
                if (item.id === id) row.remove()
            } catch {}
        }
    })
}

function initSearch() {
    const searchBtn = document.getElementById('searchBtn')
    const spinner = document.getElementById('loadingSpinner')
    const tableBody = document.querySelector('#resultTable tbody')
    const pagination = document.getElementById('pagination')

    searchBtn.addEventListener('click', () => fetchData(1))

    async function fetchData(page = 1) {
        const search = document.getElementById('search').value.trim()
        const start = document.getElementById('start_date').value
        const end = document.getElementById('end_date').value

        lastSearchParams = { search, start_date: start, end_date: end, page }

        spinner.style.display = 'inline-block'
        tableBody.innerHTML = `<tr><td colspan="9">Memuat...</td></tr>`

        const params = new URLSearchParams(lastSearchParams)

        try {
            const res = await fetch(`/api/ordersheet?${params}`)
            const json = await res.json()

            spinner.style.display = 'none'

            if (json.success) {
                currentSearchData = json.data // 🔑 simpan data
                renderTable(currentSearchData, json.current_page)
                renderPagination(json.current_page, json.last_page)
            } else {
                tableBody.innerHTML = `<tr><td colspan="9">Tidak ditemukan</td></tr>`
            }
        } catch (e) {
            spinner.style.display = 'none'
        }
    }
    // async function fetchData(page = 1) {
    //     const search = document.getElementById('search')?.value.trim() || ''
    //     const start = document.getElementById('start_date')?.value || ''
    //     const end = document.getElementById('end_date')?.value || ''

    //     lastSearchParams = {
    //         search,
    //         start_date: start,
    //         end_date: end,
    //         page
    //     }

    //     // if (!search && !start && !end) {
    //     //     Swal.fire('Peringatan', 'Isi setidaknya satu kolom!', 'warning');
    //     //     return;
    //     // }

    //     spinner.style.display = 'inline-block'
    //     tableBody.innerHTML = `<tr><td colspan="9" class="text-center">Memuat...</td></tr>`
    //     pagination.innerHTML = ''

    //     try {
    //         const params = new URLSearchParams({
    //             page
    //         })
    //         if (search) params.append('search', search)
    //         if (start) params.append('start_date', start)
    //         if (end) params.append('end_date', end)

    //         const res = await fetch(`/api/ordersheet?${params}`)
    //         const json = await res.json()

    //         spinner.style.display = 'none'

    //         if (json.success && json.data.length > 0) {
    //             renderTable(json.data, json.current_page)
    //             renderPagination(json.current_page, json.last_page)
    //         } else {
    //             tableBody.innerHTML = `<tr><td colspan="9" class="text-warning text-center">Tidak ditemukan</td></tr>`
    //         }
    //     } catch (err) {
    //         spinner.style.display = 'none'
    //         tableBody.innerHTML = `<tr><td colspan="9" class="text-danger text-center">Terjadi kesalahan</td></tr>`
    //         console.error(err)
    //     }
    // }
}

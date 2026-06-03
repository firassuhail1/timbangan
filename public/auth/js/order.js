// Simpan order yang sedang aktif di variabel global/window
window.currentSelectedItem = null;
const FG_API_BASE = 'http://192.168.0.39/TimbanganApi/Qrcodefg.php'

document.addEventListener('DOMContentLoaded', () => {
    initDateTime()
    initSearch()
    initTimbangModal()
    // initBarcodeScanner()
    initHardwareScanner()
    initSearchScanner()
    initManualMode()
    initTareButton()
    initLossWeightCalculation()
    initSaveButton()
})

function getNextItem() {
    return allFilteredData.find((item) => !getWeighedIds().includes(item.id))
}

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

const SEARCH_STATE_KEY = 'ordersheet_search_state'
const WEIGHED_IDS_KEY = 'ordersheet_weighed_ids'
const PAGE_SIZE = 10
let allFilteredData = []

function saveSearchState(state) {
    sessionStorage.setItem(SEARCH_STATE_KEY, JSON.stringify(state))
}

function getSearchState() {
    return JSON.parse(sessionStorage.getItem(SEARCH_STATE_KEY) || 'null')
}

function addWeighedId(id) {
    const ids = JSON.parse(sessionStorage.getItem(WEIGHED_IDS_KEY) || '[]')
    if (!ids.includes(id)) {
        ids.push(id)
        sessionStorage.setItem(WEIGHED_IDS_KEY, JSON.stringify(ids))
    }
}

function getWeighedIds() {
    return JSON.parse(sessionStorage.getItem(WEIGHED_IDS_KEY) || '[]')
}

// Tambahkan fungsi helper ini di awal order.js
function escapeForAttr(obj) {
    return JSON.stringify(obj)
        .replace(/&/g, '&amp;')
        .replace(/'/g, '&#39;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
}

let renderPage = null

function initSearch() {
    const searchBtn = document.getElementById('searchBtn')
    const spinner = document.getElementById('loadingSpinner')
    const tableBody = document.querySelector('#resultTable tbody')
    const pagination = document.getElementById('pagination')
    const resetBtn = document.getElementById('resetSearchBtn')

    searchBtn.addEventListener('click', () => fetchData(1))

    // Tambah variable global untuk simpan info pagination API
    let apiLastPage = 1
    let apiCurrentPage = 1

    async function fetchData(page = 1) {
        const search = document.getElementById('search')?.value.trim() || ''
        let start    = document.getElementById('start_date')?.value || ''
        let end      = document.getElementById('end_date')?.value || ''

        // PERBAIKAN LOGIKA:
        // Jika Halaman Baru Dimuat (kondisi: tanggal kosong DAN kolom search juga kosong)
        if (!start && !end && !search) {
            const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
            start = today;
            end = today;
            
            // Isi input date di HTML sebagai penanda default awal
            if(document.getElementById('start_date')) document.getElementById('start_date').value = today;
            if(document.getElementById('end_date')) document.getElementById('end_date').value = today;
        }

        saveSearchState({ search, start, end, page })

        spinner.style.display = 'inline-block'
        tableBody.innerHTML = `<tr><td colspan="11" class="text-center">Memuat...</td></tr>`

        try {
            const params = new URLSearchParams({ per_page: PAGE_SIZE, page })
            if (search) params.append('search', search)
            if (start)  params.append('start_date', start)
            if (end)    params.append('end_date', end)

            const res  = await fetch(`/api/ordersheet?${params}`)
            const json = await res.json()

            spinner.style.display = 'none'

            if (!json.success) {
                tableBody.innerHTML = `<tr><td colspan="11" class="text-danger text-center">Gagal memuat data dari server, coba gunakan rentang waktu lebih singkat</td></tr>`
                return
            }

            apiLastPage    = json.last_page    || 1
            apiCurrentPage = json.current_page || 1
            allFilteredData = json.data

            renderTable(allFilteredData, apiCurrentPage, allFilteredData.length === 0)
            renderPagination(apiCurrentPage, apiLastPage)

        } catch (err) {
            spinner.style.display = 'none'
            tableBody.innerHTML = `<tr><td colspan="11" class="text-danger text-center">Error: ${err.message}</td></tr>`
        }
    }

    function renderTable(data, currentPage, notFound = false) {
        // ✅ Tidak perlu filter lagi — data sudah bersih dari renderPage
        if (data.length === 0) {
            if (notFound) {
                tableBody.innerHTML = `<tr><td colspan="10" class="text-muted text-center py-4">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Tidak ada data yang cocok dengan pencarian.
                </td></tr>`
            } else {
                tableBody.innerHTML = `<tr><td colspan="10" class="text-success text-center py-4">
                    <i class="fa-solid fa-circle-check me-1"></i>
                    Semua ordersheet pada rentang ini sudah ditimbang ✔
                </td></tr>`
            }
            pagination.innerHTML = ''
            return
        }

        let rows = ''
        data.forEach((item, i) => {
            const no = i + 1 + (currentPage - 1) * PAGE_SIZE  // ← pakai PAGE_SIZE bukan hardcode 10
            rows += `
            <tr>
                <td>${no}</td>
                <td>${item.KJ || '-'}</td>
                <td>${item.ProductCode || '-'}</td>
                <td>${item.ColorDescription || '-'}</td>
                <td>${item.ProductName || '-'}</td>
                <td>${item.Qty || 0}</td>
                <td>${item.PurchaseOrderNumber || '-'}</td>
                <td>${item.Buyer || '-'}</td>
                <td>${item.FinalDestination || item.Destination || '-'}</td>
                <td>${item.DocumentDate || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary btn-timbang"
                        data-item='${escapeForAttr(item)}'>
                        <i class="fa-solid fa-weight-scale"></i> Timbang
                    </button>
                    ${item.max_checking > 0
                    ? `<span class="badge bg-warning text-dark ms-1">Checking #${item.max_checking}</span>`
                    : ''}
                </td>
            </tr>`
        })

        tableBody.innerHTML = rows
    }

    function renderPagination(currentPage, lastPage) {
        if (lastPage <= 1) {
            pagination.innerHTML = ''
            return
        }

        // Hitung range halaman yang ditampilkan
        function getPageRange(current, last) {
            const delta = 2 // jumlah halaman di kiri/kanan halaman aktif
            const range = []
            const rangeWithDots = []

            for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                range.push(i)
            }

            // Selalu tampilkan halaman 1
            rangeWithDots.push(1)

            // Tambah "..." sebelum range jika ada gap
            if (range[0] > 2) {
                rangeWithDots.push('...')
            }

            rangeWithDots.push(...range)

            // Tambah "..." setelah range jika ada gap
            if (range[range.length - 1] < last - 1) {
                rangeWithDots.push('...')
            }

            // Selalu tampilkan halaman terakhir
            if (last > 1) {
                rangeWithDots.push(last)
            }

            return rangeWithDots
        }

        const pages = getPageRange(currentPage, lastPage)

        let html = `<ul class="pagination pagination-sm flex-wrap justify-content-center mb-0">`

        // Tombol Previous
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>`

        // Nomor halaman
        pages.forEach(p => {
            if (p === '...') {
                html += `<li class="page-item disabled"><span class="page-link">…</span></li>`
            } else {
                html += `
                    <li class="page-item ${p === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p}</a>
                    </li>`
            }
        })

        // Tombol Next
        html += `
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>`

        html += `</ul>`

        // Tambah info "Halaman X dari Y"
        html += `
            <div class="text-muted text-center mt-1" style="font-size: 0.8rem;">
                Halaman ${currentPage} dari ${lastPage}
            </div>`

        pagination.innerHTML = html

        // Event listener
        pagination.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault()
                const page = parseInt(link.dataset.page)
                if (page > 0 && page <= apiLastPage) {
                    const state = getSearchState()
                    saveSearchState({ ...state, page })
                    fetchData(page) // ← langsung fetch ke API
                    document.getElementById('resultTable').scrollIntoView({ behavior: 'smooth', block: 'start' })
                }
            })
        })
    }

    const lastState = getSearchState()
    if (lastState) {
        document.getElementById('search').value = lastState.search || ''
        document.getElementById('start_date').value = lastState.start || ''
        document.getElementById('end_date').value = lastState.end || ''
        fetchData(lastState.page || 1)
    }  else {
        // Auto-load saat pertama buka tanpa state
        fetchData(1)  // ← tambah ini
    }

    renderPage = function (page) {
        const weighedIds = getWeighedIds()
        const visibleData = allFilteredData.filter(
            (item) => !weighedIds.includes(item.id)
        )

        const totalData = visibleData.length
        const totalPage = Math.ceil(totalData / PAGE_SIZE) || 1
        if (page > totalPage) page = totalPage
        const start = (page - 1) * PAGE_SIZE
        const pageData = visibleData.slice(start, start + PAGE_SIZE)

        // ✅ Bedakan: tidak ditemukan vs sudah semua ditimbang
        const notFound = allFilteredData.length === 0

        renderTable(pageData, page, notFound)
        renderPagination(page, totalPage)
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            // kosongkan input
            document.getElementById('search').value = ''
            document.getElementById('start_date').value = ''
            document.getElementById('end_date').value = ''

            // hapus state pencarian
            sessionStorage.removeItem(SEARCH_STATE_KEY)

            // kosongkan data
            allFilteredData = []

            // reset tabel
            tableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-muted text-center py-4">
                        Silakan cari data untuk memulai timbangan.
                    </td>
                </tr>
            `

            // hapus pagination
            pagination.innerHTML = ''
        })
    }
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

let currentId = null
let currentItemCtn = 0
let pollingInterval = null
let latestPreview = null
let currentDeviceId = null
let isManualMode = false
let hasPlayedStableBeepForThisItem = false  // ← tambah ini

function openModalForItem(item) {
    const modalElement = document.getElementById('timbangModal')

    // SIMPAN DISINI agar bisa diakses oleh listener dropdown (triggerCheck)
    window.currentSelectedItem = item
    currentId      = item.id
    currentItemCtn = parseInt(item.Ctn) || 0

    fillModalFields(item)
    resetPreviewUI()

    document.getElementById('info_checking_ke').value = 1

    const modal = new bootstrap.Modal(modalElement)

    modalElement.addEventListener('shown.bs.modal', async () => {
        try {
            await fetch('/user/order/set-id', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ id: currentId })
            })
        } catch {}

        await loadPreview()
        await loadKeteranganForOrdersheet()
        hitungLossWeight()

        const espId = window.APP?.espId
        if (espId) startListening(espId)

        await checkAndPromptChecking(item)

    }, { once: true })

    modalElement.addEventListener('hidden.bs.modal', () => {
        stopPolling()
        stopListening()
        document.getElementById('manualWeight').value = ''

        const toggle = document.getElementById('manualMode')
        const input  = document.getElementById('manualWeight')
        if (toggle && toggle.checked) {
            toggle.checked = false
            input.disabled = true
            isManualMode   = false
            resetPreviewUI()
        }
    }, { once: true })

    modal.show()
}

async function checkAndPromptChecking(item) {
    const tipeEl = document.getElementById('tipe_asal');
    const lineEl = document.getElementById('info_line');
    const subconEl = document.getElementById('info_subcon');

    console.log("--- Mencoba Fetch ---");
    console.log("Tipe:", tipeEl.value);

    const params = new URLSearchParams({ order_code: item.id });

    if (tipeEl.value === 'sewing') {
        if (!lineEl.value) {
            console.log("Fetch batal: Line belum diisi");
            return;
        }
        params.append('line', lineEl.value);
    } else if (tipeEl.value === 'subcon') {
        if (!subconEl.value) {
            console.log("Fetch batal: Subcon belum diisi");
            return;
        }
        params.append('subcon', subconEl.value);
    } else {
        console.log("Fetch batal: Tipe asal belum dipilih");
        return;
    }

    // SEKARANG LOG INI PASTI MUNCUL JIKA DETAIL SUDAH DIPILIH
    console.log("Fetching params:", params.toString());
    
    try {
        const res = await fetch('/user/order/checking-info?' + params);
        const json = await res.json();
        
        console.log("JSON Response:", json); // CEK INI DI CONSOLE

        if (!json.success) return;

        // Jika max_checking 0, set input ke 1 tapi jangan tampilkan modal pilihan
        if (parseInt(json.max_checking) === 0) {
            console.log("Belum ada history. Checking ke-1 otomatis.");
            document.getElementById('info_checking_ke').value = 1;
            return;
        }

        // Bangun opsi (Hanya jalan jika max_checking > 0)
        let checkingOptions = '';
        for (let i = 1; i <= json.max_checking; i++) {
            checkingOptions += `<option value="${i}">Lanjut Checking #${i}</option>`;
        }
        checkingOptions += `<option value="${json.next_checking}" selected>Checking #${json.next_checking} (Baru)</option>`;

        // Tampilkan Swal
        const result = await Swal.fire({
            icon: 'question',
            title: 'Order sudah pernah dicek',
            target: document.getElementById('timbangModal'), // AGAR TIDAK TERSEMBUNYI DI BELAKANG MODAL
            html: `
                <div style="text-align:left;font-size:14px;line-height:2;">
                    <b>Checking terakhir:</b> #${json.max_checking}<br>
                    <b>Total carton:</b> ${json.total_cartons} carton<br><br>
                    <label style="font-weight:600;">Pilih sesi timbang:</label><br>
                    <select id="swal-checking-select" class="swal2-input" style="margin-top:8px;width:100%;">
                        ${checkingOptions}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Pilih',
            confirmButtonColor: '#435ebe',
            preConfirm: () => document.getElementById('swal-checking-select').value
        });

        if (result.isConfirmed) {
            document.getElementById('info_checking_ke').value = result.value;
            await loadKeteranganForOrdersheet()
        }

    } catch (err) {
        console.error("Fetch error:", err);
    }
}

function initTimbangModal() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-timbang')
        if (!btn) return

        let item
        try {
            item = JSON.parse(btn.dataset.item)
        } catch {
            return
        }

        openModalForItem(item)
    })
}

function fillModalFields(item) {
    const fields = {
        info_buyer: 'Buyer',
        info_order_code: 'id',
        info_kj: 'KJ',
        info_purchaseordernumber: 'PurchaseOrderNumber',
        info_style: 'ProductName',
        info_color_description: 'ColorDescription',
        info_qty_order: 'Qty',
        info_GAC: 'GAC',
        info_FinalDestination: 'FinalDestination',
    }

    // Field yang SELALU di-overwrite dari API (identitas order)
    Object.keys(fields).forEach((id) => {
        const el = document.getElementById(id)
        if (!el) return
        let value = item[fields[id]] ?? ''
        if (id === 'info_GAC' && value) {
            value = formatDateForInput(value)
        }
        el.value = value
    })

    // Style gabungan ProductCode + ProductName
    const productCode = item.ProductCode ?? ''
    const productName = item.ProductName ?? ''
    const styleEl = document.getElementById('info_style')
    if (styleEl) {
        styleEl.value = productCode && productName
            ? `${productCode} - ${productName}`
            : productCode || productName || ''
    }

    // ✅ Field manual — HANYA isi jika masih kosong (tidak timpa input user)
    // ✅ Field manual — HANYA isi jika masih kosong (tidak timpa input user)
    const manualFields = {
        info_pcs:          '',
        info_carton_weight:'',
        info_pcs_weight:   '',
        info_ctn:          '1',
    }

    Object.keys(manualFields).forEach((id) => {
        const el = document.getElementById(id)
        if (!el) return
        if (!el.value || el.value.trim() === '') {
            el.value = manualFields[id]
        }
    })

    // ✅ Pertahankan tipe asal, line, dan subcon jika sudah diisi
    const tipeEl    = document.getElementById('tipe_asal')
    const hiddenEl  = document.getElementById('hidden_tipe_asal')
    const lineEl    = document.getElementById('info_line')
    const subconEl  = document.getElementById('info_subcon')

    if (!tipeEl.value || tipeEl.value === '') {
        // Belum dipilih → biarkan kosong, jangan timpa
    } else {
        // Sudah ada pilihan → pertahankan, jalankan toggle agar row tampil benar
        toggleAsalInput(tipeEl.value)
    }

    // Rasio tetap dari item API jika ada, kalau tidak jangan timpa
    const minEl = document.getElementById('rasio_batas_beban_min')
    const maxEl = document.getElementById('rasio_batas_beban_max')
    if (minEl && (!minEl.value || minEl.value.trim() === '')) {
        minEl.value = item.rasio_min ?? ''
    }
    if (maxEl && (!maxEl.value || maxEl.value.trim() === '')) {
        maxEl.value = item.rasio_max ?? ''
    }

    document.getElementById('lost_weight').value = ''

     // ✅ TAMBAHKAN DI SINI — paling bawah sebelum tutup fungsi
    const buyer = (item.Buyer || '').toUpperCase()
    const isNike = buyer.includes('NIKE')

    const requiredMark = document.getElementById('no_box_required_mark')
    const optionalMark = document.getElementById('no_box_optional_mark')
    const noBoxInput   = document.getElementById('no_box')

    
    if (requiredMark) requiredMark.style.display = isNike ? 'none' : ''
    if (optionalMark) optionalMark.style.display = isNike ? '' : 'none'
    if (noBoxInput)   noBoxInput.placeholder = isNike ? 'Opsional untuk Nike' : 'A001'

    const ketEl = document.getElementById('info_keterangan')
    if (ketEl) ketEl.value = ''
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
    hasPlayedStableBeepForThisItem = false  // ← tambah ini
}

// Polling
// function startPolling() {
//     stopPolling()
//     pollingInterval = setInterval(() => {
//         loadPreview()
//     }, 1500)
// }

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval)
        pollingInterval = null
    }
}

function stopListening() {
    if (window._currentEspId) {
        // Gunakan .leave() untuk memastikan semua listener di channel itu dibersihkan
        window.Echo.leave(`timbangan.${window._currentEspId}`);
        
        console.log(`[WS] Berhenti mendengarkan: timbangan.${window._currentEspId}`);
        window._currentEspId = null;
    }
}

function startListening(espId) {
    // if (!espId) return;

    stopListening(); // Bersihkan yang lama

    console.log(`[WS] Mulai mendengarkan: timbangan.${espId}`);
    window._currentEspId = espId;

    window.Echo.channel(`timbangan.${espId}`)
        .listen('.client-berat.updated', (data) => {
            console.log("[WS] Data Berat Masuk:", data); // Tambahkan ini buat debug
            
            const berat = parseFloat(data.berat) || 0;

            // Tulis cache via server
                fetch('/user/weight-cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        berat:    berat,
                        order_id: currentId  // variabel yang sudah ada
                    })
                });

            if (typeof updateBeratUI === 'function') {
                updateBeratUI(data.berat);

                
            } else {
                // Jika fungsi tidak ketemu, coba update manual ke elemen id
                const el = document.getElementById('displayBerat');
                if(el) el.innerText = data.berat;
            }
        });
}

function updateBeratUI(berat) {
    // Ambil elemen DOM
    const display = document.getElementById('currentWeight');
    const hiddenInput = document.getElementById('hidden_weight'); // sesuaikan dengan ID input hidden Anda
    const statusText = document.getElementById('previewStatus');
    const btnSimpan = document.getElementById('btnSimpanTimbang');

    // Validasi elemen ada
    if (!display || !statusText || !btnSimpan) {
        console.error('Element tidak ditemukan:', { display, statusText, btnSimpan });
        return;
    }

    // Konversi dari gram ke kilogram jika perlu
    // Asumsi: jika berat > 100, kemungkinan dalam gram, perlu dibagi 1000
    let beratKg = berat;
    if (berat > 100) {
        beratKg = berat / 1000; // konversi gram ke kg
    }

    // Update display dalam KG
    display.textContent = beratKg.toFixed(2) + ' kg';
    
    // Update hidden input jika ada (simpan dalam kg)
    if (hiddenInput) {
        hiddenInput.value = beratKg.toFixed(2);
    }

    // Hitung loss weight
    hitungLossWeight();

    // Cek kondisi berat (dalam kg)
    if (beratKg <= 0.5) {
        statusText.textContent = 'Timbangan Kosong';
        statusText.className = 'badge bg-warning text-dark fw-bold px-3 py-2';
        btnSimpan.disabled = true;
        hasPlayedStableBeepForThisItem = false;
        lastStableWeight = null;
        return;
    }

    // Data masuk = langsung stabil
    statusText.textContent = 'Stabil';
    statusText.className = 'badge bg-success fw-bold px-3 py-2';
    btnSimpan.disabled = false;

    // Play beep hanya sekali per item
    if (!hasPlayedStableBeepForThisItem) {
        playStableBeep();
        hasPlayedStableBeepForThisItem = true;
    }

    // Update latest preview untuk tombol simpan (dalam kg)
    latestPreview = {
        berat: beratKg.toFixed(2)
    };

    lastStableWeight = beratKg;
}

// Format berat dalam kg
function formatBerat(berat) {
    // Konversi ke kg jika dalam gram
    let beratKg = berat > 100 ? berat / 1000 : berat;
    return beratKg.toFixed(2) + ' kg';
}

const THRESHOLD = 0.002
let lastBerat = 0

let lastStableWeight = null
let stableStartTime = null
const STABLE_THRESHOLD = 0.02
const STABLE_DURATION = 3000

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

async function loadKeteranganForOrdersheet() {
    const tipeEl     = document.getElementById('tipe_asal')
    const lineEl     = document.getElementById('info_line')
    const subconEl   = document.getElementById('info_subcon')
    const ketEl      = document.getElementById('info_keterangan')
    const checkingEl = document.getElementById('info_checking_ke')
    if (!ketEl || !currentId) return

    const params = new URLSearchParams({
        order_code:  currentId,
        checking_ke: checkingEl?.value || 1,
    })
    if (tipeEl.value === 'sewing' && lineEl.value)   params.append('line',   lineEl.value)
    if (tipeEl.value === 'subcon' && subconEl.value) params.append('subcon', subconEl.value)

    try {
        const res  = await fetch('/user/order/get-keterangan?' + params)
        const json = await res.json()
        if (json.success) {
            ketEl.value = json.keterangan || ''
            // Simpan ordersheet_id di dataset untuk keperluan inline edit laporan
            ketEl.dataset.ordersheetId = json.ordersheet_id || ''
        }
    } catch {}
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

// function initBarcodeScanner() {
//     const scanButton = document.getElementById('btnScanBarcode')
//     if (!scanButton) return

//     scanButton.addEventListener('click', startScanner)

//     function startScanner() {
//         const modalEl = document.getElementById('scannerModal')
//         if (!modalEl) return alert('Modal scanner tidak ditemukan!')

//         const modal = new bootstrap.Modal(modalEl, {
//             backdrop: 'static',
//             keyboard: false
//         })
//         const statusEl = document.getElementById('scanStatus')
//         const torchBtn = document.getElementById('torchToggle')
//         const switchBtn = document.getElementById('switchCamera')

//         let scannerInstance = null
//         let currentCamera = 'environment'
//         let torchOn = false
//         const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(
//             navigator.userAgent
//         )

//         modal.show()

//         const onSuccess = (decodedText) => {
//             const text = decodedText.trim()
//             if (!text) return

//             const noBoxInput = document.getElementById('no_box')
//             if (noBoxInput) {
//                 noBoxInput.value = text
//                 noBoxInput.dispatchEvent(
//                     new Event('input', {
//                         bubbles: true
//                     })
//                 )
//             }

//             statusEl.innerHTML = `<span class="text-success fw-bold">Berhasil Scan!</span><br><small class="text-light">${text}</small>`
//             setTimeout(() => {
//                 stopScanner()
//                 modal.hide()
//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Scan Berhasil!',
//                     text: text,
//                     timer: 1500,
//                     showConfirmButton: false
//                 })
//             }, 800)
//         }

//         const stopScanner = () => {
//             if (scannerInstance) {
//                 scannerInstance.stop().catch(() => {})
//                 scannerInstance = null
//             }
//             torchOn = false
//         }

//         modalEl.addEventListener('shown.bs.modal', () => {
//             statusEl.textContent = 'Memuat kamera...'
//             torchBtn.disabled = true
//             torchBtn.classList.add('d-none')

//             const html5QrCode = new Html5Qrcode('reader')
//             const config = {
//                 fps: 10,
//                 qrbox: {
//                     width: 250,
//                     height: 250
//                 },
//                 aspectRatio: 1,
//                 disableFlip: false,
//                 formatsToSupport: [
//                     Html5QrcodeSupportedFormats.CODE_128,
//                     Html5QrcodeSupportedFormats.CODE_39,
//                     Html5QrcodeSupportedFormats.EAN_13,
//                     Html5QrcodeSupportedFormats.EAN_8,
//                     Html5QrcodeSupportedFormats.UPC_A
//                 ]
//             }

//             html5QrCode
//                 .start(
//                     {
//                         facingMode: currentCamera
//                     },
//                     config,
//                     onSuccess,
//                     () => {}
//                 )
//                 .then(() => {
//                     scannerInstance = html5QrCode
//                     statusEl.innerHTML =
//                         '<span class="text-info">Arahkan kamera ke barcode...</span>'

//                     if (isMobile) {
//                         torchBtn.classList.remove('d-none')
//                         torchBtn.disabled = false
//                         setupTorch()
//                     }

//                     Html5Qrcode.getCameras().then((cameras) => {
//                         if (cameras?.length > 1)
//                             switchBtn.classList.remove('d-none')
//                     })

//                     switchBtn.onclick = () => {
//                         currentCamera =
//                             currentCamera === 'environment'
//                                 ? 'user'
//                                 : 'environment'
//                         stopScanner()
//                         setTimeout(() => {
//                             html5QrCode
//                                 .start(
//                                     {
//                                         facingMode: currentCamera
//                                     },
//                                     config,
//                                     onSuccess,
//                                     () => {}
//                                 )
//                                 .then(() => (scannerInstance = html5QrCode))
//                         }, 500)
//                     }
//                 })
//                 .catch((err) => {
//                     statusEl.innerHTML = `<span class="text-danger">Gagal akses kamera:<br><small>${
//                         err.message || err
//                     }</small></span>`
//                 })
//         })

//         function setupTorch() {
//             torchBtn.onclick = () => {
//                 if (!scannerInstance) return
//                 torchOn = !torchOn
//                 scannerInstance
//                     .applyVideoConstraints({
//                         advanced: [
//                             {
//                                 torch: torchOn
//                             }
//                         ]
//                     })
//                     .then(() => {
//                         torchBtn.innerHTML = torchOn
//                             ? 'Matikan Lampu'
//                             : 'Nyalakan Lampu'
//                         torchBtn.classList.toggle('btn-danger', torchOn)
//                         torchBtn.classList.toggle('btn-warning', !torchOn)
//                     })
//                     .catch(() => {
//                         torchOn = false
//                         torchBtn.innerHTML = 'Lampu Tidak Didukung'
//                         torchBtn.className = 'btn btn-secondary btn-sm px-3'
//                         torchBtn.disabled = true
//                     })
//             }
//         }

//         modalEl.addEventListener('hidden.bs.modal', stopScanner, {
//             once: true
//         })
//     }
// }

function initHardwareScanner() {
    // Buffer menampung karakter yang dikirim scanner sebelum Enter
    let scanBuffer   = ''
    let scanTimer    = null
    const SCAN_TIMEOUT = 80  // ms — scanner kirim semua char < 80ms, manusia lebih lambat

    // ── Indikator di UI ──────────────────────────────────────
    const indicatorEl = document.getElementById('scannerIndicator')
    const scanTextEl  = document.getElementById('scannerText')

    function setIndicator(state, msg) {
        // state: 'idle' | 'scanning' | 'loading' | 'success' | 'error'
        if (!indicatorEl) return
        indicatorEl.className = `scanner-indicator scanner-${state}`
        if (scanTextEl) scanTextEl.textContent = msg || ''
    }

    setIndicator('idle', 'Siap scan')

    // ── Listener keyboard global ──────────────────────────────
    // Hanya aktif saat modal timbangModal terbuka
    document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('timbangModal')
        if (!modal || !modal.classList.contains('show')) return

        const tag = document.activeElement?.tagName?.toLowerCase()
        const activeId = document.activeElement?.id

        // Jika focus di input SELAIN no_box → skip total
        if (['input', 'textarea', 'select'].includes(tag) && activeId !== 'no_box') return

        // Jika focus di no_box, paksa blur dulu agar tidak interferensi
        if (activeId === 'no_box') {
            document.activeElement.blur()
        }

        if (e.key === 'Enter') {
            e.preventDefault()
            e.stopPropagation()
            const code = scanBuffer.trim()
            scanBuffer = ''
            clearTimeout(scanTimer)

            if (code.length >= 8) {
                processScan(code)
            } else {
                scanBuffer = ''
            }
            return
        }

        if (e.key.length === 1) {
            scanBuffer += e.key
            clearTimeout(scanTimer)
            scanTimer = setTimeout(() => {
                scanBuffer = ''
            }, 500)
        }
    })

    // ── Proses hasil scan ─────────────────────────────────────
    async function processScan(qrcode) {
        // Bersihkan whitespace tersembunyi
        qrcode = qrcode.replace(/[\r\n\t]/g, '').trim()

        if (qrcode.length < 8) return

        setIndicator('loading', `Memuat: ${qrcode}`)

        const url = `${FG_API_BASE}?qrcode=${encodeURIComponent(qrcode)}`
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Debug Scanner',
            html: `QR: <b>${qrcode}</b><br>URL: <small>${url}</small>`,
            showConfirmButton: false,
            timer: 5000,
        })

        try {
            const url = `${FG_API_BASE}?qrcode=${encodeURIComponent(qrcode)}`
            const res  = await fetch(url, { signal: AbortSignal.timeout(5000) })

            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            const json = await res.json()

            if (json.status !== 'success' || !json.data?.length) {
                setIndicator('error', `Tidak ditemukan: ${qrcode}`)
                Swal.fire({
                    icon: 'warning',
                    title: 'Karton tidak ditemukan',
                    text: `QR Code "${qrcode}" tidak ada di sistem FG.`,
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                })
                return
            }

            const d = json.data[0]
            fillFromFgApi(d)
            setIndicator('success', `OK: ${d.id_karton}`)

            playSuccessBeep()

            // Kembalikan ke idle setelah 2 detik
            setTimeout(() => setIndicator('idle', 'Siap scan'), 2000)

        } catch (err) {
            console.error('[Scanner] Fetch error:', err)
            setIndicator('error', `Error: ${err.message}`)
            Swal.fire({
                icon: 'error',
                title: 'Gagal mengambil data',
                text: err.message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            })
        }
    }

    // ── Isi field dari data API FG ────────────────────────────
    function fillFromFgApi(d) {
        // ── VALIDASI: cocokkan data scan vs data yang dipilih di tabel ──
        const selectedItem = window.currentSelectedItem
        if (selectedItem) {
            const mismatch = []

            // Normalisasi untuk perbandingan (trim + lowercase)
            const norm = (v) => String(v || '').trim().toLowerCase()

            // 1. Cek KJ
            const scanKj     = norm(d.kj)
            const selectedKj = norm(selectedItem.KJ)
            if (scanKj && selectedKj && scanKj !== selectedKj) {
                mismatch.push({
                    field:    'Order No. (KJ)',
                    expected: selectedItem.KJ,
                    scanned:  d.kj,
                })
            }

            // 2. Cek Style — bandingkan dengan ProductCode karena FG kirim style code
            const scanStyle      = norm(d.style)
            const selectedStyle  = norm(selectedItem.ProductCode)
            if (scanStyle && selectedStyle && scanStyle !== selectedStyle) {
                mismatch.push({
                    field:    'Style',
                    expected: selectedItem.ProductCode,
                    scanned:  d.style,
                })
            }

            // 3. Cek Qty Order
            const scanQty      = parseInt(d.qty_order) || 0
            const selectedQty  = parseInt(selectedItem.Qty) || 0
            if (scanQty && selectedQty && scanQty !== selectedQty) {
                mismatch.push({
                    field:    'Qty Order',
                    expected: selectedItem.Qty,
                    scanned:  d.qty_order,
                })
            }

            // Ada mismatch → tampilkan warning, JANGAN isi field
            if (mismatch.length > 0) {
                const rows = mismatch.map(m =>
                    `<tr>
                        <td style="padding:4px 10px;font-weight:600;color:#555;">${m.field}</td>
                        <td style="padding:4px 10px;color:#1a6a3e;font-weight:700;">${m.expected}</td>
                        <td style="padding:4px 10px;color:#b71c1c;font-weight:700;">${m.scanned}</td>
                    </tr>`
                ).join('')

                setIndicator('error', 'Data tidak cocok!')

                Swal.fire({
                    icon: 'error',
                    title: 'Karton tidak sesuai!',
                    html: `
                        <p style="margin-bottom:12px;font-size:13px;color:#555;">
                            Barcode yang Anda scan tidak cocok dengan data ordersheet yang dipilih.
                        </p>
                        <table style="width:100%;border-collapse:collapse;font-size:12px;text-align:left;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th style="padding:4px 10px;">Field</th>
                                    <th style="padding:4px 10px;color:#1a6a3e;">Dipilih</th>
                                    <th style="padding:4px 10px;color:#b71c1c;">Di Scan</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                        <p style="margin-top:12px;font-size:12px;color:#888;">
                            Pastikan karton yang ditimbang sesuai dengan ordersheet yang dipilih.
                        </p>
                    `,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#435ebe',
                })

                setTimeout(() => setIndicator('idle', 'Siap scan'), 3000)
                return  // STOP — tidak isi field apapun
            }
        }

        // ── Tidak ada mismatch, lanjut isi field ────────────────

        // No. Carton — dari karton_ke (urutan karton ke-N)
        setVal('no_box', d.karton_ke || '')

        // KJ / Order No.
        setVal('info_kj', d.kj || '')
        setVal('info_order_code', d.kj || '')   // hidden field order_code

        // Style code dari FG
        const styleEl = document.getElementById('info_style')
        if (styleEl && d.style) {
            // Pertahankan format "ProductCode - ProductName" jika sudah ada nama produk
            // Cek apakah sudah berisi nama produk (ada ' - ')
            const existing = styleEl.value || ''
            if (existing.includes(' - ')) {
                // Ganti bagian kode saja, pertahankan nama
                const parts = existing.split(' - ')
                styleEl.value = d.style + ' - ' + (parts[1] || '')
            } else {
                styleEl.value = d.style
            }
            styleEl.dispatchEvent(new Event('input',  { bubbles: true }))
            styleEl.dispatchEvent(new Event('change', { bubbles: true }))
        }

        // Qty Order
        if (d.qty_order) setVal('info_qty_order', d.qty_order)

        // Ctn — TIDAK diisi dari karton_ke karena karton_ke = urutan, bukan jumlah per ctn
        // karton_ke sudah masuk ke no_box di atas
        // Jika ingin jumlah_karton sebagai referensi, uncomment:
        // if (d.jumlah_karton) setVal('info_ctn', d.jumlah_karton)

        // Line sewing → set tipe_asal ke 'sewing' dan isi line
        if (d.line_sewing) {
            const tipeEl = document.getElementById('tipe_asal')
            if (tipeEl) {
                tipeEl.value = 'sewing'
                toggleAsalInput('sewing')
            }
            setVal('info_line', d.line_sewing)
        }

        // ── Field tambahan (aktifkan jika API FG menambahkan di kemudian hari) ──
        // if (d.po_number)    setVal('info_purchaseordernumber', d.po_number)
        // if (d.buyer)        setVal('info_buyer', d.buyer)
        // if (d.destination)  setVal('info_FinalDestination', d.destination)

        // Log info karton
        if (d.jumlah_karton) {
            console.info(`[Scanner Modal] Karton ke-${d.karton_ke} dari total ${d.jumlah_karton}`)
        }

        // Trigger checkAndPromptChecking setelah line/subcon terisi
        if (window.currentSelectedItem) {
            setTimeout(() => checkAndPromptChecking(window.currentSelectedItem), 200)
        }
    }

    // ── Helper setVal ────────────────────────────────────────
    function setVal(id, value) {
        const el = document.getElementById(id)
        if (!el) return
        el.value = value
        // Trigger event agar listener lain (jika ada) ikut terupdate
        el.dispatchEvent(new Event('input',  { bubbles: true }))
        el.dispatchEvent(new Event('change', { bubbles: true }))
    }
}

// ─────────────────────────────────────────────────────────────
//  SEARCH SCANNER — hardware scanner di area pencarian tabel
//  Aktif saat modal TIDAK terbuka.
//  Scan QR → fetch proxy FG → gunakan kj sebagai keyword search.
// ─────────────────────────────────────────────────────────────
function initSearchScanner() {
    let searchScanBuffer = ''
    let searchScanTimer  = null
    const SCAN_TIMEOUT   = 500

    const indicatorEl = document.getElementById('searchScannerIndicator')
    const textEl      = document.getElementById('searchScannerText')

    function setSearchIndicator(state, msg) {
        if (!indicatorEl) return
        indicatorEl.className = `scanner-indicator scanner-${state}`
        if (textEl) textEl.textContent = msg || ''
    }

    setSearchIndicator('idle', 'Scan untuk cari')

    document.addEventListener('keydown', (e) => {
        // Hanya aktif saat modal timbangModal TIDAK terbuka
        const modal = document.getElementById('timbangModal')
        if (modal && modal.classList.contains('show')) return

        const tag = document.activeElement?.tagName?.toLowerCase()
        const isInputFocused = ['input', 'textarea', 'select'].includes(tag)
        if (isInputFocused) return  // user sedang ketik manual, skip

        if (e.key === 'Enter') {
            e.preventDefault()
            const code = searchScanBuffer.trim()
            searchScanBuffer = ''
            clearTimeout(searchScanTimer)
            if (code.length >= 4) processSearchScan(code)
            return
        }

        if (e.key.length === 1) {
            searchScanBuffer += e.key
            clearTimeout(searchScanTimer)
            searchScanTimer = setTimeout(() => {
                const code = searchScanBuffer.trim()
                searchScanBuffer = ''
                if (code.length >= 3) processSearchScan(code)
            }, SCAN_TIMEOUT)

            setSearchIndicator('scanning', `Scanning: ${searchScanBuffer}`)
        }
    })

    function processSearchScan(qrcode) {
        setSearchIndicator('loading', `Memuat: ${qrcode}`)

        // Fetch ke API FG untuk dapat KJ, style, qty
        fetch(`http://192.168.0.39/TimbanganApi/Qrcodefg.php?qrcode=${encodeURIComponent(qrcode)}`, {
            signal: AbortSignal.timeout(5000)
        })
        .then(res => res.json())
        .then(json => {
            if (json.status !== 'success' || !json.data?.length) {
                setSearchIndicator('error', `Tidak ditemukan: ${qrcode}`)
                setTimeout(() => setSearchIndicator('idle', 'Scan untuk cari'), 2500)
                return
            }

            const d = json.data[0]

            // Format: kj;style;qty — sesuai format multi-kolom yang sudah ada
            const keyword = [
                d.kj        || '',
                d.style     || '',
                d.qty_order || '',
            ].join(';')

            const searchInput = document.getElementById('search')
            if (searchInput) searchInput.value = keyword

            document.getElementById('searchBtn')?.click()

            setSearchIndicator('success', `Cari: ${d.kj}`)
            setTimeout(() => setSearchIndicator('idle', 'Scan untuk cari'), 2500)

            setTimeout(() => {
                document.getElementById('resultTable')?.scrollIntoView({
                    behavior: 'smooth', block: 'start'
                })
            }, 500)
        })
        .catch(err => {
            setSearchIndicator('error', `Error: ${err.message}`)
            setTimeout(() => setSearchIndicator('idle', 'Scan untuk cari'), 3000)
        })
    }
}

function initSaveButton() {
    const btnSimpan = document.getElementById('btnSimpanTimbang')
    if (!btnSimpan) return
 
    btnSimpan.addEventListener('click', async () => {
        if (!latestPreview || parseFloat(latestPreview.berat) < 0.05) {
            Swal.fire('Peringatan', 'Berat terlalu kecil atau belum terdeteksi!', 'warning')
            return
        }

        // ✅ Cek apakah Nike — jika bukan Nike, no_box wajib diisi
        const buyer = document.getElementById('info_buyer')?.value?.trim().toUpperCase() || ''
        const isNike = buyer.includes('NIKE')
        const noBox = document.getElementById('no_box')?.value?.trim()

        if (!isNike && !noBox) {
            Swal.fire('Peringatan', 'No. Carton harus diisi!', 'warning')
            document.getElementById('no_box')?.focus()
            return
        }

        // ✅ Cek batas beban min & max
        const min = parseFloat(document.getElementById('rasio_batas_beban_min')?.value) || 0
        const max = parseFloat(document.getElementById('rasio_batas_beban_max')?.value) || 0
        const berat = parseFloat(latestPreview.berat)

        if (min > 0 && max > 0) {
            if (berat < min || berat > max) {
                Swal.fire({
                    icon: 'error',
                    title: 'Berat Tidak Valid!',
                    html: `Berat <b>${berat.toFixed(2)} kg</b> di luar batas.<br>
                        Min: <b>${min.toFixed(2)} kg</b> — Max: <b>${max.toFixed(2)} kg</b>`,
                })
                return
            }
        }
 
        const form     = document.getElementById('formOrdersheet')
        const formData = new FormData(form)
        formData.set('berat', latestPreview.berat)
        formData.set('id', currentId)
 
        btnSimpan.disabled  = true
        btnSimpan.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...'
 
        try {
            const res = await fetch('/user/order/simpan', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            })
 
            const isJson = res.headers.get('content-type')?.includes('application/json')
            const json   = isJson ? await res.json() : null
 
            if (res.ok && json?.success) {
                playSuccessBeep()
 
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    html: json.message,
                    timer: 1200,
                    showConfirmButton: false
                })
 
                // ✅ Reset HANYA field per-carton
                // Field info ordersheet (Buyer, Style, dll) TETAP terisi
                document.getElementById('no_box').value       = ''
                document.getElementById('lost_weight').value  = ''
                document.getElementById('manualWeight').value = '' 
 
                // Reset field Ctn (nomor carton) — user isi lagi untuk carton berikutnya
                // const ctnEl = document.getElementById('info_ctn')
                // if (ctnEl) ctnEl.value = ''
 
                // Reset timbangan display
                resetPreviewUI()
 
                // ✅ Modal TIDAK ditutup — user langsung isi no_box carton berikutnya
                setTimeout(() => {
                    document.getElementById('no_box')?.focus()
                }, 400)
 
                // ✅ Row di tabel pencarian TIDAK dihapus
                // Tidak ada addWeighedId(), tidak ada filter allFilteredData
 
                // Reload laporan di bawah (tanpa reload halaman)
                reloadReport()
 
            } else {
                if (res.status === 422 && json?.errors) {
                    const errorMessages = Object.values(json.errors).flat().join('<br>')
                    throw new Error(errorMessages)
                }
                throw new Error(json?.message || 'Gagal menyimpan (Status: ' + res.status + ')')
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error')
        } finally {
            btnSimpan.disabled  = false
            btnSimpan.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan'
        }
    })
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
                            }; color: ${
                                bgClass.includes('text-white') ? '#fff' : '#000'
                            };">
                                <div>
                                    <div><strong>${
                                        device.name || device.esp_id
                                    }</strong></div>
                                    <small class="text-muted">ID: ${
                                        device.esp_id
                                    }</small>
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

async function reloadReport() {
    try {
        const res  = await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        const html = await res.text()
 
        // Parse HTML response, ambil hanya bagian reportContainer
        const parser  = new DOMParser()
        const doc     = parser.parseFromString(html, 'text/html')
        const newReport = doc.getElementById('reportContainer')
        const oldReport = document.getElementById('reportContainer')
 
        if (newReport && oldReport) {
            oldReport.innerHTML = newReport.innerHTML
        }
    } catch (err) {
        // Gagal reload laporan tidak kritis, abaikan
        console.warn('Reload laporan gagal:', err)
    }
}

function showRiwayatDialog(el) {
    let data;
    try {
        data = JSON.parse(el.dataset.riwayat);
    } catch {
        return;
    }

    const currentUserId = window.APP?.userId ?? null;
    console.log(data.id_user);
    console.log(currentUserId);
    const isMine = parseInt(data.id_user) === parseInt(currentUserId);

    const today = new Date().toISOString().split('T')[0];
    const tglTimbang = (data.waktu_timbang || '').substring(0, 10);
    const isToday = tglTimbang === today;

    const canEdit = isMine && isToday;

    const minF = parseFloat(data.rasio_min).toFixed(2);
    const maxF = parseFloat(data.rasio_max).toFixed(2);
    const berat = parseFloat(data.berat);
    let statusBerat = '';
    if (data.rasio_min > 0 && data.rasio_max > 0) {
        if (berat < parseFloat(data.rasio_min)) {
            statusBerat = '<span style="color:red;font-weight:700;">⚠ Di bawah batas minimal</span>';
        } else if (berat > parseFloat(data.rasio_max)) {
            statusBerat = '<span style="color:orange;font-weight:700;">⚠ Melebihi batas maksimal</span>';
        } else {
            statusBerat = '<span style="color:green;font-weight:700;">✓ Dalam batas normal</span>';
        }
    }

    Swal.fire({
        title: `<i class="fa-solid fa-weight-scale me-2"></i>Detail Timbangan`,
        html: `
            <div style="text-align:left;font-size:13px;line-height:2.2;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="color:#888;width:40%;">No. Carton</td>
                        <td><strong>${data.no_box}</strong></td>
                    </tr>
                    <tr>
                        <td style="color:#888;">Berat</td>
                        <td><strong style="font-size:16px;">${data.berat} kg</strong></td>
                    </tr>
                    <tr>
                        <td style="color:#888;">Status Berat</td>
                        <td>${statusBerat || '-'}</td>
                    </tr>
                    <tr>
                        <td style="color:#888;">Batas Min / Max</td>
                        <td>${minF} kg / ${maxF} kg</td>
                    </tr>
                    <tr>
                        <td style="color:#888;">Waktu Timbang</td>
                        <td>${data.waktu_timbang}</td>
                    </tr>
                    <tr>
                        <td style="color:#888;">Status</td>
                        <td>${data.status}</td>
                    </tr>
                </table>

                ${canEdit ? `
                    <hr style="margin:12px 0;">
                    <div style="font-weight:600;margin-bottom:8px;">Edit Timbangan</div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div style="flex:1;">
                            <label style="font-size:11px;color:#888;">No. Carton</label>
                            <input id="swal-nobox" class="swal2-input" style="margin:2px 0;height:36px;font-size:13px;"
                                value="${data.no_box === '-' ? '' : data.no_box}" placeholder="No. Carton">
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:11px;color:#888;">Berat (kg)</label>
                            <input id="swal-berat" type="number" step="0.01" class="swal2-input"
                                style="margin:2px 0;height:36px;font-size:13px;" value="${data.berat}" placeholder="Berat">
                        </div>
                    </div>
                ` : `
                    <div style="margin-top:10px;padding:8px 12px;background:#fff3cd;border-radius:6px;font-size:12px;color:#856404;">
                        <i class="fa-solid fa-lock me-1"></i>
                        ${!isMine 
                            ? 'Hanya operator yang menimbang yang dapat mengubah data ini.' 
                            : 'Data timbangan kemarin tidak dapat diubah.'}
                    </div>
                `}
            </div>
        `,
        showCancelButton: true,
        showDenyButton: isMine,
        confirmButtonText: isMine ? '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan' : 'Tutup',
        denyButtonText: '<i class="fa-solid fa-trash me-1"></i> Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#435ebe',
        denyButtonColor: '#dc3545',
        focusConfirm: false,
        preConfirm: () => {
            if (!isMine) return null;
            const berat = parseFloat(document.getElementById('swal-berat')?.value);
            if (!berat || berat < 0.01) {
                Swal.showValidationMessage('Berat tidak valid');
                return false;
            }
            return {
                berat:  berat,
                no_box: document.getElementById('swal-nobox')?.value?.trim() || null,
            };
        },
    }).then(async (result) => {
        if (result.isConfirmed && result.value && isMine) {
            // UPDATE
            try {
                const res = await fetch(`/user/order/riwayat/${data.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(result.value),
                });
                const json = await res.json();
                if (json.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: json.message, timer: 1500, showConfirmButton: false });
                    // Update tampilan langsung di kotak tanpa reload
                    el.textContent = parseFloat(result.value.berat).toFixed(2);
                    el.dataset.riwayat = JSON.stringify({ ...data, berat: result.value.berat.toFixed(2), no_box: result.value.no_box || data.no_box });
                } else {
                    Swal.fire('Gagal', json.message, 'error');
                }
            } catch {
                Swal.fire('Error', 'Tidak dapat terhubung ke server', 'error');
            }

        } else if (result.isDenied && isMine) {
            // HAPUS — konfirmasi dua kali
            const konfirmasi = await Swal.fire({
                icon: 'warning',
                title: 'Hapus Timbangan?',
                html: `Data carton <strong>${data.no_box}</strong> berat <strong>${data.berat} kg</strong> akan dihapus permanen.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
            });

            if (konfirmasi.isConfirmed) {
                try {
                    const res = await fetch(`/user/order/riwayat/${data.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const json = await res.json();
                    if (json.success) {
                        Swal.fire({ icon: 'success', title: 'Dihapus!', text: json.message, timer: 1500, showConfirmButton: false });
                        // Ganti kotak dengan strip
                        el.textContent = '-';
                        el.classList.remove('w-ok', 'w-kurang', 'w-lebih');
                        el.classList.add('td-empty');
                        el.style.color = '#ddd';
                        el.removeAttribute('onclick');
                        el.removeAttribute('data-riwayat');
                    } else {
                        Swal.fire('Gagal', json.message, 'error');
                    }
                } catch {
                    Swal.fire('Error', 'Tidak dapat terhubung ke server', 'error');
                }
            }
        }
    });
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
            Swal.fire('Sukses!', 'Berhasil pindah devicea!', 'success').then(
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
setInterval(loadAvailableDevices, 60000)

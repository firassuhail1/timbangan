/**
 * report.js
 * Semua logika Formal Report, My Report, Buyer Report.
 * Dipisah dari Blade agar bisa di-cache browser.
 *
 * Cara pakai di Blade — ganti seluruh @push('js') report dengan:
 *
 *   <script>
 *     // Hanya kirim URL path logo — BUKAN base64 — agar HTML tetap ringan.
 *     // Base64 akan di-fetch on-demand saat user klik Print, dan di-cache.
 *     window.LOGO_URL = "{{ asset('assets/images/logo/kanindo.png') }}";
 *   </script>
 *   <script src="{{ asset('auth/js/report.js') }}" defer></script>
 *
 * PERUBAHAN UTAMA vs versi inline:
 *  - 3 report (my, formal, buyer) TIDAK lagi auto-load saat halaman buka.
 *    Sebagai gantinya, IntersectionObserver memuat laporan ketika
 *    section-nya masuk viewport (user scroll ke sana).
 *    Ini menghemat 3 request berat di load awal tanpa mengubah UX.
 *  - Semua fungsi print tetap sama persis.
 */

(function () {
    'use strict';

    // ─────────────────────────────────────────────────────────────
    // UTILS
    // ─────────────────────────────────────────────────────────────
    function chunkArray(arr, size) {
        const chunks = [];
        for (let i = 0; i < arr.length; i += size) chunks.push(arr.slice(i, i + size));
        return chunks;
    }

    function loadingHTML() {
        return '<div class="formal-empty" style="padding:40px;">' +
            '<div class="spinner-border text-primary" style="width:24px;height:24px;" role="status"></div>' +
            '<div style="margin-top:8px;font-size:12px;color:#666;">Menghubungkan ke server...</div>' +
            '</div>';
    }

    // ─────────────────────────────────────────────────────────────
    // RENDER NIKE
    // ─────────────────────────────────────────────────────────────
    function renderNike(targetEl, targetBadge, rows, idPrefix) {
        const COLS = 25;
        const ROWS_PER_PAGE = 24;

        if (!targetEl) return;

        if (!rows.length) {
            if (targetBadge) targetBadge.textContent = '0';
            targetEl.innerHTML = '<div class="formal-empty">Tidak ada data NIKE pada rentang tanggal ini</div>';
            return;
        }

        if (targetBadge) targetBadge.textContent = rows.length;

        const rowsNormal = rows.filter(r => (parseInt(r.checking_ke) || 1) === 1);
        const rowsDouble = rows.filter(r => (parseInt(r.checking_ke) || 1) >= 2);

        function buildAllRows(rowSet) {
            const result = [];
            rowSet.forEach(r => {
                const chunks = chunkArray(r.timbangans, COLS);
                const rowspan = chunks.length;
                chunks.forEach((chunk, chunkIdx) => {
                    const padded = [...chunk, ...Array(COLS - chunk.length).fill(null)];
                    const chunkLen = chunk.length;
                    let tdBerats = '';
                    padded.forEach(t => {
                        tdBerats += t ?
                            `<td class="td-berat" style="cursor:pointer;"
                                data-riwayat='${JSON.stringify({
                                    id:            t.id,
                                    berat:         parseFloat(t.berat).toFixed(2),
                                    no_box:        t.no_box || "-",
                                    waktu_timbang: t.waktu_timbang || "-",
                                    rasio_min:     t.rasio_batas_beban_min || 0,
                                    rasio_max:     t.rasio_batas_beban_max || 0,
                                    status:        t.status || "-",
                                    id_user:       t.id_user || null,
                                })}'
                                onclick="showRiwayatDialog(this)"
                            >${parseFloat(t.berat).toFixed(2)}</td>` :
                            `<td class="td-empty">-</td>`;
                    });
                    result.push({ order: r, chunkIdx, rowspan, chunkLen, tdBerats });
                });
            });
            return result;
        }

        function renderNikeGroup(containerEl, rowSet, groupLabel, groupColor, gPrefix) {
            if (!rowSet.length) {
                containerEl.innerHTML += `<div class="formal-empty" style="color:#aaa;font-size:11px;margin:8px 0;">
                    <em>Tidak ada data Nike ${groupLabel} pada rentang ini</em></div>`;
                return;
            }

            const byDate = {};
            rowSet.forEach(r => {
                const tgl = r.tanggal || 'Tanpa Tanggal';
                if (!byDate[tgl]) byDate[tgl] = [];
                byDate[tgl].push(r);
            });

            const sortedDates = Object.keys(byDate).sort((a, b) => {
                const parse = d => { const [dd, mm, yyyy] = (d || '').split('-'); return new Date(`${yyyy}-${mm}-${dd}`); };
                return parse(a) - parse(b);
            });

            const datePages = sortedDates.map(tgl => ({
                date: tgl,
                pages: chunkArray(buildAllRows(byDate[tgl]), ROWS_PER_PAGE)
            }));

            let curDateIdx = 0;
            let curSheetIdx = 0;
            const totalCarton = rowSet.reduce((s, r) => s + r.timbangans.length, 0);

            const groupDiv = document.createElement('div');
            groupDiv.id = `${gPrefix}-wrapper`;
            groupDiv.style.marginBottom = '20px';
            groupDiv.innerHTML =
                `<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;
                gap:10px;margin-bottom:10px;padding:8px 12px;border-radius:6px;
                background:${groupColor}18;border-left:4px solid ${groupColor};">
                <div style="font-size:12px;font-weight:700;color:${groupColor};">${groupLabel}</div>
                <div id="${gPrefix}-meta" style="font-size:11px;color:#666;"></div>
                <button class="btn-print-formal" id="${gPrefix}-print-btn">
                    <i class="bi bi-printer"></i> Print Lembar <span id="${gPrefix}-cur-page">1</span>
                </button>
            </div>` +
                `<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;
                margin-bottom:10px;padding:8px 10px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">
                <button id="${gPrefix}-prev-date" class="rpt-page-btn" style="min-width:32px;">‹‹</button>
                <select id="${gPrefix}-date-select"
                    style="font-size:11px;padding:3px 6px;border:1px solid #ced4da;border-radius:4px;background:#fff;cursor:pointer;max-width:160px;">
                    ${sortedDates.map((tgl, i) => `<option value="${i}">📅 ${tgl}</option>`).join('')}
                </select>
                <button id="${gPrefix}-prev-sheet" class="rpt-page-btn" style="min-width:28px;">‹</button>
                <span id="${gPrefix}-sheet-label" style="font-size:11px;color:#555;white-space:nowrap;">Lembar 1/1</span>
                <button id="${gPrefix}-next-sheet" class="rpt-page-btn" style="min-width:28px;">›</button>
                <button id="${gPrefix}-next-date" class="rpt-page-btn" style="min-width:32px;">››</button>
                <span style="margin-left:auto;font-size:10px;color:#999;">${sortedDates.length} hari · ${totalCarton} carton total</span>
            </div>` +
                `<div id="${gPrefix}-content"></div>`;

            containerEl.appendChild(groupDiv);

            function buildTableHTML(pageRows) {
                let thNums = '';
                for (let i = 1; i <= COLS; i++) thNums += `<th style="min-width:36px;font-size:10px;">${i}</th>`;

                let tbody = '';
                pageRows.forEach(row => {
                    const r = row.order;
                    const infoTds = row.chunkIdx === 0 ?
                        `<td class="td-order" rowspan="${row.rowspan}" style="text-align:left;font-size:10px;word-break:break-all;max-width:100px;">${r.kj}</td>` +
                        `<td rowspan="${row.rowspan}" style="font-size:11px;">${r.style || '-'}</td>` +
                        `<td rowspan="${row.rowspan}">${r.color || '-'}</td>` +
                        `<td rowspan="${row.rowspan}">${r.pcs || '-'}</td>` +
                        `<td rowspan="${row.rowspan}">${r.qty_order || '-'}</td>` +
                        `<td rowspan="${row.rowspan}" style="font-size:10px;">${r.gac_date || '-'}</td>` +
                        `<td rowspan="${row.rowspan}" style="font-size:10px;max-width:80px;">${r.destination || '-'}</td>` +
                        `<td rowspan="${row.rowspan}">${r.line || '-'}</td>` +
                        `<td rowspan="${row.rowspan}">${r.carton_weight_std || '-'}</td>` : '';
                    tbody += `<tr>${infoTds}${row.tdBerats}<td class="td-total">${row.chunkLen}</td><td></td></tr>`;
                });

                const emptyNeeded = ROWS_PER_PAGE - pageRows.length;
                if (emptyNeeded > 0) {
                    const es = `style="border:1px solid #dee2e6;color:#ccc;font-size:10px;"`;
                    let ei = '', eb = '';
                    for (let i = 0; i < 9; i++) ei += `<td ${es}>-</td>`;
                    for (let i = 0; i < COLS; i++) eb += `<td ${es}>-</td>`;
                    for (let r = 0; r < emptyNeeded; r++) tbody += `<tr>${ei}${eb}<td ${es}>-</td><td ${es}></td></tr>`;
                }

                return `<div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">` +
                    `<table class="nike-table"><thead>` +
                    `<tr>` +
                    `<th rowspan="2" style="min-width:100px;">Order No.</th>` +
                    `<th rowspan="2" style="min-width:90px;">Style</th>` +
                    `<th rowspan="2">CLR</th>` +
                    `<th rowspan="2">Isi Karton</th>` +
                    `<th rowspan="2">Qty Order</th>` +
                    `<th rowspan="2">GAC</th>` +
                    `<th rowspan="2" style="min-width:70px;">Destination</th>` +
                    `<th rowspan="2">Dari Line</th>` +
                    `<th rowspan="2">Standar Berat</th>` +
                    `<th colspan="${COLS}" style="background:#2d4fad;">Actual Berat Karton</th>` +
                    `<th rowspan="2">Total Karton</th>` +
                    `<th rowspan="2" style="min-width:50px;">Ket</th>` +
                    `</tr><tr>${thNums}</tr>` +
                    `</thead><tbody>${tbody}</tbody></table></div>`;
            }

            function render() {
                const dp = datePages[curDateIdx];
                const pageRows = dp.pages[curSheetIdx] || [];
                const totalSheets = dp.pages.length;

                const metaEl = document.getElementById(`${gPrefix}-meta`);
                if (metaEl) metaEl.textContent = `📅 ${dp.date} · Lembar ${curSheetIdx + 1}/${totalSheets} · ${byDate[dp.date].length} order`;

                const curPageEl = document.getElementById(`${gPrefix}-cur-page`);
                if (curPageEl) curPageEl.textContent = curSheetIdx + 1;

                const sheetLabel = document.getElementById(`${gPrefix}-sheet-label`);
                if (sheetLabel) sheetLabel.textContent = `Lembar ${curSheetIdx + 1} / ${totalSheets}`;

                document.getElementById(`${gPrefix}-prev-date`).disabled = curDateIdx === 0;
                document.getElementById(`${gPrefix}-next-date`).disabled = curDateIdx === datePages.length - 1;
                document.getElementById(`${gPrefix}-prev-sheet`).disabled = curSheetIdx === 0;
                document.getElementById(`${gPrefix}-next-sheet`).disabled = curSheetIdx === totalSheets - 1;

                const sel = document.getElementById(`${gPrefix}-date-select`);
                if (sel) sel.value = curDateIdx;

                const contentEl = document.getElementById(`${gPrefix}-content`);
                if (contentEl) contentEl.innerHTML = buildTableHTML(pageRows);
            }

            groupDiv.querySelector(`#${gPrefix}-prev-date`).addEventListener('click', () => { if (curDateIdx > 0) { curDateIdx--; curSheetIdx = 0; render(); } });
            groupDiv.querySelector(`#${gPrefix}-next-date`).addEventListener('click', () => { if (curDateIdx < datePages.length - 1) { curDateIdx++; curSheetIdx = 0; render(); } });
            groupDiv.querySelector(`#${gPrefix}-prev-sheet`).addEventListener('click', () => { if (curSheetIdx > 0) { curSheetIdx--; render(); } });
            groupDiv.querySelector(`#${gPrefix}-next-sheet`).addEventListener('click', () => { if (curSheetIdx < datePages[curDateIdx].pages.length - 1) { curSheetIdx++; render(); } });
            groupDiv.querySelector(`#${gPrefix}-date-select`).addEventListener('change', function () { curDateIdx = parseInt(this.value); curSheetIdx = 0; render(); });
            groupDiv.querySelector(`#${gPrefix}-print-btn`).addEventListener('click', () => {
                const dp = datePages[curDateIdx];
                printNikePage(curSheetIdx + 1, dp.pages[curSheetIdx] || [], dp.date);
            });

            render();
        }

        targetEl.innerHTML = '';
        renderNikeGroup(targetEl, rowsNormal, '📋 Timbangan Pertama (Checking #1)', '#435ebe', `${idPrefix}-nike-normal`);

        if (rowsDouble.length > 0) {
            const sep = document.createElement('div');
            sep.style.cssText = 'border-top:2px dashed #ff6b35;margin:16px 0 12px;padding-top:8px;';
            sep.innerHTML = `<span style="background:#fff3e0;color:#ff6b35;font-size:11px;font-weight:700;
                padding:3px 10px;border-radius:12px;border:1.5px solid #ff6b35;">
                ⚠ Lembar Double Check — Checking #2 dst.</span>`;
            targetEl.appendChild(sep);
            renderNikeGroup(targetEl, rowsDouble, '🔁 Double Check (Checking #2+)', '#ff6b35', `${idPrefix}-nike-double`);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // RENDER NON-NIKE
    // ─────────────────────────────────────────────────────────────
    function renderNonNike(targetEl, targetBadge, orders, idPrefix) {
        const COLS_PER_BLOCK = 10;
        const ROWS_PER_BLOCK = 5;
        const CARTON_PER_BLOCK = COLS_PER_BLOCK * ROWS_PER_BLOCK;
        const BLOCKS_PER_PAGE = 4;

        if (!targetEl) return;

        if (!orders.length) {
            if (targetBadge) targetBadge.textContent = '0';
            targetEl.innerHTML = '<div class="formal-empty">Tidak ada data Non-Nike pada rentang tanggal ini</div>';
            return;
        }

        if (targetBadge) targetBadge.textContent = orders.length;

        const buildAllBlocks = (orderList) => {
            const blocks = [];
            orderList.forEach(order => {
                (order.by_line || []).forEach(lineGroup => {
                    const timbangans = lineGroup.timbangans || [];
                    const cartonChunks = chunkArray(timbangans, CARTON_PER_BLOCK);
                    cartonChunks.forEach((chunk, blockIdx) => {
                        blocks.push({
                            buyer: order.buyer || '-',
                            kj: order.kj || order.order_code || '-',
                            order_code: order.order_code || '-',
                            po: order.po || '-',
                            style: order.style || '-',
                            color: order.color || '-',
                            qty_order: order.qty_order || 0,
                            carton_weight_std: order.carton_weight_std,
                            pcs_weight_std: order.pcs_weight_std,
                            gac_date: order.gac_date || '-',
                            destination: order.destination || '-',
                            inspector: order.inspector || '-',
                            opt_qc: order.opt_qc || '-',
                            spv_qc: order.spv_qc || '-',
                            chief: order.chief || '-',
                            line: lineGroup.line,
                            subcon: order.subcon || null,
                            checking_ke: parseInt(order.checking_ke) || 1,
                            pcs_default: order.pcs_default || '-',
                            ordersheet_id: order.ordersheet_id || '',
                            keterangan: order.keterangan || '',
                            timbangans: chunk,
                            blockIdx,
                            totalCartonInLine: timbangans.length,
                            startNo: blockIdx * CARTON_PER_BLOCK + 1,
                        });
                    });
                });
            });
            return blocks;
        };

        const allBlocks = buildAllBlocks(orders);
        const blocksNormal = allBlocks.filter(b => b.checking_ke === 1);
        const blocksDouble = allBlocks.filter(b => b.checking_ke >= 2);

        function checkingBadgeHTML(checkingKe, fontSize = '10px') {
            const bg = checkingKe > 1 ? '#ff6b35' : '#6c757d';
            return `<span style="background:${bg};color:#fff;font-size:${fontSize};font-weight:700;padding:2px 8px;border-radius:4px;margin-left:6px;">Checking #${checkingKe}</span>`;
        }

        function buildBlockHTML(block) {
            const isContinued = block.blockIdx > 0;
            const startNo = block.startNo;
            const cartons = block.timbangans;
            let tbodyRows = '';

            for (let row = 0; row < ROWS_PER_BLOCK; row++) {
                const startIdx = row * COLS_PER_BLOCK;
                const rowCartons = cartons.slice(startIdx, startIdx + COLS_PER_BLOCK);
                const padded = [...rowCartons, ...Array(COLS_PER_BLOCK - rowCartons.length).fill(null)];
                const rowTotalBerat = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
                const hasData = rowCartons.length > 0;
                let tdBoxes = '', tdWeights = '';

                padded.forEach((t, colIdx) => {
                    const no = startNo + startIdx + colIdx;
                    tdBoxes += t ? `<td class="td-box" style="font-weight:600;font-size:10px;">${t.no_box || no}</td>` :
                        `<td class="td-empty" style="color:#ddd;">-</td>`;
                });
                padded.forEach(t => {
                    if (t) {
                        const bv = parseFloat(t.berat);
                        const mn = parseFloat(t.rasio_batas_beban_min || 0);
                        const mx = parseFloat(t.rasio_batas_beban_max || 0);
                        let cls = 'w-ok';
                        if (mn > 0 && mx > 0) {
                            if (bv < mn) cls = 'w-kurang';
                            else if (bv > mx) cls = 'w-lebih';
                        }
                        tdWeights += `<td class="td-w ${cls}"
                            style="font-weight:700;font-size:11px;cursor:pointer;"
                            data-riwayat='${JSON.stringify({
                                id:            t.id,
                                berat:         bv.toFixed(2),
                                no_box:        t.no_box || "-",
                                waktu_timbang: t.waktu_timbang || "-",
                                rasio_min:     t.rasio_batas_beban_min || 0,
                                rasio_max:     t.rasio_batas_beban_max || 0,
                                status:        t.status || "-",
                                id_user:       t.id_user || null,
                            })}'
                            onclick="showRiwayatDialog(this)"
                        >${bv.toFixed(2)}</td>`;
                    } else {
                        tdWeights += `<td class="td-empty" style="color:#ddd;">-</td>`;
                    }
                });

                const rowDate = rowCartons[0]?.waktu_timbang ? rowCartons[0].waktu_timbang.substring(0, 10) : '-';
                tbodyRows +=
                    `<tr>` +
                    `<td class="td-date" rowspan="2" style="font-size:10px;color:#666;vertical-align:middle;white-space:nowrap;">${rowDate}</td>` +
                    tdBoxes +
                    `<td class="td-total" rowspan="2" style="font-weight:700;color:#2dce89;vertical-align:middle;">${hasData ? rowTotalBerat.toFixed(2) : '-'}</td>` +
                    (row === 0 ?
                        `<td rowspan="${ROWS_PER_BLOCK * 2}" style="vertical-align:top;padding:4px;" class="ket-cell">` +
                        `<div class="ket-display" style="font-size:10px;cursor:pointer;min-height:24px;padding:3px 4px;border:1px dashed #ced4da;border-radius:3px;background:#fafafa;" title="Klik untuk edit" data-ordersheet-id="${block.ordersheet_id}">` +
                        `${block.keterangan ? block.keterangan.replace(/</g, '&lt;') : '<em style="font-size:9px;color:#bbb;">Klik untuk tambah...</em>'}` +
                        `</div>` +
                        `<div class="ket-edit" style="display:none;flex-direction:column;gap:3px;margin-top:2px;">` +
                        `<textarea class="ket-input" data-ordersheet-id="${block.ordersheet_id}" rows="3" placeholder="Tulis keterangan..." style="width:100%;font-size:10px;padding:4px;border:1px solid #ced4da;border-radius:4px;resize:vertical;box-sizing:border-box;">${(block.keterangan || '').replace(/</g, '&lt;')}</textarea>` +
                        `<div style="display:flex;gap:3px;">` +
                        `<button class="ket-save-btn" data-ordersheet-id="${block.ordersheet_id}" style="font-size:9px;padding:2px 5px;background:#435ebe;color:#fff;border:none;border-radius:3px;cursor:pointer;">💾</button>` +
                        `<button class="ket-cancel-btn" style="font-size:9px;padding:2px 5px;background:#6c757d;color:#fff;border:none;border-radius:3px;cursor:pointer;">✕</button>` +
                        `</div><span class="ket-status" style="font-size:9px;display:none;"></span>` +
                        `</div></td>` : '') +
                    `</tr><tr>${tdWeights}</tr>`;
            }

            const continuedBadge = isContinued ?
                `<span style="background:#fff3cd;color:#856404;font-size:10px;font-weight:600;padding:2px 8px;border-radius:4px;margin-left:8px;">Lanjutan</span>` : '';
            const thHeaders = Array.from({ length: COLS_PER_BLOCK }, (_, i) => `<th style="min-width:50px;">#${i + 1}</th>`).join('');
            const totalBerat = block.timbangans.reduce((s, t) => s + parseFloat(t?.berat || 0), 0).toFixed(2);

            return (
                `<div class="non-nike-block" style="margin-bottom:12px;">` +
                `<div class="nn-info-wrap">` +
                `<div class="nn-info-left"><table class="nn-info-table">` +
                `<tr><th colspan="2" style="background:#435ebe;color:#fff;text-align:center;font-size:11px;padding:4px;">CARTON WEIGHT REPORT ${continuedBadge}${checkingBadgeHTML(block.checking_ke)}` +
                `<button class="btn-print-block" onclick="printSingleBlock(this)" style="float:right;background:#fff;color:#435ebe;border:none;border-radius:3px;padding:1px 6px;font-size:10px;cursor:pointer;font-weight:700;" data-block='${JSON.stringify(block).replace(/'/g, "&#39;")}'>` +
                `<i class="bi bi-printer"></i> Print Block</button></th></tr>` +
                `<tr><th>BUYER</th><td><strong>${block.buyer}</strong></td></tr>` +
                `<tr><th>Order No. (KJ)</th><td><strong>${block.kj}</strong></td></tr>` +
                `<tr><th>PO#</th><td>${block.po}</td></tr>` +
                `<tr><th>Style</th><td>${block.style}</td></tr>` +
                `<tr><th>Color</th><td>${block.color}</td></tr>` +
                `<tr><th>Qty Order</th><td>${parseInt(block.qty_order || 0).toLocaleString()} pcs <span style="font-size:10px;color:#666;">${block.subcon ? 'S = ' + block.subcon : 'L = ' + (block.line || '-')} &nbsp;·&nbsp; M = ${block.pcs_default}</span></td></tr>` +
                `<tr><th>Carton Weight Std.</th><td>${block.carton_weight_std ? parseFloat(block.carton_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                `<tr><th>Pcs Weight Std.</th><td>${block.pcs_weight_std ? parseFloat(block.pcs_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                `</table></div>` +
                `<div class="nn-info-right"><table class="nn-info-table">` +
                `<tr><th colspan="2" style="background:#435ebe;color:#fff;text-align:center;font-size:11px;padding:4px;">&nbsp;${continuedBadge}</th></tr>` +
                `<tr><th>GAC Date</th><td>${block.gac_date}</td></tr>` +
                `<tr><th>Destination</th><td>${block.destination}</td></tr>` +
                `<tr><th>Inspector</th><td>${block.inspector}</td></tr>` +
                `<tr><th>Total Carton</th><td><strong>${block.timbangans.length}</strong> / ${block.totalCartonInLine}</td></tr>` +
                `<tr><th>Total Berat</th><td><strong>${totalBerat} kg</strong></td></tr>` +
                `</table>` +
                `<div class="nn-sign-wrap" style="margin-top:6px;"><table class="nn-sign-table">` +
                `<thead><tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr></thead>` +
                `<tbody><tr>` +
                `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.opt_qc !== '-' ? block.opt_qc : ''}</td>` +
                `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.spv_qc !== '-' ? block.spv_qc : ''}</td>` +
                `<td style="height:45px;vertical-align:bottom;font-weight:700;">${block.chief !== '-' ? block.chief : ''}</td>` +
                `</tr></tbody></table></div></div></div>` +
                `<div class="nn-carton-wrap"><table class="nn-carton-table">` +
                `<thead><tr><th rowspan="2" style="min-width:50px;">Date</th><th colspan="${COLS_PER_BLOCK}">Ctn. No &amp; Weight (kg)</th><th rowspan="2" style="min-width:50px;">Total (kg)</th><th rowspan="2" style="min-width:45px;">Remark</th></tr>` +
                `<tr>${thHeaders}</tr></thead><tbody>${tbodyRows}</tbody></table></div></div>`
            );
        }

        function renderGroup(containerEl, blocks, groupLabel, groupColor, gPrefix) {
            if (!blocks.length) {
                containerEl.innerHTML += `<div class="formal-empty" style="color:#aaa;font-size:11px;margin:8px 0;"><em>Tidak ada data ${groupLabel} pada rentang ini</em></div>`;
                return;
            }

            const byDate = {};
            blocks.forEach(b => {
                const tgl = b.timbangans?.[0]?.waktu_timbang?.substring(0, 10) || 'Tanpa Tanggal';
                if (!byDate[tgl]) byDate[tgl] = [];
                byDate[tgl].push(b);
            });

            const sortedDates = Object.keys(byDate).sort();
            const datePages = sortedDates.map(tgl => ({
                date: tgl,
                pages: chunkArray(byDate[tgl], BLOCKS_PER_PAGE)
            }));

            let curDateIdx = 0, curSheetIdx = 0;

            const groupDiv = document.createElement('div');
            groupDiv.id = `${gPrefix}-wrapper`;
            groupDiv.style.marginBottom = '20px';
            groupDiv.innerHTML =
                `<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;
                gap:10px;margin-bottom:10px;padding:8px 12px;border-radius:6px;
                background:${groupColor}18;border-left:4px solid ${groupColor};">
                <div style="font-size:12px;font-weight:700;color:${groupColor};">${groupLabel}</div>
                <div id="${gPrefix}-meta" style="font-size:11px;color:#666;"></div>
                <button class="btn-print-formal" id="${gPrefix}-print-btn">
                    <i class="bi bi-printer"></i> Print Lembar <span id="${gPrefix}-cur-page">1</span>
                </button>
            </div>` +
                `<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;
                margin-bottom:10px;padding:8px 10px;background:#f8f9fa;border-radius:6px;border:1px solid #dee2e6;">
                <button id="${gPrefix}-prev-date" class="rpt-page-btn" style="min-width:32px;">‹‹</button>
                <select id="${gPrefix}-date-select"
                    style="font-size:11px;padding:3px 6px;border:1px solid #ced4da;border-radius:4px;background:#fff;cursor:pointer;max-width:160px;">
                    ${sortedDates.map((tgl, i) => `<option value="${i}">📅 ${tgl}</option>`).join('')}
                </select>
                <button id="${gPrefix}-prev-sheet" class="rpt-page-btn" style="min-width:28px;">‹</button>
                <span id="${gPrefix}-sheet-label" style="font-size:11px;color:#555;white-space:nowrap;">Lembar 1/1</span>
                <button id="${gPrefix}-next-sheet" class="rpt-page-btn" style="min-width:28px;">›</button>
                <button id="${gPrefix}-next-date" class="rpt-page-btn" style="min-width:32px;">››</button>
                <span style="margin-left:auto;font-size:10px;color:#999;">${sortedDates.length} hari · ${blocks.length} blok total</span>
            </div>` +
                `<div id="${gPrefix}-blocks"></div>`;

            containerEl.appendChild(groupDiv);

            function render() {
                const dp = datePages[curDateIdx];
                const pageBlocks = dp.pages[curSheetIdx] || [];
                const totalSheets = dp.pages.length;

                const metaEl = document.getElementById(`${gPrefix}-meta`);
                if (metaEl) metaEl.textContent = `📅 ${dp.date} · Lembar ${curSheetIdx + 1}/${totalSheets} · ${byDate[dp.date].length} blok`;

                const curPageEl = document.getElementById(`${gPrefix}-cur-page`);
                if (curPageEl) curPageEl.textContent = curSheetIdx + 1;

                const sheetLabel = document.getElementById(`${gPrefix}-sheet-label`);
                if (sheetLabel) sheetLabel.textContent = `Lembar ${curSheetIdx + 1} / ${totalSheets}`;

                document.getElementById(`${gPrefix}-prev-date`).disabled = curDateIdx === 0;
                document.getElementById(`${gPrefix}-next-date`).disabled = curDateIdx === datePages.length - 1;
                document.getElementById(`${gPrefix}-prev-sheet`).disabled = curSheetIdx === 0;
                document.getElementById(`${gPrefix}-next-sheet`).disabled = curSheetIdx === totalSheets - 1;

                const sel = document.getElementById(`${gPrefix}-date-select`);
                if (sel) sel.value = curDateIdx;

                const blocksArea = document.getElementById(`${gPrefix}-blocks`);
                if (blocksArea) blocksArea.innerHTML = pageBlocks.map(b => buildBlockHTML(b)).join('');
            }

            groupDiv.querySelector(`#${gPrefix}-prev-date`).addEventListener('click', () => { if (curDateIdx > 0) { curDateIdx--; curSheetIdx = 0; render(); } });
            groupDiv.querySelector(`#${gPrefix}-next-date`).addEventListener('click', () => { if (curDateIdx < datePages.length - 1) { curDateIdx++; curSheetIdx = 0; render(); } });
            groupDiv.querySelector(`#${gPrefix}-prev-sheet`).addEventListener('click', () => { if (curSheetIdx > 0) { curSheetIdx--; render(); } });
            groupDiv.querySelector(`#${gPrefix}-next-sheet`).addEventListener('click', () => { if (curSheetIdx < datePages[curDateIdx].pages.length - 1) { curSheetIdx++; render(); } });
            groupDiv.querySelector(`#${gPrefix}-date-select`).addEventListener('change', function () { curDateIdx = parseInt(this.value); curSheetIdx = 0; render(); });
            groupDiv.querySelector(`#${gPrefix}-print-btn`).addEventListener('click', () => {
                printNonNikePage(curSheetIdx + 1, datePages[curDateIdx].pages[curSheetIdx] || []);
            });

            render();
        }

        targetEl.innerHTML = '';
        renderGroup(targetEl, blocksNormal, '📋 Timbangan Pertama (Checking #1)', '#435ebe', `${idPrefix}-nn-normal`);

        if (blocksDouble.length > 0) {
            const sep = document.createElement('div');
            sep.style.cssText = 'border-top:2px dashed #ff6b35;margin:16px 0 12px;padding-top:8px;';
            sep.innerHTML = `<span style="background:#fff3e0;color:#ff6b35;font-size:11px;font-weight:700;
                padding:3px 10px;border-radius:12px;border:1.5px solid #ff6b35;">
                ⚠ Lembar Double Check — Checking #2 dst.</span>`;
            targetEl.appendChild(sep);
            renderGroup(targetEl, blocksDouble, '🔁 Double Check (Checking #2+)', '#ff6b35', `${idPrefix}-nn-double`);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GENERIC FETCH + RENDER
    // ─────────────────────────────────────────────────────────────
    async function loadReport(endpoint, cfg, params) {
        if (cfg.nikeEl) cfg.nikeEl.innerHTML = loadingHTML();
        if (cfg.nonNikeEl) cfg.nonNikeEl.innerHTML = loadingHTML();

        const today = new Date().toISOString().split('T')[0];
        const start = params.get('start') || '';
        const end = params.get('end') || '';
        if (cfg.labelEl) {
            cfg.labelEl.textContent = (start === today && end === today) ?
                'Hari ini' : (start + (start !== end ? ' s/d ' + end : ''));
        }

        try {
            const res = await fetch(endpoint + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Gagal memuat data.');

            renderNike(cfg.nikeEl, cfg.nikeBadge, json.nike || [], cfg.idPrefix + '-nk');
            renderNonNike(cfg.nonNikeEl, cfg.nonNikeBadge, json.non_nike || [], cfg.idPrefix + '-nn');
        } catch (err) {
            const errHTML = `<div class="formal-empty" style="color:#ef5350;padding:20px;">
                <i class="fas fa-exclamation-circle" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                <b>Error:</b> ${err.message}</div>`;
            if (cfg.nikeEl) cfg.nikeEl.innerHTML = errHTML;
            if (cfg.nonNikeEl) cfg.nonNikeEl.innerHTML = errHTML;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // TAB SWITCHING
    // ─────────────────────────────────────────────────────────────
    function initTabs(wrapId, attrKey, panelPrefix) {
        const wrap = document.getElementById(wrapId);
        if (!wrap) return;
        wrap.querySelectorAll(`[data-${attrKey}-tab]`).forEach(tab => {
            tab.addEventListener('click', function () {
                wrap.querySelectorAll(`[data-${attrKey}-tab]`).forEach(t => t.classList.remove('active'));
                wrap.querySelectorAll('.formal-panel').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                const panel = document.getElementById(`${panelPrefix}-panel-${this.dataset[attrKey + 'Tab']}`);
                if (panel) panel.classList.add('active');
            });
        });
    }

    // ─────────────────────────────────────────────────────────────
    // LAZY LOAD HELPER
    // Memanggil loader() hanya sekali ketika elemen masuk viewport.
    // Jika IntersectionObserver tidak tersedia (browser lama),
    // fallback ke load langsung.
    // ─────────────────────────────────────────────────────────────
    function lazyLoad(elementId, loader) {
        const el = document.getElementById(elementId);
        if (!el) { loader(); return; }

        if (!('IntersectionObserver' in window)) {
            // Fallback: load langsung jika browser tidak support
            loader();
            return;
        }

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    obs.unobserve(el);
                    loader();
                }
            });
        }, {
            rootMargin: '200px' // mulai load 200px sebelum masuk layar
        });

        observer.observe(el);
    }

    // ─────────────────────────────────────────────────────────────
    // MY REPORT
    // ─────────────────────────────────────────────────────────────
    function initMyReport() {
        initTabs('my-report-wrap', 'my', 'my');

        let loaded = false;

        function load() {
            loaded = true;
            const start = document.getElementById('my-date-start')?.value || '';
            const end = document.getElementById('my-date-end')?.value || '';
            const p = new URLSearchParams();
            if (start) p.append('start', start);
            if (end) p.append('end', end);

            loadReport('/user/order/my-report', {
                nikeEl: document.getElementById('my-nike-report-content'),
                nonNikeEl: document.getElementById('my-non-nike-report-content'),
                nikeBadge: document.getElementById('my-nike-count-badge'),
                nonNikeBadge: document.getElementById('my-non-nike-count-badge'),
                labelEl: document.getElementById('my-range-label'),
                idPrefix: 'my',
            }, p);
        }

        document.getElementById('btn-my-filter')?.addEventListener('click', load);
        document.getElementById('btn-my-reset')?.addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('my-date-start').value = today;
            document.getElementById('my-date-end').value = today;
            load();
        });

        // ── LAZY: load hanya saat section ini masuk viewport ──────
        lazyLoad('my-report-wrap', load);
    }

    // ─────────────────────────────────────────────────────────────
    // FORMAL REPORT
    // ─────────────────────────────────────────────────────────────
    function initFormalReport() {
        initTabs('formal-report-wrap', 'formal', 'formal');

        function load() {
            const start = document.getElementById('formal-date-start')?.value || '';
            const end = document.getElementById('formal-date-end')?.value || '';
            const p = new URLSearchParams();
            if (start) p.append('start', start);
            if (end) p.append('end', end);

            loadReport('/user/order/formal-report', {
                nikeEl: document.getElementById('formal-nike-report-content'),
                nonNikeEl: document.getElementById('formal-non-nike-report-content'),
                nikeBadge: document.getElementById('formal-nike-count-badge'),
                nonNikeBadge: document.getElementById('formal-non-nike-count-badge'),
                labelEl: document.getElementById('formal-range-label'),
                idPrefix: 'formal',
            }, p);
        }

        document.getElementById('btn-formal-filter')?.addEventListener('click', load);
        document.getElementById('btn-formal-reset')?.addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('formal-date-start').value = today;
            document.getElementById('formal-date-end').value = today;
            load();
        });

        // ── LAZY: load hanya saat section ini masuk viewport ──────
        lazyLoad('formal-report-wrap', load);
    }

    // ─────────────────────────────────────────────────────────────
    // BUYER REPORT
    // ─────────────────────────────────────────────────────────────
    async function initBuyerReport() {
        initTabs('buyer-report-wrap', 'buyer', 'buyer');

        try {
            const res = await fetch('/user/order/buyers', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const sel = document.getElementById('buyer-select');
            if (sel && json.success && json.buyers) {
                json.buyers.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b;
                    opt.textContent = b;
                    sel.appendChild(opt);
                });
            }
        } catch (e) {
            console.warn('Gagal load buyers:', e);
        }

        function load() {
            const start = document.getElementById('buyer-date-start')?.value || '';
            const end = document.getElementById('buyer-date-end')?.value || '';
            const buyer = document.getElementById('buyer-select')?.value || '';

            if (!buyer) {
                const msg = `<div class="formal-empty" style="color:#f39c12;padding:20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:24px;display:block;margin-bottom:8px;opacity:0.5;"></i>
                    Pilih buyer terlebih dahulu</div>`;
                const nikeEl = document.getElementById('buyer-nike-report-content');
                const nonNikeEl = document.getElementById('buyer-non-nike-report-content');
                if (nikeEl) nikeEl.innerHTML = msg;
                if (nonNikeEl) nonNikeEl.innerHTML = msg;
                return;
            }

            const p = new URLSearchParams();
            if (start) p.append('start', start);
            if (end) p.append('end', end);
            p.append('buyer', buyer);

            loadReport('/user/order/buyer-report', {
                nikeEl: document.getElementById('buyer-nike-report-content'),
                nonNikeEl: document.getElementById('buyer-non-nike-report-content'),
                nikeBadge: document.getElementById('buyer-nike-count-badge'),
                nonNikeBadge: document.getElementById('buyer-non-nike-count-badge'),
                labelEl: document.getElementById('buyer-range-label'),
                idPrefix: 'buyer',
            }, p);
        }

        document.getElementById('btn-buyer-filter')?.addEventListener('click', load);
        document.getElementById('btn-buyer-reset')?.addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('buyer-date-start').value = today;
            document.getElementById('buyer-date-end').value = today;
            document.getElementById('buyer-select').value = '';
            document.getElementById('buyer-range-label').textContent = 'Hari ini';
            const msg = `<div class="formal-empty"><i class="fas fa-filter" style="font-size:24px;opacity:0.3;display:block;margin-bottom:8px;"></i>Pilih buyer lalu klik "Tampilkan"</div>`;
            document.getElementById('buyer-nike-report-content').innerHTML = msg;
            document.getElementById('buyer-non-nike-report-content').innerHTML = msg;
            document.getElementById('buyer-nike-count-badge').textContent = '0';
            document.getElementById('buyer-non-nike-count-badge').textContent = '0';
        });

        // Buyer report tidak auto-load karena butuh pilih buyer dulu — tidak perlu lazyLoad
    }

    // ─────────────────────────────────────────────────────────────
    // LOGO HELPER — fetch base64 on-demand, cache setelah pertama kali
    // Halaman HTML hanya menerima URL path (bukan base64),
    // sehingga tidak membebani load awal halaman.
    // ─────────────────────────────────────────────────────────────
    async function getLogoForPrint() {
        if (window._logoCacheB64) return window._logoCacheB64;
        try {
            const url = window.LOGO_URL; // URL path logo, di-set dari Blade
            if (!url) return null;
            const res = await fetch(url);
            const blob = await res.blob();
            return new Promise(resolve => {
                const reader = new FileReader();
                reader.onload = () => {
                    window._logoCacheB64 = reader.result; // cache untuk print berikutnya
                    resolve(reader.result);
                };
                reader.onerror = () => resolve(null);
                reader.readAsDataURL(blob);
            });
        } catch {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PRINT: NIKE PAGE
    // ─────────────────────────────────────────────────────────────
    async function printNikePage(pageNum, pageRows, dates) {
        const ROWS_PER_PAGE = 24;
        const COLS = 25;
        const LOGO_BASE64 = await getLogoForPrint();

        const start = document.getElementById('formal-date-start')?.value ||
            document.getElementById('my-date-start')?.value || '';
        const end = document.getElementById('formal-date-end')?.value ||
            document.getElementById('my-date-end')?.value || '';

        let thNums = '';
        for (let i = 1; i <= COLS; i++) thNums += `<th style="min-width:28px;font-size:9px;">${i}</th>`;

        let tbody = '';
        pageRows.forEach(row => {
            const r = row.order;
            const infoTds = row.chunkIdx === 0 ?
                `<td class="td-order" rowspan="${row.rowspan}" style="text-align:left;font-size:10px;word-break:break-all;max-width:100px;">${r.kj}</td>` +
                `<td rowspan="${row.rowspan}" style="font-size:11px;">${r.style || '-'}</td>` +
                `<td rowspan="${row.rowspan}">${r.color || '-'}</td>` +
                `<td rowspan="${row.rowspan}">${r.pcs || '-'}</td>` +
                `<td rowspan="${row.rowspan}">${r.qty_order || '-'}</td>` +
                `<td rowspan="${row.rowspan}" style="font-size:10px;">${r.gac_date || '-'}</td>` +
                `<td rowspan="${row.rowspan}" style="font-size:10px;max-width:80px;">${r.destination || '-'}</td>` +
                `<td rowspan="${row.rowspan}">${r.line || '-'}</td>` +
                `<td rowspan="${row.rowspan}">${r.carton_weight_std || '-'}</td>` : '';
            const ketTd = row.chunkIdx === 0 ?
                `<td rowspan="${row.rowspan}" style="min-width:80px;vertical-align:top;padding:4px;font-size:8px;">${(r.keterangan || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') || '-'}</td>` : '';
            tbody += `<tr>${infoTds}${row.tdBerats}<td class="td-total">${row.chunkLen}</td>${ketTd}</tr>`;
        });

        const emptyNeeded = ROWS_PER_PAGE - pageRows.length;
        if (emptyNeeded > 0) {
            const es = 'style="border:1px solid #ccc;"';
            let ei = '', eb = '';
            for (let i = 0; i < 9; i++) ei += `<td ${es}>-</td>`;
            for (let i = 0; i < COLS; i++) eb += `<td ${es}>-</td>`;
            for (let r = 0; r < emptyNeeded; r++) tbody += `<tr>${ei}${eb}<td ${es}>-</td><td ${es}></td></tr>`;
        }

        const dateLabel = start + (start !== end ? ' s/d ' + end : '');
        const printed = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const hariTanggal = start ? new Date(start).toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) : '-';
        const firstOrder = pageRows.length > 0 ? pageRows[0].order : null;
        const optQc = firstOrder?.opt_qc && firstOrder.opt_qc !== '-' ? firstOrder.opt_qc : '';
        const spvQc = firstOrder?.spv_qc && firstOrder.spv_qc !== '-' ? firstOrder.spv_qc : '';
        const chief = firstOrder?.chief && firstOrder.chief !== '-' ? firstOrder.chief : '';

        const css = '@page{size:A4 landscape;margin:6mm 8mm;}*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}table{border-spacing:0;}body{font-family:"Segoe UI",Arial,sans-serif;font-size:8px;color:#000;margin:0;padding:0;}.form-header{display:flex;align-items:stretch;border:1.5px solid #333;border-bottom:none;margin-bottom:0;}.logo-area{width:110px;min-width:110px;border-right:1px solid #333;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;gap:3px;}.title-area{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px 8px;border-right:1px solid #333;}.title-area h4{margin:0;font-size:13px;font-weight:700;text-align:center;text-transform:uppercase;}.title-area p{margin:2px 0 0;font-size:10px;text-align:center;}.doc-area{width:175px;min-width:175px;}.doc-area table{width:100%;border-collapse:collapse;height:100%;}.doc-area td{border:1px solid #ddd;padding:2px 4px;vertical-align:middle;font-size:7px;}.doc-area td:first-child{font-weight:600;white-space:nowrap;background:#f5f5f5;width:52%;}.sub-header{display:flex;align-items:stretch;border:1.5px solid #333;margin-bottom:4px;}.hari-tanggal-area{flex:1;display:flex;align-items:center;padding:4px 8px;font-size:9px;font-weight:600;border-right:1px solid #333;}.sign-area-3{width:430px;min-width:430px;display:flex;align-items:stretch;}.sign-tbl-3{width:100%;height:100%;border-collapse:collapse;font-size:7px;text-align:center;}.sign-tbl-3 th{background:#f0f0f0!important;border:1px solid #999!important;padding:2px 3px;font-weight:700;font-size:6.5px;vertical-align:middle;color:#000!important;}.sign-tbl-3 td.sign-td{border:1px solid #999;height:50px;vertical-align:bottom;padding:2px 6px;font-weight:700;font-size:7px;min-width:130px;}table.main-tbl{width:100%;border-collapse:collapse;font-size:7.5px;}table.main-tbl th{background:#435ebe!important;color:#fff!important;padding:2px 3px;border:1px solid #3551b0;text-align:center;}table.main-tbl td{padding:2px 3px;border:1px solid #ccc!important;text-align:center;vertical-align:middle;}.bg-blue{background:#2d4fad!important;}.ket-edit{display:none!important;}.ket-display{font-size:8px!important;}';

        const html = `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Carton Weight Report NIKE — Lembar ${pageNum}</title><style>${css}</style></head><body>` +
            `<div class="form-header"><div class="logo-area">${LOGO_BASE64 ? `<img src="${LOGO_BASE64}" style="width:60px;height:auto;max-height:50px;object-fit:contain;" />` : '<div class="logo-box">M</div>'}</div>` +
            `<div class="title-area"><h4>PT. Kanindo Makmur Jaya</h4><p><strong>CARTON WEIGHT REPORT</strong></p><p style="font-size:9px;color:#555;">Nike &nbsp;·&nbsp; Lembar ${pageNum}</p></div>` +
            `<div class="doc-area"><table><tr><td>No. Dokumen</td><td>&nbsp;</td></tr><tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr><tr><td>Revisi</td><td>&nbsp;</td></tr><tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr><tr><td>Departemen</td><td>&nbsp;</td></tr></table></div></div>` +
            `<div class="sub-header"><div class="hari-tanggal-area">HARI &amp; TANGGAL : ${hariTanggal}<br><span style="font-size:7.5px;font-weight:400;color:#555;">Periode: ${dateLabel} &nbsp;·&nbsp; Dicetak: ${printed}</span></div>` +
            `<div class="sign-area-3"><table class="sign-tbl-3"><thead><tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr></thead><tbody><tr><td class="sign-td">${optQc}</td><td class="sign-td">${spvQc}</td><td class="sign-td">${chief}</td></tr></tbody></table></div></div>` +
            `<table class="main-tbl"><thead><tr><th rowspan="2" style="min-width:80px;">Order No.</th><th rowspan="2">Style</th><th rowspan="2">CLR</th><th rowspan="2">Isi<br>Karton</th><th rowspan="2">Qty<br>Order</th><th rowspan="2">GAC</th><th rowspan="2">Destination</th><th rowspan="2">Dari<br>Line</th><th rowspan="2">Standar<br>Berat</th><th colspan="${COLS}" class="bg-blue">Actual Berat Karton</th><th rowspan="2">Total<br>Karton</th><th rowspan="2">Ket</th></tr><tr>${thNums}</tr></thead><tbody>${tbody}</tbody></table>` +
            `<script>window.onload=()=>{window.print();}<\/script></body></html>`;

        const blob = new Blob([html], { type: 'text/html' });
        window.open(URL.createObjectURL(blob), '_blank');
    }

    // ─────────────────────────────────────────────────────────────
    // PRINT: NON-NIKE PAGE
    // ─────────────────────────────────────────────────────────────
    async function printNonNikePage(pageNum, pageBlocks) {
        const LOGO_BASE64 = await getLogoForPrint();
        const start = document.getElementById('formal-date-start')?.value ||
            document.getElementById('my-date-start')?.value || '';
        const end = document.getElementById('formal-date-end')?.value ||
            document.getElementById('my-date-end')?.value || '';
        const COLS = 10, ROWS = 5;
        let blocksHTML = '';

        pageBlocks.forEach(block => {
            const isContinued = block.blockIdx > 0;
            const cartons = block.timbangans;
            let tbodyRows = '';

            for (let row = 0; row < ROWS; row++) {
                const startIdx = row * COLS;
                const rowCartons = cartons.slice(startIdx, startIdx + COLS);
                const padded = [...rowCartons, ...Array(COLS - rowCartons.length).fill(null)];
                const rowTotal = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
                const hasData = rowCartons.length > 0;
                const rowDate = rowCartons[0]?.waktu_timbang?.substring(0, 10) || '-';
                let tdBoxes = '', tdWeights = '';

                padded.forEach((t, ci) => {
                    const no = block.startNo + startIdx + ci;
                    tdBoxes += t ? `<td>${t.no_box || no}</td>` : `<td style="color:#ddd;">-</td>`;
                    if (t) {
                        const bv = parseFloat(t.berat);
                        const mn = parseFloat(t.rasio_batas_beban_min || 0);
                        const mx = parseFloat(t.rasio_batas_beban_max || 0);
                        let sty = '';
                        if (mn > 0 && mx > 0) {
                            if (bv < mn) sty = 'color:red;font-weight:bold;';
                            else if (bv > mx) sty = 'color:orange;font-weight:bold;';
                        }
                        tdWeights += `<td style="${sty}">${bv.toFixed(2)}</td>`;
                    } else {
                        tdWeights += `<td style="color:#ddd;">-</td>`;
                    }
                });

                tbodyRows +=
                    `<tr><td rowspan="2" style="font-size:7px;color:#555;vertical-align:middle;white-space:nowrap;">${rowDate}</td>${tdBoxes}<td rowspan="2" style="font-weight:700;vertical-align:middle;">${hasData ? rowTotal.toFixed(2) : '-'}</td>` +
                    (row === 0 ? `<td rowspan="${ROWS * 2}" style="vertical-align:top;padding:3px;font-size:7px;">${block.keterangan || '-'}</td>` : '') +
                    `</tr><tr>${tdWeights}</tr>`;
            }

            const thCols = Array.from({ length: COLS }, (_, i) => `<th>#${i + 1}</th>`).join('');
            const totalBerat = block.timbangans.reduce((s, t) => s + parseFloat(t?.berat || 0), 0).toFixed(2);
            const checkingBadge = block.checking_ke > 1 ?
                `<span style="background:#ff6b35;color:#fff;padding:1px 6px;border-radius:3px;font-size:7px;">Checking #${block.checking_ke}</span>` : '';

            blocksHTML +=
                `<div class="block-wrap"><div class="block-title">CARTON WEIGHT REPORT &nbsp;—&nbsp; Laporan Timbangan Karton${isContinued ? ' <span style="background:#fff3cd;color:#856404;padding:1px 6px;border-radius:3px;font-size:8px;">Lanjutan</span>' : ''}${checkingBadge}</div>` +
                `<div class="block-info"><div class="block-info-left"><table class="info-tbl">` +
                `<tr><td>BUYER</td><td><strong>${block.buyer}</strong></td></tr>` +
                `<tr><td>Order No.</td><td><strong>${block.kj}</strong></td></tr>` +
                `<tr><td>PO#</td><td>${block.po}</td></tr>` +
                `<tr><td>Style</td><td>${block.style}</td></tr>` +
                `<tr><td>Qty Order</td><td>${parseInt(block.qty_order || 0).toLocaleString()} pcs</td></tr>` +
                `<tr><td>Ctn / Less Ctn</td><td>${block.timbangans.length} / -</td></tr>` +
                `<tr><td>Carton Wgt Std.</td><td>${block.carton_weight_std ? parseFloat(block.carton_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                `<tr><td>Pcs Wgt Std.</td><td>${block.pcs_weight_std ? parseFloat(block.pcs_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
                `<tr><td colspan="2"><span style="font-size:8px;">${block.subcon ? 'S = <strong>' + block.subcon + '</strong>' : 'L = <strong>' + (block.line || '-') + '</strong>'} &nbsp;&nbsp; M = <strong>${block.pcs_default}</strong></span></td></tr>` +
                `</table></div><div class="block-info-right"><table class="info-tbl">` +
                `<tr><td>GAC date</td><td>${block.gac_date}</td></tr>` +
                `<tr><td>Destination</td><td>${block.destination}</td></tr>` +
                `<tr><td>Inspector</td><td>${block.inspector}</td></tr>` +
                `</table><table class="sign-tbl"><tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr>` +
                `<tr><td style="height:20px;vertical-align:bottom;">${block.opt_qc !== '-' ? block.opt_qc : ''}</td>` +
                `<td style="height:20px;vertical-align:bottom;">${block.spv_qc !== '-' ? block.spv_qc : ''}</td>` +
                `<td style="height:20px;vertical-align:bottom;">${block.chief !== '-' ? block.chief : ''}</td></tr></table>` +
                `</div></div>` +
                `<table class="carton-tbl"><thead><tr><th rowspan="2">Date</th><th colspan="${COLS}">Ctn. No &amp; Weight (Kg)</th><th rowspan="2">Total (kg)</th><th rowspan="2">Remark</th></tr><tr>${thCols}</tr></thead><tbody>${tbodyRows}</tbody></table></div>`;
        });

        const dateLabel = start + (start !== end ? ' s/d ' + end : '');
        const css = `<style>@page{size:210mm 330mm;margin:4mm 6mm;}*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}body{font-family:"Segoe UI",Arial,sans-serif;font-size:7px;color:#000;margin:0;padding:0;}.page-grid{display:grid;grid-template-columns:1fr;grid-template-rows:repeat(4,1fr);gap:1mm;height:calc(310mm - 22mm);}.block-wrap{border:1px solid #999;border-radius:2px;overflow:hidden;display:flex;flex-direction:column;min-height:0;}.block-title{background:#435ebe!important;color:#fff!important;text-align:center;font-size:7px;font-weight:700;padding:1px 3px;}.block-info{display:flex;border-bottom:1px solid #ccc;}.block-info-left{width:55%;padding:1px 3px;border-right:1px solid #ccc;}.block-info-right{width:45%;padding:1px 3px;display:flex;flex-direction:column;}.info-tbl{width:100%;border-collapse:collapse;font-size:6.5px;}.info-tbl td{padding:0 2px;border:none;line-height:1.3;}.info-tbl td:first-child{color:#555;width:38%;font-weight:600;white-space:nowrap;}.info-tbl td:last-child{font-weight:600;}.sign-tbl{width:100%;border-collapse:collapse;font-size:6px;text-align:center;margin-top:2px;}.sign-tbl th{background:#f0f0f0!important;border:1px solid #999!important;padding:1px;font-weight:700;font-size:6px;}.sign-tbl td{border:1px solid #999!important;height:28px!important;vertical-align:bottom!important;padding:2px!important;font-weight:700;font-size:6.5px;line-height:normal!important;}.carton-tbl{width:100%;border-collapse:collapse;font-size:6px;flex:1;}.carton-tbl th{background:#435ebe!important;color:#fff!important;border:1px solid #000!important;padding:1px 0!important;text-align:center;font-weight:700;line-height:1.1;}.carton-tbl td{border:1px solid #ccc!important;padding:0px 0!important;text-align:center;vertical-align:middle;line-height:0;font-size:7px;}.carton-tbl tbody tr{height:auto!important;}.form-header{display:flex;align-items:stretch;border:1.5px solid #333;margin-bottom:5px;}.logo-area{width:110px;min-width:110px;border-right:1px solid #333;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px;gap:3px;}.title-area{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4px 8px;border-right:1px solid #333;}.title-area h4{margin:0;font-size:13px;font-weight:700;text-align:center;text-transform:uppercase;}.title-area p{margin:2px 0 0;font-size:10px;text-align:center;}.doc-area{width:175px;min-width:175px;}.doc-area table{width:100%;border-collapse:collapse;height:100%;}.doc-area td{border:1px solid #ddd;padding:2px 4px;vertical-align:middle;font-size:7px;}.doc-area td:first-child{font-weight:600;white-space:nowrap;background:#f5f5f5;width:52%;}</style>`;

        const html = `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Carton Weight Report NON-NIKE — Hal. ${pageNum}</title>${css}</head><body>` +
            `<div class="form-header"><div class="logo-area">${LOGO_BASE64 ? `<img src="${LOGO_BASE64}" style="width:60px;height:auto;max-height:50px;object-fit:contain;" />` : '<div class="logo-box">M</div>'}</div>` +
            `<div class="title-area"><h4>PT. Kanindo Makmur Jaya</h4><p><strong>CARTON WEIGHT REPORT</strong></p><p style="font-size:9px;color:#555;">Non-Nike &nbsp;·&nbsp; Lembar ${pageNum}</p></div>` +
            `<div class="doc-area"><table><tr><td>No. Dokumen</td><td>&nbsp;</td></tr><tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr><tr><td>Revisi</td><td>&nbsp;</td></tr><tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr><tr><td>Departemen</td><td>&nbsp;</td></tr></table></div></div>` +
            `<div class="page-grid">${blocksHTML}</div>` +
            `<script>window.onload=()=>{window.print();}<\/script></body></html>`;

        const blob = new Blob([html], { type: 'text/html' });
        window.open(URL.createObjectURL(blob), '_blank');
    }

    // ─────────────────────────────────────────────────────────────
    // PRINT: SINGLE BLOCK (Non-Nike)
    // ─────────────────────────────────────────────────────────────
    window.printSingleBlock = async function (btn) {
        const LOGO_BASE64 = await getLogoForPrint();
        const block = JSON.parse(btn.dataset.block);
        const COLS = 10, ROWS = 5;
        const isContinued = block.blockIdx > 0;
        const cartons = block.timbangans;
        let tbodyRows = '';

        for (let row = 0; row < ROWS; row++) {
            const startIdx = row * COLS;
            const rowCartons = cartons.slice(startIdx, startIdx + COLS);
            const padded = [...rowCartons, ...Array(COLS - rowCartons.length).fill(null)];
            const rowTotal = rowCartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0);
            const hasData = rowCartons.length > 0;
            const rowDate = rowCartons[0]?.waktu_timbang?.substring(0, 10) || '-';
            let tdBoxes = '', tdWeights = '';

            padded.forEach((t, ci) => {
                const no = block.startNo + startIdx + ci;
                tdBoxes += t ? `<td>${t.no_box || no}</td>` : `<td style="color:#ddd;">-</td>`;
                if (t) {
                    const bv = parseFloat(t.berat);
                    const mn = parseFloat(t.rasio_batas_beban_min || 0);
                    const mx = parseFloat(t.rasio_batas_beban_max || 0);
                    let sty = '';
                    if (mn > 0 && mx > 0) {
                        if (bv < mn) sty = 'color:red;font-weight:bold;';
                        else if (bv > mx) sty = 'color:orange;font-weight:bold;';
                    }
                    tdWeights += `<td style="${sty}">${bv.toFixed(2)}</td>`;
                } else {
                    tdWeights += `<td style="color:#ddd;">-</td>`;
                }
            });

            tbodyRows +=
                `<tr><td rowspan="2" style="font-size:7px;color:#555;vertical-align:middle;white-space:nowrap;">${rowDate}</td>${tdBoxes}<td rowspan="2" style="font-weight:700;vertical-align:middle;">${hasData ? rowTotal.toFixed(2) : '-'}</td>` +
                (row === 0 ? `<td rowspan="${ROWS * 2}" style="vertical-align:top;padding:3px;font-size:7px;">${block.keterangan || '-'}</td>` : '') +
                `</tr><tr>${tdWeights}</tr>`;
        }

        const thCols = Array.from({ length: COLS }, (_, i) => `<th>#${i + 1}</th>`).join('');
        const totalBerat = cartons.reduce((s, t) => s + parseFloat(t?.berat || 0), 0).toFixed(2);
        const printed = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

        const css = `@page{size:210mm 140mm;margin:4mm 6mm;}*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}body{font-family:"Segoe UI",Arial,sans-serif;font-size:7px;color:#000;margin:0;padding:0;}.form-header{display:flex;align-items:stretch;border:1.5px solid #333;margin-bottom:3px;}.logo-area{width:90px;min-width:90px;border-right:1px solid #333;display:flex;align-items:center;justify-content:center;padding:3px;}.title-area{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3px 6px;border-right:1px solid #333;}.title-area h4{margin:0;font-size:10px;font-weight:700;text-align:center;text-transform:uppercase;}.title-area p{margin:1px 0 0;font-size:8px;text-align:center;}.doc-area{width:150px;min-width:150px;}.doc-area table{width:100%;border-collapse:collapse;height:100%;}.doc-area td{border:1px solid #ddd;padding:1px 3px;vertical-align:middle;font-size:6.5px;}.doc-area td:first-child{font-weight:600;white-space:nowrap;background:#f5f5f5;width:52%;}.block-wrap{border:1px solid #999;border-radius:2px;overflow:hidden;}.block-title{background:#435ebe!important;color:#fff!important;text-align:center;font-size:8px;font-weight:700;padding:2px 4px;}.block-info{display:flex;border-bottom:1px solid #ccc;}.block-info-left{width:55%;padding:2px 4px;border-right:1px solid #ccc;}.block-info-right{width:45%;padding:2px 4px;display:flex;flex-direction:column;}.info-tbl{width:100%;border-collapse:collapse;font-size:7px;}.info-tbl td{padding:0 2px;border:none;line-height:1.4;}.info-tbl td:first-child{color:#555;width:38%;font-weight:600;white-space:nowrap;}.info-tbl td:last-child{font-weight:600;}.sign-tbl{width:100%;border-collapse:collapse;font-size:7px;text-align:center;margin-top:3px;}.sign-tbl th{background:#f0f0f0!important;border:1px solid #999!important;padding:1px;font-weight:700;font-size:7px;}.sign-tbl td{border:1px solid #999!important;height:30px!important;vertical-align:bottom!important;padding:2px!important;font-weight:700;font-size:7px;}.carton-tbl{width:100%;border-collapse:collapse;font-size:7px;}.carton-tbl th{background:#435ebe!important;color:#fff!important;border:1px solid #000!important;padding:1px 0!important;text-align:center;font-weight:700;}.carton-tbl td{border:1px solid #ccc!important;padding:1px 0!important;text-align:center;vertical-align:middle;font-size:7px;}`;

        const html = `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Block — ${block.kj}</title><style>${css}</style></head><body>` +
            `<div class="form-header"><div class="logo-area">${LOGO_BASE64 ? `<img src="${LOGO_BASE64}" style="width:55px;height:auto;max-height:40px;object-fit:contain;" />` : '<div style="font-size:18px;font-weight:900;color:#1a3a7a;">M</div>'}</div>` +
            `<div class="title-area"><h4>PT. Kanindo Makmur Jaya</h4><p><strong>CARTON WEIGHT REPORT</strong></p><p style="font-size:7px;color:#555;">Non-Nike &nbsp;·&nbsp; Dicetak: ${printed}</p></div>` +
            `<div class="doc-area"><table><tr><td>No. Dokumen</td><td>&nbsp;</td></tr><tr><td>Tgl. Terbit</td><td>&nbsp;</td></tr><tr><td>Revisi</td><td>&nbsp;</td></tr><tr><td>Tgl. Efektif</td><td>&nbsp;</td></tr><tr><td>Departemen</td><td>&nbsp;</td></tr></table></div></div>` +
            `<div class="block-wrap"><div class="block-title">CARTON WEIGHT REPORT &nbsp;—&nbsp; Laporan Timbangan Karton${isContinued ? ' <span style="background:#fff3cd;color:#856404;padding:1px 6px;border-radius:3px;font-size:7px;">Lanjutan</span>' : ''}${block.checking_ke > 1 ? `<span style="background:#ff6b35;color:#fff;padding:1px 6px;border-radius:3px;font-size:8px;">Checking #${block.checking_ke}</span>` : ''}</div>` +
            `<div class="block-info"><div class="block-info-left"><table class="info-tbl">` +
            `<tr><td>BUYER</td><td><strong>${block.buyer}</strong></td></tr>` +
            `<tr><td>Order No.</td><td><strong>${block.kj}</strong></td></tr>` +
            `<tr><td>PO#</td><td>${block.po}</td></tr>` +
            `<tr><td>Style</td><td>${block.style}</td></tr>` +
            `<tr><td>Color</td><td>${block.color}</td></tr>` +
            `<tr><td>Qty Order</td><td>${parseInt(block.qty_order || 0).toLocaleString()} pcs</td></tr>` +
            `<tr><td>Ctn / Less Ctn</td><td>${cartons.length} / -</td></tr>` +
            `<tr><td>Carton Wgt Std.</td><td>${block.carton_weight_std ? parseFloat(block.carton_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
            `<tr><td>Pcs Wgt Std.</td><td>${block.pcs_weight_std ? parseFloat(block.pcs_weight_std).toFixed(2) + ' kg' : '-'}</td></tr>` +
            `<tr><td colspan="2"><span style="font-size:7px;">${block.subcon ? 'S = <strong>' + block.subcon + '</strong>' : 'L = <strong>' + (block.line || '-') + '</strong>'} &nbsp; M = <strong>${block.pcs_default}</strong></span></td></tr>` +
            `</table></div><div class="block-info-right"><table class="info-tbl">` +
            `<tr><td>GAC date</td><td>${block.gac_date}</td></tr>` +
            `<tr><td>Destination</td><td>${block.destination}</td></tr>` +
            `<tr><td>Inspector</td><td>${block.inspector}</td></tr>` +
            `<tr><td>Total Carton</td><td><strong>${cartons.length}</strong> / ${block.totalCartonInLine}</td></tr>` +
            `</table><table class="sign-tbl"><tr><th>OPT QC TIMBANGAN</th><th>SPV QC</th><th>CHIEF FINISH GOOD</th></tr>` +
            `<tr><td style="height:35px;vertical-align:bottom;">${block.opt_qc !== '-' ? block.opt_qc : ''}</td>` +
            `<td style="height:35px;vertical-align:bottom;">${block.spv_qc !== '-' ? block.spv_qc : ''}</td>` +
            `<td style="height:35px;vertical-align:bottom;">${block.chief !== '-' ? block.chief : ''}</td></tr></table></div></div>` +
            `<table class="carton-tbl"><thead><tr><th rowspan="2">Date</th><th colspan="${COLS}">Ctn. No &amp; Weight (Kg)</th><th rowspan="2">Total (kg)</th><th rowspan="2">Remark</th></tr><tr>${thCols}</tr></thead><tbody>${tbodyRows}</tbody></table></div>` +
            `<script>window.onload=()=>{window.print();};<\/script></body></html>`;

        const win = window.open('', '_blank');
        win.document.write(html);
        win.document.close();
    };

    // ─────────────────────────────────────────────────────────────
    // INLINE EDIT KETERANGAN
    // ─────────────────────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.ket-save-btn');
        if (!btn) return;
        const ordersheetId = btn.dataset.ordersheetId;
        if (!ordersheetId) { alert('ID Ordersheet tidak ditemukan.'); return; }
        const wrap = btn.closest('td, div');
        const input = wrap?.querySelector('.ket-input') ?? btn.parentElement?.querySelector('.ket-input');
        const statusEl = wrap?.querySelector('.ket-status') ?? btn.parentElement?.querySelector('.ket-status');
        if (!input) return;
        const keterangan = input.value.trim();
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '...';
        try {
            const res = await fetch('/user/order/update-keterangan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ordersheet_id: ordersheetId, keterangan }),
            });
            const json = await res.json();
            if (json.success) {
                const cell = btn.closest('.ket-cell');
                if (cell) {
                    const displayEl = cell.querySelector('.ket-display');
                    if (displayEl) displayEl.innerHTML = keterangan || '<span style="color:#bbb;font-style:italic;">—</span>';
                    cell.querySelector('.ket-edit').style.display = 'none';
                    cell.querySelector('.ket-display').style.display = 'block';
                }
                if (statusEl) {
                    statusEl.textContent = '✓ Tersimpan';
                    statusEl.style.color = 'green';
                    statusEl.style.display = 'inline';
                    setTimeout(() => { statusEl.style.display = 'none'; }, 2500);
                }
            } else {
                throw new Error(json.message || 'Gagal');
            }
        } catch (err) {
            if (statusEl) { statusEl.textContent = '✗ ' + err.message; statusEl.style.color = 'red'; statusEl.style.display = 'inline'; }
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.classList.contains('ket-input')) {
            e.preventDefault();
            e.target.closest('td, div')?.querySelector('.ket-save-btn')?.click();
        }
    });

    document.addEventListener('click', function (e) {
        const display = e.target.closest('.ket-display');
        if (!display) return;
        const cell = display.closest('.ket-cell');
        display.style.display = 'none';
        const editDiv = cell.querySelector('.ket-edit');
        editDiv.style.display = 'flex';
        editDiv.querySelector('.ket-input')?.focus();
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.ket-cancel-btn');
        if (!btn) return;
        const cell = btn.closest('.ket-cell');
        cell.querySelector('.ket-edit').style.display = 'none';
        cell.querySelector('.ket-display').style.display = 'block';
    });

    // ─────────────────────────────────────────────────────────────
    // BOOT
    // ─────────────────────────────────────────────────────────────
    function boot() {
        initMyReport();
        initFormalReport();
        initBuyerReport();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();
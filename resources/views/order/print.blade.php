<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carton Weight Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

    <style>
        :root {
            --border: #ccc;
            --muted: #555;
            --bg: #fff;
            --light-bg: #f2f2f2;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: #222;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .page {
            width: 297mm;
            min-height: 210mm;
            background: white;
            padding: 10mm 12mm;
            margin: 0 auto;
            box-sizing: border-box;
            page-break-after: always;
            position: relative;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .two-columns {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .col-left {
            display: table-cell;
            width: 58%;
            padding-right: 8px;
        }

        .col-right {
            display: table-cell;
            width: 42%;
            padding-left: 8px;
        }

        .report-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .report-title h5 {
            font-weight: 700;
            margin: 0;
            font-size: 15px;
        }

        .report-title h6 {
            color: var(--muted);
            margin: 3px 0 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table th {
            background: var(--light-bg);
            font-weight: 600;
            padding: 5px 8px;
            border: 1px solid var(--border);
        }

        table td {
            border: 1px solid var(--border);
            padding: 5px 8px;
            word-wrap: break-word;
        }

        .qty-sub {
            display: flex;
            gap: 10px;
            margin-top: 3px;
            font-size: 11px;
            color: #555;
            flex-wrap: wrap;
        }

        .sign-name {
            font-weight: 600;
            margin-top: 45px;
            display: inline-block;
        }

        /* ===== TABEL CARTON ===== */
        table.carton {
            margin-top: 10px;
            border: 1px solid var(--border);
            font-size: 11px;
        }

        table.carton th,
        table.carton td {
            padding: 4px 5px;
            text-align: center;
        }

        table.carton thead th {
            background: var(--light-bg);
        }

        .weight-val {
            font-weight: 700;
            color: #0d47a1;
        }

        .box-val {
            font-weight: 600;
        }

        .status-kurang {
            color: #c62828;
            font-weight: 700;
        }

        .status-lebih {
            color: #e65100;
            font-weight: 700;
        }

        .status-normal {
            color: #2e7d32;
        }

        /* ===== LINE SECTION SEPARATOR ===== */
        .line-section {
            margin-top: 12px;
        }

        .line-header {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 4px;
            padding: 4px 10px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .line-header strong {
            font-size: 12px;
            color: #0d47a1;
        }

        .line-stat {
            font-size: 11px;
            color: #555;
        }

        /* ===== SUMMARY BOX ===== */
        .summary-box {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .summary-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 10px;
            text-align: center;
            background: #fafafa;
            flex: 1;
            min-width: 80px;
        }

        .summary-item .val {
            font-weight: 700;
            font-size: 13px;
            color: #1565c0;
        }

        .summary-item .lbl {
            font-size: 10px;
            color: #777;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 10.5px;
            color: #555;
        }

        .kj-divider {
            border: 0;
            border-top: 2px solid #333;
            margin: 0 0 10px 0;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            body,
            html {
                margin: 0;
                padding: 0;
                background: white;
            }

            .page {
                padding: 10mm 12mm;
                page-break-after: always;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body onload="window.print()">

    @forelse ($groupedByKJ as $kj => $kjData)

        <div class="page">

            {{-- ===== HEADER ===== --}}
            <div class="report-title">
                <div style="display: table; width: 100%;">
                    <div style="display: table-cell; width: 30%; text-align: left; vertical-align: middle;">
                        <strong style="font-size: 13px;">PT. KANINDO MAKMUR JAYA</strong>
                    </div>
                    <div style="display: table-cell; width: 40%; text-align: center;">
                        <h5 class="mb-0">CARTON WEIGHT REPORT</h5>
                        <h6>Laporan Timbangan Karton</h6>
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <span style="font-size: 11px; color: #555;">Tgl Cetak: {{ now()->format('d-m-Y H:i') }}</span>
                    </div>
                </div>
                <hr class="kj-divider">
            </div>

            {{-- ===== INFO ORDER (2 KOLOM) ===== --}}
            <div class="two-columns">
                <div class="col-left">
                    <table>
                        <tr>
                            <th width="38%">Buyer</th>
                            <td>{{ $kjData['buyer'] }}</td>
                        </tr>
                        <tr>
                            <th>KJ / Order No.</th>
                            <td>
                                <strong>{{ $kjData['kj'] }}</strong>
                                @if ($kjData['kj'] !== $kjData['order_code'])
                                    <span style="color:#555;font-size:11px;"> ({{ $kjData['order_code'] }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>PO#</th>
                            <td>{{ $kjData['po'] }}</td>
                        </tr>
                        <tr>
                            <th>Style</th>
                            <td>{{ $kjData['style'] }}</td>
                        </tr>
                        <tr>
                            <th>Line</th>
                            <td>
                                @foreach ($kjData['line_list'] as $ln)
                                    <span
                                        style="background:#e3f2fd; border:1px solid #90caf9; border-radius:3px; padding:1px 6px; margin-right:4px; font-size:11px;">
                                        Line {{ $ln }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <th>Qty Order</th>
                            <td>
                                <strong>{{ number_format($kjData['qty_total']) }} pcs</strong>
                                <div class="qty-sub">
                                    <span><strong>Ditimbang:</strong> {{ number_format($kjData['qty_sudah']) }}
                                        pcs</span>
                                    <span><strong>Total Carton:</strong> {{ $kjData['total_carton'] }}</span>
                                    <span><strong>Less Ctn:</strong> {{ $kjData['less_ctn'] ?? '-' }}</span>
                                    <span><strong>Pcs Less:</strong> {{ $kjData['pcs_less_ctn'] ?? '-' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Carton Weight Std.</th>
                            <td>{{ $kjData['carton_std'] ? number_format($kjData['carton_std'], 2) . ' kg' : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Pcs Weight Std.</th>
                            <td>{{ $kjData['pcs_std'] ? number_format($kjData['pcs_std'], 2) . ' kg' : '-' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-right">
                    <table>
                        <tr>
                            <th width="40%">GAC Date</th>
                            <td>{{ $kjData['gac_date'] }}</td>
                        </tr>
                        <tr>
                            <th>Destination</th>
                            <td>{{ $kjData['destination'] }}</td>
                        </tr>
                        <tr>
                            <th>Inspector</th>
                            <td>{{ $kjData['inspector'] }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Timbang</th>
                            <td>{{ $kjData['date'] }}</td>
                        </tr>
                    </table>

                    {{-- Summary total --}}
                    <div class="summary-box">
                        <div class="summary-item">
                            <div class="val">{{ $kjData['total_carton'] }}</div>
                            <div class="lbl">Total Carton</div>
                        </div>
                        <div class="summary-item">
                            <div class="val">{{ number_format($kjData['qty_sudah']) }}</div>
                            <div class="lbl">Pcs Ditimbang</div>
                        </div>
                        <div class="summary-item">
                            <div class="val">{{ number_format($kjData['total_berat'], 2) }} kg</div>
                            <div class="lbl">Total Berat</div>
                        </div>
                    </div>

                    {{-- Tanda tangan --}}
                    <table style="margin-top: 10px;">
                        <thead>
                            <tr class="text-center">
                                <th>OPT QC TIMBANGAN</th>
                                <th>SPV QC</th>
                                <th>CHIEF FINISH GOOD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="height: 70px; vertical-align: bottom; text-align: center;">
                                <td>
                                    <div class="sign-name">{{ $kjData['opt_qc'] }}</div>
                                </td>
                                <td>
                                    <div class="sign-name">
                                        {{ $kjData['spv_qc'] !== '-' ? $kjData['spv_qc'] : '________________' }}</div>
                                </td>
                                <td>
                                    <div class="sign-name">
                                        {{ $kjData['chief'] !== '-' ? $kjData['chief'] : '________________' }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== TABEL CARTON PER LINE ===== --}}
            @foreach ($kjData['by_line'] as $lineNo => $lineOrders)
                @php
                    $lineTimbangans = $lineOrders->flatMap(fn($o) => $o->timbangans)->sortBy('waktu_timbang')->values();
                    $lineQty = $lineTimbangans->sum('pcs');
                    $lineBerat = $lineTimbangans->sum('berat');
                    $lineCarton = $lineTimbangans->count();

                    $boxes = $lineTimbangans->pluck('no_box')->toArray();
                    $weights = $lineTimbangans->pluck('berat')->toArray();
                    $pcsList = $lineTimbangans->pluck('pcs')->toArray();
                    $minList = $lineTimbangans->pluck('rasio_batas_beban_min')->toArray();
                    $maxList = $lineTimbangans->pluck('rasio_batas_beban_max')->toArray();

                    $boxChunks = array_chunk($boxes, 10);
                    $weightChunks = array_chunk($weights, 10);
                    $pcsChunks = array_chunk($pcsList, 10);
                    $minChunks = array_chunk($minList, 10);
                    $maxChunks = array_chunk($maxList, 10);
                @endphp

                <div class="line-section">
                    {{-- Line header --}}
                    <div class="line-header">
                        <strong>Line {{ $lineNo }}</strong>
                        <span class="line-stat">
                            {{ $lineCarton }} carton ·
                            {{ number_format($lineQty) }} pcs ·
                            Total berat: <strong>{{ number_format($lineBerat, 2) }} kg</strong>
                        </span>
                    </div>

                    <table class="carton">
                        <thead>
                            <tr>
                                <th rowspan="2" style="vertical-align: middle; width: 65px;">Date</th>
                                @for ($i = 0; $i < 10; $i++)
                                    <th style="width: 60px;">Ctn. No</th>
                                @endfor
                                <th rowspan="2" style="vertical-align: middle; width: 60px;">Total (kg)</th>
                                <th rowspan="2" style="vertical-align: middle; width: 60px;">Remark</th>
                            </tr>
                            <tr>
                                @for ($i = 0; $i < 10; $i++)
                                    <th>Weight</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($boxChunks as $i => $chunk)
                                @php
                                    $boxRow = array_pad($chunk, 10, '-');
                                    $weightRow = array_pad($weightChunks[$i] ?? [], 10, '-');
                                    $minRow = array_pad($minChunks[$i] ?? [], 10, 0);
                                    $maxRow = array_pad($maxChunks[$i] ?? [], 10, 0);

                                    $totalWeight = array_sum(array_filter($weightRow, fn($v) => is_numeric($v)));
                                @endphp

                                {{-- Baris Ctn No --}}
                                <tr>
                                    <td rowspan="2" style="vertical-align: middle; font-size: 10px;">
                                        {{ $kjData['date'] }}
                                    </td>

                                    @foreach ($boxRow as $box)
                                        <td class="box-val">{{ $box }}</td>
                                    @endforeach

                                    <td rowspan="2" class="weight-val" style="vertical-align: middle;">
                                        {{ $totalWeight > 0 ? number_format($totalWeight, 2) : '-' }}
                                    </td>
                                    <td rowspan="2" style="vertical-align: middle;"></td>
                                </tr>

                                {{-- Baris Weight --}}
                                <tr>
                                    @foreach ($weightRow as $wi => $w)
                                        @php
                                            $min = floatval($minRow[$wi] ?? 0);
                                            $max = floatval($maxRow[$wi] ?? 0);
                                            $wf = floatval($w);
                                            $cls = '';
                                            if (is_numeric($w) && $min > 0 && $max > 0) {
                                                if ($wf < $min) {
                                                    $cls = 'status-kurang';
                                                } elseif ($wf > $max) {
                                                    $cls = 'status-lebih';
                                                } else {
                                                    $cls = 'status-normal';
                                                }
                                            }
                                        @endphp
                                        <td class="weight-val {{ $cls }}">
                                            {{ is_numeric($w) ? number_format($w, 2) : $w }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{-- ===== FOOTER ===== --}}
            <div class="footer-row">
                <div>
                    <small>Warna merah = berat kurang dari batas min · Warna oranye = berat melebihi batas max</small>
                </div>
                <div>
                    <small>Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $auth->username ?? '-' }}</small>
                </div>
            </div>

        </div>

    @empty
        <div class="page text-center" style="padding-top: 40mm;">
            <h5>Belum Ada Data untuk Dicetak</h5>
        </div>
    @endforelse

</body>

</html>

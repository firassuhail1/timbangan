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
            --light-bg: #f9f9f9;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: #222;
            margin: 0;
            padding: 0;
            font-size: 13px;
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
            width: 60%;
            padding-right: 8px;
        }

        .col-right {
            display: table-cell;
            width: 40%;
            padding-left: 8px;
        }

        .report-title {
            text-align: center;
            margin-bottom: 12px;
        }

        .report-title h5 {
            font-weight: 700;
            margin: 0;
            font-size: 16px;
        }

        .report-title h6 {
            color: var(--muted);
            margin: 4px 0 0;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table th {
            background: var(--light-bg);
            font-weight: 600;
            padding: 6px 10px;
            border: 1px solid var(--border);
        }

        table td {
            border: 1px solid var(--border);
            padding: 6px 10px;
            word-wrap: break-word;
        }

        .qty-sub {
            display: flex;
            gap: 12px;
            margin-top: 4px;
            font-size: 12px;
            color: #555;
            flex-wrap: wrap;
        }

        .sign-name {
            font-weight: 600;
            margin-top: 50px;
            display: inline-block;
        }

        table.carton {
            margin-top: 12px;
            border: 1px solid var(--border);
        }

        table.carton th,
        table.carton td {
            padding: 5px 6px;
            text-align: center;
        }

        .weight-row td {
            font-weight: 600;
            color: #111;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 11.5px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            color: #fff;
            font-size: 11px;
        }

        .badge-warning {
            background: #f0ad4e;
        }

        .badge-info {
            background: #0d6efd;
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

            .col-left {
                width: 60% !important;
            }

            .col-right {
                width: 40% !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>

<body onload="window.print()">

    @forelse ($groupedOrders as $groupKey => $orders)
        @php
            [$date, $buyer] = explode('|', $groupKey);
            $firstItem = $orders->first();
        @endphp

        <div class="page">
            <div class="card">
                <div class="card-body">
                    <!-- JUDUL -->
                    <div class="report-title">
                        <div class="row align-items-center text-center">
                            <!-- Kiri -->
                            <div class="col-md-3 text-start fw-bold">
                                PT. KANINDO MAKMUR JAYA
                            </div>

                            <!-- Tengah -->
                            <div class="col-md-6">
                                <h5 class="mb-1 fw-bold">CARTON WEIGHT REPORT</h5>
                                <h6 class="text-muted mb-0">Laporan Timbangan Karton</h6>
                            </div>

                            <!-- Kanan -->
                            <div class="col-md-3">
                                <!-- Kosong untuk keseimbangan -->
                            </div>
                        </div>

                        <!-- Garis pemisah penuh -->
                        <hr style="border: 0; border-top: 1px solid #7b7b7b; margin: 10px 0;">
                    </div>

                    <!-- 2 KOLOM -->
                    <div class="two-columns">
                        <div class="col-left">
                            <table>
                                <tr>
                                    <th>Buyer</th>
                                    <td>{{ $buyer }}</td>
                                </tr>
                                <tr>
                                    <th>Order No.</th>
                                    <td>{{ $firstItem->Order_code }}</td>
                                </tr>
                                <tr>
                                    <th>PO#</th>
                                    <td>{{ $firstItem->PO }}</td>
                                </tr>
                                <tr>
                                    <th>Style</th>
                                    <td>{{ $firstItem->Style }}</td>
                                </tr>
                                <tr>
                                    <th>Qty Order</th>
                                    <td>
                                        <strong>{{ $firstItem->Qty_order }}</strong>
                                        <div class="qty-sub">
                                            <span><strong>Pcs:</strong> {{ $firstItem->Qty_order }}</span>
                                            <span><strong>Ctn:</strong>
                                                {{ $orders->sum(fn($o) => $o->timbangans->count()) }}</span>
                                            <span><strong>Less Ctn:</strong> {{ $firstItem->Less_Ctn }}</span>
                                            <span><strong>Pcs Less:</strong> {{ $firstItem->Pcs_Less_Ctn }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Carton Weight Std.</th>
                                    <td>{{ number_format($firstItem->Carton_weight_std, 0) }}</td>
                                </tr>
                                <tr>
                                    <th>Pcs Weight Std.</th>
                                    <td>{{ number_format($firstItem->Pcs_weight_std, 0) }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-right">
                            <table>
                                <tr>
                                    <th>GAC Date</th>
                                    <td>{{ $date }}</td>
                                </tr>
                                <tr>
                                    <th>Destination</th>
                                    <td>{{ $firstItem->Destination }}</td>
                                </tr>
                                <tr>
                                    <th>Inspector</th>
                                    <td>{{ $firstItem->Inspector }}</td>
                                </tr>
                            </table>

                            <table style="margin-top: 10px;">
                                <thead>
                                    <tr class="text-center">
                                        <th>OPT QC TIMBANGAN</th>
                                        <th>SPV QC</th>
                                        <th>CHIEF FINISH GOOD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-center" style="height: 80px; vertical-align: bottom;">
                                        <td>
                                            <div class="sign-name">{{ $auth->username ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="sign-name">________________</div>
                                        </td>
                                        <td>
                                            <div class="sign-name">________________</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TABEL TIMBANGAN (Gabung semua orders per buyer) -->
                    <table class="carton">
                        <thead>
                            <tr>
                                <th rowspan="2">Date</th>
                                @for ($i = 0; $i < 10; $i++)
                                    <th>Ctn. No</th>
                                @endfor
                                <th rowspan="2">Total</th>
                                <th rowspan="2">Remark</th>
                            </tr>
                            <tr>
                                @for ($i = 0; $i < 10; $i++)
                                    <th>Weight</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $item)
                                @php
                                    $timbangans = $item->timbangans->take(10);
                                    $noBoxes = $timbangans->pluck('no_box')->pad(10, '-')->toArray();
                                    $weights = $timbangans
                                        ->pluck('berat')
                                        ->map(fn($w) => is_numeric($w) ? number_format($w, 2) : '-')
                                        ->pad(10, '-')
                                        ->toArray();
                                    $total = array_sum(
                                        array_filter($timbangans->pluck('berat')->toArray(), 'is_numeric'),
                                    );
                                @endphp

                                <tr>
                                    <td rowspan="2">{{ $date }}</td>
                                    @foreach ($noBoxes as $no)
                                        <td>{{ $no }}</td>
                                    @endforeach
                                    <td rowspan="2">{{ number_format($total, 2) }}</td>
                                    <td rowspan="2"></td>
                                </tr>
                                <tr class="weight-row">
                                    @foreach ($weights as $w)
                                        <td>{{ $w }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- FOOTER -->
                    <div class="footer-row">
                        <div><small>Note: Kolom Ctn. No dan Weight mengikuti urutan pengisian timbangan.</small></div>
                        {{-- <div><span class="badge badge-info">Pengecekan Data Diri</span></div> --}}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="page text-center p-5">
            <h5>Belum Ada Data untuk Dicetak</h5>
        </div>
    @endforelse

</body>

</html>

<?php

namespace App\Exports;

use App\Models\Ordersheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapOrdersheetExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $userId;
    protected $startDate;
    protected $endDate;

    public function __construct($search = null, $userId = null, $startDate = null, $endDate = null)
    {
        $this->search    = $search;
        $this->userId    = $userId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function query()
    {
        $query = Ordersheet::with(['timbangans', 'device', 'user'])
            ->where('status', 'Success')
            ->orderBy('id', 'desc');

        // Filter search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('Order_code', 'like', "%{$this->search}%")
                    ->orWhere('Buyer', 'like', "%{$this->search}%")
                    ->orWhere('PO', 'like', "%{$this->search}%")
                    ->orWhere('Style', 'like', "%{$this->search}%")
                    ->orWhere('Destination', 'like', "%{$this->search}%");
            });
        }

        // Filter user
        if ($this->userId) {
            $query->where('id_user', $this->userId);
        }

        // Filter tanggal
        if ($this->startDate && $this->endDate) {
            $query->whereDate('created_at', '>=', $this->startDate)
                ->whereDate('created_at', '<=', $this->endDate);
        } elseif ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Timbangan (ESP ID)',
            'User (OPT QC)',
            'Tanggal',
            'Jam',
            'Order Code',
            'Buyer',
            'PO',
            'No. Box',
            'Weight (kg)',
            'Qty Order',
            'Status',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        $tanggal = $item->created_at ? $item->created_at->format('d-m-Y') : '-';
        $jam     = $item->created_at ? $item->created_at->format('H:i') : '-';

        $noBox   = $item->timbangans->pluck('no_box')->implode("\n") ?: '-';
        $berat   = $item->timbangans->map(fn($t) => number_format($t->berat, 2) . ' kg')->implode("\n") ?: '-';

        return [
            $no,
            $item->device->esp_id ?? '-',
            $item->user->username ?? '-',
            $tanggal,
            $jam,
            $item->Order_code ?? '-',
            $item->Buyer ?? '-',
            $item->PO ?? '-',
            $noBox,
            $berat,
            $item->Qty_order ?? 0,
            $item->status ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style header
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

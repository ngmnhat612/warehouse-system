<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StockLedgerExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;
    protected int $rowNum = 1;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Thẻ Kho';
    }

    public function query()
    {
        $f = $this->filters;

        $query = DB::table('stock_ledger as sl')
            ->join('products as p',    'sl.product_id',  '=', 'p.id')
            ->join('locations as l',   'sl.location_id', '=', 'l.id')
            ->join('uoms as u',        'p.uom_id',       '=', 'u.id')
            ->leftJoin('lots as lt',   'sl.lot_id',      '=', 'lt.id')
            ->leftJoin('serials as sr','sl.serial_id',   '=', 'sr.id')
            ->leftJoin('users as usr', 'sl.created_by',  '=', 'usr.id')
            ->select(
                'sl.transaction_date',
                'sl.transaction_type',
                'sl.reference_code',
                'sl.direction',
                'p.code as product_code',
                'p.name as product_name',
                'l.code as location_code',
                'l.name as location_name',
                'lt.lot_number',
                'sr.serial_number',
                'u.name as uom_name',
                'sl.quantity',
                'sl.balance_after',
                'usr.name as created_by_name',
                'sl.note',
            );

        if (!empty($f['date_from'])) {
            $query->where('sl.transaction_date', '>=', $f['date_from']);
        }
        if (!empty($f['date_to'])) {
            $query->where('sl.transaction_date', '<=', $f['date_to'] . ' 23:59:59');
        }
        if (!empty($f['product_id'])) {
            $query->where('sl.product_id', $f['product_id']);
        }
        if (!empty($f['location_id'])) {
            $query->where('sl.location_id', $f['location_id']);
        }
        if (!empty($f['transaction_type'])) {
            $query->where('sl.transaction_type', $f['transaction_type']);
        }
        if (!empty($f['direction'])) {
            $query->where('sl.direction', $f['direction']);
        }
        if (!empty($f['causer_id'])) {
            $query->where('sl.created_by', $f['causer_id']);
        }
        if (!empty($f['search'])) {
            $q = $f['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('sl.reference_code',  'like', "%{$q}%")
                    ->orWhere('p.name',            'like', "%{$q}%")
                    ->orWhere('p.code',            'like', "%{$q}%")
                    ->orWhere('lt.lot_number',     'like', "%{$q}%")
                    ->orWhere('sr.serial_number',  'like', "%{$q}%");
            });
        }

        return $query->orderByDesc('sl.transaction_date')->orderByDesc('sl.id');
    }

    public function headings(): array
    {
        return [
            'STT',
            'Thời gian',
            'Loại GD',
            'Mã phiếu',
            'Chiều',
            'Mã hàng',
            'Tên hàng hóa',
            'Vị trí',
            'Số Lot',
            'Số Serial',
            'ĐVT',
            'Số lượng',
            'Tồn sau GD',
            'Người thực hiện',
            'Ghi chú',
        ];
    }

    public function map($row): array
    {
        $typeMap = [
            'RECEIPT'   => 'Nhập kho',
            'ISSUE'     => 'Xuất kho',
            'TRANSFER'  => 'Chuyển kho',
            'SCRAP'     => 'Hủy hàng',
            'ADJUST'    => 'Điều chỉnh',
            'TRANSFORM' => 'Tách/Ghép',
            'RETURN'    => 'Trả hàng',
        ];

        $qty = (int) $row->direction === 1
            ? '+' . number_format((float) $row->quantity, 3)
            : '-' . number_format((float) $row->quantity, 3);

        return [
            $this->rowNum++,
            \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y H:i'),
            $typeMap[$row->transaction_type] ?? $row->transaction_type,
            $row->reference_code ?? '—',
            (int) $row->direction === 1 ? 'Nhập' : 'Xuất',
            $row->product_code,
            $row->product_name,
            $row->location_code . ' ' . $row->location_name,
            $row->lot_number    ?? '—',
            $row->serial_number ?? '—',
            $row->uom_name,
            $qty,
            number_format((float) $row->balance_after, 3),
            $row->created_by_name ?? '—',
            $row->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
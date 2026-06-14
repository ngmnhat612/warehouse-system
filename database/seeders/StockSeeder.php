<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $locPalA = DB::table('locations')->where('code', 'WH-PAL-A')->value('id');
        $locPalB = DB::table('locations')->where('code', 'WH-PAL-B')->value('id');
        $locShA1 = DB::table('locations')->where('code', 'WH-SHELF-A1')->value('id');
        $locShA2 = DB::table('locations')->where('code', 'WH-SHELF-A2')->value('id');

        $sup1 = DB::table('suppliers')->where('code', 'NCC001')->value('id');
        $sup2 = DB::table('suppliers')->where('code', 'NCC002')->value('id');

        $products = DB::table('products')->get();

        // ── 1. HÀNG NONE (tracking_type = 1) — 1 dòng stock thường ───────────
        $noneLocations = [$locPalA, $locPalB];
        foreach ($products->where('tracking_type', 1) as $i => $p) {
            $this->upsertStock([
                'product_id'       => $p->id,
                'location_id'      => $noneLocations[$i % 2],
                'lot_id'           => null,
                'serial_id'        => null,
                'quantity'         => 50.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => null,
                'received_date'    => $now->copy()->subMonths(1)->toDateString(),
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ]);
        }

        // ── 2. HÀNG LOT (tracking_type = 2) — stock gắn đúng lot vừa seed ────
        foreach ($products->where('tracking_type', 2) as $p) {
            $lot = DB::table('lots')
                ->where('product_id', $p->id)
                ->where('lot_number', "LOT-{$p->code}-001")
                ->first();

            if (! $lot) continue;

            $this->upsertStock([
                'product_id'       => $p->id,
                'location_id'      => $locShA1,
                'lot_id'           => $lot->id,
                'serial_id'        => null,
                'quantity'         => 50.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => $lot->manufacture_date,
                'received_date'    => $lot->received_date,
                'expiry_date'      => $lot->expiry_date,
                'status'           => 1,
                'updated_at'       => $now,
            ]);
        }

        // ── 3. HÀNG SERIAL (tracking_type = 3) — 1 dòng stock / serial ───────
        foreach ($products->where('tracking_type', 3) as $p) {
            $serials = DB::table('serials')
                ->where('product_id', $p->id)
                ->where('status', 1) // InStock
                ->get();

            foreach ($serials as $serial) {
                $this->upsertStock([
                    'product_id'       => $p->id,
                    'location_id'      => $locShA2,
                    'lot_id'           => null,
                    'serial_id'        => $serial->id,
                    'quantity'         => 1.000,
                    'reserved_qty'     => 0.000,
                    'supplier_id'      => $sup2,
                    'manufacture_date' => $serial->manufacture_date,
                    'received_date'    => $serial->received_date,
                    'expiry_date'      => $serial->expiry_date,
                    'status'           => 1,
                    'updated_at'       => $now,
                ]);
            }
        }

        // ── 4. HÀNG LOT + SERIAL (tracking_type = 4) — 1 dòng stock / serial, kèm lot_id ──
        foreach ($products->where('tracking_type', 4) as $p) {
            $serials = DB::table('serials')
                ->where('product_id', $p->id)
                ->where('status', 1) // InStock
                ->get();

            foreach ($serials as $serial) {
                $this->upsertStock([
                    'product_id'       => $p->id,
                    'location_id'      => $locShA2,
                    'lot_id'           => $serial->lot_id,
                    'serial_id'        => $serial->id,
                    'quantity'         => 1.000,
                    'reserved_qty'     => 0.000,
                    'supplier_id'      => $sup1,
                    'manufacture_date' => $serial->manufacture_date,
                    'received_date'    => $serial->received_date,
                    'expiry_date'      => $serial->expiry_date,
                    'status'           => 1,
                    'updated_at'       => $now,
                ]);
            }
        }

        // ── 5. LOT SẮP HẾT HẠN ("-NEAREXP") — stock số lượng nhỏ tại WH-SHELF-A1 ──
        $nearExpLots = DB::table('lots')->where('lot_number', 'like', '%-NEAREXP')->get();

        foreach ($nearExpLots as $lot) {
            $product  = $products->firstWhere('id', $lot->product_id);
            $tracking = (int) ($product->tracking_type ?? 1);

            if ($tracking === 4) {
                // tracking=4: 1 dòng stock / serial, kèm lot_id (giống section 4)
                $serials = DB::table('serials')
                    ->where('product_id', $lot->product_id)
                    ->where('lot_id', $lot->id)
                    ->where('status', 1)
                    ->get();

                foreach ($serials as $serial) {
                    $this->upsertStock([
                        'product_id'       => $lot->product_id,
                        'location_id'      => $locShA1,
                        'lot_id'           => $lot->id,
                        'serial_id'        => $serial->id,
                        'quantity'         => 1.000,
                        'reserved_qty'     => 0.000,
                        'supplier_id'      => $lot->supplier_id,
                        'manufacture_date' => $serial->manufacture_date,
                        'received_date'    => $serial->received_date,
                        'expiry_date'      => $serial->expiry_date,
                        'status'           => 1,
                        'updated_at'       => $now,
                    ]);
                }
            } else {
                // tracking=2: lot-level, không có serial
                $this->upsertStock([
                    'product_id'       => $lot->product_id,
                    'location_id'      => $locShA1,
                    'lot_id'           => $lot->id,
                    'serial_id'        => null,
                    'quantity'         => 10.000,
                    'reserved_qty'     => 0.000,
                    'supplier_id'      => $lot->supplier_id,
                    'manufacture_date' => $lot->manufacture_date,
                    'received_date'    => $lot->received_date,
                    'expiry_date'      => $lot->expiry_date,
                    'status'           => 1,
                    'updated_at'       => $now,
                ]);
            }
        }
    }

    private function upsertStock(array $row): void
    {
        $exists = DB::table('stock')
            ->where('product_id',  $row['product_id'])
            ->where('location_id', $row['location_id'])
            ->where('lot_id',      $row['lot_id'])
            ->where('serial_id',   $row['serial_id'])
            ->exists();

        if (! $exists) {
            DB::table('stock')->insert($row);
        }
    }
}

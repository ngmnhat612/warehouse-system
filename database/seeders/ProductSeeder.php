<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // UOM ids (từ migration): Cái=1, Cuộn=2, Kg=3, Hộp=4, Bộ=5, Mét=6, Lít=7, Tấm=8
        // Category ids (từ migration + CategorySeeder):
        //   MAY=1, LK=2, NVL=3
        //   MAY-BOM=4, MAY-DONG=5, LK-VONG=6, LK-BOARD=7, NVL-CAP=8, NVL-ONG=9, NVL-DAU=10
        // Dùng firstOrCreate qua tên category để không hardcode id

        $catBom   = DB::table('categories')->where('code', 'MAY-BOM')->value('id');
        $catDong  = DB::table('categories')->where('code', 'MAY-DONG')->value('id');
        $catVong  = DB::table('categories')->where('code', 'LK-VONG')->value('id');
        $catBoard = DB::table('categories')->where('code', 'LK-BOARD')->value('id');
        $catCap   = DB::table('categories')->where('code', 'NVL-CAP')->value('id');
        $catOng   = DB::table('categories')->where('code', 'NVL-ONG')->value('id');
        $catDau   = DB::table('categories')->where('code', 'NVL-DAU')->value('id');

        $now = now();

        $products = [
            [
                'code'                => 'SP001',
                'name'                => 'Máy bơm nước LVP-50',
                'category_id'         => $catBom,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP001',
                'min_stock'           => 2.000,
                'max_stock'           => 20.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP002',
                'name'                => 'Động cơ điện 3 pha 5.5kW',
                'category_id'         => $catDong,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP002',
                'min_stock'           => 1.000,
                'max_stock'           => 10.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP003',
                'name'                => 'Vòng bi 6205-2RS',
                'category_id'         => $catVong,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => 4,   // Hộp (1 Hộp = 12 Cái)
                'barcode'             => 'BC-SP003',
                'min_stock'           => 10.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => 180,
                'tracking_type'       => 2,   // Lot
                'stock_rotation'      => 2,   // FEFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP004',
                'name'                => 'Board điều khiển PLC Siemens S7-1200',
                'category_id'         => $catBoard,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP004',
                'min_stock'           => 1.000,
                'max_stock'           => 5.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 3,   // Serial
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP005',
                'name'                => 'Cáp điện CVV 2x1.5mm²',
                'category_id'         => $catCap,
                'uom_id'              => 6,   // Mét
                'uom_purchase_id'     => 2,   // Cuộn (1 Cuộn = 100 Mét)
                'barcode'             => 'BC-SP005',
                'min_stock'           => 5.000,
                'max_stock'           => 50.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP006',
                'name'                => 'Ống thép đen DN42 (6m/cây)',
                'category_id'         => $catOng,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP006',
                'min_stock'           => 10.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP007',
                'name'                => 'Mỡ bôi trơn công nghiệp đặc chủng',
                'category_id'         => $catDau,
                'uom_id'              => 1,   // Cái (hũ)
                'uom_purchase_id'     => 4,   // Hộp (1 Hộp = 12 Cái)
                'barcode'             => 'BC-SP007',
                'min_stock'           => 20.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => 90,
                'tracking_type'       => 2,   // Lot
                'stock_rotation'      => 2,   // FEFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            // Nhóm: Máy móc, Thiết bị (Tracking: Serial = 3)
            [
                'code' => 'SP008', 'name' => 'Biến tần Mitsubishi FR-A840 5.5kW', 'category_id' => $catDong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP008', 'min_stock' => 3.00, 'max_stock' => 15.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP009', 'name' => 'Động cơ Servo Panasonic 750W kèm Driver', 'category_id' => $catDong,
                'uom_id' => 1, 'uom_purchase_id' => 5, 'barcode' => 'BC-SP009', 'min_stock' => 2.00, 'max_stock' => 10.00, // Cơ bản: Cái, mua theo Bộ
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP010', 'name' => 'Màn HÌnh HMI Weintek MT8071iE 7 inch', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP010', 'min_stock' => 4.00, 'max_stock' => 20.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP011', 'name' => 'Máy gia nhiệt vòng bi di động TMH-22', 'category_id' => $catBom,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP011', 'min_stock' => 1.00, 'max_stock' => 5.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP012', 'name' => 'Bơm định lượng hóa chất Blue-White C-660P', 'category_id' => $catBom,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP012', 'min_stock' => 3.00, 'max_stock' => 15.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP013', 'name' => 'Bộ điều khiển nguồn SCR điện 3 pha Delta', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => 5, 'barcode' => 'BC-SP013', 'min_stock' => 2.00, 'max_stock' => 12.00, // Cơ bản: Cái, mua theo Bộ
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],

            // Nhóm: Linh kiện (Tracking: Lot = 2 hoặc None = 1)
            [
                'code' => 'SP014', 'name' => 'Cảm biến tiệm cận Omron E2E-X5ME1', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP014', 'min_stock' => 20.00, 'max_stock' => 150.00,
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP015', 'name' => 'Van điện từ khí nén Airtac 4V210-08', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP015', 'min_stock' => 15.00, 'max_stock' => 100.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP016', 'name' => 'Relay trung gian IDEC RJ2S-CL-A220', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP016', 'min_stock' => 50.00, 'max_stock' => 500.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP017', 'name' => 'Khởi động từ Schneider LC1D12M7', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP017', 'min_stock' => 10.00, 'max_stock' => 80.00,
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP018', 'name' => 'Nguồn tổ ong Meanwell LRS-350-24V', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP018', 'min_stock' => 8.00, 'max_stock' => 50.00,
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP019', 'name' => 'Thanh trượt tuyến tính HIWIN HGR20 (1 Mét)', 'category_id' => $catVong,
                'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP019', 'min_stock' => 10.00, 'max_stock' => 60.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP020', 'name' => 'Con trượt tuyến tính HIWIN HGH20CA', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP020', 'min_stock' => 20.00, 'max_stock' => 200.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP021', 'name' => 'Đồng hồ đo áp suất Wika 213.53', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP021', 'min_stock' => 5.00, 'max_stock' => 40.00,
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP022', 'name' => 'Bộ lọc khí nén Festo MS6-LFR', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => 5, 'barcode' => 'BC-SP022', 'min_stock' => 3.00, 'max_stock' => 20.00, // Cơ bản: Cái, mua theo Bộ
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP023', 'name' => 'Xy lanh khí nén SMC CDU16-30D', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP023', 'min_stock' => 10.00, 'max_stock' => 70.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP024', 'name' => 'Cột đèn tháp cảnh báo Patlite 3 tầng', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP024', 'min_stock' => 5.00, 'max_stock' => 30.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP025', 'name' => 'Module truyền thông Profibus Siemens', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP025', 'min_stock' => 2.00, 'max_stock' => 15.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],

            // Nhóm: Nguyên vật liệu tiêu hao (Tracking: Lot = 2 hoặc FEFO / Hạn dùng)
            [
                'code' => 'SP026', 'name' => 'Mỡ bôi trơn chịu nhiệt SKF LGHP 2 (1kg)', 'category_id' => $catDau,
                'uom_id' => 3, 'uom_purchase_id' => null, 'barcode' => 'BC-SP026', 'min_stock' => 10.00, 'max_stock' => 100.00,
                'alert_before_expiry' => 60, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP027', 'name' => 'Thép tấm cán nóng SS400', 'category_id' => $catCap,
                'uom_id' => 3, 'uom_purchase_id' => 8, 'barcode' => 'BC-SP027', 'min_stock' => 200.00, 'max_stock' => 1000.00, // Cơ bản: Kg, mua theo Tấm
                'alert_before_expiry' => 120, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP028', 'name' => 'Cáp mạng Cat6 chống nhiễu AMP (Cuộn 305m)', 'category_id' => $catCap,
                'uom_id' => 6, 'uom_purchase_id' => 2, 'barcode' => 'BC-SP028', 'min_stock' => 5.00, 'max_stock' => 30.00, // Cơ bản: Mét, mua theo Cuộn
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP029', 'name' => 'Dây rút nhựa công nghiệp 30cm (Hộp 100 cái)', 'category_id' => $catCap,
                'uom_id' => 1, 'uom_purchase_id' => 4, 'barcode' => 'BC-SP029', 'min_stock' => 20.00, 'max_stock' => 200.00, // Cơ bản: Cái, mua theo Hộp
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP030', 'name' => 'Ống khí nén PU Phi 8 SMC (Cuộn 100m)', 'category_id' => $catOng,
                'uom_id' => 6, 'uom_purchase_id' => 2, 'barcode' => 'BC-SP030', 'min_stock' => 3.00, 'max_stock' => 25.00, // Cơ bản: Mét, mua theo Cuộn
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP031', 'name' => 'Keo khóa ren Loctite 243 (50ml)', 'category_id' => $catDau,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP031', 'min_stock' => 10.00, 'max_stock' => 100.00,
                'alert_before_expiry' => 45, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP032', 'name' => 'Keo dán công nghiệp kết cấu cứng Epoxy', 'category_id' => $catDau,
                'uom_id' => 1, 'uom_purchase_id' => 4, 'barcode' => 'BC-SP032', 'min_stock' => 50.00, 'max_stock' => 500.00, // Cơ bản: Cái (tuýp), mua theo Hộp
                'alert_before_expiry' => 90, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP033', 'name' => 'Cáp điều khiển Alantek 18 AWG 1 Pair', 'category_id' => $catCap,
                'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP033', 'min_stock' => 200.00, 'max_stock' => 1500.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP034', 'name' => 'Ống cao sau lõi thép hút nước Phi 50', 'category_id' => $catOng,
                'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP034', 'min_stock' => 30.00, 'max_stock' => 300.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP035', 'name' => 'Thiếc hàn điện tử Alpha Omegle (Kg)', 'category_id' => $catCap,
                'uom_id' => 3, 'uom_purchase_id' => null, 'barcode' => 'BC-SP035', 'min_stock' => 5.00, 'max_stock' => 40.00,
                'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],

            // Bổ sung tiếp tục để cán mốc 50 sản phẩm độc lập
            [
                'code' => 'SP036', 'name' => 'Bộ nút nhấn dừng khẩn cấp Schneider XB5', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP036', 'min_stock' => 10.00, 'max_stock' => 50.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP037', 'name' => 'Cảm biến áp suất Danfoss MBS 3000', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP037', 'min_stock' => 2.00, 'max_stock' => 15.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP038', 'name' => 'Bộ chuyển đổi tín hiệu 4-20mA sang RS485', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP038', 'min_stock' => 3.00, 'max_stock' => 20.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP039', 'name' => 'Khớp nối mềm cao su chống rung Phi 60', 'category_id' => $catOng,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP039', 'min_stock' => 5.00, 'max_stock' => 30.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP040', 'name' => 'Tấm inox chống rỉ sét 304', 'category_id' => $catCap,
                'uom_id' => 3, 'uom_purchase_id' => 8, 'barcode' => 'BC-SP040', 'min_stock' => 40.00, 'max_stock' => 400.00, // Cơ bản: Kg, mua theo Tấm
                'alert_before_expiry' => 180, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP041', 'name' => 'Đầu nối nhanh khí nén phi 8 Nitto', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP041', 'min_stock' => 100.00, 'max_stock' => 1000.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP042', 'name' => 'Đầu cốt cos trần thông tin SC16-6 (Hộp 100 cái)', 'category_id' => $catCap,
                'uom_id' => 1, 'uom_purchase_id' => 4, 'barcode' => 'BC-SP042', 'min_stock' => 10.00, 'max_stock' => 100.00, // Cơ bản: Cái, mua theo Hộp
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP043', 'name' => 'Công tắc hành trình Chint YBLX-ME/8104', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP043', 'min_stock' => 15.00, 'max_stock' => 80.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP044', 'name' => 'Bộ nguồn lưu điện UPS APC 500VA', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP044', 'min_stock' => 2.00, 'max_stock' => 10.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP045', 'name' => 'Quạt thông gió tủ điện tủ Rack 120x120', 'category_id' => $catVong,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP045', 'min_stock' => 10.00, 'max_stock' => 60.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP046', 'name' => 'Màn hình hiển thị nhiệt độ Autonics TCN4S', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP046', 'min_stock' => 5.00, 'max_stock' => 30.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP047', 'name' => 'Thanh đồng thanh cái bản 20x3 (Thanh 3 Mét)', 'category_id' => $catCap,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP047', 'min_stock' => 5.00, 'max_stock' => 40.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP048', 'name' => 'Mặt bích thép tiêu chuẩn JIS 10K Phi 50', 'category_id' => $catOng,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP048', 'min_stock' => 20.00, 'max_stock' => 150.00,
                'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP049', 'name' => 'Hóa chất tẩy rửa rỉ sét công nghiệp RP7', 'category_id' => $catDau,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP049', 'min_stock' => 30.00, 'max_stock' => 300.00,
                'alert_before_expiry' => 90, 'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
            [
                'code' => 'SP050', 'name' => 'Bộ ngắt mạch tự động Aptomat MCCB LS 100A', 'category_id' => $catBoard,
                'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP050', 'min_stock' => 5.00, 'max_stock' => 40.00,
                'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now
            ],
        ];

        foreach ($products as $row) {
            DB::table('products')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }
    }
}

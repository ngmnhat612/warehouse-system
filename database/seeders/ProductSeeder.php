<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catBom   = DB::table('categories')->where('code', 'MAY-BOM')->value('id');
        $catDong  = DB::table('categories')->where('code', 'MAY-DONG')->value('id');
        $catVong  = DB::table('categories')->where('code', 'LK-VONG')->value('id');
        $catBoard = DB::table('categories')->where('code', 'LK-BOARD')->value('id');
        $catCap   = DB::table('categories')->where('code', 'NVL-CAP')->value('id');
        $catOng   = DB::table('categories')->where('code', 'NVL-ONG')->value('id');
        $catDau   = DB::table('categories')->where('code', 'NVL-DAU')->value('id');

        $now = now();

        $products = [
            ['code' => 'SP001', 'name' => 'Máy bơm nước LVP-50',                          'category_id' => $catBom,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP001', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP002', 'name' => 'Động cơ điện 3 pha 5.5kW',                     'category_id' => $catDong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP002', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP003', 'name' => 'Vòng bi 6205-2RS',                              'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => 4,    'barcode' => 'BC-SP003', 'alert_before_expiry' => 180,  'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP004', 'name' => 'Board điều khiển PLC Siemens S7-1200',          'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP004', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP005', 'name' => 'Cáp điện CVV 2x1.5mm²',                        'category_id' => $catCap,   'uom_id' => 2, 'uom_purchase_id' => null, 'barcode' => 'BC-SP005', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP006', 'name' => 'Ống thép đen DN42 (6m/cây)',                    'category_id' => $catOng,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP006', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP007', 'name' => 'Dầu bôi trơn ISO VG 68 (20L)',                 'category_id' => $catDau,   'uom_id' => 7, 'uom_purchase_id' => null, 'barcode' => 'BC-SP007', 'alert_before_expiry' => 90,   'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP008', 'name' => 'Biến tần Mitsubishi FR-A840 5.5kW',             'category_id' => $catDong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP008', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP009', 'name' => 'Động cơ Servo Panasonic 750W kèm Driver',       'category_id' => $catDong,  'uom_id' => 5, 'uom_purchase_id' => null, 'barcode' => 'BC-SP009', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP010', 'name' => 'Màn hình HMI Weintek MT8071iE 7 inch',          'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP010', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP011', 'name' => 'Máy gia nhiệt vòng bi di động TMH-22',          'category_id' => $catBom,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP011', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP012', 'name' => 'Bơm định lượng hóa chất Blue-White C-660P',     'category_id' => $catBom,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP012', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP013', 'name' => 'Bộ điều khiển nguồn SCR điện 3 pha Delta',      'category_id' => $catBoard, 'uom_id' => 5, 'uom_purchase_id' => null, 'barcode' => 'BC-SP013', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP014', 'name' => 'Cảm biến tiệm cận Omron E2E-X5ME1',             'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP014', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP015', 'name' => 'Van điện từ khí nén Airtac 4V210-08',           'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP015', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP016', 'name' => 'Relay trung gian IDEC RJ2S-CL-A220',            'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP016', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP017', 'name' => 'Khởi động từ Schneider LC1D12M7',               'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP017', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP018', 'name' => 'Nguồn tổ ong Meanwell LRS-350-24V',             'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP018', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP019', 'name' => 'Thanh trượt tuyến tính HIWIN HGR20 (1 Mét)',    'category_id' => $catVong,  'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP019', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP020', 'name' => 'Con trượt tuyến tính HIWIN HGH20CA',            'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP020', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP021', 'name' => 'Đồng hồ đo áp suất Wika 213.53',               'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP021', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP022', 'name' => 'Bộ lọc khí nén Festo MS6-LFR',                 'category_id' => $catVong,  'uom_id' => 5, 'uom_purchase_id' => null, 'barcode' => 'BC-SP022', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP023', 'name' => 'Xy lanh khí nén SMC CDU16-30D',                 'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP023', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP024', 'name' => 'Cột đèn tháp cảnh báo Patlite 3 tầng',         'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP024', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP025', 'name' => 'Module truyền thông Profibus Siemens',           'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP025', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP026', 'name' => 'Mỡ bôi trơn chịu nhiệt SKF LGHP 2 (1kg)',      'category_id' => $catDau,   'uom_id' => 3, 'uom_purchase_id' => null, 'barcode' => 'BC-SP026', 'alert_before_expiry' => 60,   'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP027', 'name' => 'Dầu thủy lực Castrol Hyspin AWH-M 68',          'category_id' => $catDau,   'uom_id' => 7, 'uom_purchase_id' => null, 'barcode' => 'BC-SP027', 'alert_before_expiry' => 120,  'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP028', 'name' => 'Cáp mạng Cat6 chống nhiễu AMP (Cuộn 305m)',     'category_id' => $catCap,   'uom_id' => 2, 'uom_purchase_id' => null, 'barcode' => 'BC-SP028', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP029', 'name' => 'Dây rút nhựa công nghiệp 30cm (Hộp 100 cái)',   'category_id' => $catCap,   'uom_id' => 4, 'uom_purchase_id' => null, 'barcode' => 'BC-SP029', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP030', 'name' => 'Ống khí nén PU Phi 8 SMC (Cuộn 100m)',          'category_id' => $catOng,   'uom_id' => 2, 'uom_purchase_id' => null, 'barcode' => 'BC-SP030', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP031', 'name' => 'Keo khóa ren Loctite 243 (50ml)',               'category_id' => $catDau,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP031', 'alert_before_expiry' => 45,   'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP032', 'name' => 'Nước làm mát két nước công nghiệp dung dịch',   'category_id' => $catDau,   'uom_id' => 7, 'uom_purchase_id' => null, 'barcode' => 'BC-SP032', 'alert_before_expiry' => 90,   'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP033', 'name' => 'Cáp điều khiển Alantek 18 AWG 1 Pair',          'category_id' => $catCap,   'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP033', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP034', 'name' => 'Ống cao su lõi thép hút nước Phi 50',           'category_id' => $catOng,   'uom_id' => 6, 'uom_purchase_id' => null, 'barcode' => 'BC-SP034', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP035', 'name' => 'Thiếc hàn điện tử Alpha Omegle (Kg)',           'category_id' => $catCap,   'uom_id' => 3, 'uom_purchase_id' => null, 'barcode' => 'BC-SP035', 'alert_before_expiry' => null, 'tracking_type' => 2, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP036', 'name' => 'Bộ nút nhấn dừng khẩn cấp Schneider XB5',      'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP036', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP037', 'name' => 'Cảm biến áp suất Danfoss MBS 3000',             'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP037', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP038', 'name' => 'Bộ chuyển đổi tín hiệu 4-20mA sang RS485',     'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP038', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP039', 'name' => 'Khớp nối mềm cao su chống rung Phi 60',         'category_id' => $catOng,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP039', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP040', 'name' => 'Dầu máy nén khí Shell Corena S3 R46',           'category_id' => $catDau,   'uom_id' => 7, 'uom_purchase_id' => null, 'barcode' => 'BC-SP040', 'alert_before_expiry' => 180,  'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP041', 'name' => 'Đầu nối nhanh khí nén phi 8 Nitto',             'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP041', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP042', 'name' => 'Đầu cốt cos trần thông tin SC16-6 (Hộp 100c)', 'category_id' => $catCap,   'uom_id' => 4, 'uom_purchase_id' => null, 'barcode' => 'BC-SP042', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP043', 'name' => 'Công tắc hành trình Chint YBLX-ME/8104',        'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP043', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP044', 'name' => 'Bộ nguồn lưu điện UPS APC 500VA',               'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP044', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP045', 'name' => 'Quạt thông gió tủ điện tủ Rack 120x120',        'category_id' => $catVong,  'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP045', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP046', 'name' => 'Màn hình hiển thị nhiệt độ Autonics TCN4S',     'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP046', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP047', 'name' => 'Thanh đồng thanh cái bản 20x3 (Thanh 3 Mét)',   'category_id' => $catCap,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP047', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP048', 'name' => 'Mặt bích thép tiêu chuẩn JIS 10K Phi 50',       'category_id' => $catOng,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP048', 'alert_before_expiry' => null, 'tracking_type' => 1, 'stock_rotation' => 1, 'status' => 1],
            ['code' => 'SP049', 'name' => 'Hóa chất tẩy rửa rỉ sét công nghiệp RP7',      'category_id' => $catDau,   'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP049', 'alert_before_expiry' => 90,   'tracking_type' => 2, 'stock_rotation' => 2, 'status' => 1],
            ['code' => 'SP050', 'name' => 'Bộ ngắt mạch tự động Aptomat MCCB LS 100A',    'category_id' => $catBoard, 'uom_id' => 1, 'uom_purchase_id' => null, 'barcode' => 'BC-SP050', 'alert_before_expiry' => null, 'tracking_type' => 3, 'stock_rotation' => 1, 'status' => 1],
        ];

        foreach ($products as $row) {
            DB::table('products')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}

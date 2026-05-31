# Warehouse System — Hướng dẫn khởi tạo môi trường

Hệ thống quản lý kho cho **Ment Automation** — xây dựng trên Laravel 11 + SQL Server 2022.

---

## Yêu cầu hệ thống

| Công cụ | Phiên bản | Ghi chú |
|---|---|---|
| PHP | 8.2+ | Kèm extensions: `gd`, `zip`, `intl`, `curl`, `mbstring`, `openssl`, `pdo`, `sqlsrv`, `pdo_sqlsrv` |
| Composer | 2.x | getcomposer.org |
| Node.js | 18+ | nodejs.org |
| Docker Desktop | Mới nhất | docker.com/products/docker-desktop |
| SSMS | 20 | Quản lý SQL Server (tuỳ chọn) |
| Git | Mới nhất | git-scm.com |

---

## Bước 1 — Clone project

```bash
git clone https://github.com/your-username/warehouse-system.git
cd warehouse-system
```

---

## Bước 2 — Khởi động SQL Server bằng Docker

> ⚠️ Đảm bảo Docker Desktop đang chạy trước khi thực hiện bước này.

> ⚠️ Nếu máy đã cài SQL Server trực tiếp trên Windows, cần tắt service trước để tránh xung đột port 1433:
> - Mở **services.msc** → tìm **SQL Server (MSSQLSERVER)** → chuột phải → **Stop**
> - Hoặc chạy CMD với quyền Admin: `net stop MSSQLSERVER`

```bash
docker compose up -d
```

Kiểm tra container đang chạy:
```bash
docker compose ps
```

Kết quả đúng: cột `STATUS` hiển thị `running`.

---

## Bước 3 — Cài đặt dependencies

```bash
composer install
npm install && npm run build
```

---

## Bước 4 — Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Mở file `.env`, cập nhật phần database:

```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=warehouse_db
DB_USERNAME=sa
DB_PASSWORD=Warehouse123@
```

---

## Bước 5 — Tạo database

Kết nối vào SQL Server bằng SSMS hoặc chạy lệnh sau:

```bash
docker exec -it warehouse-system-sqlserver-1 /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "Warehouse123@" -No \
  -Q "CREATE DATABASE warehouse_db COLLATE Vietnamese_CI_AS"
```

> Collation `Vietnamese_CI_AS` bắt buộc để hỗ trợ tiếng Việt có dấu.

---

## Bước 6 — Chạy migration

```bash
php artisan migrate
```

---

## Bước 7 — Chạy ứng dụng

```bash
php artisan serve
```

Truy cập: **http://localhost:8000**

---

## Kết nối SSMS 20

| Trường | Giá trị |
|---|---|
| Server name | `127.0.0.1,1433` |
| Authentication | `SQL Server Authentication` |
| Login | `sa` |
| Password | `Warehouse123@` |
| Encryption | `Optional` |
| Trust server certificate | ✅ Bật |

---

## Quản lý Docker

```bash
# Bật SQL Server
docker compose up -d

# Tắt (giữ nguyên data)
docker compose stop

# Tắt và xoá data
docker compose down -v

# Xem log nếu có lỗi
docker compose logs sqlserver
```

---

## Cấu trúc dự án

```
warehouse-system/
├── app/
│   ├── Http/Controllers/     # Controllers
│   ├── Models/               # Eloquent Models
│   └── Services/             # Business logic
├── database/
│   ├── migrations/           # 23 bảng theo thứ tự FK
│   └── seeders/              # Dữ liệu mẫu
├── resources/
│   └── views/
│       ├── layouts/          # Layout chính + partials
│       │   └── partials/     # sidebar, header, footer
│       ├── auth/             # Login (CoreUI)
│       ├── dashboard/        # Dashboard
│       └── warehouse/        # Các màn hình nghiệp vụ
├── public/
│   └── vendor/coreui/        # CSS, JS, Icons của CoreUI
├── docker-compose.yml
└── .env.example
```

---

## Packages đã cài

| Package | Mục đích |
|---|---|
| `laravel/breeze` | Authentication (Login/Logout) |
| `spatie/laravel-permission` | Phân quyền Thủ kho / Nhân viên kho |
| `spatie/laravel-activitylog` | Audit log thay đổi master data |
| `maatwebsite/excel` | Xuất báo cáo Excel |
| `barryvdh/laravel-dompdf` | In phiếu nhập/xuất/chuyển kho PDF |
| `picqer/php-barcode-generator` | Sinh barcode cho lot, serial, vị trí kho |

---

## Lưu ý quan trọng

- File `.env` chứa mật khẩu — **không commit lên Git**
- Mỗi lần restart Windows, kiểm tra SQL Server Windows service chưa tự bật lại (sẽ chiếm port 1433)
- Chạy `docker compose up -d` trước khi `php artisan serve`

---

## Liên hệ

Dự án NCKH — Hệ thống quản lý kho Ment Automation

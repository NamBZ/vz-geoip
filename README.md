# GeoIP API với Lumen

Một API GeoIP đầy đủ tính năng được xây dựng bằng Lumen (Laravel micro-framework), hỗ trợ tra cứu thông tin địa lý cho địa chỉ IP.

## 🌟 Tính năng

-   ✅ Hỗ trợ IPv4 và IPv6
-   ✅ **Hỗ trợ nhiều nguồn database: MaxMind GeoLite2 và DB-IP Lite**
-   ✅ **Chuyển đổi provider động thông qua parameter hoặc endpoint**
-   ✅ Nhiều định dạng output: JSON, XML, CSV, YAML
-   ✅ Hỗ trợ JSONP callback
-   ✅ Cache để tối ưu hiệu suất
-   ✅ **Rate Limiting: 100 requests/phút/IP**
-   ✅ CORS enabled
-   ✅ Validation IP address
-   ✅ Error handling chi tiết
-   ✅ **Endpoint thống kê database và health check**
-   ✅ **Provider management API**

## 📡 API Endpoints

### 1. GET `/geoip`

Tra cứu thông tin GeoIP cho bất kỳ địa chỉ IP nào (IPv4 hoặc IPv6)

**Parameters:**

-   `ip` (optional): Địa chỉ IP cần tra cứu. Nếu không có, sẽ dùng IP của client
-   `format` (optional): Định dạng output (json, xml, csv, yaml). Mặc định: json
-   `callback` (optional): Tên hàm callback cho JSONP (chỉ áp dụng với JSON)
-   `provider` (optional): Provider database (maxmind, dbip). Mặc định: maxmind

### 2. GET `/geoip/ipv4`

Tra cứu thông tin GeoIP chỉ cho địa chỉ IPv4

### 3. GET `/geoip/ipv6`

Tra cứu thông tin GeoIP chỉ cho địa chỉ IPv6

### 4. GET `/geoip/stats`

Lấy thông tin thống kê và metadata của các database MMDB

**Response bao gồm:**

-   Thông tin chi tiết về GeoLite2-City database
-   Thông tin chi tiết về GeoLite2-ASN database
-   Tổng số records trong cả hai database
-   Ngày cập nhật mới nhất
-   Metadata và version của database

**Ví dụ:**

```bash
curl "localhost:8000/geoip/stats"
```

### 5. GET `/geoip/health`

Health check endpoint để kiểm tra tình trạng API và database

**Response bao gồm:**

-   Trạng thái service (healthy/error)
-   Thông tin cơ bản về từng database
-   Thông tin provider hiện tại
-   Tổng số records available
-   Timestamp hiện tại

**Ví dụ:**

```bash
curl "localhost:8000/geoip/health"
```

### 6. GET `/geoip/providers`

Lấy thông tin về các provider database có sẵn

**Response bao gồm:**

-   Provider hiện tại đang sử dụng
-   Tên và website của provider
-   Danh sách tất cả provider có sẵn

**Ví dụ:**

```bash
curl "localhost:8000/geoip/providers"
```

### 7. GET/POST `/geoip/switch-provider`

Chuyển đổi provider database động

**Parameters:**

-   `provider` (required): Provider muốn chuyển sang (maxmind, dbip)

**Ví dụ:**

```bash
curl "localhost:8000/geoip/switch-provider?provider=dbip"
curl -X POST "localhost:8000/geoip/switch-provider" -d "provider=maxmind"
```

### 8. POST `/geoip/update` 🔐

**⚠️ ADMIN ENDPOINT - Yêu cầu API Key**

Cập nhật database GeoIP từ các nguồn MaxMind và DB-IP thông qua web interface.

**🔐 Bảo mật:**

-   Endpoint này được bảo vệ bằng API key để ngăn chặn truy cập trái phép
-   API key được cấu hình trong biến môi trường `GEOIP_ADMIN_API_KEY`
-   Rate limiting: 10 requests/phút để bảo vệ hiệu suất server

**Parameters:**

-   `provider` (optional): Provider to update (maxmind, dbip, all). Default: all
-   `force` (optional): Force update even if files exist
-   `no-backup` (optional): Skip backup of existing databases

**API Key Authentication - chọn 1 trong 3 cách:**

1. **Header:** `X-API-Key: your-api-key`
2. **Parameter:** `?api_key=your-api-key`
3. **Bearer Token:** `Authorization: Bearer your-api-key`

**Ví dụ:**

```bash
# Sử dụng header (khuyến nghị)
curl -H "X-API-Key: your-api-key" \
     -X POST "localhost:8000/geoip/update?provider=dbip"

# Sử dụng parameter
curl -X POST "localhost:8000/geoip/update?provider=all&api_key=your-api-key"

# Sử dụng Bearer token
curl -H "Authorization: Bearer your-api-key" \
     -X POST "localhost:8000/geoip/update?provider=maxmind&force=1"
```

**Response thành công:**

```json
{
    "success": true,
    "message": "Database update completed successfully for provider: dbip",
    "provider": "dbip",
    "options": {
        "force": false,
        "no_backup": true
    },
    "output": "🌍 Starting GeoIP database update...",
    "exit_code": 0,
    "timestamp": "2025-07-10T04:20:32.263280Z"
}
```

**Response lỗi (401 - Missing API Key):**

```json
{
    "success": false,
    "message": "API key required. Provide via X-API-Key header, api_key parameter, or Authorization Bearer token",
    "code": 401,
    "help": {
        "header": "X-API-Key: your-api-key",
        "parameter": "?api_key=your-api-key",
        "bearer": "Authorization: Bearer your-api-key"
    }
}
```

**Response lỗi (403 - Invalid API Key):**

```json
{
    "success": false,
    "message": "Invalid API key",
    "code": 403
}
```

## 📄 Response Schema

### GeoIP Lookup Response

```json
{
    "ip": "8.8.8.8",
    "country_code": "US",
    "country": "United States",
    "region_code": null,
    "region": null,
    "city": null,
    "postal_code": null,
    "continent_code": "NA",
    "latitude": 37.751,
    "longitude": -97.822,
    "organization": "GOOGLE",
    "asn": 15169,
    "asn_organization": "GOOGLE",
    "isp": "GOOGLE",
    "timezone": "America/Chicago"
}
```

### Database Stats Response

```json
{
    "databases": {
        "city": {
            "database_name": "GeoLite2-City",
            "database_type": "GeoIP2-City",
            "record_count": 10850729,
            "build_date": "2020-09-14T17:00:04+00:00",
            "description": "GeoIP2 City database"
        },
        "asn": {
            "database_name": "GeoLite2-ASN",
            "database_type": "GeoLite2-ASN",
            "record_count": 1383096,
            "build_date": "2025-07-06T08:15:42+00:00",
            "description": "GeoLite2 ASN database"
        }
    },
    "total_records": 12233825,
    "last_updated": "2025-07-06T08:15:42+00:00",
    "api_version": "1.0.0"
}
```

## Laravel/Lumen Command

```bash
# Cập nhật tất cả GeoIP DB
php artisan geoip:update

# Cập nhật GeoIP DB Chỉ MaxMind với force update
php artisan geoip:update maxmind --force

# Cập nhật GeoIP DB DB-IP không backup
php artisan geoip:update dbip --no-backup

# Kiểm tra database info
php artisan geoip:stats
```

## 🔧 Khởi động Server

```bash
cd /var/www/geoip.vuiz.net
php -S localhost:8000 -t public
```

## ✅ Testing Results

Tất cả endpoints đã được test và hoạt động tốt:

### Basic GeoIP Lookup

-   ✅ JSON format: `curl "localhost:8000/geoip?ip=8.8.8.8"`
-   ✅ XML format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=xml"`
-   ✅ CSV format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=csv"`
-   ✅ YAML format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=yaml"`
-   ✅ JSONP callback: `curl "localhost:8000/geoip?ip=8.8.8.8&callback=myCallback"`

### Provider Testing

-   ✅ MaxMind provider: `curl "localhost:8000/geoip?ip=8.8.8.8&provider=maxmind"`
-   ✅ DB-IP provider: `curl "localhost:8000/geoip?ip=8.8.8.8&provider=dbip"`
-   ✅ Provider info: `curl "localhost:8000/geoip/providers"`
-   ✅ Switch provider: `curl "localhost:8000/geoip/switch-provider?provider=dbip"`

### Advanced Features

-   ✅ IPv4 validation
-   ✅ IPv6 validation
-   ✅ Database stats: `curl "localhost:8000/geoip/stats"`
-   ✅ Health check: `curl "localhost:8000/geoip/health"`
-   ✅ Error handling
-   ✅ Rate limiting

## 🗄️ Database Providers

API hỗ trợ hai nguồn database chính:

### 1. MaxMind GeoLite2 (mặc định)

-   **City Database**: GeoLite2-City.mmdb
-   **ASN Database**: GeoLite2-ASN.mmdb
-   **Ưu điểm**: Được cập nhật thường xuyên, độ chính xác cao
-   **Website**: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data

### 2. DB-IP Lite

-   **City Database**: dbip-city-lite.mmdb
-   **ASN Database**: dbip-asn-lite.mmdb
-   **Ưu điểm**: Thay thế tốt cho MaxMind, cung cấp thông tin địa lý chính xác
-   **Website**: https://db-ip.com/db/download/ip-to-city-lite

### Cấu trúc thư mục DB

```
storage/geoip/
├── maxmind/
│   ├── GeoLite2-City.mmdb
│   ├── GeoLite2-ASN.mmdb
│   └── GeoLite2-Country.mmdb
├── dbip/
│   ├── dbip-city-lite.mmdb
│   └── dbip-asn-lite.mmdb
└── backup/
    ├── maxmind_20250710_120000/
    └── dbip_20250710_120000/
```

### Tần suất cập nhật của DB Providers

-   **MaxMind**: Chủ nhật hàng tuần lúc 2:00 AM
-   **DB-IP**: Ngày 1 hàng tháng lúc 3:00 AM
-   **Full update**: Ngày 1 mỗi quý lúc 4:00 AM

### Cách sử dụng Provider

1. **Sử dụng provider mặc định (MaxMind)**:

    ```bash
    curl "localhost:8000/geoip?ip=8.8.8.8"
    ```

2. **Chỉ định provider trong request**:

    ```bash
    curl "localhost:8000/geoip?ip=8.8.8.8&provider=dbip"
    curl "localhost:8000/geoip?ip=8.8.8.8&provider=maxmind"
    ```

3. **Thay đổi provider mặc định**:

    ```bash
    curl "localhost:8000/geoip/switch-provider?provider=dbip"
    ```

4. **Kiểm tra provider hiện tại**:
    ```bash
    curl "localhost:8000/geoip/providers"
    ```

## 🔐 API Key Management

Database update endpoints được bảo vệ bằng API key để ngăn chặn truy cập trái phép và bảo vệ hiệu suất server.

### Cấu hình API Key

API key được lưu trữ trong file `.env` dưới tên `GEOIP_ADMIN_API_KEY`:

```bash
# Trong file .env
GEOIP_ADMIN_API_KEY=your-64-character-hex-api-key
```

### Sử dụng API Key

**1. Header Authentication (khuyến nghị):**

```bash
curl -H "X-API-Key: your-api-key" \
     -X POST "localhost:8000/geoip/update?provider=dbip"
```

**2. URL Parameter:**

```bash
curl -X POST "localhost:8000/geoip/update?provider=dbip&api_key=your-api-key"
```

**3. Bearer Token:**

```bash
curl -H "Authorization: Bearer your-api-key" \
     -X POST "localhost:8000/geoip/update?provider=dbip"
```

### Bảo mật API Key

-   ⚠️ **Không bao giờ** commit API key vào version control
-   🔒 Chỉ chia sẻ API key với administrator được ủy quyền
-   🌐 Sử dụng HTTPS trong môi trường production
-   📝 API key có độ dài 64 ký tự hex để đảm bảo bảo mật cao

### Admin Panel

Truy cập giao diện quản trị web tại:

-   **URL:** `http://localhost:8000/admin.html`
-   **Redirect:** `http://localhost:8000/admin` → `/admin.html`

**Features:**

-   🎨 Giao diện web hiện đại và responsive
-   🔐 Form nhập API key để authentication
-   📊 Cập nhật database với real-time output
-   📈 Hiển thị thống kê database sau khi update
-   ⚡ AJAX calls không reload trang
-   🛡️ Error handling cho invalid API key

## 🔧 Troubleshooting

### MaxMind Download Fails

1. Kiểm tra license key trong `.env`
2. Verify account tại MaxMind website
3. Kiểm tra internet connection
4. Xem logs chi tiết

### DB-IP Download Fails

1. DB-IP release monthly, có thể chưa có database cho tháng hiện tại
2. Script sẽ tự động thử tháng trước
3. Kiểm tra URL có thể đã thay đổi

### Permission Issues

```bash
# Set permissions
sudo chown -R www-data:www-data storage/geoip/
chmod -R 755 storage/geoip/
chmod -R 644 storage/geoip/*.mmdb
```

### Disk Space

```bash
# Kiểm tra dung lượng
du -sh storage/geoip/

# Cleanup old backups manually
rm -rf storage/geoip/backup/old_backup_folders
```

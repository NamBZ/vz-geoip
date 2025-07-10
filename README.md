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

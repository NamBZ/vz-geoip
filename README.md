# GeoIP API với Lumen

Một API GeoIP đầy đủ tính năng được xây dựng bằng Lumen (Laravel micro-framework), hỗ trợ tra cứu thông tin địa lý cho địa chỉ IP.

## 🌟 Tính năng

-   ✅ Hỗ trợ IPv4 và IPv6
-   ✅ Nhiều định dạng output: JSON, XML, CSV, YAML
-   ✅ Hỗ trợ JSONP callback
-   ✅ Cache để tối ưu hiệu suất
-   ✅ **Rate Limiting: 100 requests/phút/IP**
-   ✅ CORS enabled
-   ✅ Validation IP address
-   ✅ Error handling chi tiết

## 📡 API Endpoints

### 1. GET `/geoip`

Tra cứu thông tin GeoIP cho bất kỳ địa chỉ IP nào (IPv4 hoặc IPv6)

**Parameters:**

-   `ip` (optional): Địa chỉ IP cần tra cứu. Nếu không có, sẽ dùng IP của client
-   `format` (optional): Định dạng output (json, xml, csv, yaml). Mặc định: json
-   `callback` (optional): Tên hàm callback cho JSONP (chỉ áp dụng với JSON)

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
-   Tổng số records available
-   Timestamp hiện tại

**Ví dụ:**

```bash
curl "localhost:8000/geoip/health"
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

-   ✅ JSON format: `curl "localhost:8000/geoip?ip=8.8.8.8"`
-   ✅ XML format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=xml"`
-   ✅ CSV format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=csv"`
-   ✅ YAML format: `curl "localhost:8000/geoip?ip=8.8.8.8&format=yaml"`
-   ✅ JSONP callback: `curl "localhost:8000/geoip?ip=8.8.8.8&callback=myCallback"`
-   ✅ IPv4 validation
-   ✅ IPv6 validation
-   ✅ Error handling

## 🔒 Rate Limiting

API được bảo vệ bởi rate limiting để tránh spam và abuse:

**Giới hạn:** 100 requests/phút/IP address

**Headers trả về:**

-   `X-RateLimit-Limit`: Số requests tối đa cho phép
-   `X-RateLimit-Remaining`: Số requests còn lại
-   `X-RateLimit-Reset`: Timestamp khi rate limit được reset

**Khi vượt quá giới hạn:**

-   HTTP Status: `429 Too Many Requests`
-   Response chứa thông tin chi tiết về rate limit
-   Header `Retry-After` cho biết thời gian chờ

**Ví dụ response khi rate limit exceeded:**

```json
{
    "error": true,
    "message": "Rate limit exceeded. Too many requests.",
    "code": 429,
    "details": {
        "max_attempts": 100,
        "current_attempts": 100,
        "time_window": "1 minute(s)",
        "retry_after": "60 seconds",
        "reset_time": "2025-07-09T17:11:53.963369Z"
    }
}
```

## Official Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Contributing

Thank you for considering contributing to Lumen! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# GeoIP API với Lumen

Một API GeoIP đầy đủ tính năng được xây dựng bằng Lumen (Laravel micro-framework), hỗ trợ tra cứu thông tin địa lý cho địa chỉ IP.

## 🌟 Tính năng

-   ✅ Hỗ trợ IPv4 và IPv6
-   ✅ Nhiều định dạng output: JSON, XML, CSV, YAML
-   ✅ Hỗ trợ JSONP callback
-   ✅ Cache để tối ưu hiệu suất
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

## 📄 Response Schema

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
    "organization": null,
    "timezone": "America/Chicago"
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

## Official Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Contributing

Thank you for considering contributing to Lumen! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# GeoIP Database Update Scripts

Các script để cập nhật GeoIP database từ MaxMind và DB-IP một cách tự động.

## 📁 Files

1. **`update-geoip-db.sh`** - Bash script (Linux/macOS)
2. **`update-geoip.php`** - PHP script (cross-platform)
3. **`UpdateGeoIPCommand.php`** - Laravel/Lumen command
4. **`setup-cron.sh`** - Cron job setup script

## 🚀 Cách sử dụng

### 1. PHP Script (Khuyến nghị)

```bash
# Cập nhật tất cả databases
php scripts/update-geoip.php

# Chỉ cập nhật MaxMind
php scripts/update-geoip.php maxmind

# Chỉ cập nhật DB-IP
php scripts/update-geoip.php dbip

# Hiển thị help
php scripts/update-geoip.php --help
```

### 2. Laravel/Lumen Command

```bash
# Cập nhật tất cả
php artisan geoip:update

# Chỉ MaxMind với force update
php artisan geoip:update maxmind --force

# DB-IP không backup
php artisan geoip:update dbip --no-backup
```

### 3. Bash Script

```bash
# Cập nhật tất cả
./scripts/update-geoip-db.sh

# Chỉ MaxMind
./scripts/update-geoip-db.sh maxmind

# Chỉ DB-IP
./scripts/update-geoip-db.sh dbip
```

## ⚙️ Cấu hình

### MaxMind License Key

1. Đăng ký tài khoản tại: https://www.maxmind.com/en/geolite2/signup
2. Lấy license key tại: https://www.maxmind.com/en/my_license_key
3. Thêm vào file `.env`:

```env
MAXMIND_LICENSE_KEY=your_license_key_here
```

### DB-IP (Miễn phí)

DB-IP không cần license key, script sẽ tự động download từ URL public.

## 🕒 Cập nhật tự động

### Setup Cron Jobs

```bash
# Chạy script setup
chmod +x scripts/setup-cron.sh
./scripts/setup-cron.sh
```

### Cron Schedule mặc định:

-   **MaxMind**: Chủ nhật hàng tuần lúc 2:00 AM
-   **DB-IP**: Ngày 1 hàng tháng lúc 3:00 AM
-   **Full update**: Ngày 1 mỗi quý lúc 4:00 AM

### Custom Cron Jobs

```bash
# Thêm vào crontab (crontab -e)

# Hàng ngày lúc 3:00 AM
0 3 * * * cd /var/www/geoip.vuiz.net && php scripts/update-geoip.php >> storage/logs/geoip-cron.log 2>&1

# Hàng tuần (Chủ nhật 2:00 AM)
0 2 * * 0 cd /var/www/geoip.vuiz.net && php scripts/update-geoip.php all

# Hàng tháng (ngày 1 lúc 4:00 AM)
0 4 1 * * cd /var/www/geoip.vuiz.net && php scripts/update-geoip.php
```

## 📊 Monitoring

### Kiểm tra logs

```bash
# Update logs
tail -f storage/logs/geoip-update.log

# Cron logs
tail -f storage/logs/geoip-cron.log
```

### Kiểm tra database info

```bash
# API endpoint
curl localhost:8000/geoip/stats

# Laravel command
php artisan geoip:stats
```

## 🗂️ Cấu trúc thư mục

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

## 🎯 Best Practices

1. **Test trước khi production**: Chạy manual update trước
2. **Monitor logs**: Setup log monitoring/alerting
3. **Backup strategy**: Script tự backup, nhưng nên có external backup
4. **Update frequency**:
    - MaxMind: Weekly (họ update weekly)
    - DB-IP: Monthly (họ update monthly)
5. **Health checks**: Setup monitoring cho API endpoints
6. **Fallback**: Giữ multiple providers để fallback

## 📈 Performance Tips

1. **Caching**: API đã có built-in caching
2. **CDN**: Có thể setup CDN cho static database files
3. **Load balancing**: Distribute requests across multiple instances
4. **Database optimization**: Regular cleanup của old databases

## 🔐 Security

1. **License key**: Không commit license key vào git
2. **File permissions**: Đảm bảo files không writable by web
3. **Web access**: PHP script mặc định disable web access
4. **Logs**: Secure log files, có thể chứa sensitive info

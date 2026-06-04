# Installation Guide for WiFi HaLow Testing System

## Prerequisites

### Software Requirements
- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: PDO, GD, JSON, MBString

### Hardware Requirements
- Minimum 2GB RAM
- 10GB disk space
- 1GHz processor

## Installation Steps

### 1. Download and Extract
```bash
# Clone or download the project
cd /var/www/html/
wget [project-url]
tar -xzvf wifi-testing-system.tar.gz
```

### 2. Set Permissions
```bash
# For Linux/Apache
sudo chown -R www-data:www-data wifi-testing-system
sudo chmod -R 755 wifi-testing-system
```

### 3. Database Setup

#### Option A: Using Command Line
```bash
mysql -u root -p
CREATE DATABASE wifi_holow_testing;
GRANT ALL PRIVILEGES ON wifi_holow_testing.* TO 'wifi_user'@'localhost' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
EXIT;

mysql -u wifi_user -p wifi_holow_testing < wifi-testing-system/database.sql
mysql -u wifi_user -p wifi_holow_testing < wifi-testing-system/database-seed-dummy-data.sql
```

#### Option B: Using phpMyAdmin
1. Open phpMyAdmin
2. Create new database: `wifi_holow_testing`
3. Import `database.sql`
4. Import `database-seed-dummy-data.sql`

### 4. Configure Database Connection

Edit `config/database.php`:

```php
$config = [
    'host' => 'localhost',
    'dbname' => 'wifi_holow_testing',
    'username' => 'your_db_username',
    'password' => 'your_db_password',
    'charset' => 'utf8mb4'
];
```

### 5. Virtual Host Configuration (Optional)

#### Apache
```apache
<VirtualHost *:80>
    ServerName wifitest.local
    DocumentRoot /var/www/html/wifi-testing-system/public
    
    <Directory /var/www/html/wifi-testing-system/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/wifi-test-error.log
    CustomLog ${APACHE_LOG_DIR}/wifi-test-access.log combined
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name wifitest.local;
    root /var/www/html/wifi-testing-system/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Test Installation

Open browser and navigate to:
```
http://your-domain/wifi-testing-system/public/
```

Or if using virtual host:
```
http://wifitest.local/
```

### 7. Login Credentials

**Default Admin Account:**
- Username: `admin`
- Password: `admin123`

**Default Viewer Account:**
- Username: `viewer`
- Password: `admin123`

**Demo Accounts (from seed data):**
- Operator: `operator1` / `admin123`
- Viewer: `viewer1` / `admin123`

## Post-Installation Checklist

- [ ] Database created and populated
- [ ] Database connection configured
- [ ] File permissions set correctly
- [ ] .htaccess working (mod_rewrite enabled)
- [ ] PHP extensions installed (PDO, GD, JSON)
- [ ] Test login successful
- [ ] All modules accessible
- [ ] Charts loading properly
- [ ] Export functions working

## Troubleshooting

### White Screen / 500 Error
```bash
# Check PHP error log
tail -f /var/log/apache2/error.log

# Check file permissions
ls -la /var/www/html/wifi-testing-system/

# Enable PHP error display (temporarily)
php_flag display_errors on
```

### Database Connection Error
```bash
# Test database connection
mysql -u [username] -p -h localhost

# Verify credentials in config/database.php
cat config/database.php
```

### Charts Not Loading
- Check internet connection (CDN resources)
- Verify Chart.js CDN is accessible
- Check browser console for errors

### Import Errors
```bash
# Check SQL syntax
mysql -u root -p < database.sql 2>&1 | head -20

# Check MySQL version
mysql --version
```

## Security Recommendations

1. **Change Default Passwords**
   ```sql
   UPDATE users SET password = SHA2('new_password', 256) WHERE username = 'admin';
   ```

2. **Remove Setup Files**
   ```bash
   rm database-seed-dummy-data.sql
   rm INSTALL.md
   ```

3. **Enable HTTPS**
   ```bash
   sudo certbot --apache -d wifitest.local
   ```

4. **Restrict Access**
   ```apache
   <Directory /var/www/html/wifi-testing-system>
       Order deny,allow
       Deny from all
       Allow from 192.168.1.0/24
   </Directory>
   ```

5. **Regular Backups**
   ```bash
   # Daily backup script
   0 2 * * * mysqldump -u root -pPASSWORD wifi_holow_testing > /backup/wifi-test-$(date +\%Y\%m\%d).sql
   ```

## Performance Optimization

### PHP OPcache
```ini
; php.ini settings
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

### MySQL Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_test_date ON connectivity_tests(test_date);
CREATE INDEX idx_node_id ON connectivity_tests(node_id);
CREATE INDEX idx_location ON range_tests(location_name);
```

### Apache Optimization
```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
```

## Maintenance

### Database Backup
```bash
# Full backup
mysqldump -u root -p wifi_holow_testing > backup_$(date +%Y%m%d).sql

# Backup with compression
mysqldump -u root -p wifi_holow_testing | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Log Rotation
```bash
# /etc/logrotate.d/wifi-test
/var/log/apache2/wifi-test-*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
}
```

## Upgrade Instructions

1. **Backup everything**
   ```bash
   mysqldump -u root -p wifi_holow_testing > backup.sql
   cp -r wifi-testing-system wifi-testing-system-backup
   ```

2. **Download new version**
3. **Compare config files**
4. **Update database schema if needed**
5. **Test thoroughly**
6. **Deploy**

## Support

For issues or questions:
- Check logs: `/var/log/apache2/`
- Verify permissions
- Test database connection
- Review error messages

## License

This system is for educational and military tactical use.

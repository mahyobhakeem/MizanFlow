# Production Deployment Guide — MizanFlow

This guide provides end-to-end instructions for deploying **MizanFlow** into an enterprise production environment.

---

## 1. System Requirements & Architecture

### Hardware Sizing Guidelines
| Capacity Tier | Concurrent Users | Daily Invoices | Min CPU | Min RAM | Storage |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Small Business** | 1 – 10 | Up to 1,000 | 2 vCPU | 4 GB | 40 GB SSD |
| **Medium Enterprise** | 10 – 50 | 1,000 – 10,000 | 4 vCPU | 8 GB | 100 GB NVMe |
| **Large Enterprise** | 50+ | 10,000+ | 8 vCPU | 16 GB+ | 250 GB+ NVMe |

### Software Stack Prerequisites
- **Operating System:** Ubuntu 24.04 LTS / Debian 12 / Windows Server 2022.
- **PHP Version:** **PHP 8.3 or 8.4** (CLI and FPM).
  - *Required PHP Extensions:* `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `opcache`.
- **Database Server:** **MySQL 8.4 LTS** (InnoDB storage engine, strict SQL mode enabled).
- **Web Server:** **Nginx** (recommended) or Apache 2.4.
- **Process Supervisor:** `Supervisor` (Linux) for queue workers.
- **Node.js (Build Phase):** Node.js 20+ LTS and npm (for building front-end assets on CI/CD or build server).

---

## 2. Step-by-Step Server Setup & Code Deployment

### Step 1: Install System Packages (Ubuntu Example)
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server supervisor unzip git curl

# Install PHP 8.3 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-bcmath php8.3-intl php8.3-opcache php8.3-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Configure MySQL Database
```sql
CREATE DATABASE mizanflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mizanflow_user'@'localhost' IDENTIFIED BY 'StrongRandomPassword_123!';
GRANT ALL PRIVILEGES ON mizanflow.* TO 'mizanflow_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 3: Deploy Application Code
Deploy code to `/var/www/mizanflow`:
```bash
cd /var/www/mizanflow
git clone <repository_url> .

# Install PHP dependencies without dev packages
composer install --no-dev --optimize-autoloader --no-interaction
```

### Step 4: Configure Production Environment (`.env`)
Copy the production template and set strict environment variables:
```bash
cp .env.example .env
```
Edit `.env` and configure:
```ini
APP_NAME=MizanFlow
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mizanflow.yourcompany.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

BCRYPT_ROUNDS=12
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mizanflow
DB_USERNAME=mizanflow_user
DB_PASSWORD=StrongRandomPassword_123!

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

Generate the encryption key:
```bash
php artisan key:generate --force
```

### Step 5: Execute Database Migrations & Seeds
```bash
# Run database migrations
php artisan migrate --force

# Run initial seed data (if deploying first-time master data)
php artisan db:seed --force
```

### Step 6: Build Front-End Assets
```bash
npm install --ignore-scripts
npm run build
```

### Step 7: File Permissions & Ownership
```bash
sudo chown -R www-data:www-data /var/www/mizanflow
sudo find /var/www/mizanflow -type f -exec chmod 644 {} \;
sudo find /var/www/mizanflow -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/mizanflow/storage /var/www/mizanflow/bootstrap/cache
```

### Step 8: Cache Configuration & Routes for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 3. Web Server Configuration (Nginx)

Create `/etc/nginx/sites-available/mizanflow`:
```nginx
server {
    listen 80;
    server_name mizanflow.yourcompany.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name mizanflow.yourcompany.com;

    root /var/www/mizanflow/public;
    index index.php index.html;

    # SSL Certificates (Let's Encrypt / Commercial)
    ssl_certificate /etc/letsencrypt/live/mizanflow.yourcompany.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mizanflow.yourcompany.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' 'unsafe-inline' data: font:;" always;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache static assets (fonts, images, compiled Vite assets)
    location ~* \.(woff|woff2|ttf|svg|eot|png|jpg|jpeg|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Enable the site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/mizanflow /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 4. PHP-FPM & OPcache Optimization

Edit `/etc/php/8.3/fpm/php.ini`:
```ini
; Production Performance Settings
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60

; OPcache Production Tuning
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.save_comments = 1
opcache.fast_shutdown = 1
```
Restart PHP-FPM:
```bash
sudo systemctl restart php8.3-fpm
```

---

## 5. Background Task Scheduling & Queue Workers

### A. Cron Task Scheduler
Add the Laravel schedule worker to the `www-data` crontab:
```bash
sudo crontab -u www-data -e
```
Add line:
```cron
* * * * * cd /var/www/mizanflow && php artisan schedule:run >> /dev/null 2>&1
```

### B. Supervisor Queue Worker
Create `/etc/supervisor/conf.d/mizanflow-worker.conf`:
```ini
[program:mizanflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mizanflow/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mizanflow/storage/logs/worker.log
stopwaitsecs=3600
```
Update Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mizanflow-worker:*
```

---

## 6. Backup & Disaster Recovery Strategy

1. **Automated Daily Database Backups:**
   Set up a nightly cron job to export MySQL dumps with single-transaction isolation:
   ```bash
   mysqldump -u mizanflow_user -p'password' --single-transaction --quick --routines --triggers mizanflow | gzip > /backups/mizanflow_$(date +\%F).sql.gz
   ```
2. **Offsite Storage:** Synchronize backups to an encrypted remote object storage bucket (e.g., S3, Google Cloud Storage, or private backup server).
3. **Retention Policy:** Retain daily backups for 30 days, weekly backups for 12 weeks, and monthly backups for 1 year.
4. **Disaster Recovery Drill:** Regularly restore the latest database dump to a staging server to verify backup integrity and test recovery procedures.

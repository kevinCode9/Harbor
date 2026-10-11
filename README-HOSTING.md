# Hosting Guide - Liveled Platform

This guide explains how to deploy the Liveled Laravel platform to cPanel or VPS hosting environments.

---

## Table of Contents
- [cPanel Hosting](#cpanel-hosting)
- [VPS Hosting](#vps-hosting)
- [Environment Configuration](#environment-configuration)
- [Common Issues](#common-issues)

---

## cPanel Hosting

### Prerequisites
- cPanel account with PHP 8.1+ support
- MySQL database access
- File Manager or FTP access
- SSL certificate (recommended)

### Step 1: Upload Files

**Via File Manager:**
1. Log in to cPanel
2. Navigate to **File Manager** → **home/your_username**
3. Upload all project files
4. Exclude from upload: `node_modules`, `vendor`, `.git`, `.env.local`

**Via FTP:**
1. Create FTP account in cPanel → **FTP Accounts**
2. Use FileZilla or similar to upload files

### Step 2: Install Dependencies

**Option A: Install Locally First (Recommended)**
```bash
# On your local machine
composer install --no-dev --optimize-autoloader
npm install
npm run build
```
Then upload the `vendor` and `node_modules` folders to cPanel.

**Option B: Install on Server (if Terminal available)**
```bash
cd /home/your_username
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### Step 3: Create Database

1. Go to **MySQL® Database Wizard**
2. Create database: `your_username_liveled`
3. Create database user with strong password
4. Grant all privileges to the user
5. Note down: database name, username, password

### Step 4: Configure .env File

**Critical Changes Required:**

```env
# Application Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration - CHANGE THESE
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_username_liveled        # Change to your cPanel database name
DB_USERNAME=your_username_dbuser        # Change to your cPanel database user
DB_PASSWORD=your_strong_password        # Change to your database password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file

# Admin Password
ADMIN_PASSWORD=admin1234                # Change to secure password
```

**What to Change:**
- `APP_URL` - Your actual domain
- `DB_DATABASE` - Your cPanel database name (format: `username_dbname`)
- `DB_USERNAME` - Your cPanel database user (format: `username_dbuser`)
- `DB_PASSWORD` - Your database password
- `ADMIN_PASSWORD` - Secure admin password

### Step 5: Generate Application Key

**If Terminal Available:**
```bash
php artisan key:generate
```

**If No Terminal:**
Copy the `APP_KEY` from your local `.env` file to the production `.env` file.

### Step 6: Configure Document Root


1. Move contents of `public` folder to `public_html`
2. Edit `public_html/index.php`:

EDIT INDEX.PHP TO MATCH UR CURRENT USERNAME OF CPANEL OR VPS!! 

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// CHANGE THESE PATHS based on your cPanel username
// If using main domain (public_html):
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// If using subdomain (public/subdomain):
// require __DIR__.'/../../vendor/autoload.php';
// $app = require_once __DIR__.'/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Path Configuration Guide:**
- **Main Domain** (`public_html`): Use `../vendor/autoload.php` and `../bootstrap/app.php`
- **Subdomain** (`public/subdomain`): Use `../../vendor/autoload.php` and `../../bootstrap/app.php`
- **Add-on Domain** (`public/addon_domain`): Use `../../vendor/autoload.php` and `../../bootstrap/app.php`

### Step 7: Set File Permissions

**Via File Manager:**
1. Select `storage` folder → **Change Permissions** → Set to **755**
2. Select `bootstrap/cache` folder → **Change Permissions** → Set to **755**

**Via SSH (if available):**
```bash
chmod -R 755 storage bootstrap/cache
chown -R your_username:your_username storage bootstrap/cache
```

### Step 8: Run Database Migrations

**Create setup.php in public folder:**

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run migrations
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "Migrations completed successfully.\n";

// Run seeders
\Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
echo "Seeders completed successfully.\n";

// Clear cache
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('route:clear');
\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "Cache cleared successfully.\n";
```

Visit `https://your-domain.com/setup.php` to run it, then **delete the file**.

### Step 9: Configure SSL

1. Go to **SSL/TLS Status**
2. Select your domain
3. Click **Run AutoSSL** to install Let's Encrypt certificate

### Step 10: Configure .htaccess

Create/Edit `.htaccess` in your public directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
```

---

## VPS Hosting

### Prerequisites
- VPS with Ubuntu 20.04+ or CentOS 7+
- SSH access
- Root or sudo privileges
- Domain pointed to VPS IP

### Step 1: Install Required Software

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-bcmath git composer unzip
```

**CentOS/RHEL:**
```bash
sudo yum update
sudo yum install -y nginx mariadb-server php81-php-fpm php81-php-mysqlnd php81-php-mbstring php81-php-xml php81-php-curl php81-php-zip php81-php-bcmath git composer unzip
```

### Step 2: Configure MySQL

```bash
sudo mysql_secure_installation
```

Create database:
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE liveled;
CREATE USER 'liveled_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON liveled.* TO 'liveled_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Deploy Application

```bash
# Clone or upload files
cd /var/www
git clone your-repo-url liveled
# OR upload via SCP

cd liveled
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### Step 4: Configure .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=liveled
DB_USERNAME=liveled_user
DB_PASSWORD=strong_password

SESSION_DRIVER=database
CACHE_DRIVER=redis
QUEUE_CONNECTION=database

ADMIN_PASSWORD=admin1234
```

### Step 5: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/liveled
sudo chmod -R 755 /var/www/liveled/storage
sudo chmod -R 755 /var/www/liveled/bootstrap/cache
```

### Step 6: Run Migrations

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 7: Configure Nginx

Create `/etc/nginx/sites-available/liveled`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/liveled/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/liveled /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 8: Configure SSL with Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

### Step 9: Set Up Supervisor (for Queues)

Create `/etc/supervisor/conf.d/liveled-worker.conf`:

```ini
[program:liveled-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/liveled/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/liveled/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start liveled-worker:*
```

---

## Environment Configuration

### Files to Modify When Moving to New Environment

#### 1. .env File
**Critical variables to change:**
```env
APP_URL=https://new-domain.com              # Your new domain
DB_DATABASE=new_username_liveled           # New database name
DB_USERNAME=new_username_dbuser            # New database user
DB_PASSWORD=new_password                   # New database password
ADMIN_PASSWORD=new_secure_password         # New admin password
```

#### 2. index.php (if using public_html)
**Change paths based on installation location:**
```php
// Main domain (public_html)
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Subdomain (public/subdomain)
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

// Add-on domain (public/addon_domain)
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
```

#### 3. Database Configuration
**SQLite (for simple setups):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

**MySQL (recommended for production):**
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_username_dbname
DB_USERNAME=your_username_dbuser
DB_PASSWORD=your_password
```

#### 4. File Permissions
**cPanel:**
```bash
chmod -R 755 storage bootstrap/cache
```

**VPS:**
```bash
sudo chown -R www-data:www-data /var/www/liveled
sudo chmod -R 755 /var/www/liveled/storage
```

### Username/Directory Mapping

**cPanel Structure:**
```
/home/username/
├── public_html/              # Main domain
├── public/subdomain/         # Subdomain
├── public/addon_domain/      # Add-on domain
└── liveled/                  # Application files
```

**Database Naming Convention:**
- Database: `username_dbname`
- User: `username_dbuser`
- Example: `lubqtcbh_liveled`, `lubqtcbh_liveled_user`

---

## Common Issues

### Issue 1: 500 Internal Server Error

**Solutions:**
1. Check `.env` file configuration
2. Verify database credentials
3. Set correct file permissions: `chmod -R 755 storage bootstrap/cache`
4. Check PHP version (requires 8.1+)
5. Clear cache: `php artisan cache:clear`

### Issue 2: Database Connection Failed

**Solutions:**
1. Verify database name, username, password in `.env`
2. Check if MySQL service is running: `sudo systemctl status mysql`
3. Ensure database user has proper privileges
4. Test connection manually via cPanel phpMyAdmin

### Issue 3: Images/Assets Not Loading

**Solutions:**
1. Verify `APP_URL` in `.env` matches your domain
2. Check storage link: `php artisan storage:link`
3. Verify asset permissions
4. Clear browser cache

### Issue 4: Admin Panel Not Accessible

**Solutions:**
1. Verify route is accessible: `/admin777888xxx`
2. Check `ADMIN_PASSWORD` in `.env`
3. Clear route cache: `php artisan route:clear`
4. Check middleware configuration

### Issue 5: SSL Certificate Errors

**Solutions:**
1. Run AutoSSL in cPanel
2. For VPS: `sudo certbot --nginx -d your-domain.com`
3. Ensure domain DNS is correctly pointed
4. Wait for DNS propagation (up to 48 hours)

---

## Security Recommendations

1. **Change Default Passwords**
   - Admin password in `.env`
   - Database password
   - cPanel/SSH passwords

2. **Enable HTTPS**
   - Install SSL certificate
   - Force HTTPS redirect in `.htaccess` or Nginx config

3. **Disable Debug Mode**
   ```env
   APP_DEBUG=false
   ```

4. **Restrict File Access**
   - Block access to `.env`, `.git`, `composer.json`
   - Use proper file permissions

5. **Regular Backups**
   - cPanel: Use Backup Wizard
   - VPS: Set up automated backups

6. **Keep Software Updated**
   - Regularly update Laravel packages
   - Update PHP version when needed
   - Update server packages

---

## Post-Deployment Checklist

- [ ] Files uploaded successfully
- [ ] Dependencies installed (vendor, node_modules)
- [ ] Database created and configured
- [ ] .env file updated with correct credentials
- [ ] Application key generated
- [ ] Document root configured correctly
- [ ] File permissions set (755 for storage, bootstrap/cache)
- [ ] Database migrations run successfully
- [ ] SSL certificate installed
- [ ] .htaccess configured
- [ ] Admin panel accessible
- [ ] Test user flows (phrase pages, admin panel)
- [ ] Backups configured
- [ ] Monitoring set up

---

## Support

For issues or questions:
- Check Laravel documentation: https://laravel.com/docs
- Check cPanel documentation: https://docs.cpanel.net
- Review error logs in `storage/logs/laravel.log`

---

**Last Updated:** June 2026
**Platform Version:** Laravel 10.x

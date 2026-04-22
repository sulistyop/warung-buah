sudo bash -c 'cat > /root/deploy-warung-buah.sh' <<'BASH'
#!/usr/bin/env bash
set -euo pipefail

# =========================
# UBAH SESUAI SERVER ANDA
# =========================
APP_DIR="/var/www/warung-buah"
DEPLOY_USER="sulistyop"
DOMAIN="example.com"
DB_NAME="warung_buah"
DB_USER="warung_user"
DB_PASS="GantiPasswordKuat123!"
APP_URL="http://example.com"   # ganti ke https://domain-anda.com setelah SSL
PHP_VER="8.2"

echo "==> Update & install packages"
apt update -y
apt upgrade -y
apt install -y nginx mysql-server supervisor git curl unzip software-properties-common
apt install -y php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-bcmath php${PHP_VER}-gd php${PHP_VER}-intl

echo "==> Install Composer (if missing)"
if ! command -v composer >/dev/null 2>&1; then
  cd /tmp
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php
  mv composer.phar /usr/local/bin/composer
fi

echo "==> Install Node.js 20 (if missing)"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt install -y nodejs
fi

echo "==> Enable services"
systemctl enable --now nginx
systemctl enable --now php${PHP_VER}-fpm
systemctl enable --now mysql
systemctl enable --now supervisor

echo "==> Create database/user"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

if [ ! -d "${APP_DIR}" ]; then
  echo "ERROR: folder ${APP_DIR} tidak ditemukan. Clone repo dulu ke path itu."
  exit 1
fi

echo "==> Setup app env"
cd "${APP_DIR}"
if [ ! -f .env ]; then
  cp .env.example .env
fi

set_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${val}|g" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

set_env "APP_ENV" "production"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "${APP_URL}"
set_env "DB_CONNECTION" "mysql"
set_env "DB_HOST" "127.0.0.1"
set_env "DB_PORT" "3306"
set_env "DB_DATABASE" "${DB_NAME}"
set_env "DB_USERNAME" "${DB_USER}"
set_env "DB_PASSWORD" "${DB_PASS}"
set_env "QUEUE_CONNECTION" "database"
set_env "SESSION_DRIVER" "database"
set_env "CACHE_STORE" "database"

echo "==> Install dependencies & build"
composer install --no-dev --optimize-autoloader
npm ci
npm run build

echo "==> Laravel optimize/migrate"
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Permission"
chown -R ${DEPLOY_USER}:www-data "${APP_DIR}"
chmod -R ug+rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "==> Nginx config"
cat > /etc/nginx/sites-available/warung-buah <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/warung-buah /etc/nginx/sites-enabled/warung-buah
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> Supervisor queue worker"
cat > /etc/supervisor/conf.d/warung-buah-worker.conf <<SUP
[program:warung-buah-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
SUP

supervisorctl reread
supervisorctl update
supervisorctl start warung-buah-worker:* || true

echo "==> Scheduler (cron)"
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u www-data -l 2>/dev/null | grep -v "schedule:run" ; echo "${CRON_LINE}" ) | crontab -u www-data -

echo "==> DONE"
systemctl --no-pager status nginx | head -n 8
systemctl --no-pager status php${PHP_VER}-fpm | head -n 8
systemctl --no-pager status mysql | head -n 8
supervisorctl status
echo "Aplikasi harusnya sudah bisa diakses via: ${APP_URL}"
BASH

sudo chmod +x /root/deploy-warung-buah.sh
sudo /root/deploy-warung-buah.sh

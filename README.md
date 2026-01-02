# COHV-App

COHV-App adalah aplikasi web berbasis Laravel yang digunakan untuk pengelolaan dan sinkronisasi data COHV. Aplikasi ini dirancang agar mudah diinstal, dikembangkan, dan digunakan oleh user maupun developer.

---

## Overview Teknologi
- Backend: Laravel (PHP)
- Frontend: Blade Template, JavaScript
- Database: MySQL / PostgreSQL
- Automation: Python (data synchronization)
- Package Manager: Composer & NPM

---

## Instalasi

### 1. Clone Repository
git clone https://github.com/tataamal/cohv-app.git
cd cohv-app

### 2. Setup Environment
cp .env.example .env

Sesuaikan konfigurasi berikut di file .env:
- APP_NAME
- APP_ENV
- APP_KEY
- APP_URL
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### 3. Install Dependency
composer install
npm install

### 4. Generate App Key
php artisan key:generate

### 5. Migrasi Database
php artisan migrate --seed

### 6. Build Asset
npm run dev
atau untuk production:
npm run build

### 7. Jalankan Aplikasi
php artisan serve
Akses melalui http://localhost:8000

---

## Struktur Infrastruktur

app/                : Core aplikasi (Controller, Model)
routes/             : Routing web & API
resources/views/    : Blade templates
database/migrations : Struktur database
database/seeders    : Data awal
public/             : Asset publik
scripts/            : Python automation & sync
tests/              : Unit & feature test

---

## Sinkronisasi Data (Python)
Project ini menyertakan script Python seperti:
- sync_cohv.py
- sync_gr.py

Script digunakan untuk:
- Sinkronisasi data eksternal
- Automasi update database
- Integrasi API

Jalankan dengan:
python sync_cohv.py

Pastikan Python sudah terinstall.

---

## Deployment (Production)

- PHP >= 8.x
- Web server (Nginx / Apache)
- Composer & Node.js
- Database server

Langkah deployment:
composer install --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache

Setup cron:
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1

---

## Testing
php artisan test

---

## Kontribusi
1. Fork repository
2. Buat branch feature
3. Commit perubahan
4. Pull request

---

## License
Mengikuti lisensi yang tertera pada repository.

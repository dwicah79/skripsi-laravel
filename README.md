<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<h1 align="center">📊 Skripsi: Perbandingan Performa Insert Data Besar dengan Laravel</h1>

---

## 📌 Deskripsi

Repositori ini berisi proyek Laravel yang digunakan untuk melakukan pengujian performa dalam proses **import dan insert data besar dari file CSV ke database MySQL**. Proyek ini dibuat sebagai bagian dari **penelitian skripsi** dengan fokus pada penggunaan **Laravel Framework** sebagai backend.

### 🎯 Tujuan

Menguji kecepatan dan efisiensi Laravel dalam menangani file CSV besar (500.000 – 1.500.000 baris) dan menganalisis performanya melalui alat bantu seperti **TablePlus** atau **phpMyAdmin**.

---

## 🧪 Teknologi yang Digunakan

-   **Laravel 12**
-   **PHP 8.2+**
-   **MySQL/MariaDB**
-   **Laragon / XAMPP** (server lokal)
-   **TablePlus / phpMyAdmin** (analisa database)
-   (Opsional) Laravel Excel, Laravel Queue, dan fitur chunking untuk optimasi

---

## 🧭 Alur Proses

1. User mengunggah file CSV melalui antarmuka Laravel.
2. File disimpan di folder `storage/app/csv` atau `public/csv`.
3. Sistem membaca isi CSV.
4. Data dimasukkan ke database menggunakan metode:
    - `insert()` batch
    - Eloquent mass insert
    - Chunk (per batch 500 / 1000)
5. Waktu dan memori proses dicatat untuk evaluasi performa.
6. Hasil akhir dianalisis melalui TablePlus/phpMyAdmin dan log Laravel.

---

## ⚙️ Instalasi Proyek

### 💻 Persyaratan

-   Composer
-   PHP >= 8.2
-   MySQL atau MariaDB
-   Laragon atau XAMPP

### 📥 Langkah Instalasi

```bash
git clone https://github.com/username/skripsi-laravel-csv-import.git
cd skripsi-laravel-csv-import
cp .env.example .env
composer install
php artisan key:generate
```

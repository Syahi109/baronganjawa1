# Barongan Karangsari

Website informasi Barongan Karangsari dengan galeri, jadwal, forum komentar, admin panel, dan fitur multi-bahasa.

## Fitur
- Landing page budaya Barongan Karangsari
- Galeri foto & video (dengan upload admin)
- Jadwal & informasi pertunjukan (editable oleh admin)
- Forum/ulasan pengunjung (tersimpan di MySQL)
- Admin login + edit/hapus komentar
- Multi bahasa: Indonesia, English, 日本語

## Struktur Singkat
- `index.php` halaman utama
- `forum.php` halaman forum khusus
- `koneksi.php` koneksi database MySQL
- `forum.sql` struktur database
- `uploads/` penyimpanan file gambar (diabaikan git)

## Setup Lokal
1. Jalankan Apache & MySQL (XAMPP)
2. Import `forum.sql` ke database `barongan`
3. Pastikan `koneksi.php` sesuai kredensial MySQL
4. Buka `http://localhost/barongan/index.php`

## Akun Admin Default
- Username: `admin`
- Password: `admin123`

> Ganti password admin setelah login untuk keamanan.

<?php
session_start();
require_once "koneksi.php";

$errors = [];
$success = "";
$admin_error = "";
$settings_success = "";

function new_captcha()
{
    $_SESSION["captcha_a"] = random_int(1, 9);
    $_SESSION["captcha_b"] = random_int(1, 9);
}

function is_admin(): bool
{
    return !empty($_SESSION["is_admin"]);
}

if (!isset($_SESSION["captcha_a"], $_SESSION["captcha_b"])) {
    new_captcha();
}

// Ensure admin table exists and at least one admin account is available
$admin_table_exists = false;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admin_users'");
if ($table_check) {
    $admin_table_exists = mysqli_num_rows($table_check) > 0;
    mysqli_free_result($table_check);
}

if (!$admin_table_exists) {
    $create_sql = "CREATE TABLE admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_sql);
    $admin_table_exists = true;
}

if ($admin_table_exists) {
    $admin_check = mysqli_query($conn, "SELECT COUNT(*) AS c FROM admin_users");
    if ($admin_check) {
        $row = mysqli_fetch_assoc($admin_check);
        $count = (int)($row["c"] ?? 0);
        mysqli_free_result($admin_check);
        if ($count === 0) {
            $default_user = "admin";
            $default_pass = password_hash("admin123", PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $default_user, $default_pass);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Ensure site settings table exists and defaults are available
$settings_table_exists = false;
$settings_check = mysqli_query($conn, "SHOW TABLES LIKE 'site_settings'");
if ($settings_check) {
    $settings_table_exists = mysqli_num_rows($settings_check) > 0;
    mysqli_free_result($settings_check);
}

if (!$settings_table_exists) {
    $create_settings = "CREATE TABLE site_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_settings);
    $settings_table_exists = true;
}

function get_setting(mysqli $conn, string $key, string $default = ""): string
{
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM site_settings WHERE setting_key = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if ($row && isset($row["setting_value"])) {
            return (string)$row["setting_value"];
        }
    }
    return $default;
}

function set_setting(mysqli $conn, string $key, string $value): void
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO site_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $key, $value);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function video_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === "" || $url === "https://") {
        return "";
    }
    if (preg_match("~youtu\\.be/([A-Za-z0-9_-]+)~", $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }
    if (preg_match("~youtube\\.com/watch\\?v=([A-Za-z0-9_-]+)~", $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }
    if (preg_match("~youtube\\.com/embed/([A-Za-z0-9_-]+)~", $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }
    if (preg_match("~drive\\.google\\.com/file/d/([^/]+)~", $url, $m)) {
        return "https://drive.google.com/file/d/" . $m[1] . "/preview";
    }
    return "";
}

$defaults = [
    "hero_image" => "hero.jpg",
    "story_image" => "story.jpg",
    "jadwal_info" => "Belum terdapat jadwal tetap.\nContoh: 17 Agustus 2026 — Peringatan HUT RI\nContoh: (Tanggal) — Sedekah Bumi Karangsari",
    "kontak_info" => "Nama: (isi nanti)\nTelepon/WA: (isi nanti)\nAlamat: Karangsari, (isi nanti)",
    "gallery_photo1" => "gallery1.jpg",
    "gallery_caption1" => "Pementasan Barongan pada acara sedekah bumi (isi tanggal).",
    "gallery_photo2" => "gallery2.jpg",
    "gallery_caption2" => "Persiapan kostum dan riasan sebelum tampil.",
    "gallery_photo3" => "gallery3.jpg",
    "gallery_caption3" => "Suasana penonton dan iringan musik (isi tanggal).",
    "gallery_video_link" => "https://",
    "gallery_video_caption" => "Cuplikan pertunjukan Barongan (isi sumber video)."
];

foreach ($defaults as $key => $value) {
    $current = get_setting($conn, $key, "");
    if ($current === "") {
        set_setting($conn, $key, $value);
    }
}

$current_hero = get_setting($conn, "hero_image", $defaults["hero_image"]);
$current_story = get_setting($conn, "story_image", $defaults["story_image"]);

if (isset($_GET["logout"])) {
    $_SESSION["is_admin"] = false;
    $_SESSION["admin_user"] = null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["admin_login"])) {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    if ($username === "" || $password === "") {
        $admin_error = "Username dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, password_hash FROM admin_users WHERE username = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($user && password_verify($password, $user["password_hash"])) {
                $_SESSION["is_admin"] = true;
                $_SESSION["admin_user"] = $username;
                $_SESSION["admin_login_time"] = time();
                $_SESSION["admin_login_notice"] = true;
            } else {
                $admin_error = "Login gagal. Periksa username dan password.";
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && is_admin() && isset($_POST["settings_submit"])) {
    $hero_image = trim($_POST["hero_image"] ?? "");
    $story_image = trim($_POST["story_image"] ?? "");
    $jadwal_info = trim($_POST["jadwal_info"] ?? "");
    $kontak_info = trim($_POST["kontak_info"] ?? "");
    $gallery_photo1 = trim($_POST["gallery_photo1"] ?? "");
    $gallery_caption1 = trim($_POST["gallery_caption1"] ?? "");
    $gallery_photo2 = trim($_POST["gallery_photo2"] ?? "");
    $gallery_caption2 = trim($_POST["gallery_caption2"] ?? "");
    $gallery_photo3 = trim($_POST["gallery_photo3"] ?? "");
    $gallery_caption3 = trim($_POST["gallery_caption3"] ?? "");
    $gallery_video_link = trim($_POST["gallery_video_link"] ?? "");
    $gallery_video_caption = trim($_POST["gallery_video_caption"] ?? "");

    $allowed_ext = ["jpg", "jpeg", "png", "webp"];
    $max_size = 2 * 1024 * 1024;
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_POST["delete_hero"])) {
        if (strpos($current_hero, "uploads/") === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $current_hero);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        set_setting($conn, "hero_image", $defaults["hero_image"]);
        $hero_image = $defaults["hero_image"];
    }

    if (isset($_POST["delete_story"])) {
        if (strpos($current_story, "uploads/") === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $current_story);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        set_setting($conn, "story_image", $defaults["story_image"]);
        $story_image = $defaults["story_image"];
    }

    $current_gallery1 = get_setting($conn, "gallery_photo1", $defaults["gallery_photo1"]);
    $current_gallery2 = get_setting($conn, "gallery_photo2", $defaults["gallery_photo2"]);
    $current_gallery3 = get_setting($conn, "gallery_photo3", $defaults["gallery_photo3"]);
    if (isset($_POST["delete_gallery1"])) {
        if (strpos($current_gallery1, "uploads/") === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $current_gallery1);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        set_setting($conn, "gallery_photo1", $defaults["gallery_photo1"]);
        $gallery_photo1 = $defaults["gallery_photo1"];
    }
    if (isset($_POST["delete_gallery2"])) {
        if (strpos($current_gallery2, "uploads/") === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $current_gallery2);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        set_setting($conn, "gallery_photo2", $defaults["gallery_photo2"]);
        $gallery_photo2 = $defaults["gallery_photo2"];
    }
    if (isset($_POST["delete_gallery3"])) {
        if (strpos($current_gallery3, "uploads/") === 0) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $current_gallery3);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        set_setting($conn, "gallery_photo3", $defaults["gallery_photo3"]);
        $gallery_photo3 = $defaults["gallery_photo3"];
    }

    $uploaded_hero_path = "";
    $uploaded_story_path = "";
    $uploaded_gallery1_path = "";
    $uploaded_gallery2_path = "";
    $uploaded_gallery3_path = "";

    if (!empty($_FILES["hero_file"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["hero_file"]["name"], PATHINFO_EXTENSION));
        if ($_FILES["hero_file"]["size"] > $max_size) {
            $errors[] = "Ukuran foto hero terlalu besar. Maksimal 2MB.";
        } elseif (in_array($ext, $allowed_ext, true) && is_uploaded_file($_FILES["hero_file"]["tmp_name"])) {
            $filename = "hero_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["hero_file"]["tmp_name"], $dest)) {
                $uploaded_hero_path = "uploads/" . $filename;
                set_setting($conn, "hero_image", $uploaded_hero_path);
            }
        } else {
            $errors[] = "Format foto hero tidak didukung. Gunakan jpg, jpeg, png, atau webp.";
        }
    }

    if (!empty($_FILES["story_file"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["story_file"]["name"], PATHINFO_EXTENSION));
        if ($_FILES["story_file"]["size"] > $max_size) {
            $errors[] = "Ukuran foto alur cerita terlalu besar. Maksimal 2MB.";
        } elseif (in_array($ext, $allowed_ext, true) && is_uploaded_file($_FILES["story_file"]["tmp_name"])) {
            $filename = "story_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["story_file"]["tmp_name"], $dest)) {
                $uploaded_story_path = "uploads/" . $filename;
                set_setting($conn, "story_image", $uploaded_story_path);
            }
        } else {
            $errors[] = "Format foto alur cerita tidak didukung. Gunakan jpg, jpeg, png, atau webp.";
        }
    }

    if (!empty($_FILES["gallery_file1"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["gallery_file1"]["name"], PATHINFO_EXTENSION));
        if ($_FILES["gallery_file1"]["size"] > $max_size) {
            $errors[] = "Ukuran foto galeri 1 terlalu besar. Maksimal 2MB.";
        } elseif (in_array($ext, $allowed_ext, true) && is_uploaded_file($_FILES["gallery_file1"]["tmp_name"])) {
            $filename = "gallery1_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["gallery_file1"]["tmp_name"], $dest)) {
                $uploaded_gallery1_path = "uploads/" . $filename;
                set_setting($conn, "gallery_photo1", $uploaded_gallery1_path);
            }
        } else {
            $errors[] = "Format foto galeri 1 tidak didukung. Gunakan jpg, jpeg, png, atau webp.";
        }
    }

    if (!empty($_FILES["gallery_file2"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["gallery_file2"]["name"], PATHINFO_EXTENSION));
        if ($_FILES["gallery_file2"]["size"] > $max_size) {
            $errors[] = "Ukuran foto galeri 2 terlalu besar. Maksimal 2MB.";
        } elseif (in_array($ext, $allowed_ext, true) && is_uploaded_file($_FILES["gallery_file2"]["tmp_name"])) {
            $filename = "gallery2_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["gallery_file2"]["tmp_name"], $dest)) {
                $uploaded_gallery2_path = "uploads/" . $filename;
                set_setting($conn, "gallery_photo2", $uploaded_gallery2_path);
            }
        } else {
            $errors[] = "Format foto galeri 2 tidak didukung. Gunakan jpg, jpeg, png, atau webp.";
        }
    }
    if (!empty($_FILES["gallery_file3"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["gallery_file3"]["name"], PATHINFO_EXTENSION));
        if ($_FILES["gallery_file3"]["size"] > $max_size) {
            $errors[] = "Ukuran foto galeri 3 terlalu besar. Maksimal 2MB.";
        } elseif (in_array($ext, $allowed_ext, true) && is_uploaded_file($_FILES["gallery_file3"]["tmp_name"])) {
            $filename = "gallery3_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES["gallery_file3"]["tmp_name"], $dest)) {
                $uploaded_gallery3_path = "uploads/" . $filename;
                set_setting($conn, "gallery_photo3", $uploaded_gallery3_path);
            }
        } else {
            $errors[] = "Format foto galeri 3 tidak didukung. Gunakan jpg, jpeg, png, atau webp.";
        }
    }

    if ($hero_image === "" && empty($_FILES["hero_file"]["name"])) {
        $errors[] = "Nama file foto hero tidak boleh kosong.";
    }
    if ($story_image === "" && empty($_FILES["story_file"]["name"])) {
        $errors[] = "Nama file foto alur cerita tidak boleh kosong.";
    }
    if ($gallery_photo1 === "" && empty($_FILES["gallery_file1"]["name"])) {
        $errors[] = "Nama file foto galeri 1 tidak boleh kosong.";
    }
    if ($gallery_photo2 === "" && empty($_FILES["gallery_file2"]["name"])) {
        $errors[] = "Nama file foto galeri 2 tidak boleh kosong.";
    }
    if ($gallery_photo3 === "" && empty($_FILES["gallery_file3"]["name"])) {
        $errors[] = "Nama file foto galeri 3 tidak boleh kosong.";
    }

    if ($hero_image !== "" && $uploaded_hero_path === "") {
        set_setting($conn, "hero_image", $hero_image);
    }
    if ($story_image !== "" && $uploaded_story_path === "") {
        set_setting($conn, "story_image", $story_image);
    }
    if ($jadwal_info !== "") {
        set_setting($conn, "jadwal_info", $jadwal_info);
    }
    if ($kontak_info !== "") {
        set_setting($conn, "kontak_info", $kontak_info);
    }
    if ($gallery_photo1 !== "" && $uploaded_gallery1_path === "") {
        set_setting($conn, "gallery_photo1", $gallery_photo1);
    }
    if ($gallery_caption1 !== "") {
        set_setting($conn, "gallery_caption1", $gallery_caption1);
    }
    if ($gallery_photo2 !== "" && $uploaded_gallery2_path === "") {
        set_setting($conn, "gallery_photo2", $gallery_photo2);
    }
    if ($gallery_caption2 !== "") {
        set_setting($conn, "gallery_caption2", $gallery_caption2);
    }
    if ($gallery_photo3 !== "" && $uploaded_gallery3_path === "") {
        set_setting($conn, "gallery_photo3", $gallery_photo3);
    }
    if ($gallery_caption3 !== "") {
        set_setting($conn, "gallery_caption3", $gallery_caption3);
    }
    if ($gallery_video_link !== "") {
        set_setting($conn, "gallery_video_link", $gallery_video_link);
    }
    if ($gallery_video_caption !== "") {
        set_setting($conn, "gallery_video_caption", $gallery_video_caption);
    }

    if (empty($errors)) {
        $settings_success = "Pengaturan berhasil disimpan.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["forum_submit"])) {
    $nama = trim($_POST["nama"] ?? "");
    $kesan = trim($_POST["kesan"] ?? "");
    $harapan = trim($_POST["harapan"] ?? "");
    $captcha = trim($_POST["captcha"] ?? "");

    if ($kesan === "") {
        $errors[] = "Kesan wajib diisi.";
    }

    $expected = (int)$_SESSION["captcha_a"] + (int)$_SESSION["captcha_b"];
    if ($captcha === "" || !ctype_digit($captcha) || (int)$captcha !== $expected) {
        $errors[] = "Jawaban verifikasi salah. Silakan coba lagi.";
    }

    if (empty($errors)) {
        if ($nama === "") {
            $nama = "Anonim";
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO komentar (nama, kesan, harapan) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $nama, $kesan, $harapan);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Terima kasih, komentar Anda sudah tersimpan.";
            } else {
                $errors[] = "Gagal menyimpan komentar. Silakan coba lagi.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal menyiapkan query. Silakan cek koneksi database.";
        }
    }

    new_captcha();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && is_admin() && isset($_POST["delete_id"])) {
    $delete_id = (int)$_POST["delete_id"];
    if ($delete_id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM komentar WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $delete_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && is_admin() && isset($_POST["edit_id"])) {
    $edit_id = (int)$_POST["edit_id"];
    $edit_nama = trim($_POST["edit_nama"] ?? "");
    $edit_kesan = trim($_POST["edit_kesan"] ?? "");
    $edit_harapan = trim($_POST["edit_harapan"] ?? "");

    if ($edit_id > 0 && $edit_kesan !== "") {
        if ($edit_nama === "") {
            $edit_nama = "Anonim";
        }
        $stmt = mysqli_prepare($conn, "UPDATE komentar SET nama = ?, kesan = ?, harapan = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $edit_nama, $edit_kesan, $edit_harapan, $edit_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
if ($page < 1) {
    $page = 1;
}
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total = 0;
$total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM komentar");
if ($total_res) {
    $row = mysqli_fetch_assoc($total_res);
    $total = (int)($row["total"] ?? 0);
    mysqli_free_result($total_res);
}
$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;

$hero_image = get_setting($conn, "hero_image", $defaults["hero_image"]);
$story_image = get_setting($conn, "story_image", $defaults["story_image"]);
$gallery_photo1 = get_setting($conn, "gallery_photo1", $defaults["gallery_photo1"]);
$gallery_caption1 = get_setting($conn, "gallery_caption1", $defaults["gallery_caption1"]);
$gallery_photo2 = get_setting($conn, "gallery_photo2", $defaults["gallery_photo2"]);
$gallery_caption2 = get_setting($conn, "gallery_caption2", $defaults["gallery_caption2"]);
$gallery_photo3 = get_setting($conn, "gallery_photo3", $defaults["gallery_photo3"]);
$gallery_caption3 = get_setting($conn, "gallery_caption3", $defaults["gallery_caption3"]);
$gallery_video_link = get_setting($conn, "gallery_video_link", $defaults["gallery_video_link"]);
$gallery_video_caption = get_setting($conn, "gallery_video_caption", $defaults["gallery_video_caption"]);

$hero_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $hero_image);
$story_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $story_image);
$gallery1_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $gallery_photo1);
$gallery2_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $gallery_photo2);
$gallery3_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $gallery_photo3);
$hero_exists = $hero_image !== "" && is_file($hero_path);
$story_exists = $story_image !== "" && is_file($story_path);
$gallery1_exists = $gallery_photo1 !== "" && is_file($gallery1_path);
$gallery2_exists = $gallery_photo2 !== "" && is_file($gallery2_path);
$gallery3_exists = $gallery_photo3 !== "" && is_file($gallery3_path);
$video_embed = video_embed_url($gallery_video_link);
$jadwal_info = get_setting($conn, "jadwal_info", $defaults["jadwal_info"]);
$kontak_info = get_setting($conn, "kontak_info", $defaults["kontak_info"]);

$list = [];
$result = mysqli_query(
    $conn,
    "SELECT id, nama, kesan, harapan, tanggal FROM komentar ORDER BY tanggal DESC, id DESC LIMIT $per_page OFFSET $offset"
);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row;
    }
    mysqli_free_result($result);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Barongan Karangsari</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <button class="admin-fab" type="button" data-admin-open>A</button>
  <div class="admin-modal" id="admin-modal">
    <div class="admin-modal-card">
      <div class="admin-modal-header">
        <strong>Admin Login</strong>
        <button class="admin-modal-close" type="button" data-admin-close>×</button>
      </div>
      <?php if (!is_admin()): ?>
        <form method="post" action="#ulasan" class="admin-modal-form">
          <?php if ($admin_error !== ""): ?>
            <div class="admin-error"><?= htmlspecialchars($admin_error) ?></div>
          <?php endif; ?>
          <label>
            Username
            <input type="text" name="username" placeholder="Username" />
          </label>
          <label>
            Password
            <input type="password" name="password" placeholder="Password" />
          </label>
          <button class="btn" type="submit" name="admin_login" value="1">Masuk</button>
        </form>
      <?php else: ?>
        <div class="admin-modal-status">
          Login sebagai <strong><?= htmlspecialchars($_SESSION["admin_user"] ?? "admin") ?></strong>
          <a class="btn ghost" href="?logout=1#ulasan">Logout</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($_SESSION["admin_login_notice"])): ?>
    <div class="admin-toast" data-toast>Login berhasil</div>
    <?php $_SESSION["admin_login_notice"] = false; ?>
  <?php endif; ?>
  <header class="site-header">
    <div class="brand">
      <img class="brand-logo" src="undip.png" alt="Logo Universitas Diponegoro" />
      <div>
        <div class="brand-title">UNIVERSITAS DIPONEGORO</div>
        <div class="brand-sub">Barongan Karangsari</div>
      </div>
    </div>
    <nav class="nav">
      <a href="#home" data-i18n="nav_home">Beranda</a>
      <a href="#tentang" data-i18n="nav_about">Tentang</a>
      <a href="#alur" data-i18n="nav_story">Alur Cerita</a>
      <a href="#tokoh" data-i18n="nav_chars">Tokoh</a>
      <a href="#galeri" data-i18n="nav_gallery">Galeri</a>
      <a href="#jadwal" data-i18n="nav_schedule">Jadwal</a>
      <a href="forum.php" data-i18n="nav_forum">Forum</a>
      <a href="#kkn" data-i18n="nav_kkn">KKN & Tim</a>
    </nav>
    <div class="lang-switch">
      <select id="lang-switch" aria-label="Pilih bahasa">
        <option value="id">Indonesia</option>
        <option value="en">English</option>
        <option value="ja">日本語</option>
      </select>
    </div>
  </header>

  <main>
    <section id="home" class="hero">
      <div class="hero-image" role="img" aria-label="Foto Barongan Karangsari"
        style="--hero-image: url('<?= htmlspecialchars($hero_exists ? $hero_image : $defaults["hero_image"]) ?>');"></div>
      <div class="hero-content">
        <p class="eyebrow" data-i18n="hero_tag">Seni Tradisi Desa Karangsari</p>
        <h1 data-i18n="hero_title">Barongan Karangsari: Warisan Budaya yang Menghidupkan Desa</h1>
        <p class="lead" data-i18n="hero_lead">
          Barongan Karangsari merupakan seni pertunjukan rakyat yang memadukan musik gamelan, tari, dan narasi
          pertarungan antara kebaikan dan kejahatan. Kesenian ini menjadi identitas desa, penguat kebersamaan,
          serta media edukasi budaya bagi generasi muda.
        </p>
        <div class="hero-actions">
          <a class="btn" href="#tentang" data-i18n="hero_btn_about">Tentang Barongan</a>
          <a class="btn ghost" href="#galeri" data-i18n="hero_btn_gallery">Galeri</a>
          <a class="btn ghost" href="#jadwal" data-i18n="hero_btn_schedule">Jadwal</a>
          <a class="btn ghost" href="#ulasan" data-i18n="hero_btn_forum">Ulasan</a>
        </div>
      </div>
    </section>

    <section id="tentang" class="section">
      <h2 data-i18n="about_title">Tentang Barongan Karangsari</h2>
      <div class="grid-2">
        <div class="card">
          <h3 data-i18n="about_hist_title">Sejarah Singkat</h3>
          <p data-i18n="about_hist_text">
            Barongan di Karangsari berakar dari tradisi gotong royong dan perayaan desa. Sejak puluhan tahun
            lalu, pertunjukan ini hadir pada berbagai acara penting sebagai wujud syukur dan doa bersama.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="about_mean_title">Makna bagi Masyarakat</h3>
          <p data-i18n="about_mean_text">
            Barongan menjadi ruang pertemuan lintas generasi, sarana pewarisan nilai, serta media untuk
            menumbuhkan kecintaan terhadap budaya lokal.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="about_value_title">Nilai Budaya & Filosofi</h3>
          <p data-i18n="about_value_text">
            Kisahnya menegaskan pertarungan kebaikan dan kejahatan, keberanian menghadapi tantangan, serta
            harmoni sebagai tujuan akhir. Kemenangan bukan sekadar unggul, melainkan menjaga keseimbangan.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="about_role_title">Peran dalam Acara Desa</h3>
          <p data-i18n="about_role_text">
            Barongan tampil pada sedekah bumi, hajatan, dan peringatan penting desa. Kesenian ini menjadi
            penanda momen sakral sekaligus hiburan yang meriah.
          </p>
        </div>
      </div>
    </section>

    <section id="alur" class="section alt">
      <h2 data-i18n="story_title">Alur Cerita Pertunjukan Barongan</h2>
      <div class="story">
        <div class="story-image" role="img" aria-label="Foto suasana pertunjukan Barongan"
          style="--story-image: url('<?= htmlspecialchars($story_exists ? $story_image : $defaults["story_image"]) ?>');"></div>
        <div class="story-text">
          <p data-i18n="story_p1">
            Pertunjukan dibuka dengan tabuhan gamelan yang membangun suasana hangat, sakral, dan penuh
            antisipasi. Penonton perlahan larut dalam ritme yang mengiringi gerak para penari.
          </p>
          <p data-i18n="story_p2">
            Barongan kemudian tampil sebagai tokoh utama, disusul hadirnya tokoh jahat yang menantang tatanan.
            Konflik berkembang menjadi pertarungan dramatis yang menguji keberanian, kekuatan, dan kesetiaan
            pada nilai-nilai kebaikan.
          </p>
          <p data-i18n="story_p3">
            Pada akhirnya Barongan meraih kemenangan, menegaskan pesan bahwa kebajikan menuntun harmoni.
            Penutup diiringi ritual singkat sebagai simbol doa keselamatan bagi masyarakat.
          </p>
          <div class="timeline">
            <span>Suasana Awal (musik gamelan)</span>
            <span>Munculnya Barongan</span>
            <span>Munculnya Tokoh Jahat</span>
            <span>Konflik & Pertarungan Dramatis</span>
            <span>Kemenangan Barongan</span>
            <span>Penutup & Ritual</span>
          </div>
        </div>
      </div>
    </section>

    <section id="tokoh" class="section">
      <h2 data-i18n="chars_title">Tokoh & Karakter</h2>
      <div class="grid-2">
        <div class="card">
          <h3 data-i18n="chars_main_title">Barongan (Tokoh Utama)</h3>
          <p data-i18n="chars_main_text">
            Melambangkan keberanian, pelindung, dan penjaga keseimbangan. Geraknya kuat, tegas, dan penuh
            wibawa sehingga menjadi pusat perhatian sepanjang pertunjukan.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="chars_evil_title">Tokoh Jahat (Rakshasa)</h3>
          <p data-i18n="chars_evil_text">
            Mewakili sifat angkara dan kekacauan. Kehadirannya memicu konflik sebagai titik uji bagi
            Barongan.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="chars_dancer_title">Penari & Perannya</h3>
          <p data-i18n="chars_dancer_text">
            Penari membentuk dinamika cerita, menjaga ritme, dan menghubungkan tiap babak. Terdapat penari
            inti, pengiring, serta penabuh gamelan yang menghidupkan pertunjukan.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="chars_mask_title">Topeng & Kostum</h3>
          <p data-i18n="chars_mask_text">
            Warna merah sering melambangkan keberanian, hitam keteguhan, dan emas kemuliaan. Bentuk topeng
            yang ekspresif mempertegas karakter baik dan jahat.
          </p>
        </div>
      </div>
    </section>

    <section id="galeri" class="section alt">
      <h2 data-i18n="gallery_title">Galeri Foto & Video</h2>
      <div class="gallery">
        <figure class="gallery-item">
          <div class="gallery-media" style="--gallery-image: url('<?= htmlspecialchars($gallery1_exists ? $gallery_photo1 : $defaults["gallery_photo1"]) ?>');">
            <span data-i18n="gallery_photo1_label">Foto Pertunjukan</span>
          </div>
          <figcaption><?= htmlspecialchars($gallery_caption1) ?></figcaption>
        </figure>
        <figure class="gallery-item">
          <div class="gallery-media" style="--gallery-image: url('<?= htmlspecialchars($gallery2_exists ? $gallery_photo2 : $defaults["gallery_photo2"]) ?>');">
            <span data-i18n="gallery_photo2_label">Foto Persiapan</span>
          </div>
          <figcaption><?= htmlspecialchars($gallery_caption2) ?></figcaption>
        </figure>
        <figure class="gallery-item">
          <div class="gallery-media" style="--gallery-image: url('<?= htmlspecialchars($gallery3_exists ? $gallery_photo3 : $defaults["gallery_photo3"]) ?>');">
            <span data-i18n="gallery_photo3_label">Foto Suasana</span>
          </div>
          <figcaption><?= htmlspecialchars($gallery_caption3) ?></figcaption>
        </figure>
        <figure class="gallery-item">
          <div class="gallery-media video">
            <span data-i18n="gallery_video_label">Video Singkat</span>
          </div>
          <figcaption><?= htmlspecialchars($gallery_video_caption) ?></figcaption>
          <?php if ($video_embed !== ""): ?>
            <div class="video-embed">
              <iframe src="<?= htmlspecialchars($video_embed) ?>" title="Video Barongan" allowfullscreen></iframe>
            </div>
          <?php endif; ?>
          <a class="btn ghost" href="<?= htmlspecialchars($gallery_video_link) ?>" target="_blank" rel="noopener" data-i18n="gallery_video_btn">Buka Video</a>
        </figure>
      </div>
      <?php if (is_admin()): ?>
        <div class="card admin-settings">
          <h3>Pengaturan Admin (Galeri Foto & Video)</h3>
          <?php if ($settings_success !== ""): ?>
            <div class="hint admin-success"><?= htmlspecialchars($settings_success) ?></div>
          <?php endif; ?>
          <form method="post" action="#galeri" class="admin-settings-form" enctype="multipart/form-data">
            <label>
              Nama file foto galeri 1
              <input type="text" name="gallery_photo1" value="<?= htmlspecialchars($gallery_photo1) ?>" />
            </label>
            <div class="dropzone" data-dropzone>
              <span>Upload foto galeri 1 (JPG/PNG/WEBP, maks 2MB)</span>
              <input type="file" name="gallery_file1" accept=".jpg,.jpeg,.png,.webp" />
            </div>
            <div class="admin-preview">
              <span>Preview galeri 1</span>
              <?php if ($gallery1_exists): ?>
                <img src="<?= htmlspecialchars($gallery_photo1) ?>" alt="Preview galeri 1" />
              <?php else: ?>
                <div class="admin-preview-fallback">File tidak ditemukan. Gunakan nama file yang benar.</div>
              <?php endif; ?>
            </div>
            <button class="btn ghost" type="submit" name="delete_gallery1" value="1" onclick="return confirm('Hapus foto galeri 1?');">Hapus Foto Galeri 1</button>
            <label>
              Caption foto galeri 1
              <input type="text" name="gallery_caption1" value="<?= htmlspecialchars($gallery_caption1) ?>" />
            </label>
            <label>
              Nama file foto galeri 2
              <input type="text" name="gallery_photo2" value="<?= htmlspecialchars($gallery_photo2) ?>" />
            </label>
            <div class="dropzone" data-dropzone>
              <span>Upload foto galeri 2 (JPG/PNG/WEBP, maks 2MB)</span>
              <input type="file" name="gallery_file2" accept=".jpg,.jpeg,.png,.webp" />
            </div>
            <div class="admin-preview">
              <span>Preview galeri 2</span>
              <?php if ($gallery2_exists): ?>
                <img src="<?= htmlspecialchars($gallery_photo2) ?>" alt="Preview galeri 2" />
              <?php else: ?>
                <div class="admin-preview-fallback">File tidak ditemukan. Gunakan nama file yang benar.</div>
              <?php endif; ?>
            </div>
            <button class="btn ghost" type="submit" name="delete_gallery2" value="1" onclick="return confirm('Hapus foto galeri 2?');">Hapus Foto Galeri 2</button>
            <label>
              Caption foto galeri 2
              <input type="text" name="gallery_caption2" value="<?= htmlspecialchars($gallery_caption2) ?>" />
            </label>
            <label>
              Nama file foto galeri 3
              <input type="text" name="gallery_photo3" value="<?= htmlspecialchars($gallery_photo3) ?>" />
            </label>
            <div class="dropzone" data-dropzone>
              <span>Upload foto galeri 3 (JPG/PNG/WEBP, maks 2MB)</span>
              <input type="file" name="gallery_file3" accept=".jpg,.jpeg,.png,.webp" />
            </div>
            <div class="admin-preview">
              <span>Preview galeri 3</span>
              <?php if ($gallery3_exists): ?>
                <img src="<?= htmlspecialchars($gallery_photo3) ?>" alt="Preview galeri 3" />
              <?php else: ?>
                <div class="admin-preview-fallback">File tidak ditemukan. Gunakan nama file yang benar.</div>
              <?php endif; ?>
            </div>
            <button class="btn ghost" type="submit" name="delete_gallery3" value="1" onclick="return confirm('Hapus foto galeri 3?');">Hapus Foto Galeri 3</button>
            <label>
              Caption foto galeri 3
              <input type="text" name="gallery_caption3" value="<?= htmlspecialchars($gallery_caption3) ?>" />
            </label>
            <label>
              Link video (YouTube/Drive)
              <input type="text" name="gallery_video_link" value="<?= htmlspecialchars($gallery_video_link) ?>" />
            </label>
            <label>
              Caption video
              <input type="text" name="gallery_video_caption" value="<?= htmlspecialchars($gallery_video_caption) ?>" />
            </label>
            <button class="btn" type="submit" name="settings_submit" value="1">Simpan Pengaturan</button>
          </form>
        </div>
      <?php endif; ?>
    </section>

    <section id="jadwal" class="section">
      <h2 data-i18n="schedule_title">Jadwal & Informasi Pertunjukan</h2>
      <div class="grid-2">
        <div class="card">
          <h3 data-i18n="schedule_subtitle">Jadwal Pertunjukan</h3>
          <p class="schedule-text"><?= nl2br(htmlspecialchars($jadwal_info)) ?></p>
        </div>
        <div class="card">
          <h3 data-i18n="schedule_types_title">Jenis Kegiatan</h3>
          <p data-i18n="schedule_types_text">
            Barongan Karangsari dapat diundang untuk sedekah bumi, hajatan, peringatan hari besar, festival
            budaya, serta kegiatan desa lainnya.
          </p>
        </div>
        <div class="card">
          <h3 data-i18n="schedule_contact_title">Kontak Narahubung</h3>
          <p data-i18n="schedule_contact_text">Hubungi perwakilan atau grup untuk permohonan pertunjukan:</p>
          <p class="contact"><?= nl2br(htmlspecialchars($kontak_info)) ?></p>
        </div>
      </div>
      <?php if (is_admin()): ?>
        <div class="card admin-settings">
          <h3>Pengaturan Admin (Foto & Jadwal)</h3>
          <?php if ($settings_success !== ""): ?>
            <div class="hint admin-success"><?= htmlspecialchars($settings_success) ?></div>
          <?php endif; ?>
          <form method="post" action="#jadwal" class="admin-settings-form" enctype="multipart/form-data">
            <label>
              Nama file foto hero (contoh: hero.jpg)
              <input type="text" name="hero_image" value="<?= htmlspecialchars($hero_image) ?>" />
            </label>
            <div class="dropzone" data-dropzone>
              <span>Upload foto hero (JPG/PNG/WEBP, maks 2MB)</span>
              <input type="file" name="hero_file" accept=".jpg,.jpeg,.png,.webp" />
            </div>
            <div class="admin-preview">
              <span>Preview hero</span>
              <?php if ($hero_exists): ?>
                <img src="<?= htmlspecialchars($hero_image) ?>" alt="Preview hero" />
              <?php else: ?>
                <div class="admin-preview-fallback">File tidak ditemukan. Gunakan nama file yang benar.</div>
              <?php endif; ?>
            </div>
            <button class="btn ghost" type="submit" name="delete_hero" value="1" onclick="return confirm('Hapus foto hero?');">Hapus Foto Hero</button>
            <label>
              Nama file foto alur cerita (contoh: story.jpg)
              <input type="text" name="story_image" value="<?= htmlspecialchars($story_image) ?>" />
            </label>
            <div class="dropzone" data-dropzone>
              <span>Upload foto alur cerita (JPG/PNG/WEBP, maks 2MB)</span>
              <input type="file" name="story_file" accept=".jpg,.jpeg,.png,.webp" />
            </div>
            <div class="admin-preview">
              <span>Preview alur cerita</span>
              <?php if ($story_exists): ?>
                <img src="<?= htmlspecialchars($story_image) ?>" alt="Preview alur cerita" />
              <?php else: ?>
                <div class="admin-preview-fallback">File tidak ditemukan. Gunakan nama file yang benar.</div>
              <?php endif; ?>
            </div>
            <button class="btn ghost" type="submit" name="delete_story" value="1" onclick="return confirm('Hapus foto alur cerita?');">Hapus Foto Alur Cerita</button>
            <label>
              Jadwal & Informasi Pertunjukan (teks bebas)
              <textarea name="jadwal_info" rows="5"><?= htmlspecialchars($jadwal_info) ?></textarea>
            </label>
            <label>
              Kontak Narahubung (teks bebas)
              <textarea name="kontak_info" rows="4"><?= htmlspecialchars($kontak_info) ?></textarea>
            </label>
            <button class="btn" type="submit" name="settings_submit" value="1">Simpan Pengaturan</button>
          </form>
          <p class="hint">Catatan: pastikan file gambar ada di folder yang sama dengan `index.php`.</p>
        </div>
      <?php endif; ?>
    </section>

    <section id="ulasan" class="section alt">
      <h2 data-i18n="forum_title">Forum / Ulasan Pengunjung</h2>
      <div class="reviews">
        <form class="card" method="post" action="#ulasan">
          <?php if (!empty($errors)): ?>
            <div class="contact" style="margin-bottom:12px;">
              <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($success !== ""): ?>
            <div class="contact" style="margin-bottom:12px;">
              <?= htmlspecialchars($success) ?>
            </div>
          <?php endif; ?>

          <label>
            <span data-i18n="forum_name_label">Nama (dapat anonim)</span>
            <input type="text" name="nama" placeholder="Nama (opsional)" />
          </label>
          <label>
            <span data-i18n="forum_impression_label">Kesan setelah menonton pertunjukan</span>
            <textarea name="kesan" rows="4" placeholder="Tuliskan kesan Anda"></textarea>
          </label>
          <label>
            <span data-i18n="forum_hope_label">Harapan untuk Barongan Karangsari ke depan</span>
            <textarea name="harapan" rows="3" placeholder="Tuliskan harapan Anda"></textarea>
          </label>
          <label>
            <span data-i18n="forum_captcha_label">Verifikasi</span>: <?= (int)$_SESSION["captcha_a"] ?> + <?= (int)$_SESSION["captcha_b"] ?> = ?
            <input type="text" name="captcha" placeholder="Jawaban" />
          </label>
          <button class="btn" type="submit" name="forum_submit" value="1" data-i18n="forum_submit_btn">Kirim Komentar</button>
          <p class="hint" data-i18n="forum_hint">Komentar akan tampil di daftar setelah berhasil disimpan.</p>
        </form>

        <div class="card">
          <h3 data-i18n="forum_list_title">Daftar Komentar</h3>
          <ul class="review-list">
            <?php if (empty($list)): ?>
              <li>
                <strong data-i18n="forum_empty_title">Belum ada komentar</strong>
                <p data-i18n="forum_empty_text">Jadilah yang pertama berbagi kesan dan harapan.</p>
                <span data-i18n="forum_empty_note">Terima kasih atas partisipasi Anda.</span>
              </li>
            <?php else: ?>
              <?php foreach ($list as $row): ?>
                <li>
                  <strong><?= htmlspecialchars($row["nama"]) ?></strong>
                  <?php if (is_admin()): ?>
                    <span class="admin-badge">Admin</span>
                  <?php endif; ?>
                  <p><?= nl2br(htmlspecialchars($row["kesan"])) ?></p>
                  <span><span data-i18n="forum_hope_prefix">Harapan</span>: <?= htmlspecialchars($row["harapan"] !== "" ? $row["harapan"] : "-") ?></span>
                  <div class="hint"><span data-i18n="forum_date_prefix">Tanggal</span>: <?= htmlspecialchars($row["tanggal"]) ?></div>
                  <?php if (is_admin()): ?>
                    <form method="post" action="#ulasan" style="margin-top:8px;">
                      <input type="hidden" name="edit_id" value="<?= (int)$row["id"] ?>" />
                      <label>
                        Nama
                        <input type="text" name="edit_nama" value="<?= htmlspecialchars($row["nama"]) ?>" />
                      </label>
                      <label>
                        Kesan
                        <textarea name="edit_kesan" rows="3"><?= htmlspecialchars($row["kesan"]) ?></textarea>
                      </label>
                      <label>
                        Harapan
                        <textarea name="edit_harapan" rows="2"><?= htmlspecialchars($row["harapan"]) ?></textarea>
                      </label>
                      <button class="btn ghost" type="submit">Simpan Perubahan</button>
                    </form>
                    <form method="post" action="#ulasan" onsubmit="return confirm('Hapus komentar ini?');">
                      <input type="hidden" name="delete_id" value="<?= (int)$row["id"] ?>" />
                      <button class="btn" type="submit" style="background:#6b2c23;border-color:#6b2c23;">Hapus</button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
          <?php if ($total_pages > 1): ?>
            <div class="timeline" style="margin-top:16px;">
              <?php if ($page > 1): ?>
                <a class="btn ghost" href="?page=<?= $page - 1 ?>#ulasan">Prev</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $page): ?>
                  <span style="background:#b03a2e;color:#fff;"><?= $i ?></span>
                <?php else: ?>
                  <a class="btn ghost" href="?page=<?= $i ?>#ulasan"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              <?php if ($page < $total_pages): ?>
                <a class="btn ghost" href="?page=<?= $page + 1 ?>#ulasan">Next</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section id="kkn" class="section">
      <h2 data-i18n="kkn_title">Tentang KKN & Tim</h2>
      <div class="grid-2">
        <div class="card">
          <h3>Tentang KKN Undip</h3>
          <p>
            Kuliah Kerja Nyata (KKN) Undip merupakan program pengabdian kepada masyarakat yang mendorong
            kolaborasi antara kampus dan desa, sekaligus memperkuat potensi lokal.
          </p>
        </div>
        <div class="card">
          <h3>Tujuan Pembuatan Website</h3>
          <p>
            Website ini disusun untuk mendokumentasikan budaya Barongan Karangsari, memperluas jangkauan
            informasi, serta mendukung pelestarian seni tradisi secara berkelanjutan.
          </p>
        </div>
        <div class="card">
          <h3>Tim KKNT Tim 5 Karangsari</h3>
          <p>Daftar anggota tim: (isi nanti)</p>
        </div>
        <div class="card">
          <h3>Tahun Pelaksanaan</h3>
          <p>2026</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="logos">
      <div class="logo">Logo Undip</div>
      <div class="logo">Logo KKNT Tim 5</div>
    </div>
    <p>© 2026 Barongan Karangsari — KKN Undip</p>
  </footer>

  <script src="app.js"></script>
</body>
</html>

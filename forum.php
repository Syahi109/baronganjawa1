<?php
session_start();
require_once "koneksi.php";

$errors = [];
$success = "";
$admin_error = "";

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

$list = [];
$result = mysqli_query($conn, "SELECT id, nama, kesan, harapan, tanggal FROM komentar ORDER BY tanggal DESC, id DESC LIMIT $per_page OFFSET $offset");
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
  <title>Forum Diskusi - Barongan Karangsari</title>
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
        <form method="post" action="#forum" class="admin-modal-form">
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
          <a class="btn ghost" href="?logout=1#forum">Logout</a>
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
      <div class="brand-mark" aria-hidden="true">BK</div>
      <div>
        <div class="brand-title">Barongan Karangsari</div>
        <div class="brand-sub">Forum Diskusi</div>
      </div>
    </div>
    <nav class="nav">
      <a href="index.html#home">Beranda</a>
      <a href="index.html#tentang">Tentang</a>
      <a href="index.html#alur">Alur Cerita</a>
      <a href="index.html#tokoh">Tokoh</a>
      <a href="index.html#galeri">Galeri</a>
      <a href="index.html#jadwal">Jadwal</a>
      <a href="index.html#kkn">KKN & Tim</a>
      <a href="forum.php">Forum</a>
    </nav>
  </header>

  <main>
    <section class="section" id="forum">
      <h2>Forum Diskusi / Ulasan Pengunjung</h2>
      <p class="lead">
        Silakan tulis komentar, kesan, dan harapan untuk Barongan Karangsari. Semua masukan sangat berarti
        untuk pelestarian budaya ini.
      </p>

      <?php /* Data & logic handled at top */ ?>

      <div class="reviews">
        <form class="card" method="post" action="forum.php">
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
            Nama (dapat anonim)
            <input type="text" name="nama" placeholder="Nama (opsional)" />
          </label>
          <label>
            Kesan setelah menonton pertunjukan
            <textarea name="kesan" rows="4" placeholder="Tuliskan kesan Anda"></textarea>
          </label>
          <label>
            Harapan untuk Barongan Karangsari ke depan
            <textarea name="harapan" rows="3" placeholder="Tuliskan harapan Anda"></textarea>
          </label>
          <label>
            Verifikasi: <?= (int)$_SESSION["captcha_a"] ?> + <?= (int)$_SESSION["captcha_b"] ?> = ?
            <input type="text" name="captcha" placeholder="Jawaban" />
          </label>
          <button class="btn" type="submit" name="forum_submit" value="1">Kirim Komentar</button>
          <p class="hint">Komentar akan tampil di daftar setelah berhasil disimpan.</p>
        </form>

        <div class="card">
          <h3>Daftar Komentar</h3>
          <ul class="review-list">
            <?php if (empty($list)): ?>
              <li>
                <strong>Belum ada komentar</strong>
                <p>Jadilah yang pertama berbagi kesan dan harapan.</p>
                <span>Terima kasih atas partisipasi Anda.</span>
              </li>
            <?php else: ?>
              <?php foreach ($list as $row): ?>
                <li>
                  <strong><?= htmlspecialchars($row["nama"]) ?></strong>
                  <?php if (is_admin()): ?>
                    <span class="admin-badge">Admin</span>
                  <?php endif; ?>
                  <p><?= nl2br(htmlspecialchars($row["kesan"])) ?></p>
                  <span>Harapan: <?= htmlspecialchars($row["harapan"] !== "" ? $row["harapan"] : "-") ?></span>
                  <div class="hint">Tanggal: <?= htmlspecialchars($row["tanggal"]) ?></div>
                  <?php if (is_admin()): ?>
                    <form method="post" action="#forum" style="margin-top:8px;">
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
                    <form method="post" action="#forum" onsubmit="return confirm('Hapus komentar ini?');">
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
                <a class="btn ghost" href="?page=<?= $page - 1 ?>#forum">Prev</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $page): ?>
                  <span style="background:#b03a2e;color:#fff;"><?= $i ?></span>
                <?php else: ?>
                  <a class="btn ghost" href="?page=<?= $i ?>#forum"><?= $i ?></a>
                <?php endif; ?>
              <?php endfor; ?>
              <?php if ($page < $total_pages): ?>
                <a class="btn ghost" href="?page=<?= $page + 1 ?>#forum">Next</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
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
</body>
</html>

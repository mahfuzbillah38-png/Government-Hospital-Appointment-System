<?php
// Database connection + shared helpers
date_default_timezone_set('Asia/Dhaka');

$pdo = new PDO(
    'mysql:host=localhost;dbname=hospital_db;charset=utf8mb4',
    'root', '',   // XAMPP default
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function page_start($title) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> | Govt. Hospital Appointment</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="wrap">
    <h1>Government Hospital Appointment Portal</h1>
    <nav>
      <a href="index.php">Doctors</a>
      <a href="book.php">Book Appointment</a>
      <a href="token.php">Find My Token</a>
    </nav>
  </div>
</header>
<main class="wrap">
<?php }

function page_end() { ?>
</main>
<footer class="wrap"><small>Public Healthcare Service &middot; Outpatient appointments</small></footer>
</body>
</html>
<?php }

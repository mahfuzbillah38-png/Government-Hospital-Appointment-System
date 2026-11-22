<?php
require 'config.php';

$depts = $pdo->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll();
$dept  = (int)($_GET['dept'] ?? 0);
$q     = trim($_GET['q'] ?? '');

$sql = "SELECT d.*, dp.dept_name
        FROM doctors d JOIN departments dp ON dp.dept_id = d.dept_id
        WHERE 1=1";
$params = [];
if ($dept) { $sql .= " AND d.dept_id = ?";  $params[] = $dept; }
if ($q !== '') { $sql .= " AND d.name LIKE ?"; $params[] = "%$q%"; }
$sql .= " ORDER BY dp.dept_name, d.name";

$st = $pdo->prepare($sql);
$st->execute($params);
$doctors = $st->fetchAll();

page_start('Doctor Directory');
?>
<h2>Doctor Directory</h2>

<form method="get" class="filter">
  <select name="dept" onchange="this.form.submit()">
    <option value="0">All Departments</option>
    <?php foreach ($depts as $d): ?>
      <option value="<?= $d['dept_id'] ?>" <?= $dept == $d['dept_id'] ? 'selected' : '' ?>>
        <?= h($d['dept_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="q" placeholder="Search doctor name" value="<?= h($q) ?>">
  <button type="submit">Search</button>
  <a class="btn light" href="index.php">Reset</a>
</form>

<p class="muted"><?= count($doctors) ?> doctor(s) found</p>

<div class="grid">
<?php foreach ($doctors as $d): ?>
  <div class="card">
    <h3><?= h($d['name']) ?></h3>
    <span class="tag"><?= h($d['dept_name']) ?></span>
    <p><?= h($d['designation']) ?><br><small><?= h($d['qualification']) ?></small></p>
    <p><strong>Room:</strong> <?= h($d['room_no']) ?><br>
       <strong>Days:</strong> <?= h(str_replace(',', ', ', $d['available_days'])) ?><br>
       <strong>Visit fee:</strong> Tk <?= number_format($d['visit_fee'], 0) ?></p>
    <a class="btn" href="book.php?doctor_id=<?= $d['doctor_id'] ?>">Book Appointment</a>
  </div>
<?php endforeach; ?>
<?php if (!$doctors): ?>
  <p>No doctors match your search.</p>
<?php endif; ?>
</div>
<?php page_end(); ?>

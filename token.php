<?php
require 'config.php';

$token = strtoupper(trim($_GET['token'] ?? ''));
$appt  = null;

if ($token !== '') {
    $st = $pdo->prepare(
        "SELECT a.*, d.name AS doctor, d.room_no, d.visit_fee, dp.dept_name
         FROM appointments a
         JOIN doctors d      ON d.doctor_id = a.doctor_id
         JOIN departments dp ON dp.dept_id  = d.dept_id
         WHERE a.token_no = ?");
    $st->execute([$token]);
    $appt = $st->fetch();
}

page_start('Appointment Token');
?>
<h2>Appointment Token</h2>

<?php if ($appt): ?>
  <div class="ticket" id="ticket">
    <div class="ticket-head">
      <small>Your Token Number</small>
      <div class="token"><?= h($appt['token_no']) ?></div>
    </div>
    <table>
      <tr><th>Patient</th><td><?= h($appt['patient_name']) ?> (<?= h($appt['age']) ?>, <?= h($appt['gender']) ?>)</td></tr>
      <tr><th>Mobile</th><td><?= h($appt['phone']) ?></td></tr>
      <tr><th>Doctor</th><td><?= h($appt['doctor']) ?></td></tr>
      <tr><th>Department</th><td><?= h($appt['dept_name']) ?></td></tr>
      <tr><th>Room</th><td><?= h($appt['room_no']) ?></td></tr>
      <tr><th>Date</th><td><?= h(date('l, d M Y', strtotime($appt['appt_date']))) ?></td></tr>
      <tr><th>Time</th><td><?= h(date('h:i A', strtotime($appt['slot_time']))) ?></td></tr>
      <tr><th>Visit fee</th><td>Tk <?= number_format($appt['visit_fee'], 0) ?> (pay at counter)</td></tr>
    </table>
    <p class="muted">Please arrive 15 minutes early and bring this token and your national ID / birth certificate.</p>
  </div>
  <p class="noprint">
    <button class="btn" onclick="window.print()">Print Token</button>
    <a class="btn light" href="book.php">Book Another</a>
  </p>
<?php else: ?>
  <?php if ($token !== ''): ?><div class="alert">No appointment found for token "<?= h($token) ?>".</div><?php endif; ?>
  <form method="get" class="filter">
    <input type="text" name="token" placeholder="Enter token e.g. MED261125001" value="<?= h($token) ?>" required>
    <button type="submit">Find</button>
  </form>
<?php endif; ?>
<?php page_end(); ?>

<?php
require 'config.php';

$doctor_id = (int)($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 0);
$date      = $_GET['date'] ?? $_POST['date'] ?? '';
$errors    = [];

// All doctors for the dropdown
$allDoctors = $pdo->query(
    "SELECT d.doctor_id, d.name, dp.dept_name
     FROM doctors d JOIN departments dp ON dp.dept_id = d.dept_id
     ORDER BY dp.dept_name, d.name")->fetchAll();

// Selected doctor
$doc = null;
if ($doctor_id) {
    $st = $pdo->prepare(
        "SELECT d.*, dp.dept_name, dp.dept_code
         FROM doctors d JOIN departments dp ON dp.dept_id = d.dept_id
         WHERE d.doctor_id = ?");
    $st->execute([$doctor_id]);
    $doc = $st->fetch();
}

// Check chosen date is valid for the doctor
function check_date($doc, $date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) return 'Please choose a valid date.';
    $today = new DateTime('today');
    if ($d < $today) return 'Past dates cannot be booked.';
    if ($d > (clone $today)->modify('+14 days')) return 'Appointments open only 14 days ahead.';
    $days = explode(',', $doc['available_days']);
    if (!in_array($d->format('D'), $days)) {
        return 'Dr. is not available on ' . $d->format('l') . '. Available: ' . implode(', ', $days);
    }
    return '';
}

// Available slots = doctor's slots minus already-booked ones
function get_slots($pdo, $doctor_id, $date) {
    $st = $pdo->prepare(
        "SELECT s.slot_time FROM doctor_slots s
         WHERE s.doctor_id = ?
           AND s.slot_time NOT IN (SELECT slot_time FROM appointments
                                   WHERE doctor_id = ? AND appt_date = ?)
         ORDER BY s.slot_time");
    $st->execute([$doctor_id, $doctor_id, $date]);
    $slots = $st->fetchAll(PDO::FETCH_COLUMN);
    if ($date === date('Y-m-d')) {                       // hide slots already passed today
        $now = date('H:i:s');
        $slots = array_values(array_filter($slots, fn($t) => $t > $now));
    }
    return $slots;
}

// ---------- Handle booking ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doc) {
    $name   = trim($_POST['patient_name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $age    = (int)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $slot   = $_POST['slot_time'] ?? '';

    if ($name === '') $errors[] = 'Patient name is required.';
    if (!preg_match('/^01[3-9]\d{8}$/', $phone)) $errors[] = 'Enter a valid mobile number (01XXXXXXXXX).';
    if ($age < 1 || $age > 120) $errors[] = 'Enter a valid age.';
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = 'Select gender.';
    if ($e = check_date($doc, $date)) $errors[] = $e;
    if (!in_array($slot, get_slots($pdo, $doctor_id, $date))) $errors[] = 'Please select an available time slot.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Token = DEPTCODE + yymmdd + serial (serial counts per department per day)
            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM appointments a
                 JOIN doctors d ON d.doctor_id = a.doctor_id
                 WHERE d.dept_id = ? AND a.appt_date = ?");
            $st->execute([$doc['dept_id'], $date]);
            $serial = (int)$st->fetchColumn() + 1;
            $token  = $doc['dept_code'] . date('ymd', strtotime($date)) . str_pad($serial, 3, '0', STR_PAD_LEFT);

            $ins = $pdo->prepare(
                "INSERT INTO appointments
                 (token_no, patient_name, phone, age, gender, reason, doctor_id, appt_date, slot_time)
                 VALUES (?,?,?,?,?,?,?,?,?)");
            $ins->execute([$token, $name, $phone, $age, $gender, $reason, $doctor_id, $date, $slot]);
            $pdo->commit();

            header('Location: token.php?token=' . urlencode($token));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = ($e->getCode() == '23000')
                ? 'Sorry, that slot was just taken. Please choose another slot.'
                : 'Something went wrong. Please try again.';
        }
    }
}

// Slots to show
$slots = [];
$dateError = '';
if ($doc && $date !== '') {
    $dateError = check_date($doc, $date);
    if (!$dateError) $slots = get_slots($pdo, $doctor_id, $date);
}

page_start('Book Appointment');
?>
<h2>Book an Appointment</h2>

<?php foreach ($errors as $e): ?><div class="alert"><?= h($e) ?></div><?php endforeach; ?>

<!-- Step 1: doctor + date -->
<form method="get" class="filter">
  <select name="doctor_id" required onchange="this.form.submit()">
    <option value="">-- Select Doctor --</option>
    <?php foreach ($allDoctors as $d): ?>
      <option value="<?= $d['doctor_id'] ?>" <?= $doctor_id == $d['doctor_id'] ? 'selected' : '' ?>>
        <?= h($d['name']) ?> (<?= h($d['dept_name']) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date" value="<?= h($date) ?>"
         min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+14 days')) ?>"
         onchange="this.form.submit()">
  <button type="submit">Check Slots</button>
</form>

<?php if ($doc): ?>
  <div class="card info">
    <strong><?= h($doc['name']) ?></strong> &middot; <?= h($doc['dept_name']) ?> &middot; Room <?= h($doc['room_no']) ?><br>
    <small>Available days: <?= h(str_replace(',', ', ', $doc['available_days'])) ?> &middot; Fee: Tk <?= number_format($doc['visit_fee'], 0) ?></small>
  </div>

  <?php if ($date !== '' && $dateError): ?>
    <div class="alert"><?= h($dateError) ?></div>
  <?php elseif ($date !== ''): ?>

    <!-- Step 2: slot + patient details -->
    <form method="post" class="booking">
      <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
      <input type="hidden" name="date" value="<?= h($date) ?>">

      <h3>Available time slots &mdash; <?= h(date('D, d M Y', strtotime($date))) ?></h3>
      <?php if ($slots): ?>
        <div class="slots">
          <?php foreach ($slots as $t): ?>
            <label class="slot">
              <input type="radio" name="slot_time" value="<?= h($t) ?>" required>
              <span><?= h(date('h:i A', strtotime($t))) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <h3>Patient details</h3>
        <div class="fields">
          <label>Full name
            <input type="text" name="patient_name" required value="<?= h($_POST['patient_name'] ?? '') ?>"></label>
          <label>Mobile number
            <input type="text" name="phone" placeholder="01XXXXXXXXX" required value="<?= h($_POST['phone'] ?? '') ?>"></label>
          <label>Age
            <input type="number" name="age" min="1" max="120" required value="<?= h($_POST['age'] ?? '') ?>"></label>
          <label>Gender
            <select name="gender" required>
              <option value="">Select</option>
              <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                <option <?= (($_POST['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select></label>
          <label class="full">Reason / symptoms (optional)
            <textarea name="reason" rows="2" maxlength="255"><?= h($_POST['reason'] ?? '') ?></textarea></label>
        </div>
        <button type="submit" class="btn">Confirm &amp; Get Token</button>
      <?php else: ?>
        <div class="alert">No slots left for this date. Please try another date.</div>
      <?php endif; ?>
    </form>
  <?php endif; ?>
<?php endif; ?>
<?php page_end(); ?>

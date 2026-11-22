-- Government Hospital Appointment System (XAMPP / MySQL)
-- Run in phpMyAdmin > SQL tab
CREATE DATABASE IF NOT EXISTS hospital_db;
USE hospital_db;

DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctor_slots;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
  dept_id   INT AUTO_INCREMENT PRIMARY KEY,
  dept_name VARCHAR(60) NOT NULL UNIQUE,
  dept_code VARCHAR(4)  NOT NULL UNIQUE      -- used in token, e.g. MED
);

CREATE TABLE doctors (
  doctor_id      INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(80) NOT NULL,
  dept_id        INT NOT NULL,
  designation    VARCHAR(60) NOT NULL,
  qualification  VARCHAR(100),
  room_no        VARCHAR(10),
  available_days VARCHAR(40) NOT NULL,        -- e.g. Sat,Sun,Mon
  visit_fee      DECIMAL(8,2) DEFAULT 0,
  FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
);

CREATE TABLE doctor_slots (
  slot_id   INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  slot_time TIME NOT NULL,
  UNIQUE (doctor_id, slot_time),
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
);

CREATE TABLE appointments (
  appt_id      INT AUTO_INCREMENT PRIMARY KEY,
  token_no     VARCHAR(20) NOT NULL UNIQUE,
  patient_name VARCHAR(80) NOT NULL,
  phone        VARCHAR(15) NOT NULL,
  age          INT NOT NULL,
  gender       ENUM('Male','Female','Other') NOT NULL,
  reason       VARCHAR(255),
  doctor_id    INT NOT NULL,
  appt_date    DATE NOT NULL,
  slot_time    TIME NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (doctor_id, appt_date, slot_time),   -- prevents double booking
  FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
);

-- Sample data
INSERT INTO departments (dept_name, dept_code) VALUES
('Medicine','MED'), ('Cardiology','CAR'), ('Pediatrics','PED'),
('Gynecology','GYN'), ('Orthopedics','ORT'), ('ENT','ENT');

INSERT INTO doctors (name, dept_id, designation, qualification, room_no, available_days, visit_fee) VALUES
('Dr. Rahim Uddin',    1, 'Associate Professor', 'MBBS, FCPS (Medicine)',   '101', 'Sat,Sun,Mon,Tue,Wed', 50),
('Dr. Nasrin Akter',   1, 'Assistant Professor', 'MBBS, MD (Medicine)',     '102', 'Sun,Tue,Thu',        50),
('Dr. Kamal Hossain',  2, 'Professor',           'MBBS, MD (Cardiology)',   '201', 'Sat,Mon,Wed',        50),
('Dr. Farhana Islam',  3, 'Assistant Professor', 'MBBS, DCH',               '105', 'Sat,Sun,Mon,Tue,Wed,Thu', 50),
('Dr. Sabina Yasmin',  4, 'Consultant',          'MBBS, FCPS (Gynae)',      '110', 'Sun,Mon,Wed,Thu',    50),
('Dr. Abdul Karim',    5, 'Associate Professor', 'MBBS, MS (Orthopedics)',  '210', 'Sat,Tue,Thu',        50),
('Dr. Mizanur Rahman', 6, 'Assistant Professor', 'MBBS, DLO',               '115', 'Sun,Mon,Tue,Wed',    50),
('Dr. Tania Sultana',  3, 'Consultant',          'MBBS, MD (Pediatrics)',   '106', 'Sat,Mon,Wed,Thu',    50);

-- 20-minute slots, 09:00 to 11:40 for every doctor
INSERT INTO doctor_slots (doctor_id, slot_time)
SELECT d.doctor_id, t.slot
FROM doctors d
CROSS JOIN (
  SELECT '09:00:00' AS slot UNION SELECT '09:20:00' UNION SELECT '09:40:00' UNION
  SELECT '10:00:00' UNION SELECT '10:20:00' UNION SELECT '10:40:00' UNION
  SELECT '11:00:00' UNION SELECT '11:20:00' UNION SELECT '11:40:00'
) t;

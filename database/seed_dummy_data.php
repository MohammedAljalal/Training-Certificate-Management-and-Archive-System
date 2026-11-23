<?php
/**
 * Dummy Data Generator & Seeder
 * Training Certificate Management and Archive System (TCMAS)
 *
 * Populates the system with realistic participants, courses, enrollments,
 * and valid/revoked certificates (complete with physical PDF generation).
 *
 * Run from CLI:
 *   php database/seed_dummy_data.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../config/db.php';

// Check Dompdf
$autoload = APP_ROOT . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("Error: vendor/autoload.php not found. Run composer install first.\n");
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = get_pdo();

echo "====================================================\n";
echo "   TCMAS — Dummy Data Seeder\n";
echo "====================================================\n\n";

// ── 1. Participants ─────────────────────────────────────────────────────────
$participants = [
    ['full_name' => 'Mohammed Al-Otaibi',    'email' => 'm.otaibi@example.com',     'phone' => '+966 50 123 4567', 'dob' => '1995-04-12', 'status' => 'Active'],
    ['full_name' => 'Sarah Al-Ghamdi',       'email' => 'sarah.ghamdi@example.com', 'phone' => '+966 55 234 5678', 'dob' => '1998-09-23', 'status' => 'Active'],
    ['full_name' => 'Ahmed Al-Shehri',       'email' => 'ahmed.shehri@example.com', 'phone' => '+966 54 345 6789', 'dob' => '1992-11-05', 'status' => 'Active'],
    ['full_name' => 'Fatima Al-Zahrani',     'email' => 'fatima.z@example.com',     'phone' => '+966 56 456 7890', 'dob' => '1997-02-18', 'status' => 'Active'],
    ['full_name' => 'Khalid Al-Harbi',       'email' => 'khalid.harbi@example.com', 'phone' => '+966 50 567 8901', 'dob' => '1994-07-30', 'status' => 'Active'],
    ['full_name' => 'Nora Al-Qahtani',       'email' => 'nora.q@example.com',       'phone' => '+966 55 678 9012', 'dob' => '1999-05-14', 'status' => 'Active'],
    ['full_name' => 'Omar Farooq',           'email' => 'omar.farooq@example.com',  'phone' => '+966 54 789 0123', 'dob' => '1991-03-25', 'status' => 'Active'],
    ['full_name' => 'Reem Al-Dossari',       'email' => 'reem.dossari@example.com', 'phone' => '+966 56 890 1234', 'dob' => '1996-12-08', 'status' => 'Active'],
    ['full_name' => 'Youssef Al-Mutairi',    'email' => 'youssef.m@example.com',    'phone' => '+966 50 901 2345', 'dob' => '1993-08-19', 'status' => 'Active'],
    ['full_name' => 'Layla Al-Anazi',        'email' => 'layla.anazi@example.com',  'phone' => '+966 55 012 3456', 'dob' => '2000-01-22', 'status' => 'Active'],
    ['full_name' => 'Faisal Al-Subaie',      'email' => 'faisal.subaie@example.com','phone' => '+966 54 123 7890', 'dob' => '1995-10-15', 'status' => 'Active'],
    ['full_name' => 'Huda Al-Mansour',       'email' => 'huda.mansour@example.com', 'phone' => '+966 56 234 8901', 'dob' => '1997-06-27', 'status' => 'Active'],
    ['full_name' => 'Abdullah Al-Ghamdi',    'email' => 'abdullah.g@example.com',   'phone' => '+966 50 345 9012', 'dob' => '1990-12-03', 'status' => 'Active'],
    ['full_name' => 'Mona Al-Rashidi',       'email' => 'mona.rashidi@example.com', 'phone' => '+966 55 456 0123', 'dob' => '1998-04-09', 'status' => 'Active'],
    ['full_name' => 'Fahad Al-Balawi',       'email' => 'fahad.balawi@example.com', 'phone' => '+966 54 567 1234', 'dob' => '1994-01-17', 'status' => 'Active'],
    ['full_name' => 'Amira Hassan',          'email' => 'amira.hassan@example.com', 'phone' => '+966 56 678 2345', 'dob' => '1999-08-31', 'status' => 'Active'],
    ['full_name' => 'Sultan Al-Shammari',    'email' => 'sultan.sh@example.com',    'phone' => '+966 50 789 3456', 'dob' => '1992-05-20', 'status' => 'Active'],
    ['full_name' => 'Rania Al-Omari',        'email' => 'rania.omari@example.com',  'phone' => '+966 55 890 4567', 'dob' => '1996-03-11', 'status' => 'Active'],
    ['full_name' => 'Tariq Al-Khaldi',       'email' => 'tariq.khaldi@example.com', 'phone' => '+966 54 901 5678', 'dob' => '1993-09-04', 'status' => 'Active'],
    ['full_name' => 'Zainab Al-Hassan',      'email' => 'zainab.h@example.com',     'phone' => '+966 56 012 6789', 'dob' => '1997-11-28', 'status' => 'Active'],
    ['full_name' => 'Saad Al-Obeid',         'email' => 'saad.obeid@example.com',   'phone' => '+966 50 135 7924', 'dob' => '1991-07-16', 'status' => 'Active'],
    ['full_name' => 'Maha Al-Sulaiman',      'email' => 'maha.sulaiman@example.com','phone' => '+966 55 246 8035', 'dob' => '1998-10-02', 'status' => 'Active'],
    ['full_name' => 'Nawaf Al-Rowaili',      'email' => 'nawaf.r@example.com',      'phone' => '+966 54 357 9146', 'dob' => '1995-02-14', 'status' => 'Active'],
    ['full_name' => 'Asma Al-Garni',         'email' => 'asma.garni@example.com',   'phone' => '+966 56 468 0257', 'dob' => '2001-04-20', 'status' => 'Inactive'],
    ['full_name' => 'Mansour Al-Malki',      'email' => 'mansour.m@example.com',    'phone' => '+966 50 579 1368', 'dob' => '1993-12-25', 'status' => 'Active'],
];

$defaultPasswordHash = password_hash('Password@123', PASSWORD_BCRYPT);

$participantIds = [];
$stmtUser = $pdo->prepare('
    INSERT INTO users (full_name, email, password, role, phone, date_of_birth, status)
    VALUES (:name, :email, :pass, "participant", :phone, :dob, :status)
    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), full_name=VALUES(full_name), status=VALUES(status)
');

foreach ($participants as $p) {
    $stmtUser->execute([
        ':name'   => $p['full_name'],
        ':email'  => $p['email'],
        ':pass'   => $defaultPasswordHash,
        ':phone'  => $p['phone'],
        ':dob'    => $p['dob'],
        ':status' => $p['status'],
    ]);
    $participantIds[] = (int)$pdo->lastInsertId();
}

echo "[1/4] Seeded " . count($participantIds) . " participants (Default password: Password@123)\n";

// ── 2. Courses ───────────────────────────────────────────────────────────────
$courses = [
    [
        'title'       => 'Advanced Web Application Development with PHP & MySQL',
        'description' => 'Comprehensive deep dive into modern PHP, PDO, MVC architecture, secure database transactions, and REST APIs.',
        'start_date'  => '2026-01-10',
        'end_date'    => '2026-02-20',
        'location'    => 'Computer Science Lab 3, Campus A',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Cybersecurity Fundamentals & Network Defense',
        'description' => 'Hands-on training covering vulnerability assessment, penetration testing techniques, cryptographic principles, and network hardening.',
        'start_date'  => '2026-02-01',
        'end_date'    => '2026-03-15',
        'location'    => 'Cyber Defense Training Center',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Cloud Computing Architecture with AWS & Azure',
        'description' => 'Design, deployment, and governance of scalable cloud native architectures, serverless computing, and VPC configurations.',
        'start_date'  => '2026-02-15',
        'end_date'    => '2026-04-01',
        'location'    => 'Online Virtual Classroom',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Data Science & Machine Learning Essentials with Python',
        'description' => 'Exploratory data analysis, statistical modeling, supervised/unsupervised algorithms, and scikit-learn workflow pipelines.',
        'start_date'  => '2026-03-01',
        'end_date'    => '2026-04-25',
        'location'    => 'Data Analytics Hall, Innovation Hub',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Cross-Platform Mobile App Engineering with Flutter',
        'description' => 'Building high-performance native iOS and Android applications with Dart, state management with Bloc, and backend integration.',
        'start_date'  => '2026-03-20',
        'end_date'    => '2026-05-10',
        'location'    => 'Mobile Development Lab 2',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Modern Frontend Architecture with React & TypeScript',
        'description' => 'Production React development, component design patterns, TypeScript strict typing, performance optimization, and Next.js foundations.',
        'start_date'  => '2026-04-05',
        'end_date'    => '2026-05-25',
        'location'    => 'Interactive Media Center',
        'status'      => 'Active'
    ],
    [
        'title'       => 'DevOps Practices, CI/CD Pipelines & Containerization',
        'description' => 'Automated deployment pipelines with GitHub Actions, Docker container orchestration, Kubernetes clustering, and infrastructure monitoring.',
        'start_date'  => '2026-05-01',
        'end_date'    => '2026-06-20',
        'location'    => 'Cloud Infrastructure Lab',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Database Administration & SQL Performance Tuning',
        'description' => 'Relational database schema optimization, indexing strategies, query execution plan analysis, replication, and disaster recovery.',
        'start_date'  => '2026-06-01',
        'end_date'    => '2026-07-15',
        'location'    => 'Main Tech Auditorium',
        'status'      => 'Active'
    ],
    [
        'title'       => 'UI/UX Design Systems & Human-Centered Interaction',
        'description' => 'User research methodologies, wireframing, interactive prototyping in Figma, accessibility standards (WCAG), and usability evaluation.',
        'start_date'  => '2026-07-01',
        'end_date'    => '2026-08-10',
        'location'    => 'Digital Media Studio B',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Agile Project Management & Professional Scrum Framework',
        'description' => 'Sprint planning, backlog refinement, team velocity tracking, and agile transformation leadership principles.',
        'start_date'  => '2026-08-01',
        'end_date'    => '2026-09-05',
        'location'    => 'Executive Seminar Room',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Internet of Things (IoT) Prototyping & Edge Computing',
        'description' => 'Embedded sensors, microcontroller programming with ESP32, MQTT messaging protocol, and real-time dashboard monitoring.',
        'start_date'  => '2026-08-15',
        'end_date'    => '2026-09-30',
        'location'    => 'Hardware Engineering Lab',
        'status'      => 'Active'
    ],
    [
        'title'       => 'Legacy Enterprise Systems & COBOL Maintenance (Archived)',
        'description' => 'Historical overview and maintenance procedures for legacy mainframe transaction processing systems.',
        'start_date'  => '2025-01-10',
        'end_date'    => '2025-02-15',
        'location'    => 'Archive Seminar Room',
        'status'      => 'Inactive'
    ]
];

$courseIds = [];
$stmtCourse = $pdo->prepare('
    INSERT INTO courses (title, description, start_date, end_date, location, status)
    VALUES (:title, :description, :start_date, :end_date, :location, :status)
');

foreach ($courses as $c) {
    // Check if course already exists by title
    $chk = $pdo->prepare('SELECT id FROM courses WHERE title = :t LIMIT 1');
    $chk->execute([':t' => $c['title']]);
    $exId = $chk->fetchColumn();
    if ($exId) {
        $courseIds[] = (int)$exId;
    } else {
        $stmtCourse->execute([
            ':title'       => $c['title'],
            ':description' => $c['description'],
            ':start_date'  => $c['start_date'],
            ':end_date'    => $c['end_date'],
            ':location'    => $c['location'],
            ':status'      => $c['status'],
        ]);
        $courseIds[] = (int)$pdo->lastInsertId();
    }
}

echo "[2/4] Seeded " . count($courseIds) . " courses\n";

// ── 3. Enrollments ───────────────────────────────────────────────────────────
// Define enrollment distribution
$enrollmentSpecs = [
    // [participantIndex, courseIndex, status, completionDateOffsetDays]
    // Course 0: Web App Dev (start: 2026-01-10, end: 2026-02-20)
    [0, 0, 'Completed', '2026-02-18'],
    [1, 0, 'Completed', '2026-02-20'],
    [2, 0, 'Completed', '2026-02-19'],
    [3, 0, 'Completed', '2026-02-20'],
    [4, 0, 'Completed', '2026-02-17'],
    [5, 0, 'In Progress', null],
    [6, 0, 'Enrolled', null],

    // Course 1: Cybersecurity (start: 2026-02-01, end: 2026-03-15)
    [1, 1, 'Completed', '2026-03-14'],
    [4, 1, 'Completed', '2026-03-12'],
    [7, 1, 'Completed', '2026-03-15'],
    [8, 1, 'Completed', '2026-03-13'],
    [9, 1, 'In Progress', null],
    [10, 1, 'Enrolled', null],

    // Course 2: Cloud Computing (start: 2026-02-15, end: 2026-04-01)
    [0, 2, 'Completed', '2026-03-30'],
    [2, 2, 'Completed', '2026-03-29'],
    [11, 2, 'Completed', '2026-04-01'],
    [12, 2, 'Completed', '2026-03-31'],
    [13, 2, 'In Progress', null],
    [14, 2, 'Enrolled', null],

    // Course 3: Data Science (start: 2026-03-01, end: 2026-04-25)
    [3, 3, 'Completed', '2026-04-22'],
    [5, 3, 'Completed', '2026-04-24'],
    [15, 3, 'Completed', '2026-04-25'],
    [16, 3, 'In Progress', null],
    [17, 3, 'Enrolled', null],

    // Course 4: Mobile App Flutter (start: 2026-03-20, end: 2026-05-10)
    [6, 4, 'Completed', '2026-05-08'],
    [7, 4, 'Completed', '2026-05-09'],
    [18, 4, 'Completed', '2026-05-10'],
    [19, 4, 'In Progress', null],
    [20, 4, 'Enrolled', null],

    // Course 5: Frontend React (start: 2026-04-05, end: 2026-05-25)
    [8, 5, 'Completed', '2026-05-24'],
    [9, 5, 'Completed', '2026-05-25'],
    [21, 5, 'Completed', '2026-05-22'],
    [22, 5, 'In Progress', null],
    [24, 5, 'Enrolled', null],

    // Course 6: DevOps & CI/CD (start: 2026-05-01, end: 2026-06-20)
    [10, 6, 'Completed', '2026-06-18'],
    [11, 6, 'Completed', '2026-06-19'],
    [12, 6, 'In Progress', null],
    [13, 6, 'Enrolled', null],

    // Course 7: Database Administration (start: 2026-06-01, end: 2026-07-15)
    [14, 7, 'Completed', '2026-07-14'],
    [15, 7, 'Completed', '2026-07-12'],
    [16, 7, 'In Progress', null],
    [17, 7, 'Enrolled', null],

    // Course 8: UI/UX Design (start: 2026-07-01, end: 2026-08-10)
    [18, 8, 'Completed', '2026-08-08'],
    [19, 8, 'Completed', '2026-08-10'],
    [20, 8, 'In Progress', null],
    [21, 8, 'Enrolled', null],

    // Course 9: Agile & Scrum (start: 2026-08-01, end: 2026-09-05)
    [22, 9, 'Completed', '2026-09-04'],
    [24, 9, 'Completed', '2026-09-05'],
    [0, 9, 'In Progress', null],
    [1, 9, 'Enrolled', null],

    // Course 10: IoT Prototyping (start: 2026-08-15, end: 2026-09-30)
    [2, 10, 'Completed', '2026-09-28'],
    [3, 10, 'In Progress', null],
    [4, 10, 'Enrolled', null],
];

$stmtEnroll = $pdo->prepare('
    INSERT INTO enrollments (user_id, course_id, status, completion_date)
    VALUES (:user_id, :course_id, :status, :comp_date)
    ON DUPLICATE KEY UPDATE status=VALUES(status), completion_date=VALUES(completion_date)
');

$enrollmentMap = []; // key: "$uId-$cId" => enrollmentId

foreach ($enrollmentSpecs as $spec) {
    $pIdx = $spec[0];
    $cIdx = $spec[1];
    $status = $spec[2];
    $cDate = $spec[3];

    if (!isset($participantIds[$pIdx]) || !isset($courseIds[$cIdx])) {
        continue;
    }

    $uId = $participantIds[$pIdx];
    $cId = $courseIds[$cIdx];

    $stmtEnroll->execute([
        ':user_id'   => $uId,
        ':course_id' => $cId,
        ':status'    => $status,
        ':comp_date' => $cDate,
    ]);

    // Retrieve enrollment id
    $chk = $pdo->prepare('SELECT id FROM enrollments WHERE user_id = :u AND course_id = :c');
    $chk->execute([':u' => $uId, ':c' => $cId]);
    $eId = (int)$chk->fetchColumn();

    $enrollmentMap["$pIdx-$cIdx"] = [
        'id'              => $eId,
        'user_id'         => $uId,
        'course_id'       => $cId,
        'status'          => $status,
        'completion_date' => $cDate,
    ];
}

echo "[3/4] Seeded " . count($enrollmentMap) . " enrollments\n";

// ── 4. Certificates & Physical PDF Generation ───────────────────────────────
$storageDir = APP_ROOT . '/storage/certificates/';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0750, true);
}

// Dompdf setup
$opts = new Options();
$opts->setIsRemoteEnabled(false);
$opts->setIsPhpEnabled(false);
$opts->setDefaultFont('DejaVu Sans');

$dompdf = new Dompdf($opts);

function buildCertHtml(string $name, string $course, string $compDate, string $certNo, string $issued): string {
    $cDateStr = date('d F Y', strtotime($compDate));
    $iDateStr = date('d F Y', strtotime($issued));
    $nameEsc  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $crsEsc   = htmlspecialchars($course, ENT_QUOTES, 'UTF-8');
    $certNoEsc = htmlspecialchars($certNo, ENT_QUOTES, 'UTF-8');
    $verifyUrl = "http://localhost:8000/verify.php";

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page {
    size: 297mm 210mm;
    margin: 8mm 10mm;
  }
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    font-family: 'DejaVu Sans', sans-serif;
    color: #1e293b;
    line-height: 1.35;
  }
  .cert-outer {
    border: 4px solid #0f2952;
    padding: 3mm;
    background: #ffffff;
  }
  .cert-inner {
    border: 1.5px solid #c59b27;
    padding: 7mm 12mm 6mm;
    text-align: center;
    background: #ffffff;
  }

  /* Header Branding */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2mm;
  }
  .header-left, .header-right {
    width: 25%;
    font-size: 7.5pt;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.4;
  }
  .header-left { text-align: left; }
  .header-right { text-align: right; }
  .header-center {
    width: 50%;
    text-align: center;
  }
  .org-title {
    font-size: 11pt;
    font-weight: bold;
    color: #0f2952;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }
  .org-sub {
    font-size: 7.5pt;
    color: #64748b;
    margin-top: 1mm;
    letter-spacing: 0.8px;
    text-transform: uppercase;
  }

  /* Main Title Area */
  .title-area {
    margin: 3mm 0 2.5mm;
  }
  .main-title {
    font-size: 24pt;
    font-weight: bold;
    color: #0f2952;
    letter-spacing: 3.5px;
    text-transform: uppercase;
  }
  .gold-divider {
    width: 60mm;
    height: 2px;
    background: #c59b27;
    margin: 2mm auto 0;
  }

  /* Presentation text */
  .cert-present {
    font-size: 9.5pt;
    color: #64748b;
    font-style: italic;
    margin: 2.5mm 0 2mm;
  }

  /* Recipient Name */
  .recipient-wrap {
    margin: 1.5mm 0 2.5mm;
  }
  .recipient-name {
    font-size: 23pt;
    font-weight: bold;
    color: #0f2952;
    padding-bottom: 2mm;
    border-bottom: 2px solid #0f2952;
    display: inline-block;
    min-width: 95mm;
  }

  /* Course info */
  .course-lead {
    font-size: 9pt;
    color: #64748b;
    margin-bottom: 1.5mm;
  }
  .course-title {
    font-size: 15pt;
    font-weight: bold;
    color: #1e3a8a;
    margin-bottom: 5mm;
  }

  /* Bottom Row Table */
  .cert-footer-table {
    width: 100%;
    table-layout: fixed;
    margin-top: 2mm;
    border-collapse: collapse;
  }
  .cert-footer-table td {
    vertical-align: middle;
  }
  .col-dates {
    width: 33%;
    text-align: left;
    font-size: 8pt;
    color: #334155;
    line-height: 1.6;
  }
  .col-seal {
    width: 34%;
    text-align: center;
  }
  .col-sig {
    width: 33%;
    text-align: right;
  }

  /* Seal badge */
  .seal-outer-badge {
    display: inline-block;
    width: 27mm;
    height: 27mm;
    border: 2px solid #c59b27;
    border-radius: 13.5mm;
    background: #ffffff;
    text-align: center;
    box-sizing: border-box;
    padding: 1.5mm;
  }
  .seal-inner-badge {
    border: 1px dashed #c59b27;
    border-radius: 12mm;
    height: 22mm;
    box-sizing: border-box;
    padding-top: 3mm;
    text-align: center;
  }
  .seal-stars {
    font-size: 7pt;
    color: #c59b27;
    line-height: 1;
    margin-bottom: 1mm;
  }
  .seal-text-main {
    font-size: 6pt;
    font-weight: bold;
    color: #0f2952;
    text-transform: uppercase;
    line-height: 1.25;
    letter-spacing: 0.5px;
  }
  .seal-text-sub {
    font-size: 5pt;
    color: #c59b27;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 0.8mm;
    letter-spacing: 0.5px;
  }

  /* Signature line */
  .sig-block {
    display: inline-block;
    text-align: center;
    width: 58mm;
  }
  .sig-line-rule {
    border-top: 1.5px solid #0f2952;
    width: 100%;
    margin-bottom: 2mm;
  }
  .sig-author {
    font-size: 8.5pt;
    font-weight: bold;
    color: #0f2952;
  }
  .sig-title {
    font-size: 7pt;
    color: #64748b;
    margin-top: 0.5mm;
  }

  /* Bottom Credential Bar */
  .serial-bar {
    margin-top: 4.5mm;
    padding-top: 2mm;
    border-top: 1px solid #e2e8f0;
    font-size: 7.5pt;
    color: #64748b;
    text-align: center;
  }
  .serial-bar strong {
    color: #0f2952;
  }
</style>
</head>
<body>
<div class="cert-outer">
  <div class="cert-inner">

    <table class="header-table">
      <tr>
        <td class="header-left">
          Kingdom of Saudi Arabia<br>
          Ministry of Education
        </td>
        <td class="header-center">
          <div class="org-title">Training Certificate Management and Archive System</div>
          <div class="org-sub">University of Tabuk &nbsp;·&nbsp; Faculty of Computers &amp; IT</div>
        </td>
        <td class="header-right">
          Accredited Training<br>
          Deanship of Academic Affairs
        </td>
      </tr>
    </table>

    <div class="title-area">
      <div class="main-title">Certificate of Completion</div>
      <div class="gold-divider"></div>
    </div>

    <div class="cert-present">This is to officially certify that</div>

    <div class="recipient-wrap">
      <span class="recipient-name">$nameEsc</span>
    </div>

    <div class="course-lead">has successfully completed the prescribed curriculum and assessment requirements for the training course</div>

    <div class="course-title">$crsEsc</div>

    <table class="cert-footer-table">
      <tr>
        <td class="col-dates">
          <div>Completion Date: <strong>$cDateStr</strong></div>
          <div>Date of Issuance: <strong>$iDateStr</strong></div>
          <div style="color:#64748b;margin-top:1mm;font-size:7pt;">Institutional Credential Record</div>
        </td>
        <td class="col-seal">
          <div class="seal-outer-badge">
            <div class="seal-inner-badge">
              <div class="seal-stars">★ ★ ★</div>
              <div class="seal-text-main">Official Credential</div>
              <div class="seal-text-sub">Authenticated</div>
            </div>
          </div>
        </td>
        <td class="col-sig">
          <div class="sig-block">
            <div class="sig-line-rule"></div>
            <div class="sig-author">Dean of Faculty</div>
            <div class="sig-title">Faculty of Computers &amp; Information Technology</div>
          </div>
        </td>
      </tr>
    </table>

    <div class="serial-bar">
      Certificate Identifier: <strong>$certNoEsc</strong> &nbsp;|&nbsp;
      Verify Online: <strong>$verifyUrl</strong> &nbsp;|&nbsp;
      Tamper-evident Credential Record
    </div>

  </div>
</div>
</body>
</html>
HTML;
}

// Select completed enrollments to issue certificates for
$completedEnrollmentKeys = [
    [0, 0, 'Valid', null],
    [1, 0, 'Valid', null],
    [2, 0, 'Valid', null],
    [3, 0, 'Valid', null],
    [4, 0, 'Revoked', 'Certificate cancelled due to administrative revision of course assessment requirements.'],
    [1, 1, 'Valid', null],
    [4, 1, 'Valid', null],
    [7, 1, 'Valid', null],
    [8, 1, 'Valid', null],
    [0, 2, 'Valid', null],
    [2, 2, 'Valid', null],
    [11, 2, 'Valid', null],
    [12, 2, 'Revoked', 'Student withdrew credit transfer verification post issuance.'],
    [3, 3, 'Valid', null],
    [5, 3, 'Valid', null],
    [15, 3, 'Valid', null],
    [6, 4, 'Valid', null],
    [7, 4, 'Valid', null],
    [18, 4, 'Valid', null],
    [8, 5, 'Valid', null],
    [9, 5, 'Valid', null],
    [21, 5, 'Revoked', 'Duplicate certificate sequence issue replaced with corrected identity.'],
    [10, 6, 'Valid', null],
    [14, 7, 'Valid', null],
    [18, 8, 'Valid', null],
    [22, 9, 'Valid', null],
    [2, 10, 'Valid', null],
];

$stmtCert = $pdo->prepare('
    INSERT INTO certificates (enrollment_id, certificate_number, issued_at, file_path, status, revoked_at, revocation_reason)
    VALUES (:enrollment_id, :cert_no, :issued_at, :file_path, :status, :revoked_at, :rev_reason)
    ON DUPLICATE KEY UPDATE status=VALUES(status), file_path=VALUES(file_path), revoked_at=VALUES(revoked_at), revocation_reason=VALUES(revocation_reason)
');

$certCount = 0;

foreach ($completedEnrollmentKeys as $cSpec) {
    $pIdx = $cSpec[0];
    $cIdx = $cSpec[1];
    $cStatus = $cSpec[2];
    $revReason = $cSpec[3];

    $key = "$pIdx-$cIdx";
    if (!isset($enrollmentMap[$key])) continue;

    $enr = $enrollmentMap[$key];
    $eId = $enr['id'];
    $uId = $enr['user_id'];
    $courseId = $enr['course_id'];
    $compDate = $enr['completion_date'];

    // Get participant name and course title
    $pName = $participants[$pIdx]['full_name'];
    $cTitle = $courses[$cIdx]['title'];

    // Generate deterministic certificate number based on eId for idempotency
    $hex = strtoupper(substr(md5("cert-$eId-salt"), 0, 8));
    $certNo = "CERT-2026-$hex";
    $issuedAt = date('Y-m-d', strtotime($compDate . ' + 2 days'));
    $relPath = "storage/certificates/{$certNo}.pdf";
    $absPath = APP_ROOT . '/' . $relPath;

    // Render PDF with Dompdf
    $html = buildCertHtml($pName, $cTitle, $compDate, $certNo, $issuedAt);
    $dompdf = new Dompdf($opts);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    file_put_contents($absPath, $dompdf->output());

    $revokedAt = ($cStatus === 'Revoked') ? date('Y-m-d H:i:s', strtotime($issuedAt . ' + 10 days')) : null;

    $stmtCert->execute([
        ':enrollment_id' => $eId,
        ':cert_no'       => $certNo,
        ':issued_at'     => $issuedAt,
        ':file_path'     => $relPath,
        ':status'        => $cStatus,
        ':revoked_at'    => $revokedAt,
        ':rev_reason'    => $revReason,
    ]);

    $certCount++;
}

echo "[4/4] Seeded $certCount certificates (PDF files generated in storage/certificates/)\n\n";

// ── Summary ─────────────────────────────────────────────────────────────────
$statsUsers = $pdo->query("SELECT role, status, COUNT(*) as cnt FROM users GROUP BY role, status")->fetchAll();
$statsCourses = $pdo->query("SELECT status, COUNT(*) as cnt FROM courses GROUP BY status")->fetchAll();
$statsEnr = $pdo->query("SELECT status, COUNT(*) as cnt FROM enrollments GROUP BY status")->fetchAll();
$statsCert = $pdo->query("SELECT status, COUNT(*) as cnt FROM certificates GROUP BY status")->fetchAll();

echo "====================================================\n";
echo "   Database Summary after Seeding\n";
echo "====================================================\n";
echo "Users:\n";
foreach ($statsUsers as $r) {
    echo "  - Role: {$r['role']} | Status: {$r['status']} => {$r['cnt']}\n";
}
echo "Courses:\n";
foreach ($statsCourses as $r) {
    echo "  - Status: {$r['status']} => {$r['cnt']}\n";
}
echo "Enrollments:\n";
foreach ($statsEnr as $r) {
    echo "  - Status: {$r['status']} => {$r['cnt']}\n";
}
echo "Certificates:\n";
foreach ($statsCert as $r) {
    echo "  - Status: {$r['status']} => {$r['cnt']}\n";
}

echo "\n====================================================\n";
echo "   Login Credentials for Testing\n";
echo "====================================================\n";
echo "1. Administrator Account:\n";
echo "   Email    : admin@tcmas.local\n";
echo "   Password : Admin@1234\n\n";
echo "2. Sample Participant Accounts (All use: Password@123):\n";
echo "   - m.otaibi@example.com     (Mohammed Al-Otaibi - Has 2 Valid Certificates)\n";
echo "   - sarah.ghamdi@example.com (Sarah Al-Ghamdi   - Has 2 Valid Certificates)\n";
echo "   - ahmed.shehri@example.com (Ahmed Al-Shehri   - Has 2 Valid Certificates)\n";
echo "   - khalid.harbi@example.com (Khalid Al-Harbi   - Has 1 Revoked + 1 Valid)\n";
echo "   - reem.dossari@example.com (Reem Al-Dossari   - Has 2 Valid Certificates)\n";
echo "====================================================\n";

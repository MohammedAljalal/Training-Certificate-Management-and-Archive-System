<?php
/**
 * Shared Certificate PDF Rendering & Serving Helpers
 * Training Certificate Management and Archive System
 */

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Render the certificate PDF binary bytes using Dompdf.
 */
function generateCertificatePdfBytes(
    string $participantName,
    string $courseTitle,
    string $completionDate,
    string $certNumber,
    string $issuedDate
): string {
    $vendorAutoload = APP_ROOT . '/vendor/autoload.php';
    if (!file_exists($vendorAutoload)) {
        throw new RuntimeException('Dompdf not installed. Run: composer install (NFR-30)');
    }
    require_once $vendorAutoload;

    $html = renderCertificateHtml(
        $participantName,
        $courseTitle,
        $completionDate,
        $certNumber,
        $issuedDate
    );

    $opts = new Options();
    $opts->setIsRemoteEnabled(false);
    $opts->setIsPhpEnabled(false);
    $opts->setDefaultFont('DejaVu Sans'); // Unicode support (NFR-30)

    $dompdf = new Dompdf($opts);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return $dompdf->output();
}

/**
 * Validate path safely without requiring the file to already exist on disk,
 * regenerate the PDF on demand if missing (e.g. migrated DB or ephemeral storage),
 * and stream it to the client.
 */
function serveCertificatePdf(array $cert): never
{
    $normalized = ltrim(str_replace('\\', '/', (string)($cert['file_path'] ?? '')), '/');
    $filename   = basename($normalized);

    if (
        $normalized === ''
        || str_contains($normalized, '..')
        || !str_starts_with($normalized, 'storage/certificates/')
        || !str_ends_with(strtolower($filename), '.pdf')
    ) {
        http_response_code(403);
        exit('403 Forbidden — invalid file path.');
    }

    $storageDir = APP_ROOT . '/storage/certificates';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0750, true);
    }

    $baseDir    = realpath($storageDir);
    $targetPath = $baseDir ? ($baseDir . DIRECTORY_SEPARATOR . $filename) : ($storageDir . '/' . $filename);

    if (!file_exists($targetPath)) {
        $pdfBytes = generateCertificatePdfBytes(
            (string)$cert['participant_name'],
            (string)$cert['course_title'],
            (string)($cert['completion_date'] ?: $cert['issued_at']),
            (string)$cert['certificate_number'],
            (string)$cert['issued_at']
        );

        if ($baseDir && is_writable($baseDir)) {
            @file_put_contents($targetPath, $pdfBytes);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBytes));
        header('Cache-Control: private, no-cache');
        echo $pdfBytes;
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($targetPath));
    header('Cache-Control: private, no-cache');
    readfile($targetPath);
    exit;
}

/**
 * Render certificate HTML template for Dompdf.
 */
function renderCertificateHtml(
    string $participantName,
    string $courseTitle,
    string $completionDate,
    string $certNumber,
    string $issuedDate
): string {
    $name       = htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8');
    $course     = htmlspecialchars($courseTitle,     ENT_QUOTES, 'UTF-8');
    $completion = date('d F Y', strtotime($completionDate));
    $issued     = date('d F Y', strtotime($issuedDate));
    $certNo     = htmlspecialchars($certNumber, ENT_QUOTES, 'UTF-8');

    $verifyHost  = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $isHttps     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $verifyProto = $isHttps ? 'https' : 'http';
    $verifyUrl   = "{$verifyProto}://{$verifyHost}/verify.php";

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
      <span class="recipient-name">$name</span>
    </div>

    <div class="course-lead">has successfully completed the prescribed curriculum and assessment requirements for the training course</div>

    <div class="course-title">$course</div>

    <table class="cert-footer-table">
      <tr>
        <td class="col-dates">
          <div>Completion Date: <strong>$completion</strong></div>
          <div>Date of Issuance: <strong>$issued</strong></div>
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
      Certificate Identifier: <strong>$certNo</strong> &nbsp;|&nbsp;
      Verify Online: <strong>$verifyUrl</strong> &nbsp;|&nbsp;
      Tamper-evident Credential Record
    </div>

  </div>
</div>
</body>
</html>
HTML;
}

<?php
if (PHP_VERSION_ID >= 80000) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}
if (ob_get_level()) ob_end_clean();
ob_start();

require_once '../../config/config.php';
require_once '../../src/includes/auth.php';
requireLogin();
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ── Parameters ────────────────────────────────────────────────────────────────
$incType   = in_array($_GET['type'] ?? '', ['downtime', 'security', 'fraud', 'all']) ? $_GET['type'] : 'all';
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date']   ?? '');
$companyId = isset($_GET['company_id']) && $_GET['company_id'] !== '' ? intval($_GET['company_id']) : null;
$status    = in_array($_GET['status'] ?? '', ['pending', 'resolved']) ? $_GET['status'] : '';
$format    = ($_GET['format'] ?? 'xlsx') === 'csv' ? 'csv' : 'xlsx';

// ── Style helpers ─────────────────────────────────────────────────────────────
function incHdrStyle(string $bgHex, bool $whiteText = true): array {
    return [
        'font'      => ['bold' => true, 'color' => ['argb' => $whiteText ? 'FFFFFFFF' : 'FF000000']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $bgHex]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
    ];
}
function incCellStyle(bool $alt = false): array {
    $s = [
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
    ];
    if ($alt) {
        $s['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']];
    }
    return $s;
}

try {

// ════════════════════════════════════════════════════════════════════════════
// DATA QUERIES
// ════════════════════════════════════════════════════════════════════════════
function fetchDowntimeIncidents(PDO $pdo, string $startDate, string $endDate, ?int $companyId, string $status): array {
    $where  = [];
    $params = [];

    if ($startDate !== '') { $where[] = 'DATE(i.actual_start_time) >= ?'; $params[] = $startDate; }
    if ($endDate   !== '') { $where[] = 'DATE(i.actual_start_time) <= ?'; $params[] = $endDate; }
    if ($status    !== '') { $where[] = 'i.status = ?';                   $params[] = $status; }
    if ($companyId !== null) {
        $where[] = 'EXISTS (SELECT 1 FROM incident_affected_companies iac2 WHERE iac2.incident_id = i.incident_id AND iac2.company_id = ?)';
        $params[] = $companyId;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            i.incident_ref,
            i.actual_start_time,
            i.resolved_at,
            CASE WHEN i.status = 'resolved' AND i.resolved_at IS NOT NULL
                 THEN ROUND(TIMESTAMPDIFF(MINUTE, i.actual_start_time, i.resolved_at))
                 ELSE NULL END AS duration_minutes,
            s.service_name,
            it.name   AS incident_type,
            CASE WHEN comp_agg.company_list LIKE '%All%' THEN 'All' ELSE comp_agg.company_list END AS affected_companies,
            GROUP_CONCAT(DISTINCT sc.name ORDER BY sc.name SEPARATOR ', ') AS components,
            i.impact_level,
            i.priority,
            i.incident_source,
            i.status,
            i.description,
            i.root_cause,
            i.lessons_learned,
            rep.full_name  AS reported_by,
            res.full_name  AS resolved_by
        FROM incidents i
        JOIN services s        ON i.service_id        = s.service_id
        JOIN users rep         ON i.reported_by        = rep.user_id
        LEFT JOIN users res    ON i.resolved_by        = res.user_id
        LEFT JOIN incident_types it ON i.incident_type_id = it.type_id
        LEFT JOIN incident_components icomp ON i.incident_id = icomp.incident_id
        LEFT JOIN components sc ON icomp.component_id  = sc.component_id
        LEFT JOIN (
            SELECT iac.incident_id,
                   GROUP_CONCAT(DISTINCT c.company_name ORDER BY c.company_name SEPARATOR ', ') AS company_list
            FROM incident_affected_companies iac
            JOIN companies c ON iac.company_id = c.company_id
            GROUP BY iac.incident_id
        ) comp_agg ON comp_agg.incident_id = i.incident_id
        $whereSQL
        GROUP BY i.incident_id
        ORDER BY FIELD(i.status,'pending','resolved'), i.actual_start_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSecurityIncidents(PDO $pdo, string $startDate, string $endDate, string $status): array {
    $where  = [];
    $params = [];

    if ($startDate !== '') { $where[] = 'DATE(s.actual_start_time) >= ?'; $params[] = $startDate; }
    if ($endDate   !== '') { $where[] = 'DATE(s.actual_start_time) <= ?'; $params[] = $endDate; }
    if ($status    !== '') { $where[] = 's.status = ?';                   $params[] = $status; }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            s.incident_ref,
            s.actual_start_time,
            s.resolved_at,
            s.threat_type,
            s.systems_affected,
            s.description,
            s.impact_level,
            s.priority,
            s.containment_status,
            s.escalated_to,
            s.status,
            u.full_name AS reported_by
        FROM security_incidents s
        JOIN users u ON s.reported_by = u.user_id
        $whereSQL
        ORDER BY FIELD(s.status,'pending','resolved'), s.actual_start_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchFraudIncidents(PDO $pdo, string $startDate, string $endDate, string $status): array {
    $where  = [];
    $params = [];

    if ($startDate !== '') { $where[] = 'DATE(f.actual_start_time) >= ?'; $params[] = $startDate; }
    if ($endDate   !== '') { $where[] = 'DATE(f.actual_start_time) <= ?'; $params[] = $endDate; }
    if ($status    !== '') { $where[] = 'f.status = ?';                   $params[] = $status; }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            f.incident_ref,
            f.actual_start_time,
            f.resolved_at,
            f.fraud_type,
            sv.service_name,
            f.description,
            f.financial_impact,
            f.impact_level,
            f.priority,
            f.regulatory_reported,
            f.status,
            u.full_name AS reported_by
        FROM fraud_incidents f
        JOIN users u ON f.reported_by = u.user_id
        LEFT JOIN services sv ON f.service_id = sv.service_id
        $whereSQL
        ORDER BY FIELD(f.status,'pending','resolved'), f.actual_start_time DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ════════════════════════════════════════════════════════════════════════════
// LABEL MAPS
// ════════════════════════════════════════════════════════════════════════════
$threatLabels = [
    'phishing'            => 'Phishing',
    'unauthorized_access' => 'Unauthorized Access',
    'data_breach'         => 'Data Breach',
    'malware'             => 'Malware',
    'social_engineering'  => 'Social Engineering',
    'other'               => 'Other',
];
$fraudLabels = [
    'card_fraud'        => 'Card Fraud',
    'account_takeover'  => 'Account Takeover',
    'transaction_fraud' => 'Transaction Fraud',
    'internal_fraud'    => 'Internal Fraud',
    'other'             => 'Other',
];

// ════════════════════════════════════════════════════════════════════════════
// WORKSHEET BUILDERS  (each receives a Worksheet directly — no sheet creation)
// ════════════════════════════════════════════════════════════════════════════
function populateDowntimeSheet(Worksheet $ws, array $rows): void {
    $headers = [
        'Ref #', 'Start Time', 'Resolved At', 'Duration (min)',
        'Service', 'Incident Type', 'Affected Banks/Companies', 'Components',
        'Impact', 'Priority', 'Source', 'Status',
        'Description', 'Root Cause', 'Lessons Learned',
        'Reported By', 'Resolved By',
    ];
    $ws->fromArray($headers, null, 'A1');
    $lastCol = chr(ord('A') + count($headers) - 1);
    $ws->getStyle("A1:{$lastCol}1")->applyFromArray(incHdrStyle('1F3864'));
    $ws->getRowDimension(1)->setRowHeight(22);

    $r = 2;
    foreach ($rows as $inc) {
        $ws->fromArray([
            $inc['incident_ref']        ?? '',
            $inc['actual_start_time']   ?? '',
            $inc['resolved_at']         ?? '',
            $inc['duration_minutes'] !== null ? (int)$inc['duration_minutes'] : '',
            $inc['service_name']        ?? '',
            $inc['incident_type']       ?? '',
            $inc['affected_companies']  ?? '',
            $inc['components']          ?? '',
            $inc['impact_level']        ?? '',
            $inc['priority']            ?? '',
            ucfirst($inc['incident_source'] ?? ''),
            ucfirst($inc['status']      ?? ''),
            $inc['description']         ?? '',
            $inc['root_cause']          ?? '',
            $inc['lessons_learned']     ?? '',
            $inc['reported_by']         ?? '',
            $inc['resolved_by']         ?? '',
        ], null, "A{$r}");
        $ws->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray(incCellStyle($r % 2 === 0));
        $r++;
    }

    $widths = [14, 18, 18, 14, 22, 20, 32, 24, 10, 10, 10, 10, 40, 40, 40, 18, 18];
    foreach ($widths as $i => $w) {
        $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
    }
    $ws->freezePane('A2');

    if (empty($rows)) {
        $ws->setCellValue('A2', 'No incidents found for the selected filters.');
        $ws->mergeCells("A2:{$lastCol}2");
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}

function populateSecuritySheet(Worksheet $ws, array $rows, array $threatLabels): void {
    $headers = [
        'Ref #', 'Start Time', 'Resolved At', 'Threat Type',
        'Systems Affected', 'Description', 'Impact', 'Priority',
        'Containment Status', 'Escalated To', 'Status', 'Reported By',
    ];
    $ws->fromArray($headers, null, 'A1');
    $lastCol = chr(ord('A') + count($headers) - 1);
    $ws->getStyle("A1:{$lastCol}1")->applyFromArray(incHdrStyle('1E3A5F'));
    $ws->getRowDimension(1)->setRowHeight(22);

    $r = 2;
    foreach ($rows as $inc) {
        $ws->fromArray([
            $inc['incident_ref']       ?? '',
            $inc['actual_start_time']  ?? '',
            $inc['resolved_at']        ?? '',
            $threatLabels[$inc['threat_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $inc['threat_type'] ?? '')),
            $inc['systems_affected']   ?? '',
            $inc['description']        ?? '',
            $inc['impact_level']       ?? '',
            $inc['priority']           ?? '',
            $inc['containment_status'] ?? '',
            $inc['escalated_to']       ?? '',
            ucfirst($inc['status']     ?? ''),
            $inc['reported_by']        ?? '',
        ], null, "A{$r}");
        $ws->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray(incCellStyle($r % 2 === 0));
        $r++;
    }

    $widths = [14, 18, 18, 22, 30, 40, 10, 10, 20, 22, 10, 18];
    foreach ($widths as $i => $w) {
        $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
    }
    $ws->freezePane('A2');

    if (empty($rows)) {
        $ws->setCellValue('A2', 'No incidents found for the selected filters.');
        $ws->mergeCells("A2:{$lastCol}2");
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}

function populateFraudSheet(Worksheet $ws, array $rows, array $fraudLabels): void {
    $headers = [
        'Ref #', 'Start Time', 'Resolved At', 'Fraud Type',
        'Service', 'Description', 'Financial Impact', 'Impact Level',
        'Priority', 'Regulatory Reported', 'Status', 'Reported By',
    ];
    $ws->fromArray($headers, null, 'A1');
    $lastCol = chr(ord('A') + count($headers) - 1);
    $ws->getStyle("A1:{$lastCol}1")->applyFromArray(incHdrStyle('78350F'));
    $ws->getRowDimension(1)->setRowHeight(22);

    $r = 2;
    foreach ($rows as $inc) {
        $ws->fromArray([
            $inc['incident_ref']       ?? '',
            $inc['actual_start_time']  ?? '',
            $inc['resolved_at']        ?? '',
            $fraudLabels[$inc['fraud_type'] ?? ''] ?? ucfirst(str_replace('_', ' ', $inc['fraud_type'] ?? '')),
            $inc['service_name']       ?? '',
            $inc['description']        ?? '',
            $inc['financial_impact']   ?? '',
            $inc['impact_level']       ?? '',
            $inc['priority']           ?? '',
            ($inc['regulatory_reported'] ?? 0) ? 'Yes' : 'No',
            ucfirst($inc['status']     ?? ''),
            $inc['reported_by']        ?? '',
        ], null, "A{$r}");
        $ws->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray(incCellStyle($r % 2 === 0));
        $r++;
    }

    $widths = [14, 18, 18, 20, 22, 40, 16, 10, 10, 18, 10, 18];
    foreach ($widths as $i => $w) {
        $ws->getColumnDimensionByColumn($i + 1)->setWidth($w);
    }
    $ws->freezePane('A2');

    if (empty($rows)) {
        $ws->setCellValue('A2', 'No incidents found for the selected filters.');
        $ws->mergeCells("A2:{$lastCol}2");
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// CSV HELPER
// ════════════════════════════════════════════════════════════════════════════
function outputCsv(array $rows, array $threatLabels, array $fraudLabels): void {
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

    fputcsv($out, ['Incident Type', 'Ref #', 'Start Time', 'Resolved At', 'Status', 'Impact', 'Priority', 'Reported By', 'Details']);

    foreach ($rows as [$type, $inc]) {
        if ($type === 'Downtime') {
            $details = 'Service: ' . ($inc['service_name'] ?? '') . ' | Banks: ' . ($inc['affected_companies'] ?? '') . ' | Root Cause: ' . ($inc['root_cause'] ?? '');
        } elseif ($type === 'Security') {
            $details = 'Threat: ' . ($threatLabels[$inc['threat_type'] ?? ''] ?? '') . ' | Systems: ' . ($inc['systems_affected'] ?? '');
        } else {
            $details = 'Fraud Type: ' . ($fraudLabels[$inc['fraud_type'] ?? ''] ?? '') . ' | Service: ' . ($inc['service_name'] ?? '') . ' | Financial Impact: ' . ($inc['financial_impact'] ?? '');
        }
        fputcsv($out, [
            $type,
            $inc['incident_ref']      ?? '',
            $inc['actual_start_time'] ?? '',
            $inc['resolved_at']       ?? '',
            ucfirst($inc['status']    ?? ''),
            $inc['impact_level']      ?? '',
            $inc['priority']          ?? '',
            $inc['reported_by']       ?? '',
            $details,
        ]);
    }
    fclose($out);
}

// ════════════════════════════════════════════════════════════════════════════
// FETCH DATA
// ════════════════════════════════════════════════════════════════════════════
$dtRows  = ($incType === 'downtime' || $incType === 'all')
    ? fetchDowntimeIncidents($pdo, $startDate, $endDate, $companyId, $status) : [];
$secRows = ($incType === 'security' || $incType === 'all')
    ? fetchSecurityIncidents($pdo, $startDate, $endDate, $status) : [];
$frRows  = ($incType === 'fraud' || $incType === 'all')
    ? fetchFraudIncidents($pdo, $startDate, $endDate, $status) : [];

// ── Filename ──────────────────────────────────────────────────────────────────
$dateParts = array_filter([$startDate, $endDate]);
$dateLabel = $dateParts ? implode('_to_', $dateParts) : date('Y-m-d');
$filename  = 'Incidents_' . strtoupper($incType) . '_' . $dateLabel;

// ── CSV ───────────────────────────────────────────────────────────────────────
if ($format === 'csv') {
    $allRows = [];
    foreach ($dtRows  as $r) { $allRows[] = ['Downtime', $r]; }
    foreach ($secRows as $r) { $allRows[] = ['Security', $r]; }
    foreach ($frRows  as $r) { $allRows[] = ['Fraud',    $r]; }

    ob_end_clean();
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    header('Cache-Control: max-age=0');
    outputCsv($allRows, $threatLabels, $fraudLabels);
    exit;
}

// ── Excel ─────────────────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0); // remove the default empty sheet
$spreadsheet->getProperties()
    ->setCreator('eTranzact Downtime Tracker')
    ->setTitle("Incidents Export — {$dateLabel}");

if ($incType === 'downtime' || $incType === 'all') {
    $ws = $spreadsheet->createSheet();
    $ws->setTitle('Downtime Incidents');
    populateDowntimeSheet($ws, $dtRows);
}
if ($incType === 'security' || $incType === 'all') {
    $ws = $spreadsheet->createSheet();
    $ws->setTitle('Security Incidents');
    populateSecuritySheet($ws, $secRows, $threatLabels);
}
if ($incType === 'fraud' || $incType === 'all') {
    $ws = $spreadsheet->createSheet();
    $ws->setTitle('Fraud Incidents');
    populateFraudSheet($ws, $frRows, $fraudLabels);
}

$spreadsheet->setActiveSheetIndex(0);
ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    die('Export failed: ' . htmlspecialchars($e->getMessage()));
}

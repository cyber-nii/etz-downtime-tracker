<?php
if (PHP_VERSION_ID >= 80000) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}
if (ob_get_level()) ob_end_clean();
ob_start();

require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ── Parameters ───────────────────────────────────────────────────────────────
$companyId  = $_GET['company_id'] ?? null;
$startDate  = $_GET['start_date'] ?? date('Y-m-01');
$endDate    = $_GET['end_date']   ?? date('Y-m-t');
$slaTarget  = 99.99;
$slaDecimal = $slaTarget / 100; // 0.9999

try {

// ── Months in range ───────────────────────────────────────────────────────────
function getMonths(string $start, string $end): array {
    $months = [];
    $cur    = new DateTime($start);
    $cur->modify('first day of this month');
    $endDt  = new DateTime($end);
    while ($cur <= $endDt) {
        $months[] = [
            'label'         => $cur->format('F Y'),
            'start'         => $cur->format('Y-m-01') . ' 00:00:00',
            'end'           => $cur->format('Y-m-t')  . ' 23:59:59',
            'total_minutes' => (int)$cur->format('t') * 1440,
            'days'          => (int)$cur->format('t'),
        ];
        $cur->modify('+1 month');
    }
    return $months;
}
$months = getMonths($startDate, $endDate);

// ── Query all downtime incidents in range ─────────────────────────────────────
$sql = "SELECT
    i.incident_id,
    iac.company_id,
    c.company_name,
    s.service_name,
    i.incident_source,
    i.root_cause,
    di.actual_start_time,
    di.actual_end_time
FROM incidents i
JOIN services s ON i.service_id = s.service_id
JOIN incident_affected_companies iac ON i.incident_id = iac.incident_id
JOIN companies c ON iac.company_id = c.company_id
JOIN downtime_incidents di ON i.incident_id = di.incident_id AND di.is_planned = 0
WHERE di.actual_start_time < :end
  AND (COALESCE(di.actual_end_time, NOW()) > :start)";

$params = [
    ':start' => $startDate . ' 00:00:00',
    ':end'   => $endDate   . ' 23:59:59',
];
if ($companyId) {
    $sql .= " AND iac.company_id = :company_id";
    $params[':company_id'] = $companyId;
}
$sql .= " ORDER BY c.company_name, di.actual_start_time";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allIncidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Group incidents by company ────────────────────────────────────────────────
$companiesData = [];
foreach ($allIncidents as $inc) {
    $cid = $inc['company_id'];
    if (!isset($companiesData[$cid])) {
        $companiesData[$cid] = ['name' => $inc['company_name'], 'incidents' => []];
    }
    $companiesData[$cid]['incidents'][] = $inc;
}

// Ensure selected company appears even with zero incidents
if ($companyId && empty($companiesData)) {
    $cs = $pdo->prepare("SELECT company_id, company_name FROM companies WHERE company_id = ?");
    $cs->execute([$companyId]);
    if ($r = $cs->fetch(PDO::FETCH_ASSOC)) {
        $companiesData[$r['company_id']] = ['name' => $r['company_name'], 'incidents' => []];
    }
}

// If still empty (all companies, no data) load all companies
if (empty($companiesData)) {
    foreach ($pdo->query("SELECT company_id, company_name FROM companies ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $companiesData[$r['company_id']] = ['name' => $r['company_name'], 'incidents' => []];
    }
}

// ── Helper: downtime minutes clipped to a month window ───────────────────────
function monthDowntime(array $incidents, string $mStart, string $mEnd, ?string $source = null): int {
    $total = 0;
    foreach ($incidents as $inc) {
        if ($source !== null && ($inc['incident_source'] ?? 'external') !== $source) continue;
        $iStart = max(strtotime($inc['actual_start_time']), strtotime($mStart));
        $iEnd   = min(
            $inc['actual_end_time'] ? strtotime($inc['actual_end_time']) : time(),
            strtotime($mEnd)
        );
        if ($iEnd > $iStart) {
            $total += ($iEnd - $iStart) / 60;
        }
    }
    return max(0, (int)round($total));
}

// ── Style helpers ─────────────────────────────────────────────────────────────
function hdrStyle(string $bgHex, bool $whiteText = true): array {
    return [
        'font'      => ['bold' => true, 'color' => ['argb' => $whiteText ? 'FFFFFFFF' : 'FF000000']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $bgHex]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
    ];
}
function cellStyle(): array {
    return [
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
    ];
}
function safeSheetName(string $name, string $suffix): string {
    $clean = preg_replace('/[\\\\\/\?\*\[\]:]/u', '', $name);
    $max   = 31 - strlen($suffix);
    return mb_substr($clean, 0, $max) . $suffix;
}

// ════════════════════════════════════════════════════════════════════════════
// SHEET BUILDER: SLA % summary (one row per company, one col per month)
// ════════════════════════════════════════════════════════════════════════════
function buildSlaSheet(
    Spreadsheet $sp,
    string $title,
    array  $companiesData,
    array  $months,
    float  $slaDecimal,
    string $source,
    string $darkBg,
    string $lightBg
): void {
    $ws = $sp->createSheet();
    $ws->setTitle($title);

    // Row 1 — column headers
    $ws->setCellValue('A1', 'Clients');
    $ws->setCellValue('B1', 'Service Description');
    $ws->setCellValue('C1', 'Service Level Target');
    $col = 4;
    foreach ($months as $m) {
        $ws->setCellValueByColumnAndRow($col, 1, $m['label']);
        $col++;
    }
    $lastCol = Coordinate::stringFromColumnIndex($col - 1);
    $ws->getStyle("A1:{$lastCol}1")->applyFromArray(hdrStyle($darkBg));

    // Data rows — one per company
    $row = 2;
    foreach ($companiesData as $cData) {
        $ws->setCellValue("A{$row}", $cData['name']);
        $ws->setCellValue("B{$row}", 'Mobile Banking Service');
        $ws->setCellValue("C{$row}", $slaDecimal);
        $ws->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.00%');

        $col = 4;
        foreach ($months as $m) {
            $dm     = monthDowntime($cData['incidents'], $m['start'], $m['end'], $source);
            $uptime = $m['total_minutes'] > 0
                ? max(0, min($slaDecimal, ($m['total_minutes'] - $dm) / $m['total_minutes']))
                : $slaDecimal;
            $cell = Coordinate::stringFromColumnIndex($col) . $row;
            $ws->setCellValue($cell, $uptime);
            $ws->getStyle($cell)->getNumberFormat()->setFormatCode('0.00%');
            // Highlight red if below SLA target
            if ($uptime < $slaDecimal) {
                $ws->getStyle($cell)->getFont()->getColor()->setARGB('FFFF0000');
                $ws->getStyle($cell)->getFont()->setBold(true);
            }
            $col++;
        }
        $ws->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray(cellStyle());
        $ws->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        // Alternate row shade
        if ($row % 2 === 0) {
            $ws->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
        }
        $row++;
    }

    // Column widths
    $ws->getColumnDimension('A')->setWidth(22);
    $ws->getColumnDimension('B')->setWidth(28);
    $ws->getColumnDimension('C')->setWidth(22);
    for ($c = 4; $c < $col; $c++) {
        $ws->getColumnDimensionByColumn($c)->setWidth(16);
    }
    $ws->getRowDimension(1)->setRowHeight(24);
    $ws->freezePane('D2');
}

// ════════════════════════════════════════════════════════════════════════════
// SHEET BUILDER: Downtime Summary (incident list grouped by month)
// ════════════════════════════════════════════════════════════════════════════
function buildSummarySheet(Spreadsheet $sp, array $allIncidents, array $months): void {
    $ws = $sp->createSheet();
    $ws->setTitle('Downtime Summary');

    $colHdrs = ['Institution', 'Start Time', 'End Time', 'Duration', 'Service Affected', 'Root Cause', 'Total Minutes'];
    $row = 1;

    foreach ($months as $m) {
        // Month title
        $ws->setCellValue("A{$row}", $m['label']);
        $ws->mergeCells("A{$row}:G{$row}");
        $ws->getStyle("A{$row}:G{$row}")->applyFromArray(hdrStyle('1F3864'));
        $row++;

        // Column headers
        $ws->fromArray($colHdrs, null, "A{$row}");
        $ws->getStyle("A{$row}:G{$row}")->applyFromArray(hdrStyle('2E75B6'));
        $row++;

        // Incidents for this month
        $monthIncs = array_filter($allIncidents, function($inc) use ($m) {
            return strtotime($inc['actual_start_time']) < strtotime($m['end'])
                && ($inc['actual_end_time'] ? strtotime($inc['actual_end_time']) : time()) > strtotime($m['start']);
        });

        if (empty($monthIncs)) {
            $ws->setCellValue("A{$row}", 'No downtime incidents recorded this period.');
            $ws->mergeCells("A{$row}:G{$row}");
            $ws->getStyle("A{$row}")->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['argb' => 'FF888888']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $row += 2;
            continue;
        }

        $monthTotal = 0;
        foreach ($monthIncs as $inc) {
            $iStart       = strtotime($inc['actual_start_time']);
            $iEnd         = $inc['actual_end_time'] ? strtotime($inc['actual_end_time']) : time();
            $clippedStart = max($iStart, strtotime($m['start']));
            $clippedEnd   = min($iEnd,   strtotime($m['end']));
            $dm           = max(0, (int)(($clippedEnd - $clippedStart) / 60));
            $monthTotal  += $dm;

            $source      = ($inc['incident_source'] ?? 'external') === 'internal' ? 'ETRANZACT' : strtoupper($inc['company_name']);
            $endLabel    = $inc['actual_end_time'] ? date('M d,Y H:i:s', $iEnd) : 'Ongoing';

            $ws->fromArray([
                $source,
                date('M d,Y H:i:s', $iStart),
                $endLabel,
                $dm . ' minutes',
                $inc['service_name'],
                $inc['root_cause'] ?: 'N/A',
                '',
            ], null, "A{$row}");

            $ws->getStyle("A{$row}:G{$row}")->applyFromArray(cellStyle());
            $ws->getStyle("A{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            if ($row % 2 === 0) {
                $ws->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
            }
            $row++;
        }

        // Month total
        $ws->setCellValue("G{$row}", $monthTotal . ' minutes.');
        $ws->getStyle("G{$row}")->getFont()->setBold(true);
        $row += 2; // blank spacer
    }

    $widths = ['A' => 22, 'B' => 22, 'C' => 22, 'D' => 16, 'E' => 28, 'F' => 44, 'G' => 16];
    foreach ($widths as $c => $w) {
        $ws->getColumnDimension($c)->setWidth($w);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// SHEET BUILDER: Monthly downtime breakdown (company or eTranzact)
// ════════════════════════════════════════════════════════════════════════════
function buildDowntimeSheet(
    Spreadsheet $sp,
    string $title,
    string $entityLabel,
    array  $incidents,
    array  $months,
    string $source,
    string $darkBg,
    string $midBg
): void {
    $ws = $sp->createSheet();
    $ws->setTitle($title);

    // Each month = 4 cols: Downtime(min) | Total(min) | %Downtime | Remarks
    // Col A = entity label / service name

    $ws->setCellValue('A1', $entityLabel);
    $ws->setCellValue('A2', 'No. Incidence / Time Lasted');
    $ws->getStyle('A1')->applyFromArray(hdrStyle($darkBg));
    $ws->getStyle('A2')->applyFromArray(hdrStyle($midBg));
    $ws->mergeCells('A1:A2');

    $colStart = 2;
    foreach ($months as $m) {
        $c1 = Coordinate::stringFromColumnIndex($colStart);
        $c2 = Coordinate::stringFromColumnIndex($colStart + 3);

        // Month header (merged)
        $ws->setCellValue("{$c1}1", $m['label']);
        $ws->mergeCells("{$c1}1:{$c2}1");
        $ws->getStyle("{$c1}1:{$c2}1")->applyFromArray(hdrStyle($darkBg));

        // Subheaders
        $ws->setCellValueByColumnAndRow($colStart,     2, 'Downtime (min)');
        $ws->setCellValueByColumnAndRow($colStart + 1, 2, $m['days'] . ' days / ' . number_format($m['total_minutes']) . ' min/mnth');
        $ws->setCellValueByColumnAndRow($colStart + 2, 2, '% Downtime');
        $ws->setCellValueByColumnAndRow($colStart + 3, 2, 'Remarks');
        $ws->getStyle("{$c1}2:{$c2}2")->applyFromArray(hdrStyle($midBg));

        $colStart += 4;
    }

    // Data row (row 3)
    $ws->setCellValue('A3', 'Mobile Banking Services');
    $ws->getStyle('A3')->applyFromArray([
        'font'      => ['bold' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD0D0D0']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $colStart = 2;
    foreach ($months as $m) {
        $dm    = monthDowntime($incidents, $m['start'], $m['end'], $source);
        $total = $m['total_minutes'];
        $pct   = $total > 0 ? $dm / $total : 0;

        $ws->setCellValueByColumnAndRow($colStart,     3, $dm ?: 0);
        $ws->setCellValueByColumnAndRow($colStart + 1, 3, $total);
        $ws->setCellValueByColumnAndRow($colStart + 2, 3, $pct);
        $ws->setCellValueByColumnAndRow($colStart + 3, 3, '');

        $pctCell = Coordinate::stringFromColumnIndex($colStart + 2) . '3';
        $ws->getStyle($pctCell)->getNumberFormat()->setFormatCode('0.0000%');

        $c1 = Coordinate::stringFromColumnIndex($colStart);
        $c2 = Coordinate::stringFromColumnIndex($colStart + 3);
        $ws->getStyle("{$c1}3:{$c2}3")->applyFromArray(cellStyle());

        $colStart += 4;
    }

    // Column widths
    $ws->getColumnDimension('A')->setWidth(26);
    $colStart = 2;
    foreach ($months as $_) {
        $ws->getColumnDimensionByColumn($colStart)->setWidth(16);
        $ws->getColumnDimensionByColumn($colStart + 1)->setWidth(26);
        $ws->getColumnDimensionByColumn($colStart + 2)->setWidth(14);
        $ws->getColumnDimensionByColumn($colStart + 3)->setWidth(18);
        $colStart += 4;
    }
    $ws->getRowDimension(1)->setRowHeight(24);
    $ws->getRowDimension(2)->setRowHeight(30);
    $ws->getRowDimension(3)->setRowHeight(20);
    $ws->freezePane('B3');
}

// ════════════════════════════════════════════════════════════════════════════
// BUILD THE WORKBOOK
// ════════════════════════════════════════════════════════════════════════════
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);
$spreadsheet->getProperties()
    ->setCreator('eTranzact Downtime Tracker')
    ->setTitle('SLA Report ' . $startDate . ' to ' . $endDate);

// Sheet 1 — Company SLA % (External incidents)
buildSlaSheet($spreadsheet, 'SLA %',       $companiesData, $months, $slaDecimal, 'external', '1F3864', '2E75B6');

// Sheet 2 — eTranzact SLA % (Internal incidents)
buildSlaSheet($spreadsheet, 'ETRANZACT %', $companiesData, $months, $slaDecimal, 'internal', '833C00', 'C55A11');

// Sheet 3 — Downtime Summary
buildSummarySheet($spreadsheet, $allIncidents, $months);

// Sheets 4+ — Per company external downtime breakdown
foreach ($companiesData as $cData) {
    $sheetName = safeSheetName($cData['name'], ' Downtime');
    buildDowntimeSheet($spreadsheet, $sheetName, $cData['name'], $cData['incidents'], $months, 'external', '1F3864', '2E75B6');
}

// Final sheet — eTranzact internal downtime breakdown
buildDowntimeSheet($spreadsheet, 'Etranzact Downtime', 'Etranzact', $allIncidents, $months, 'internal', '833C00', 'C55A11');

// ── Output ────────────────────────────────────────────────────────────────────
$spreadsheet->setActiveSheetIndex(0);
ob_end_clean();

$companyLabel = ($companyId && !empty($companiesData))
    ? preg_replace('/[^a-zA-Z0-9_-]/', '_', reset($companiesData)['name']) . '_'
    : '';
$filename = 'SLA_Report_' . $companyLabel . $startDate . '_to_' . $endDate . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;

} catch (Exception $e) {
    ob_end_clean();
    die('Error generating report: ' . $e->getMessage());
}

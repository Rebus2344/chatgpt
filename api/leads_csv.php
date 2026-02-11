<?php
require __DIR__ . '/_config.php';
require_admin();

ensure_leads_csv();

$csv = file_get_contents(LEADS_CSV);
if ($csv === false) {
  text_response(500, 'cannot read leads.csv');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo $csv;
exit;

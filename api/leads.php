<?php
require __DIR__ . '/_config.php';
require_admin();

ensure_leads_csv();

$rows = [];
$fh = fopen(LEADS_CSV, 'rb');
if (!$fh) json_response(500, ['ok'=>false,'error'=>'cannot read leads.csv']);

$header = fgetcsv($fh);
while (($r = fgetcsv($fh)) !== false) {
  // map by header if possible
  $item = [];
  if (is_array($header) && count($header) === count($r)) {
    $item = array_combine($header, $r) ?: [];
  } else {
    $item = [
      'ts' => $r[0] ?? '',
      'ip' => $r[1] ?? '',
      'lead_type' => $r[2] ?? '',
      'page' => $r[3] ?? '',
      'referer' => $r[4] ?? '',
      'fields_json' => $r[5] ?? '',
      'utm_json' => $r[6] ?? '',
    ];
  }

  $fields = json_decode((string)($item['fields_json'] ?? ''), true);
  $utm = json_decode((string)($item['utm_json'] ?? ''), true);
  if (!is_array($fields)) $fields = [];
  if (!is_array($utm)) $utm = [];

  $item['fields_pretty'] = pretty_kv($fields);
  $item['utm_pretty'] = pretty_kv($utm);

  $rows[] = [
    'ts' => (string)($item['ts'] ?? ''),
    'ip' => (string)($item['ip'] ?? ''),
    'lead_type' => (string)($item['lead_type'] ?? ''),
    'page' => (string)($item['page'] ?? ''),
    'referer' => (string)($item['referer'] ?? ''),
    'fields_pretty' => $item['fields_pretty'],
    'utm_pretty' => $item['utm_pretty'],
  ];
}
fclose($fh);

json_response(200, $rows);

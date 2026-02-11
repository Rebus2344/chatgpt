<?php
require __DIR__ . '/_config.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  json_response(405, ['ok'=>false, 'msg'=>'Method not allowed']);
}

$payload = get_json_body();
$leadType = trim((string)($payload['lead_type'] ?? 'lead_form')) ?: 'lead_form';
$page = trim((string)($payload['page'] ?? ''));
$referer = trim((string)($payload['referer'] ?? ''));
$utm = $payload['utm'] ?? [];
$fields = $payload['fields'] ?? [];
if (!is_array($utm)) $utm = [];
if (!is_array($fields)) $fields = [];

// Basic validation: at least one contact field present
$hasContact = false;
foreach (['phone','tel','email','telegram','name'] as $k) {
  if (!empty($fields[$k])) { $hasContact = true; break; }
}
if (!$hasContact) {
  // still accept, but mark message
}

ensure_leads_csv();
$ip = client_ip();
$ts = date('Y-m-d H:i:s');

$line = [
  $ts,
  $ip,
  $leadType,
  $page,
  $referer,
  json_encode($utm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
];

$fh = fopen(LEADS_CSV, 'ab');
if (!$fh) {
  json_response(500, ['ok'=>false, 'msg'=>'Не удалось сохранить заявку']);
}
if (flock($fh, LOCK_EX)) {
  fputcsv($fh, $line);
  flock($fh, LOCK_UN);
}
fclose($fh);

json_response(200, ['ok'=>true, 'msg'=>'Заявка отправлена ✅']);
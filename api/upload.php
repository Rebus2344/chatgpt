<?php
require __DIR__ . '/_config.php';
require_admin();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  json_response(405, ['ok'=>false, 'error'=>'Method not allowed']);
}

$category = sanitize_category((string)($_POST['category'] ?? 'kmu'));
$slug = trim((string)($_POST['slug'] ?? 'image'));
if ($slug === '') $slug = 'image';
$slug = slugify_ru($slug);

// accept file field name "file" (admin uses it); also support "files" / "images"
$files = [];
if (!empty($_FILES['file'])) {
  $files[] = $_FILES['file'];
} elseif (!empty($_FILES['files'])) {
  $files = is_array($_FILES['files']['name'] ?? null) ? $_FILES['files'] : [$_FILES['files']];
} elseif (!empty($_FILES['images'])) {
  $files = is_array($_FILES['images']['name'] ?? null) ? $_FILES['images'] : [$_FILES['images']];
}

// normalize multiple structure
function explode_files_array(array $f): array {
  if (!is_array($f['name'] ?? null)) return [$f];
  $out = [];
  $n = count($f['name']);
  for ($i=0; $i<$n; $i++) {
    $out[] = [
      'name' => $f['name'][$i],
      'type' => $f['type'][$i] ?? '',
      'tmp_name' => $f['tmp_name'][$i],
      'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
      'size' => $f['size'][$i] ?? 0,
    ];
  }
  return $out;
}

if (isset($files['name']) && is_array($files['name'] ?? null)) {
  $files = explode_files_array($files);
}

if (!$files) {
  json_response(400, ['ok'=>false, 'error'=>'file required']);
}

$allowed = ['jpg','jpeg','png','webp','avif','jfif','gif'];
$targetDir = UPLOADS_DIR . '/' . $category;
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
  json_response(500, ['ok'=>false, 'error'=>'cannot create uploads dir']);
}

$saved = [];
foreach (array_slice($files, 0, 10) as $idx => $f) {
  if (!is_array($f)) continue;
  $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($err !== UPLOAD_ERR_OK) continue;
  $tmp = (string)($f['tmp_name'] ?? '');
  if ($tmp === '' || !is_uploaded_file($tmp)) continue;

  $name = (string)($f['name'] ?? 'image');
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext === '' || !in_array($ext, $allowed, true)) {
    $ext = 'jpg';
  }

  $rand = bin2hex(random_bytes(5));
  $filename = $slug . '-' . $rand . '.' . $ext;
  $dest = $targetDir . '/' . $filename;

  if (!move_uploaded_file($tmp, $dest)) {
    continue;
  }

  // try make readable
  @chmod($dest, 0664);

  $saved[] = '/assets/uploads/' . $category . '/' . $filename;
}

if (!$saved) {
  json_response(400, ['ok'=>false, 'error'=>'upload failed']);
}

json_response(200, ['ok'=>true, 'path'=>$saved[0], 'paths'=>$saved]);

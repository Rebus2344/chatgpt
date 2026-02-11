<?php
require __DIR__ . '/_config.php';
require_admin();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  json_response(405, ['ok'=>false,'error'=>'Method not allowed']);
}

$csvPath = DATA_DIR . '/products.csv';
if (!file_exists($csvPath)) {
  json_response(404, ['ok'=>false,'error'=>'products.csv not found']);
}

$fh = fopen($csvPath, 'rb');
if (!$fh) json_response(500, ['ok'=>false,'error'=>'cannot read products.csv']);

$header = fgetcsv($fh);
if (!$header) {
  fclose($fh);
  json_response(400, ['ok'=>false,'error'=>'empty CSV']);
}

$prods = [];
while (($row = fgetcsv($fh)) !== false) {
  $r = [];
  for ($i=0; $i<count($header); $i++) {
    $k = (string)$header[$i];
    $r[$k] = $row[$i] ?? '';
  }

  $p = [
    'id' => trim((string)($r['id'] ?? '')),
    'category' => sanitize_category((string)($r['category'] ?? 'kmu')),
    'brand' => trim((string)($r['brand'] ?? '')),
    'model' => trim((string)($r['model'] ?? '')),
    'year' => trim((string)($r['year'] ?? '')),
    'status' => trim((string)($r['status'] ?? 'В наличии')) ?: 'В наличии',
    'price' => trim((string)($r['price'] ?? 'Цена по запросу')) ?: 'Цена по запросу',
    'city' => trim((string)($r['city'] ?? '')),
    'short' => trim((string)($r['short'] ?? '')),
    'description' => trim((string)($r['description'] ?? '')),
    'specs' => trim((string)($r['specs'] ?? '')),
    'cta' => trim((string)($r['cta'] ?? '')) ?: 'Узнать цену',
    'cargo' => trim((string)($r['cargo'] ?? '')),
    'outreach' => trim((string)($r['outreach'] ?? '')),
    'sections' => trim((string)($r['sections'] ?? '')),
    'control' => trim((string)($r['control'] ?? '')),
  ];

  $title = trim((string)($r['title'] ?? ''));
  if ($title === '') {
    $title = trim(($p['brand'] . ' ' . $p['model']));
    if ($title === '') $title = $p['id'] ?: 'Товар';
  }
  $p['title'] = $title;

  $slug = trim((string)($r['slug'] ?? ''));
  $p['slug'] = $slug ?: slugify_ru($title);

  $image = trim((string)($r['image'] ?? ''));
  if ($image === '') $image = '/assets/img/placeholder.svg';

  $imgs = [];
  if (!empty($r['images'])) {
    $imgs = normalize_images((string)$r['images']);
  }
  // support image2..image10 columns
  if (!$imgs) {
    $imgs = [$image];
    for ($i=2; $i<=10; $i++) {
      foreach (["image{$i}","img{$i}","photo{$i}"] as $k) {
        if (!empty($r[$k])) {
          $imgs[] = trim((string)$r[$k]);
          break;
        }
      }
    }
    $imgs = normalize_images($imgs);
  }

  $p['images'] = $imgs;
  $p['image'] = $imgs[0] ?? $image;

  if ($p['id'] === '') {
    $p['id'] = 'p' . (string)round(microtime(true) * 1000);
  }

  $prods[] = $p;
}
fclose($fh);

write_json_atomic(PRODUCTS_JSON, $prods);

json_response(200, ['ok'=>true, 'count'=>count($prods), 'note'=>'products.json перезаписан. Пересборка статических страниц не выполняется на PHP-версии API.']);

<?php
require __DIR__ . '/_config.php';
require_admin();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$products = read_json_file(PRODUCTS_JSON, []);
if (!is_array($products)) $products = [];

if ($method === 'GET') {
  json_response(200, $products);
}

if ($method !== 'POST') {
  json_response(405, ['ok'=>false, 'error'=>'Method not allowed']);
}

$payload = get_json_body();
$action = (string)($payload['action'] ?? '');

function normalize_product(array $p): array {
  $p['title'] = trim((string)($p['title'] ?? ''));
  $p['slug'] = trim((string)($p['slug'] ?? ''));
  if ($p['slug'] === '' && $p['title'] !== '') {
    $p['slug'] = slugify_ru($p['title']);
  }
  if ($p['slug'] === '') $p['slug'] = 'item';

  $p['category'] = sanitize_category((string)($p['category'] ?? 'kmu'));
  $p['status'] = trim((string)($p['status'] ?? 'В наличии')) ?: 'В наличии';
  $p['brand'] = trim((string)($p['brand'] ?? ''));
  $p['model'] = trim((string)($p['model'] ?? ''));
  $p['year'] = trim((string)($p['year'] ?? ''));
  $p['price'] = trim((string)($p['price'] ?? 'Цена по запросу')) ?: 'Цена по запросу';
  $p['city'] = trim((string)($p['city'] ?? ''));
  $p['cta'] = trim((string)($p['cta'] ?? 'Узнать цену')) ?: 'Узнать цену';
  $p['short'] = trim((string)($p['short'] ?? ''));
  $p['description'] = trim((string)($p['description'] ?? ''));
  $p['specs'] = trim((string)($p['specs'] ?? ''));

  $p['featured'] = !empty($p['featured']);
  $p['featured_rank'] = trim((string)($p['featured_rank'] ?? ''));

  $imgs = normalize_images($p['images'] ?? []);
  $p['images'] = $imgs;
  // Backward compatibility: keep "image" as first (some pages use it)
  $p['image'] = $imgs[0] ?? ($p['image'] ?? '');

  return $p;
}

if ($action === 'create') {
  $p = $payload['product'] ?? [];
  if (!is_array($p)) $p = [];
  $p = normalize_product($p);
  $p['id'] = 'p' . (string)round(microtime(true) * 1000);
  $products[] = $p;
  write_json_atomic(PRODUCTS_JSON, $products);
  json_response(200, ['ok'=>true, 'id'=>$p['id']]);
}

if ($action === 'update') {
  $p = $payload['product'] ?? [];
  if (!is_array($p)) $p = [];
  $id = (string)($p['id'] ?? '');
  if ($id === '') json_response(400, ['ok'=>false,'error'=>'id required']);

  $found = false;
  for ($i=0; $i<count($products); $i++) {
    $cur = $products[$i];
    if (!is_array($cur)) continue;
    if ((string)($cur['id'] ?? '') === $id) {
      // keep some fields if not provided
      if (!array_key_exists('featured', $p)) $p['featured'] = $cur['featured'] ?? false;
      if (!array_key_exists('featured_rank', $p)) $p['featured_rank'] = $cur['featured_rank'] ?? '';
      if (!array_key_exists('cta', $p)) $p['cta'] = $cur['cta'] ?? 'Узнать цену';
      foreach (['cargo','outreach','sections','control'] as $k) {
        if (!array_key_exists($k, $p) && array_key_exists($k, $cur)) {
          $p[$k] = $cur[$k];
        }
      }
      $p['id'] = $id;
      $p = normalize_product($p);
      $products[$i] = $p;
      $found = true;
      break;
    }
  }
  if (!$found) json_response(404, ['ok'=>false,'error'=>'not found']);
  write_json_atomic(PRODUCTS_JSON, $products);
  json_response(200, ['ok'=>true]);
}

if ($action === 'delete') {
  $id = (string)($payload['id'] ?? '');
  if ($id === '') json_response(400, ['ok'=>false,'error'=>'id required']);
  $products = array_values(array_filter($products, fn($x) => is_array($x) ? (string)($x['id'] ?? '') !== $id : true));
  write_json_atomic(PRODUCTS_JSON, $products);
  json_response(200, ['ok'=>true]);
}

json_response(400, ['ok'=>false, 'error'=>'unknown action']);

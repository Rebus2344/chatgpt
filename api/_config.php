<?php
declare(strict_types=1);

// =====================
// CONFIG
// =====================
// Можно переопределить через переменные окружения ADMIN_USER / ADMIN_PASS.

define('ADMIN_USER', getenv('ADMIN_USER') ?: 'cryptocommunity28');
define('ADMIN_PASS', getenv('ADMIN_PASS') ?: 'ip6zVP2F2WF0fji8');

// =====================
// PATHS
// =====================
define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . '/data');
define('LEADS_DIR', BASE_DIR . '/leads');
define('PRODUCTS_JSON', DATA_DIR . '/products.json');
define('SETTINGS_JSON', DATA_DIR . '/settings.json');
define('LEADS_CSV', LEADS_DIR . '/leads.csv');
define('UPLOADS_DIR', BASE_DIR . '/assets/uploads');

// =====================
// HELPERS
// =====================
function json_response(int $code, $payload, array $headers = []): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  foreach ($headers as $k => $v) {
    header($k . ': ' . $v);
  }
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function text_response(int $code, string $text, string $ctype = 'text/plain; charset=utf-8'): void {
  http_response_code($code);
  header('Content-Type: ' . $ctype);
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  echo $text;
  exit;
}

function get_json_body(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function read_json_file(string $path, $default) {
  if (!file_exists($path)) return $default;
  $raw = file_get_contents($path);
  if ($raw === false) return $default;
  $data = json_decode($raw, true);
  return ($data === null || $data === false) ? $default : $data;
}

function write_json_atomic(string $path, $data): void {
  $dir = dirname($path);
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true)) {
      throw new RuntimeException('Cannot create dir: ' . $dir);
    }
  }
  $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    throw new RuntimeException('JSON encode failed');
  }
  if (file_put_contents($tmp, $json, LOCK_EX) === false) {
    throw new RuntimeException('Cannot write tmp file');
  }
  if (!rename($tmp, $path)) {
    @unlink($tmp);
    throw new RuntimeException('Cannot replace file: ' . $path);
  }
}

function require_admin(): void {
  // На shared-хостингах (PHP-FPM/CGI) PHP часто НЕ получает логин/пароль в PHP_AUTH_*
  // Поэтому пытаемся достать BasicAuth из разных мест.

  $user = '';
  $pass = '';

  // 1) Стандартно (Apache + mod_php)
  if (!empty($_SERVER['PHP_AUTH_USER']) || !empty($_SERVER['PHP_AUTH_PW'])) {
    $user = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $pass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
  }

  // 2) Authorization header (PHP-FPM/CGI) — если сервер передаёт заголовок в PHP
  if ($user === '' && $pass === '') {
    $auth = '';
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $k) {
      if (!empty($_SERVER[$k])) {
        $auth = (string)$_SERVER[$k];
        break;
      }
    }

    if ($auth === '' && function_exists('getallheaders')) {
      $h = getallheaders();
      foreach (['Authorization', 'authorization'] as $k) {
        if (isset($h[$k])) {
          $auth = (string)$h[$k];
          break;
        }
      }
    }

    $auth = trim($auth);
    if ($auth !== '' && stripos($auth, 'basic ') === 0) {
      $dec = base64_decode(substr($auth, 6));
      if ($dec !== false && strpos($dec, ':') !== false) {
        [$user, $pass] = explode(':', $dec, 2);
      }
    }
  }

  if ($user === ADMIN_USER && $pass === ADMIN_PASS) return;

  header('WWW-Authenticate: Basic realm="Admin"');
  json_response(401, ['ok' => false, 'error' => 'Unauthorized']);
}

function slugify_ru(string $s): string {
  $s = mb_strtolower($s, 'UTF-8');
  $s = str_replace('ё', 'е', $s);
  // replace non letters/digits with dashes
  $s = preg_replace('/[^a-z0-9а-я\s-]/u', '', $s);
  $s = trim($s);
  $s = preg_replace('/\s+/u', '-', $s);
  $s = preg_replace('/-+/u', '-', $s);
  $s = trim($s, '-');
  return $s ?: 'item';
}

function sanitize_category(string $cat): string {
  $cat = trim($cat);
  if ($cat === '') return 'kmu';
  $cat = mb_strtolower($cat, 'UTF-8');
  $cat = preg_replace('/[^a-z0-9_-]/', '', $cat);
  return $cat ?: 'kmu';
}

function normalize_images($images): array {
  $arr = [];
  if (is_array($images)) {
    $arr = $images;
  } else {
    $txt = (string)$images;
    $arr = preg_split('/\R+/', $txt) ?: [];
  }
  $out = [];
  $seen = [];
  foreach ($arr as $x) {
    $x = trim((string)$x);
    if ($x === '') continue;
    if (isset($seen[$x])) continue;
    $seen[$x] = true;
    $out[] = $x;
    if (count($out) >= 10) break;
  }
  return $out;
}

function ensure_leads_csv(): void {
  if (!is_dir(LEADS_DIR)) {
    @mkdir(LEADS_DIR, 0775, true);
  }
  if (!file_exists(LEADS_CSV)) {
    $header = "ts,ip,lead_type,page,referer,utm_json,fields_json\n";
    file_put_contents(LEADS_CSV, $header, LOCK_EX);
  }
}

function client_ip(): string {
  // If behind proxy, headers might exist; otherwise REMOTE_ADDR
  foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
    if (!empty($_SERVER[$k])) {
      $v = (string)$_SERVER[$k];
      // take first if list
      if (strpos($v, ',') !== false) $v = trim(explode(',', $v)[0]);
      return $v;
    }
  }
  return '';
}

function pretty_kv(array $a): string {
  $parts = [];
  foreach ($a as $k => $v) {
    if ($v === null || $v === '') continue;
    if (is_array($v) || is_object($v)) {
      $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $parts[] = $k . '=' . $v;
  }
  return implode(' | ', $parts);
}

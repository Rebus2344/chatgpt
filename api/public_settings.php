<?php
require __DIR__ . '/_config.php';

$s = read_json_file(SETTINGS_JSON, []);
if (!is_array($s)) $s = [];

json_response(200, [
  'theme_default' => $s['theme_default'] ?? 'blue',
  'logo_path' => $s['logo_path'] ?? '',
  'hero_bg_path' => $s['hero_bg_path'] ?? '',
]);

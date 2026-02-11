<?php
require __DIR__ . '/_config.php';
require_admin();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  json_response(405, ['ok'=>false,'error'=>'Method not allowed']);
}

// В оригинале пересборка делалась Python-скриптом (server.py), который генерирует статические HTML-страницы.
// На shared-хостинге Python-сервер обычно не работает. 
// Каталог в этом проекте обновляется через JS (он читает products.json). 
// Но новые товарные страницы /catalog/{cat}/{slug}/... не появятся автоматически.

json_response(200, [
  'ok' => true,
  'note' => 'Пересборка статических страниц отключена в PHP-API. Каталог обновляется из products.json, но для новых товаров нужно либо генерировать страницы локально (server.py), либо перевести карточку товара на один динамический шаблон в MODX.'
]);

<?php
require __DIR__ . '/_config.php';

$products = read_json_file(PRODUCTS_JSON, []);
if (!is_array($products)) $products = [];

json_response(200, $products);

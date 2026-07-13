<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = $query === '' ? 'public/' : 'public/index.php?' . $query;

header('Location: ' . $target);
exit;

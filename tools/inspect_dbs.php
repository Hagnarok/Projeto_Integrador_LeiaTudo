<?php
$files = [
  __DIR__ . '/../data/leiatudo.sqlite',
  __DIR__ . '/../public/data/leiatudo.sqlite',
  __DIR__ . '/../public/data/livros.sqlite',
];
foreach ($files as $f) {
  echo "\nDB: $f\n";
  if (!file_exists($f)) { echo "  (não existe)\n"; continue; }
  try {
    $pdo = new PDO('sqlite:' . $f);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    if (!$tables) { echo "  (sem tabelas)\n"; continue; }
    foreach ($tables as $t) {
      $c = $pdo->query("SELECT COUNT(*) AS c FROM $t")->fetch(PDO::FETCH_ASSOC);
      echo "  $t: " . ($c['c'] ?? 'n/a') . "\n";
    }
  } catch (Throwable $e) { echo "  erro: " . $e->getMessage() . "\n"; }
}

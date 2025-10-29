<?php
// tools/migrate.php
// Script simples para aplicar/inspecionar migrações idempotentes no DB
declare(strict_types=1);
require __DIR__ . '/../home/config.php';
require __DIR__ . '/../public/lib/db.php';

echo "Running DB migrations...\n";
try {
    $pdo1 = get_pdo(); // users and resets
    $pdo2 = db();      // livros and favoritos

    // Lista tabelas existentes
    $tables = $pdo1->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n\n";

    // Mostra colunas principais
    $show = function(PDO $pdo, $table){
        $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        if(!$cols) return;
        echo "Columns for $table:\n";
        foreach($cols as $c){
            echo " - {$c['name']} ({$c['type']})\n";
        }
        echo "\n";
    };

    foreach (['users','livros','user_favoritos','password_resets'] as $t) {
        $show($pdo1, $t);
    }

    echo "Migrations applied (idempotent).\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";

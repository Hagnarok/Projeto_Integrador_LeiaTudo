<?php
// tools/clear_public_leiatudo.php
// Backup + limpa user_favoritos e livros em public/data/leiatudo.sqlite
// Uso: php tools/clear_public_leiatudo.php

declare(strict_types=1);
$path = __DIR__ . '/../public/data/leiatudo.sqlite';
if (!file_exists($path)) {
    echo "Arquivo não encontrado: $path\n";
    exit(1);
}
$bak = $path . '.bak.' . date('Ymd_His');
if (!copy($path, $bak)) {
    echo "Falha ao criar backup em: $bak\n";
    exit(1);
}
echo "Backup criado: $bak\n";

try {
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas detectadas: " . implode(', ', $tables) . "\n";

    $before = [];
    foreach ($tables as $t) {
        $c = $pdo->query("SELECT COUNT(*) AS c FROM " . $t)->fetch(PDO::FETCH_ASSOC);
        $before[$t] = (int)($c['c'] ?? 0);
    }
    echo "Contagens antes:\n";
    foreach ($before as $t=>$c) echo "  $t: $c\n";

    $pdo->beginTransaction();

    if (in_array('user_favoritos', $tables, true)) {
        $d = $pdo->exec('DELETE FROM user_favoritos');
        echo "Apagados " . ($d ?: 0) . " registros de user_favoritos\n";
    }
    if (in_array('livros', $tables, true)) {
        $d = $pdo->exec('DELETE FROM livros');
        echo "Apagados " . ($d ?: 0) . " registros de livros\n";
        // reset sequence
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='livros'");
    }

    $pdo->commit();

    $after = [];
    foreach ($tables as $t) {
        $c = $pdo->query("SELECT COUNT(*) AS c FROM " . $t)->fetch(PDO::FETCH_ASSOC);
        $after[$t] = (int)($c['c'] ?? 0);
    }
    echo "Contagens depois:\n";
    foreach ($after as $t=>$c) echo "  $t: $c\n";

    echo "Limpeza concluída com sucesso. Backup em: $bak\n";
    exit(0);
} catch (Throwable $e) {
    try { if ($pdo && $pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $_) {}
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
// tools/clear_livros_db.php
// Faz backup do public/data/livros.sqlite (se existir) e apaga todas as linhas das tabelas dentro dele.
declare(strict_types=1);
$path = __DIR__ . '/../public/data/livros.sqlite';
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
    foreach ($tables as $t) {
        $cnt = $pdo->query("SELECT COUNT(*) AS c FROM " . $t)->fetch(PDO::FETCH_ASSOC);
        echo "Tabela $t: " . ($cnt['c'] ?? 0) . " registros\n";
    }
    // Apaga apenas tabela 'livros' se existir
    if (in_array('livros', $tables, true)) {
        $deleted = $pdo->exec('DELETE FROM livros');
        echo "Apagados " . ($deleted ?: 0) . " registros da tabela livros\n";
    } else {
        echo "Tabela 'livros' não existe neste arquivo de DB.\n";
    }
    echo "Operação concluída.\n";
} catch (Throwable $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
// tools/clear_db_data.php
// Uso:
// php tools/clear_db_data.php [--yes] [--backup]
// Sem --yes faz apenas um dry-run (mostra contagens e ações). --backup faz cópia do arquivo SQLite antes de apagar.

declare(strict_types=1);
require __DIR__ . '/../home/config.php';

function plural(int $n, string $s){ return $n === 1 ? "$n $s" : "$n {$s}s"; }

$opts = getopt('', ['yes', 'backup']);
$doIt = isset($opts['yes']);
$doBackup = isset($opts['backup']);

echo "LeiaTudo — Clear DB Data (safe tool)\n";
if (!$doIt) echo "Modo dry-run: nenhum dado será apagado. Use --yes para executar.\n";

$dbFile = DB_FILE;
if (!file_exists($dbFile)) {
    echo "Arquivo DB não encontrado em: $dbFile\n";
    exit(1);
}

if ($doBackup) {
    $bak = $dbFile . '.bak.' . date('Ymd_His');
    if (!copy($dbFile, $bak)) {
        echo "Falha ao criar backup em: $bak\n";
        exit(1);
    }
    echo "Backup criado: $bak\n";
}

$pdo = get_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Mostrar contagens antes
function countTable(PDO $pdo, string $table){
    $st = $pdo->query("SELECT COUNT(*) AS c FROM " . $table);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return (int)$r['c'];
}

$tablesToReport = ['users','livros','user_favoritos','password_resets'];
foreach ($tablesToReport as $t) {
    try { $c = countTable($pdo, $t); } catch (Throwable $e) { $c = -1; }
    echo sprintf("% -18s : %s\n", $t, $c >=0 ? plural($c, 'registro') : 'não existe');
}

if (!$doIt) {
    echo "\nDry-run completo. Use --yes --backup para executar e fazer backup.\n";
    exit(0);
}

// Execução real — apaga dados preservando esquema
try {
    $pdo->beginTransaction();

    // Apagar favoritos primeiro (depende de users e livros)
    if (tableExists($pdo, 'user_favoritos')) {
        $deleted = $pdo->exec('DELETE FROM user_favoritos');
        echo "Apagados " . plural($deleted ?: 0, 'favorito') . "\n";
    }

    // Apagar password_resets
    if (tableExists($pdo, 'password_resets')) {
        $deleted = $pdo->exec('DELETE FROM password_resets');
        echo "Apagados " . plural($deleted ?: 0, 'password_reset') . "\n";
    }

    // Apagar livros
    if (tableExists($pdo, 'livros')) {
        $deleted = $pdo->exec('DELETE FROM livros');
        echo "Apagados " . plural($deleted ?: 0, 'livro') . "\n";
    }

    // Apagar users
    if (tableExists($pdo, 'users')) {
        // Proteção: não apagar usuário com username = 'Admin' se existir e ambiente parecer produção
        $adminCount = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE username='Admin'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
        if ($adminCount > 0) {
            echo "Aviso: usuário 'Admin' existe. Mantendo todos os usuários apagando normalmente (você pode alterar o script se quiser preservar admin).\n";
        }
        $deleted = $pdo->exec('DELETE FROM users');
        echo "Apagados " . plural($deleted ?: 0, 'usuario') . "\n";
    }

    $pdo->commit();
    echo "\nLimpeza concluída com sucesso.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Erro durante a limpeza: " . $e->getMessage() . "\n";
    exit(1);
}

// função utilitária
function tableExists(PDO $pdo, string $table): bool{
    $st = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
}

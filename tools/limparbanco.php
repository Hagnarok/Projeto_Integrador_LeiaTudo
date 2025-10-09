<?php
try {
    // Conecta no banco SQLite
    $pdo = new PDO('sqlite:data/leiatudo.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pega todas as tabelas (ignorando tabelas internas do SQLite)
    $tables = $pdo->query("
        SELECT name 
        FROM sqlite_master 
        WHERE type='table' 
          AND name NOT LIKE 'sqlite_%'
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Apaga todos os dados
        $pdo->exec("DELETE FROM $table");
        // Reseta autoincrement
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$table'");
    }

    echo "Todos os dados foram apagados, mas a estrutura das tabelas foi mantida!";
} catch (PDOException $e) {
    echo "Erro ao limpar banco: " . $e->getMessage();
}

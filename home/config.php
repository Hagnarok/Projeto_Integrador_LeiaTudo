<?php
// home/config.php
declare(strict_types=1);

// Caminho para a pasta "data" (um nível acima de /home)
$dir = realpath(__DIR__ . '/../data');
if ($dir === false) {
  $dir = __DIR__ . '/../data';
  if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
  }
}
define('DB_FILE', rtrim($dir, '/\\') . '/leiatudo.sqlite');

/**
 * Retorna um PDO conectado ao SQLite e garante a tabela "users".
 */
function get_pdo(): PDO {
  // Verifica se a extensão pdo_sqlite está disponível
  if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('Extensão PDO_SQLite não está habilitada no PHP.');
  }

  $pdo = new PDO('sqlite:' . DB_FILE);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  // Boas práticas no SQLite
  $pdo->exec('PRAGMA foreign_keys = ON;');
  $pdo->exec('PRAGMA journal_mode = WAL;');

  // Cria tabela se não existir
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id             INTEGER PRIMARY KEY AUTOINCREMENT,
      username       TEXT NOT NULL UNIQUE,
      cpf            TEXT NOT NULL UNIQUE,   -- salvar só números (11)
      email          TEXT NOT NULL UNIQUE,
      password_hash  TEXT NOT NULL,
      created_at     TEXT NOT NULL DEFAULT (datetime('now'))
    );
    CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
    CREATE INDEX IF NOT EXISTS idx_users_email    ON users(email);
  ");

  return $pdo;
}

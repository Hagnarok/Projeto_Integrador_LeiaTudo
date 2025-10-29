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

  // ====== Migrações idempotentes para enriquecer o schema de users
  $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
  $have = array_column($cols, 'name');
  if (!in_array('last_login', $have, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login TEXT DEFAULT NULL");
  }
  if (!in_array('is_active', $have, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1");
  }
  if (!in_array('full_name', $have, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN full_name TEXT DEFAULT NULL");
  }
  if (!in_array('role', $have, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'");
  }

  // ====== Tabela para reset de senha (centralizada aqui)
  $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expiracao TEXT NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
  )");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets(user_id)");

  return $pdo;
}

<?php
// public/lib/db.php
function db() {
  $dbFile = __DIR__ . '/../data/leiatudo.sqlite';
  if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0775, true);
  }

  $pdo = new PDO('sqlite:' . $dbFile);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS livros (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      titulo TEXT NOT NULL,
      autor TEXT NOT NULL,
      genero TEXT NOT NULL,
      preco REAL NOT NULL,
      descricao TEXT,
      pdf_path TEXT NOT NULL,
      capa_path TEXT NOT NULL,
      criado_em DATETIME NOT NULL DEFAULT (datetime('now','localtime'))
    );
    CREATE INDEX IF NOT EXISTS idx_livros_genero ON livros(genero);
    CREATE INDEX IF NOT EXISTS idx_livros_criado_em ON livros(criado_em);
  ");

  return $pdo;
}

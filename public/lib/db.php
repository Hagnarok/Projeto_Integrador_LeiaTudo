<?php
declare(strict_types=1);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  // Caminho do arquivo SQLite
  $dbFile = __DIR__ . '/../data/leiatudo.sqlite';
  $dbDir  = dirname($dbFile);

  // Garante a pasta
  if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0775, true) && !is_dir($dbDir)) {
      throw new RuntimeException("Não foi possível criar a pasta do banco: {$dbDir}");
    }
  }

  // Abre conexão
  $pdo = new PDO('sqlite:' . $dbFile, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);

  // Boas práticas no SQLite
  $pdo->exec('PRAGMA foreign_keys = ON;');
  $pdo->exec('PRAGMA journal_mode = WAL;');
  $pdo->exec('PRAGMA synchronous = NORMAL;');

  // ====== Tabela LIVROS (como você já tinha) ======
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
      criado_em DATETIME NOT NULL DEFAULT (datetime('now','localtime')),
      criado_por_id INTEGER,
      criado_por_username TEXT
    );
    CREATE INDEX IF NOT EXISTS idx_livros_genero    ON livros(genero);
    CREATE INDEX IF NOT EXISTS idx_livros_criado_em ON livros(criado_em);
  ");

  // (Opcional) Se vier de versões antigas sem colunas de autoria, garante via ALTER
  $cols = $pdo->query("PRAGMA table_info(livros)")->fetchAll(PDO::FETCH_ASSOC);
  $have = array_column($cols, 'name');
  if (!in_array('criado_por_id', $have, true)) {
    $pdo->exec("ALTER TABLE livros ADD COLUMN criado_por_id INTEGER");
  }
  if (!in_array('criado_por_username', $have, true)) {
    $pdo->exec("ALTER TABLE livros ADD COLUMN criado_por_username TEXT");
  }

  // ====== Tabela de FAVORITOS ======
  // Armazena o relacionamento N:N entre usuários e livros favoritados.
  // PK composta impede duplicados; índices aceleram consultas por user e por livro.
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS user_favoritos (
      user_id   INTEGER NOT NULL,
      livro_id  INTEGER NOT NULL,
      criado_em DATETIME NOT NULL DEFAULT (datetime('now','localtime')),
      PRIMARY KEY (user_id, livro_id)
      -- Se quiser ativar FKs reais, descomente as linhas abaixo
      -- ,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
      -- ,FOREIGN KEY(livro_id) REFERENCES livros(id) ON DELETE CASCADE
    );
    CREATE INDEX IF NOT EXISTS idx_user_favoritos_user  ON user_favoritos(user_id);
    CREATE INDEX IF NOT EXISTS idx_user_favoritos_livro ON user_favoritos(livro_id);
  ");

  // Migrações pequenas e índices adicionais (idempotentes)
  $colsLivros = $pdo->query("PRAGMA table_info(livros)")->fetchAll(PDO::FETCH_ASSOC);
  $haveLivros = array_column($colsLivros, 'name');
  if (!in_array('slug', $haveLivros, true)) {
    $pdo->exec("ALTER TABLE livros ADD COLUMN slug TEXT DEFAULT NULL");
  }

  // Índices úteis para busca rápida (títulos/autores)
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_livros_titulo ON livros(titulo)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_livros_autor ON livros(autor)");

  return $pdo;
}

<?php
declare(strict_types=1);
// Endpoint JSON para pesquisa de livros (usado pelo typeahead)
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../lib/db.php';

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($q === '') {
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
try {
    $st = $pdo->prepare("SELECT id, titulo, autor, capa_path AS img, criado_por_username, publicado_por
        FROM livros
        WHERE titulo LIKE :q OR autor LIKE :q
        ORDER BY criado_em DESC
        LIMIT 20");
    $like = '%' . str_replace('%', '\\%', $q) . '%';
    $st->bindValue(':q', $like, PDO::PARAM_STR);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar livros'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Normaliza dados para o front
$out = [];
foreach ($rows as $r) {
    $out[] = [
        'id' => (int)($r['id'] ?? 0),
        'titulo' => (string)($r['titulo'] ?? ''),
        'autor' => (string)($r['autor'] ?? ''),
        'img' => (string)($r['img'] ?? ''),
        'publicado_por' => (string)($r['criado_por_username'] ?? $r['publicado_por'] ?? ''),
    ];
}

echo json_encode(['results' => $out], JSON_UNESCAPED_UNICODE);


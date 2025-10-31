<?php
// Arquivo de diagnóstico rápido — remova após uso
declare(strict_types=1);

function fmt_perms(int $perms): string {
  return sprintf('%o', $perms & 0x1FF);
}

header('Content-Type: text/plain; charset=utf-8');
echo "Diagnóstico PHP e permissões\n";
echo str_repeat('=', 60) . "\n";
echo "upload_max_filesize = " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size      = " . ini_get('post_max_size') . "\n";
echo "memory_limit       = " . ini_get('memory_limit') . "\n";
echo "file_uploads       = " . ini_get('file_uploads') . "\n";
echo "upload_tmp_dir     = " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . "\n";
echo "sys_get_temp_dir   = " . sys_get_temp_dir() . "\n";
echo "php_sapi_name      = " . php_sapi_name() . "\n";
echo "PHP version        = " . PHP_VERSION . "\n";

$base = __DIR__ . '/uploads';
$pdfs = $base . '/pdfs';
$capas = $base . '/capas';

echo str_repeat('-', 60) . "\n";
echo "Pasta de uploads e permissões\n";
foreach ([$base, $pdfs, $capas] as $p) {
  if (!file_exists($p)) {
    echo "$p : (não existe)\n";
    continue;
  }
  $perms = fileperms($p);
  $w = is_writable($p) ? 'writable' : 'not writable';
  echo sprintf("%s : %s, perms=%s\n", $p, $w, fmt_perms($perms));
}

echo str_repeat('-', 60) . "\n";
echo "Espaço em disco (pasta do projeto)\n";
if (function_exists('disk_free_space')) {
  $free = disk_free_space(__DIR__);
  echo "free bytes = " . ($free === false ? 'unknown' : $free) . "\n";
}

echo str_repeat('=', 60) . "\n";
echo "Fim do diagnóstico. Remova este arquivo quando finalizar.\n";

?>

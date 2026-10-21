<?php
declare(strict_types=1);
// Build validation only. Extract as www-data, without chmod or application installation.
umask(0022);
[$script, $archive, $destination] = $argv;
$zip = new ZipArchive();
if ($zip->open($archive) !== true || !mkdir($destination, 0755) || !$zip->extractTo($destination)) {
    throw new RuntimeException('ZIP extraction failed');
}
$zip->close();
$manifest = [];
$directories = 0;
$root = $destination . '/love-fortune-core';
if (array_values(array_diff(scandir($destination), ['.', '..'])) !== ['love-fortune-core']) {
    throw new RuntimeException('Unexpected ZIP root');
}
$check = static function (string $path, int $expected): void {
    if (is_link($path) || (fileperms($path) & 0777) !== $expected || !is_readable($path)
        || ($expected === 0755 && !is_executable($path))) { throw new RuntimeException('Unsafe extracted permissions'); }
};
$check($root, 0755);
$directories++;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
    if ($file->isDir()) { $check($file->getPathname(), 0755); $directories++; }
    else { $check($file->getPathname(), 0644); $manifest[substr($file->getPathname(), strlen($root) + 1)] = hash_file('sha256', $file->getPathname()); }
}
ksort($manifest);
echo json_encode(['result'=>'PASS','files'=>count($manifest),'directories'=>$directories,'user'=>posix_geteuid(),'chmodRequired'=>false,'manifest'=>$manifest], JSON_THROW_ON_ERROR);

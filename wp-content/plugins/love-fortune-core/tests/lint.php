<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__)));
$failed = false;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $status);
        $failed = $failed || $status !== 0;
    }
}
exit($failed ? 1 : 0);

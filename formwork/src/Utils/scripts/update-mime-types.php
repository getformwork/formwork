<?php

if (PHP_SAPI !== 'cli') {
    exit('This script can only be run from the command line');
}

$db = 'https://cdn.jsdelivr.net/gh/jshttp/mime-db@master/db.json';

$data = file_get_contents($db);

if ($data === false) {
    exit('Failed to fetch MIME types database' . PHP_EOL);
}

/** @var ?array<string, array{source: string, charset?: string, compressible?: bool, extensions?: list<string>, ...}> $json */
$json = json_decode($data, true);

if ($json === null) {
    exit('Failed to parse MIME types database' . PHP_EOL);
}

$extensions = [
    'image/jpeg'    => ['jpg', 'jpeg', 'jpe'],
    'image/tiff'    => ['tiff', 'tif'],
    'text/html'     => ['html', 'htm'],
    'text/markdown' => ['md', 'markdown'],
    'text/yaml'     => ['yaml', 'yml'],
];

foreach ($json as $mimeType => $data) {
    if (!isset($data['extensions'])) {
        continue;
    }
    if (isset($extensions[$mimeType])) {
        $extensions[$mimeType] += $data['extensions'];
    } else {
        $extensions[$mimeType] = $data['extensions'];
    }
}

ksort($extensions);

$mimeTypes = [];

foreach ($extensions as $mimeType => $exts) {
    foreach ($exts as $ext) {
        $mimeTypes[$ext] = $mimeType;
    }
}

/** @var list<string> */
$keys = array_keys($mimeTypes);

$maxlen = max(0, ...array_map(strlen(...), $keys));

$lines = [];

/** @var string $ext */
foreach ($mimeTypes as $ext => $mimeType) {
    $lines[] = sprintf("        '%s'%s => '%s',", $ext, str_repeat(' ', $maxlen - strlen($ext)), $mimeType);
}

$file = dirname(__DIR__) . '/MimeType.php';

$content = file_get_contents($file);

if ($content === false) {
    exit('Failed to read MimeType.php' . PHP_EOL);
}

$content = preg_replace([
    '/Last updated: \d{4}-\d{2}-\d{2}\n/',
    '/private const array MIME_TYPES = \[.*?\];/s',
], [
    sprintf("Last updated: %s\n", date('Y-m-d')),
    sprintf("private const array MIME_TYPES = [\n%s\n    ];", implode("\n", $lines)),
], $content);

file_put_contents($file, $content);

echo 'MIME types updated successfully' . PHP_EOL;

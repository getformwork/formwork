<?php

namespace Formwork\Utils;

use Formwork\Tests\Environment;
use Formwork\Tests\Utils\Fixtures\FileSystemFixture;

function extension_loaded(string $extension): bool
{
    return Environment::isExtensionEnabled($extension);
}

function getcwd(): string|false
{
    return FileSystemFixture::cwd();
}

function fileatime(string $filename): int|false
{
    return FileSystemFixture::fileatime($filename);
}

function filectime(string $filename): int|false
{
    return FileSystemFixture::filectime($filename);
}

function filemtime(string $filename): int|false
{
    return FileSystemFixture::filemtime($filename);
}

function touch(string $filename, ?int $time = null, ?int $atime = null): bool
{
    return FileSystemFixture::touch($filename, $time, $atime);
}

function fileperms(string $filename): int|false
{
    return FileSystemFixture::fileperms($filename);
}

function filesize(string $filename): int|false
{
    return FileSystemFixture::filesize($filename);
}

function unlink(string $filename): bool
{
    return FileSystemFixture::unlink($filename);
}

function rmdir(string $dirname): bool
{
    return FileSystemFixture::rmdir($dirname);
}

function copy(string $source, string $dest): bool
{
    return FileSystemFixture::copy($source, $dest);
}

function symlink(string $target, string $link): bool
{
    return FileSystemFixture::symlink($target, $link);
}

function readlink(string $path): string|false
{
    return FileSystemFixture::readlink($path);
}

function rename(string $from, string $to): bool
{
    return FileSystemFixture::rename($from, $to);
}

function file_get_contents(string $filename): string|false
{
    return FileSystemFixture::file_get_contents($filename);
}

function file_put_contents(string $filename, mixed $data, int $flags = 0): int|false
{
    return FileSystemFixture::file_put_contents($filename, $data, $flags);
}

function opendir(string $directory): mixed
{
    return FileSystemFixture::opendir($directory);
}

function fopen(string $filename, string $mode): mixed
{
    return FileSystemFixture::fopen($filename, $mode);
}

function mkdir(string $pathname, int $mode = 0o777, bool $recursive = false): bool
{
    return FileSystemFixture::mkdir($pathname, $mode, $recursive);
}

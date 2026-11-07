<?php

namespace Formwork\Utils;

use Formwork\Tests\Environment;
use Formwork\Tests\Unit\Utils\Fixtures\NativeFunctionsFixture;

function extension_loaded(string $extension): bool
{
    return Environment::isExtensionEnabled($extension);
}

function getcwd(): string|false
{
    return NativeFunctionsFixture::cwd();
}

function fileatime(string $filename): int|false
{
    return NativeFunctionsFixture::fileatime($filename);
}

function filectime(string $filename): int|false
{
    return NativeFunctionsFixture::filectime($filename);
}

function filemtime(string $filename): int|false
{
    return NativeFunctionsFixture::filemtime($filename);
}

function touch(string $filename, ?int $time = null, ?int $atime = null): bool
{
    return NativeFunctionsFixture::touch($filename, $time, $atime);
}

function fileperms(string $filename): int|false
{
    return NativeFunctionsFixture::fileperms($filename);
}

function filesize(string $filename): int|false
{
    return NativeFunctionsFixture::filesize($filename);
}

function unlink(string $filename): bool
{
    return NativeFunctionsFixture::unlink($filename);
}

function rmdir(string $dirname): bool
{
    return NativeFunctionsFixture::rmdir($dirname);
}

function copy(string $source, string $dest): bool
{
    return NativeFunctionsFixture::copy($source, $dest);
}

function symlink(string $target, string $link): bool
{
    return NativeFunctionsFixture::symlink($target, $link);
}

function readlink(string $path): string|false
{
    return NativeFunctionsFixture::readlink($path);
}

function rename(string $from, string $to): bool
{
    return NativeFunctionsFixture::rename($from, $to);
}

function file_get_contents(string $filename): string|false
{
    return NativeFunctionsFixture::file_get_contents($filename);
}

function file_put_contents(string $filename, mixed $data, int $flags = 0): int|false
{
    return NativeFunctionsFixture::file_put_contents($filename, $data, $flags);
}

function opendir(string $directory): mixed
{
    return NativeFunctionsFixture::opendir($directory);
}

function fopen(string $filename, string $mode): mixed
{
    return NativeFunctionsFixture::fopen($filename, $mode);
}

function mkdir(string $pathname, int $mode = 0o777, bool $recursive = false): bool
{
    return NativeFunctionsFixture::mkdir($pathname, $mode, $recursive);
}

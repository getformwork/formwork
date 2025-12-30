<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Path;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Path::class)]
final class PathTest extends TestCase
{
    public function testIsAbsolute(): void
    {
        // POSIX-style
        $this->assertTrue(Path::isAbsolute('/foo/bar'));
        $this->assertFalse(Path::isAbsolute('./foo/bar'));
        $this->assertFalse(Path::isAbsolute('../foo/bar'));

        // Windows-style
        $this->assertTrue(Path::isAbsolute('C:\foo\bar', '\\'));
        $this->assertFalse(Path::isAbsolute('.\foo\bar', '\\'));
        $this->assertFalse(Path::isAbsolute('..\foo\bar', '\\'));
    }

    public function testHasSeparators(): void
    {
        $this->assertTrue(Path::isSeparator('/'));
        $this->assertTrue(Path::isSeparator('\\'));
        $this->assertFalse(Path::isSeparator('invalid_separator'));
    }

    public function testIsRelative(): void
    {
        // POSIX-style
        $this->assertTrue(Path::isRelativeTo('/foo/bar/baz', '/foo/bar'));
        $this->assertFalse(Path::isRelativeTo('/foo/bar/baz', '/bar/foo'));

        // Windows-style
        $this->assertTrue(Path::isRelativeTo('C:\foo\bar\baz', 'C:\foo\bar', '\\'));
        $this->assertFalse(Path::isRelativeTo('D:\foo\bar\baz', 'D:\bar\foo', '\\'));
    }

    public function testIsRelativeToThrowsOnNonAbsolutePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$path must be an absolute path');
        Path::isRelativeTo('../foo/bar/baz', '/foo/bar');
    }

    public function testIsRelativeToThrowsOnNonAbsoluteBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$base must be an absolute path');
        Path::isRelativeTo('C:\foo\bar\baz', 'foo\bar', '\\');
    }

    public function testNormalize(): void
    {
        // POSIX-style
        $this->assertSame('fixtures/b/c.js', Path::normalize('./fixtures///b/../b/c.js'));
        $this->assertSame('/bar', Path::normalize('/foo/../../../bar'));
        $this->assertSame('a/b', Path::normalize('a//b//../b'));
        $this->assertSame('a/b/c', Path::normalize('a//b//./c'));
        $this->assertSame('a/b', Path::normalize('a//b//.'));
        $this->assertSame('/x/y/z', Path::normalize('/a/b/c/../../../x/y/z'));
        $this->assertSame('/foo/bar', Path::normalize('///..//./foo/.//bar'));
        $this->assertSame('bar/', Path::normalize('bar/foo../../'));
        $this->assertSame('bar', Path::normalize('bar/foo../..'));
        $this->assertSame('bar/baz', Path::normalize('bar/foo../../baz'));
        $this->assertSame('bar/foo../', Path::normalize('bar/foo../'));
        $this->assertSame('bar/foo..', Path::normalize('bar/foo..'));
        $this->assertSame('../../bar', Path::normalize('../foo../../../bar'));
        $this->assertSame('../../bar', Path::normalize('../.../.././.../../../bar'));
        $this->assertSame('../../../../../bar', Path::normalize('../../../foo/../../../bar'));
        $this->assertSame('../../../../../../', Path::normalize('../../../foo/../../../bar/../../'));
        $this->assertSame('../../', Path::normalize('../foobar/barfoo/foo/../../../bar/../../'));
        $this->assertSame('../../../../baz', Path::normalize('../.../../foobar/../../../bar/../../baz'));
        $this->assertSame('foo/bar/baz', Path::normalize('foo/bar\baz'));

        // Windows-style
        $this->assertSame('fixtures\b\c.js', Path::normalize('./fixtures///b/../b/c.js', '\\'));
        $this->assertSame('\bar', Path::normalize('/foo/../../../bar', '\\'));
        $this->assertSame('a\b', Path::normalize('a//b//../b', '\\'));
        $this->assertSame('a\b\c', Path::normalize('a//b//./c', '\\'));
        $this->assertSame('a\b', Path::normalize('a//b//.', '\\'));
        $this->assertSame('\x\y\z', Path::normalize('/a/b/c/../../../x/y/z', '\\'));
        $this->assertSame('C:', Path::normalize('C:', '\\'));
        $this->assertSame('C:..\abc', Path::normalize('C:..\abc', '\\'));
        $this->assertSame('C:..\..\def', Path::normalize('C:..\..\abc\..\def', '\\'));
        $this->assertSame('C:', Path::normalize('C:\.', '\\'));
        $this->assertSame('file:stream', Path::normalize('file:stream', '\\'));
        $this->assertSame('bar\\', Path::normalize('bar\foo..\..\\', '\\'));
        $this->assertSame('bar', Path::normalize('bar\foo..\..', '\\'));
        $this->assertSame('bar\baz', Path::normalize('bar\foo..\..\baz', '\\'));
        $this->assertSame('bar\foo..\\', Path::normalize('bar\foo..\\', '\\'));
        $this->assertSame('bar\foo..', Path::normalize('bar\foo..', '\\'));
        $this->assertSame('..\..\bar', Path::normalize('..\foo..\..\..\bar', '\\'));
        $this->assertSame('..\..\bar', Path::normalize('..\...\..\.\...\..\..\bar', '\\'));
        $this->assertSame('..\..\..\..\..\bar', Path::normalize('../../../foo/../../../bar', '\\'));
        $this->assertSame('..\..\..\..\..\..\\', Path::normalize('../../../foo/../../../bar/../../', '\\'));
        $this->assertSame('..\..\\', Path::normalize('../foobar/barfoo/foo/../../../bar/../../', '\\'));
        $this->assertSame('..\..\..\..\baz', Path::normalize('../.../../foobar/../../../bar/../../baz', '\\'));
        $this->assertSame('foo\bar\baz', Path::normalize('foo/bar\baz', '\\'));
    }

    public function testNormalizeThrowsOnInvalidSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$separator must be a valid directory separator');
        Path::normalize('some/path', 'invalid_separator');
    }

    public function testSegments(): void
    {
        // POSIX-style
        $this->assertSame(['', 'var', 'www', 'html', 'index.php'], Path::split('/var/www/html/index.php'));
        $this->assertSame(['home', 'user', 'documents', 'file.txt'], Path::split('home/user/documents/file.txt'));

        // Windows-style
        $this->assertSame(['C:', 'Program Files', 'App', 'app.exe'], Path::split('C:\Program Files\App\app.exe', '\\'));
        $this->assertSame(['D:', 'Data', 'Projects', 'project.docx'], Path::split('D:\Data\Projects\project.docx', '\\'));
    }

    public function testJoin(): void
    {
        // POSIX-style
        $this->assertSame('/var/www/html/index.php', Path::join(['/var', 'www', 'html', 'index.php']));
        $this->assertSame('home/user/documents/file.txt', Path::join(['home', 'user', 'documents', 'file.txt']));

        // Windows-style
        $this->assertSame('C:\Program Files\App\app.exe', Path::join(['C:', 'Program Files', 'App', 'app.exe'], '\\'));
        $this->assertSame('D:\Data\Projects\project.docx', Path::join(['D:', 'Data', 'Projects', 'project.docx'], '\\'));
    }

    public function testJoinThrowsOnInvalidSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$separator must be a valid directory separator');
        Path::join(['some', 'path'], 'invalid_separator');
    }

    public function testResolve(): void
    {
        // POSIX-style
        $this->assertSame('/var/file/', Path::resolve('../file/', '/var/lib'));
        $this->assertSame('/file/', Path::resolve('/../file/', '/var/lib'));
        $this->assertSame('', Path::resolve('../../..', 'a/b/c'));
        $this->assertSame('/absolute/', Path::resolve('/absolute/', './some/dir'));
        $this->assertSame('/foo/tmp.3/cycles/root.js', Path::resolve('../tmp.3/cycles/root.js', '/foo/tmp.3/'));

        // Windows-style
        $this->assertSame('c:\blah\a', Path::resolve('c:../a', 'c:/blah\blah', '\\'));
        $this->assertSame('d:\e.exe', Path::resolve('\e.exe', 'd:\a/b\c/d', '\\'));
        $this->assertSame('c:\some\file', Path::resolve('c:/some/file', 'c:/ignore', '\\'));
        $this->assertSame('d:\ignore\some\dir\\', Path::resolve('d:some/dir//', 'd:/ignore', '\\'));
        $this->assertSame('c:\\', Path::resolve('//', 'c:/', '\\'));
        $this->assertSame('c:\dir', Path::resolve('//dir', 'c:/', '\\'));
        $this->assertSame('c:\some\dir', Path::resolve('///some//dir', 'c:/', '\\'));
        $this->assertSame('C:\foo\tmp.3\cycles\root.js', Path::resolve('..\tmp.3\cycles\root.js', 'C:\foo\tmp.3\\', '\\'));
    }

    public function testResolveThrowsOnInvalidSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$separator must be a valid directory separator');
        Path::resolve('some/path', 'some/base', 'invalid_separator');
    }

    public function testRelativeTo(): void
    {
        // POSIX-style
        $this->assertSame('html/index.php', Path::makeRelative('/var/www/html/index.php', '/var/www'));
        $this->assertSame('../documents/file.txt', Path::makeRelative('/home/user/documents/file.txt', '/home/user/downloads'));
        $this->assertSame('../../../etc/config.yaml', Path::makeRelative('/etc/config.yaml', '/var/www/html/'));

        // Windows-style
        $this->assertSame('App\app.exe', Path::makeRelative('C:\Program Files\App\app.exe', 'C:\Program Files', '\\'));
        $this->assertSame('..\Projects\project.docx', Path::makeRelative('D:\Data\Projects\project.docx', 'D:\Data\Downloads', '\\'));
    }

    public function testMakeRelativeThrowsOnNonAbsolutePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$path must be an absolute path');
        Path::makeRelative('relative/path', '/absolute/base');
    }

    public function testMakeRelativeThrowsOnNonAbsoluteBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$base must be an absolute path');
        Path::makeRelative('/absolute/path', 'relative/base');
    }

    public function testMakeRelativeThrowsOnInvalidSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$separator must be a valid directory separator');
        Path::makeRelative('/some/path', '/some/base', 'invalid_separator');
    }

    public function testMakeRelativeThrowsOnIncompatibleDrives(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$path and $base must have a compatible drive letter');
        Path::makeRelative('C:\folder\file.txt', 'D:\folder', '\\');
    }
}

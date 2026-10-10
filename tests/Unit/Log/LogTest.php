<?php

namespace Formwork\Tests\Unit\Log;

use Formwork\Log\Log;
use Formwork\Log\Registry;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(Log::class)]
final class LogTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->file = FileSystem::joinPaths(TESTS_TMP_PATH, 'log.json');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    public function testLoadingTheClassTriggersADeprecation(): void
    {
        $messages = $this->captureDeprecations(static function (): void {
            class_exists(Log::class);
        });

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('is deprecated since Formwork 2.3.0', $messages[0]);
        $this->assertStringContainsString('Logger', $messages[0]);
    }

    #[RunInSeparateProcess]
    public function testLogIsARegistry(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));

        $this->assertInstanceOf(Registry::class, new Log($this->file));
    }

    #[RunInSeparateProcess]
    public function testMessagesAreStoredByTimestamp(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));
        $log = new Log($this->file);

        $key = $log->log('First message');

        $this->assertMatchesRegularExpression('/^\d+\.\d{6}$/', $key);
        $this->assertSame('First message', $log->get($key));
    }

    #[RunInSeparateProcess]
    public function testMessagesLoggedAtDifferentTimesHaveDifferentKeys(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));
        $log = new Log($this->file);

        $first = $log->log('one');
        usleep(10);
        $second = $log->log('two');

        $this->assertNotSame($first, $second);
        $this->assertCount(2, $log->toArray());
    }

    #[RunInSeparateProcess]
    public function testOnlyTheLatestMessagesWithinTheLimitAreSaved(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));
        $log = new Log($this->file, 3);

        foreach (['one', 'two', 'three', 'four', 'five'] as $message) {
            $log->log($message);
            usleep(10);
        }
        $log->save();

        $saved = array_values(json_decode(FileSystem::read($this->file), true));
        $this->assertSame(['three', 'four', 'five'], $saved);
    }

    #[RunInSeparateProcess]
    public function testMessagesWithinTheLimitAreAllSaved(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));
        $log = new Log($this->file, 5);

        $log->log('one');
        usleep(10);
        $log->log('two');
        $log->save();

        $this->assertCount(2, json_decode(FileSystem::read($this->file), true));
    }

    #[RunInSeparateProcess]
    public function testDefaultLimitIs128Messages(): void
    {
        $this->captureDeprecations(static fn(): bool => class_exists(Log::class));
        $log = new Log($this->file);

        for ($i = 0; $i < 130; $i++) {
            $log->set((string) (1000 + $i), "message {$i}");
        }
        $log->save();

        $saved = json_decode(FileSystem::read($this->file), true);
        $this->assertCount(128, $saved);
        $this->assertSame('message 129', end($saved));
        $this->assertSame('message 2', reset($saved));
    }
}

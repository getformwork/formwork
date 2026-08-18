<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Log\Formatter\FormatterInterface;
use Formwork\Log\Formatter\JsonFormatter;
use Formwork\Log\Formatter\TextFormatter;
use Formwork\Log\Handler\FileHandler;
use Formwork\Log\Handler\HandlerInterface;
use Formwork\Log\Handler\StderrHandler;
use Formwork\Log\Logger;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use InvalidArgumentException;

/**
 * @since 2.3.0
 */
final class LoggerServiceLoader implements ServiceLoaderInterface
{
    public function __construct(
        private Container $container,
        private Config $config
    ) {}

    public function load(Container $container): Logger
    {
        $logger = $container->build(Logger::class);
        foreach ($this->config->getArray('system.logs.handlers', []) as $handlerConfig) {
            $logger->addHandler($this->buildHandler($handlerConfig));
        }

        return $logger;
    }

    /**
     * @param array{type?: string, path?: string, level?: string, formatter?: string} $config
     */
    private function buildHandler(array $config): HandlerInterface
    {
        $config['type'] ??= null;

        if (isset($config['formatter'])) {
            $config['formatter'] = $this->getFormatter($config['formatter']);
        }

        return match ($config['type']) {
            'file'   => $this->container->build(FileHandler::class, $config),
            'stderr' => $this->container->build(StderrHandler::class, $config),
            default  => throw new InvalidArgumentException(sprintf('Unknown log handler type: %s', $config['type']))
        };
    }

    private function getFormatter(string $formatter): FormatterInterface
    {
        return match ($formatter) {
            'json'  => new JsonFormatter(),
            'text'  => new TextFormatter(),
            default => throw new InvalidArgumentException(sprintf('Unknown log formatter: %s', $formatter))
        };
    }
}

<?php

namespace Formwork\Commands;

use DateTimeImmutable;
use Formwork\Cms\App;
use Formwork\Utils\Str;
use League\CLImate\CLImate;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

final class ServeCommand implements CommandInterface
{
    /**
     * Host to bind the server to
     */
    private string $host = '127.0.0.1';

    /**
     * Port to bind the server to
     */
    private int $port = 8000;

    /**
     * Number of retry attempts left to restart the server on the next port
     */
    private int $retryAttempts = 10;

    /**
     * Current request data for the server process
     *
     * @var array<mixed>
     */
    private array $requestData;

    /**
     * PHP process
     */
    private Process $process;

    /**
     * CLImate instance
     */
    private CLImate $climate;

    /**
     * Server start time
     */
    private float $startTime;

    public function __construct()
    {
        $this->climate = new CLImate();

        // Fix clear command for terminals that don't clear the scrollback buffer with the default clear command
        $this->climate->style->addCommand('clear', "\033[H\033[2J\033[3J");
    }

    public function __invoke(?array $argv = null): never
    {
        $argv ??= $_SERVER['argv'] ?? [];

        $this->climate->description(sprintf('<bold>Formwork <cyan>%s</cyan></bold> Development Server', App::VERSION));

        $this->climate->arguments->add([
            'host' => [
                'longPrefix'   => 'host',
                'description'  => 'Host to bind the server to',
                'defaultValue' => $this->host,
            ],
            'port' => [
                'longPrefix'   => 'port',
                'description'  => 'Port to bind the server to',
                'defaultValue' => $this->port,
                'castTo'       => 'int',
            ],
            'retry' => [
                'longPrefix'   => 'retry',
                'description'  => 'Number of retry attempts to bind the server to the next port if the specified one is not available',
                'defaultValue' => $this->retryAttempts,
                'castTo'       => 'int',
            ],
            'help' => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Show this help screen',
                'noValue'     => true,
            ],
        ]);

        $this->climate->arguments->parse();

        if ($this->climate->arguments->get('help')) {
            $this->climate->usage($argv);
            exit(0);
        }

        /** @var string */
        $host = $this->climate->arguments->get('host');

        /** @var int */
        $port = $this->climate->arguments->get('port');

        /** @var int */
        $retryAttempts = $this->climate->arguments->get('retry');

        [$this->host, $this->port, $this->retryAttempts] = [$host, $port, $retryAttempts];

        $this->start();
        exit(0);
    }

    /**
     * Start the server
     */
    private function start(): void
    {
        $this->startTime = microtime(true);

        $php = (new PhpExecutableFinder())->find();

        $this->process = new Process([
            $php,
            '-S',
            "{$this->formatHost($this->host)}:{$this->port}",
            'formwork/server.php',
        ], dirname(__DIR__, 3), null, null, 0);

        $this->process->run(function ($type, $buffer): void {
            $this->handleOutput($type, explode("\n", $buffer));
        });
    }

    /**
     * Handle server output
     *
     * @param 'err'|'out'  $type
     * @param list<string> $lines
     */
    private function handleOutput(string $type, array $lines): void
    {
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^\[([^[\]]+)\] (.+)$/', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
                $this->outputRawLine($type, $line);
                continue;
            }

            [, $date, $message] = $matches;

            $date = (new DateTimeImmutable($date));

            switch (true) {
                case Str::contains($line, 'Development Server ('):
                    $this->climate->clear();
                    $this->climate->br();
                    $this->climate->out(sprintf('<bold>Formwork <cyan>%s</cyan></bold> <dark_gray>Server ready in %s</dark_gray>', App::VERSION, $this->formatTime(microtime(true) - $this->startTime)));
                    $this->climate->br();
                    $this->climate->out(sprintf('PHP runtime <bold>%s</bold>', preg_replace('/^PHP (\d+\.\d+\.\d+[^ ]*) Development Server.+/', '$1', $message)));
                    $this->climate->br();
                    $this->climate->out(sprintf('➜ Listening on <cyan>http://%s:<bold>%s</bold>/</cyan>', $this->formatHost($this->host), $this->port));
                    $this->climate->br();
                    $this->climate->out('<dark_gray>Press <bold>CTRL+C</bold> to stop</dark_gray>');
                    $this->climate->br();
                    break;

                case Str::contains($line, 'Accepted'):
                    $acceptedTime = microtime(true);

                    [, $requestPort, $requestInfo] = $this->splitMessage($message);
                    $requestPort ??= '';

                    $this->requestData[$requestPort] = ['time' => $acceptedTime];

                    break;

                case Str::contains($line, 'Closing'):
                    $closingTime = microtime(true);

                    [, $requestPort, $requestInfo] = $this->splitMessage($message);
                    $requestPort ??= '';

                    if (!preg_match(
                        '/^(?:\[(?<status>\d{3})\]: (?<method>[A-Z]+) (?<uri>[^ ]+)(?: -(?<description> .+))?|(?<message>.+))/',
                        $this->requestData[$requestPort]['info'],
                        $info,
                        PREG_UNMATCHED_AS_NULL
                    )) {
                        throw new UnexpectedValueException('Unexpected PHP Development Server message format');
                    }

                    $this->climate->out(sprintf(
                        '<light_gray>%s</light_gray> %s <dark_gray>~%s</dark_gray>',
                        $date->format('Y-m-d H:i:s'),
                        $info['method']
                            ? sprintf('%s <bold>%s</bold> %s%s', $this->colorStatus((int) $info['status']), $info['method'], $info['uri'], $info['description'])
                            : $info['message'],
                        $this->formatTime($closingTime - $this->requestData[$requestPort]['time'])
                    ));

                    break;

                case Str::contains($line, 'Failed to listen on'):
                    $this->process->stop(0);

                    if ($this->retryAttempts-- > 0) {
                        $this->port++;
                        $this->start();
                        break;
                    }

                    $this->climate->clear();
                    $this->climate->to('error')->out(sprintf('<bold>Formwork <cyan>%s</cyan></bold> <dark_gray>Server</dark_gray> <red>failed to listen on port <bold>%d</bold></red>', App::VERSION, $this->port));
                    $this->climate->br();
                    $this->climate->out('<dark_gray>Press <bold>CTRL+C</bold> to quit</dark_gray>');
                    $this->climate->br();

                    $input = $this->climate->input('➜ Enter another port:');
                    $input->accept(fn(string $response) => ctype_digit($response));

                    $this->port = (int) $input->prompt();

                    $this->climate->clear();

                    $this->start();

                    exit(1);

                default:
                    if (($data = $this->splitMessage($message)) !== []) {
                        [, $requestPort, $requestInfo] = $data;
                        $requestPort ??= '';
                        $this->requestData[$requestPort]['info'] = $requestInfo;
                    } else {
                        // Unknown message format
                        $this->outputRawLine($type, $line);
                    }
                    break;
            }
        }
    }

    /**
     * Split request message into parts
     *
     * @return list<?string>
     */
    private function splitMessage(string $message): array
    {
        preg_match('/^(.+):(\d+) (.+)$/', $message, $matches, PREG_UNMATCHED_AS_NULL);
        array_shift($matches);
        return $matches;
    }

    /**
     * Colorize status code
     */
    private function colorStatus(int $status): string
    {
        if ($status <= 299) {
            return "<blue>{$status}</blue>";
        }
        if ($status <= 399) {
            return "<green>{$status}</green>";
        }
        if ($status <= 499) {
            return "<yellow>{$status}</yellow>";
        }
        if ($status <= 599) {
            return "<red>{$status}<red>";
        }

        throw new UnexpectedValueException(sprintf('Unexpected status code %d', $status));
    }

    /**
     * Format host for display and binding
     */
    private function formatHost(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return "[{$host}]";
        }
        return $host;
    }

    /**
     * Format time interval
     */
    private function formatTime(float $dt): string
    {
        if ($dt > 60) {
            $m = floor($dt / 60); // minutes
            $s = round($dt % 60); // seconds
            return "{$m} m {$s} s";
        }

        if ($dt > 1) {
            return round($dt, 1) . ' s'; // seconds
        }

        if ($dt > 1e-3) {
            return round($dt * 1e3) . ' ms'; // milliseconds
        }

        return round($dt * 1e6) . ' μs'; // microseconds
    }

    /**
     * Output a raw line without processing
     *
     * @param 'err'|'out' $type
     */
    private function outputRawLine(string $type, string $line): void
    {
        switch ($type) {
            case 'out':
                $this->climate->to('out')->out($line);
                break;

            case 'err':
                $this->climate->to('error')->error($line);
                break;

            default:
                throw new UnexpectedValueException(sprintf('Unexpected output type "%s"', $type));
        }
    }
}

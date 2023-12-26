<?php

namespace Formwork\Commands;

use DateTimeImmutable;
use Formwork\App;
use Formwork\Utils\Str;
use League\CLImate\CLImate;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class ServeCommand
{
    protected string $host;

    protected int $port;

    /**
     * @var array<mixed>
     */
    protected array $requestData;

    protected Process $process;

    protected CLImate $climate;

    public function __construct(string $host = '127.0.0.1', int $port = 8000)
    {
        $this->host = $host;
        $this->port = $port;
        $this->climate = new CLImate();
    }

    public function start(): void
    {
        $php = (new PhpExecutableFinder())->find();

        $this->process = new Process([
            $php,
            '-S',
            $this->host . ':' . $this->port,
            'formwork/server.php',
        ], dirname(__DIR__, 3), null, null, 0);

        $this->process->run(function ($type, $buffer): void {
            $this->handleOutput(explode("\n", $buffer));
        });
    }

    /**
     * @param list<string> $lines
     */
    protected function handleOutput(array $lines): void
    {
        foreach ($lines as $line) {
            if (!preg_match('/^\[(.+)\] (.+)$/', $line, $matches, PREG_UNMATCHED_AS_NULL)) {
                continue;
            }

            [, $date, $message] = $matches;

            if (!isset($date, $message)) {
                continue;
            }

            $date = (new DateTimeImmutable($date));

            switch (true) {
                case Str::contains($line, 'Development Server ('):
                    $this->climate->out(sprintf('Formwork <bold>%s</bold> Server running at <dark_gray>http://%s:%d</dark_gray>', App::VERSION, $this->host, $this->port));
                    $this->climate->out('Press <bold>CTRL+C</bold> to stop');
                    $this->climate->br();
                    break;

                case Str::contains($line, 'Accepted'):
                    $acceptedTime = microtime(true);

                    [, $requestPort, $requestInfo] = $this->splitMessage($message);

                    $this->requestData[$requestPort] = ['time' => $acceptedTime];

                    break;

                case Str::contains($line, 'Closing'):
                    $closingTime = microtime(true);

                    [, $requestPort, $requestInfo] = $this->splitMessage($message);

                    preg_match(
                        '/^(?:\[(?<status>\d{3})\]: (?<method>[A-Z]+) (?<uri>[^ ]+)(?: -(?<description> .+))?|(?<message>.+))/',
                        $this->requestData[$requestPort]['info'],
                        $info,
                        PREG_UNMATCHED_AS_NULL
                    );

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

                    $this->climate->to('error')->out(sprintf('Formwork <bold>%s</bold> Server <red>failed to listen on port <bold>%d</bold></red>', App::VERSION, $this->port));
                    $this->climate->br();

                    $input = $this->climate->input('Enter another port:');
                    $input->accept(fn ($response) => ctype_digit($response));

                    $this->port = (int) $input->prompt();

                    $this->climate->clear();

                    $this->start();

                    exit(1);

                default:
                    [, $requestPort, $requestInfo] = $this->splitMessage($message);
                    $this->requestData[$requestPort]['info'] = $requestInfo;
                    break;
            }
        }
    }

    /**
     * @return list<?string>
     */
    protected function splitMessage(string $message): array
    {
        preg_match('/^([0-9.]+):(\d+) (.+)$/', $message, $matches, PREG_UNMATCHED_AS_NULL);
        array_shift($matches);
        return $matches;
    }

    protected function colorStatus(int $status): string
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

    protected function formatTime(float $dt): string
    {
        if ($dt > 60) {
            $m = floor($dt / 60); // minutes
            $s = round($dt % 60); // seconds
            return $m . ' m ' . $s . ' s';
        }

        if ($dt > 1) {
            return round($dt, 1) . ' s'; // seconds
        }

        if ($dt > 1e-3) {
            return round($dt * 1e3) . ' ms'; // milliseconds
        }

        return round($dt * 1e6) . ' μs'; // microseconds
    }
}

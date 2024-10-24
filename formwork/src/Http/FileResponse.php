<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Utils\FileSystem;
use RuntimeException;

class FileResponse extends Response
{
    protected const CHUNK_SIZE = 512 * 1024;

    protected int $fileSize;

    protected int $offset = 0;

    protected int $length;

    /**
     * @inheritdoc
     */
    public function __construct(protected string $path, ResponseStatus $responseStatus = ResponseStatus::OK, array $headers = [], bool $download = false)
    {
        $this->fileSize = FileSystem::fileSize($path);

        $headers += [
            'Content-Type'        => FileSystem::mimeType($path),
            'Content-Disposition' => $download ? Header::make(['attachment', 'filename' => basename($path)]) : 'inline',
            'Content-Length'      => (string) $this->fileSize,
        ];

        parent::__construct('', $responseStatus, $headers);
    }

    /**
     * @inheritdoc
     */
    public function send(): void
    {
        parent::cleanOutputBuffers();

        $this->sendHeaders();

        $length = $this->length ?? $this->fileSize;

        if ($length === 0) {
            return;
        }

        $file = fopen($this->path, 'r');
        $output = fopen('php://output', 'w');

        if ($output === false) {
            throw new RuntimeException('Unable to open output stream');
        }

        if ($file === false) {
            throw new RuntimeException('Unable to open file: ' . $this->path);
        }

        ignore_user_abort(true);

        if ($this->offset > 0) {
            fseek($file, $this->offset);
        }

        while ($length > 0 && !feof($file)) {
            $read = fread($file, self::CHUNK_SIZE);

            if ($read === false) {
                break;
            }

            $written = fwrite($output, $read);

            if (connection_aborted() || $written === false) {
                break;
            }

            $length -= $written;
        }

        fclose($output);
        fclose($file);
    }

    public function prepare(Request $request): static
    {
        parent::prepare($request);

        if (!isset($this->headers['Accept-Ranges']) && in_array($request->method(), [RequestMethod::HEAD, RequestMethod::GET], true)) {
            $this->headers['Accept-Ranges'] = 'bytes';
        }

        if ($request->method() === RequestMethod::HEAD || $this->requiresEmptyContent()) {
            $this->length = 0;
            return $this;
        }

        if ($request->method() === RequestMethod::GET && preg_match('/^bytes=(\d+)?-(\d+)?$/', $request->headers()->get('Range', ''), $matches, PREG_UNMATCHED_AS_NULL)) {
            [, $start, $end] = $matches;

            if ($start === null) {
                $start = max(0, $this->fileSize - (int) $end);
                $end = $this->fileSize - 1;
            } elseif ($end === null || $end > $this->fileSize - 1) {
                $end = $this->fileSize - 1;
            }

            $this->offset = (int) $start;

            if ($start > $end) {
                $this->length = 0;
                $this->responseStatus = ResponseStatus::RangeNotSatisfiable;
                $this->headers['Content-Range'] = sprintf('bytes */%s', $this->fileSize);
            } else {
                $this->length = (int) ($end - $start + 1);
                $this->responseStatus = ResponseStatus::PartialContent;
                $this->headers['Content-Range'] = sprintf('bytes %s-%s/%s', $start, $end, $this->fileSize);
                $this->headers['Content-Length'] = sprintf('%s', $this->length);
            }
        }

        return $this;
    }
}

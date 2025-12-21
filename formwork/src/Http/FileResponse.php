<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Utils\FileSystem;
use RuntimeException;

class FileResponse extends Response
{
    /**
     * Chunk size for file streaming
     */
    protected const int CHUNK_SIZE = 512 * 1024;

    /**
     * File size in bytes
     */
    protected int $fileSize;

    /**
     * Offset for partial content responses
     */
    protected int $offset = 0;

    /**
     * Length of partial content responses
     */
    protected int $length;

    public function __construct(
        protected string $path,
        ResponseStatus $responseStatus = ResponseStatus::OK,
        array $headers = [],
        bool $download = false,
        protected bool $autoEtag = false,
        protected bool $autoLastModified = false,
    ) {
        $this->fileSize = FileSystem::fileSize($path);

        $headers += [
            'Content-Type'        => FileSystem::mimeType($path),
            'Content-Disposition' => $download ? Header::make(['attachment', 'filename' => basename($path)]) : 'inline',
            'Content-Length'      => (string) $this->fileSize,
        ];

        parent::__construct('', $responseStatus, $headers);
    }

    public function send(): void
    {
        parent::cleanOutputBuffers();

        $this->sendHeaders();

        $length = $this->length ?? $this->fileSize;

        if ($length === 0) {
            $this->flush();
            return;
        }

        $file = fopen($this->path, 'r');
        $output = fopen('php://output', 'w');

        if ($output === false) {
            throw new RuntimeException('Unable to open output stream');
        }

        if ($file === false) {
            throw new RuntimeException(sprintf('Unable to open file: %s', $this->path));
        }

        ignore_user_abort(true);

        if ($this->offset > 0) {
            fseek($file, $this->offset);
        }

        while ($length > 0 && !feof($file)) {
            $read = fread($file, min(self::CHUNK_SIZE, $length));

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

        $this->flush();
    }

    public function prepare(Request $request): static
    {
        if ($this->autoEtag && !$this->headers->has('ETag')) {
            $this->headers->set('ETag', hash('sha256', $this->path . ':' . FileSystem::lastModifiedTime($this->path)));
        }

        if ($this->autoLastModified && !$this->headers->has('Last-Modified')) {
            $this->headers->set('Last-Modified', gmdate('D, d M Y H:i:s T', FileSystem::lastModifiedTime($this->path)));
        }

        parent::prepare($request);

        if ($this->requiresEmptyContent()) {
            $this->length = 0;
            return $this;
        }

        if (!$this->headers->has('Accept-Ranges') && in_array($request->method(), [RequestMethod::HEAD, RequestMethod::GET], true)) {
            $this->headers->set('Accept-Ranges', 'bytes');
        }

        if ($request->method() === RequestMethod::GET && preg_match('/^bytes=(\d+)?-(\d+)?$/', $request->headers()->get('Range', ''), $matches, PREG_UNMATCHED_AS_NULL)) {
            [, $start, $end] = $matches;

            // RFC 7233: ignore invalid "bytes=-"
            if ($start === null && $end === null) {
                return $this;
            }

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
                $this->headers->set('Content-Range', sprintf('bytes */%s', $this->fileSize));
                $this->headers->set('Content-Length', '0');
            } else {
                $this->length = (int) ($end - $start + 1);
                $this->responseStatus = ResponseStatus::PartialContent;
                $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $this->fileSize));
                $this->headers->set('Content-Length', sprintf('%s', $this->length));
            }
        }

        if ($request->method() === RequestMethod::HEAD) {
            $this->length = 0;
        }

        return $this;
    }
}

<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Language;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;
use Exception;

class Uploader
{
    protected $destination;

    protected $options;

    protected $uploadedFiles;

    public function __construct($destination, $options = array())
    {
        $this->destination = FileSystem::normalize($destination);
        $this->options = array_merge($this->defaults(), $options);
    }

    public function defaults()
    {
        $mimeTypes = array_map(
            array(MimeType::class, 'fromExtension'),
            Formwork::instance()->option('files.allowed_extensions')
        );
        return array(
            'allowedMimeTypes' => $mimeTypes,
            'overwrite' => false,
        );
    }

    public function upload($name = null)
    {
        if (!HTTPRequest::hasFiles()) {
            return false;
        }
        $count = count(HTTPRequest::files());

        foreach (HTTPRequest::files() as $file) {
            if ($file['error'] === 0) {
                if (is_null($name) || $count > 1) {
                    $name = $file['name'];
                }
                $this->move($file['tmp_name'], $this->destination, $name);
            } else {
                throw new Exception($this->errorMessage($file['error']));
            }
        }

        return true;
    }

    public function isAllowedMimeType($mimeType)
    {
        if (is_null($this->options['allowedMimeTypes'])) {
            return true;
        }
        return in_array($mimeType, (array) $this->options['allowedMimeTypes']);
    }

    public function uploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function errorMessage($code)
    {
        switch ($code) {
            case 1: //UPLOAD_ERR_INI_SIZE
            case 2: //UPLOAD_ERR_FORM_SIZE
                return Language::get('uploader.error.size');
            case 3: //UPLOAD_ERR_PARTIAL
                return Language::get('uploader.error.partial');
            case 4: //UPLOAD_ERR_NO_FILE
                return Language::get('uploader.error.no-file');
            case 6: //UPLOAD_ERR_NO_TMP_DIR
                return Language::get('uploader.error.no-temp');
            case 7: //UPLOAD_ERR_CANT_WRITE
                return Language::get('uploader.error.cannot-write');
            case 8: //UPLOAD_ERR_EXTENSION
                return Language::get('uploader.error.php-extension');
            default:
                return Language::get('uploader.error.unknown');
        }
    }

    private function move($source, $destination, $filename)
    {
        $mimeType = FileSystem::mimeType($source);

        if (!$this->isAllowedMimeType($mimeType)) {
            throw new Exception(Language::get('uploader.error.mime-type'));
        }

        $destination = FileSystem::normalize($destination);

        if (FileSystem::basename($filename)[0] == '.') {
            throw new Exception(Language::get('uploader.error.hidden-files'));
        }

        $name = str_replace(array(' ', '.'), '-', FileSystem::name($filename));
        $extension = strtolower(FileSystem::extension($filename));

        if (empty($extension)) {
            $mimeToExt = MimeType::toExtension($mimeType);
            $extension = is_array($mimeToExt) ? $mimeToExt[0] : $mimeToExt;
        }

        $filename = $name . '.' . $extension;

        if (!(bool) preg_match('/^[a-z0-9_-]+(?:\.[a-z0-9]+)?$/i', $filename)) {
            throw new Exception(Language::get('uploader.error.file-name'));
        }

        if (!$this->options['overwrite'] && FileSystem::exists($destination . $filename)) {
            return false;
        }

        if (@move_uploaded_file($source, $destination . $filename)) {
            $this->uploadedFiles[] = $filename;
            return true;
        }

        return false;
    }
}

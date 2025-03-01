<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Pages\Page;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use UnexpectedValueException;

final class FilesController extends AbstractController
{
    /**
     * FilesController@index action
     */
    public function index(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('pages.file')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        $filename = $routeParams->get('filename');

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotGetFileInfo.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$page->files()->has($filename)) {
            $this->panel->notify($this->translate('panel.pages.page.cannotGetFileInfo.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        $files = $page->files();

        $file = $files->get($filename);

        switch ($this->request->method()) {
            case RequestMethod::GET:
                $data = $file->data();

                $file->fields()->setValues($data);

                break;

            case RequestMethod::POST:
                $data = $this->request->input();

                $file->fields()->setValues($data)->validate();

                $this->updateFileMetadata($file, $file->fields());

                $this->updateLastModifiedTime($page);

                $this->panel->notify($this->translate('panel.files.metadata.updated'), 'success');

                return $this->redirect($this->generateRoute('panel.files.index', ['page' => $page->route(), 'filename' => $filename]));
        }

        return new Response($this->view('files.index', [
            'title' => $file->name(),
            'page'  => $page,
            'file'  => $file,
            ...$this->getPreviousAndNextFile($files, $file),
        ]));
    }

    /**
     * FilesController@delete action
     */
    public function delete(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('pages.deleteFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.files.cannotDelete.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$page->files()->has($routeParams->get('filename'))) {
            $this->panel->notify($this->translate('panel.files.cannotDelete.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        FileSystem::delete($page->contentPath() . $routeParams->get('filename'));

        $this->updateLastModifiedTime($page);

        $this->panel->notify($this->translate('panel.pages.page.fileDeleted'), 'success');
        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
    }

    /**
     * FilesController@rename action
     */
    public function rename(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('pages.renameFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        $fields = $this->modal('renameFile')->fields();

        $fields->setValues($this->request->input())->validate();

        $data = $fields->everyItem()->value();

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotRenameFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$page->files()->has($routeParams->get('filename'))) {
            $this->panel->notify($this->translate('panel.files.cannotRename.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        $name = Str::slug(FileSystem::name($data->get('filename')));
        $extension = FileSystem::extension($routeParams->get('filename'));

        $newName = $name . '.' . $extension;

        $previousName = $routeParams->get('filename');

        if ($newName !== $previousName) {
            if ($page->files()->has($newName)) {
                $this->panel->notify($this->translate('panel.pages.page.cannotRenameFile.fileAlreadyExists'), 'error');
            } else {
                FileSystem::move($page->contentPath() . $previousName, $page->contentPath() . $newName);
                $this->updateLastModifiedTime($page);

                $this->panel->notify($this->translate('panel.pages.page.fileRenamed'), 'success');
            }
        }

        return $this->redirect($this->generateRoute('panel.files.index', ['page' => $routeParams->get('page'), 'filename' => $newName]));
    }

    /**
     * FilesController@replace action
     */
    public function replace(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('pages.replaceFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        $filename = $routeParams->get('filename');

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotReplaceFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$page->files()->has($filename)) {
            $this->panel->notify($this->translate('panel.pages.page.cannotReplaceFile.fileNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$this->request->files()->isEmpty()) {
            $files = $this->request->files()->getAll();

            if (count($files) > 1) {
                $this->panel->notify($this->translate('panel.pages.page.cannotReplaceFile.multipleFiles'), 'error');
                return $this->redirect($this->generateRoute('panel.files.index', ['page' => $routeParams->get('page'), 'filename' => $filename]));
            }

            try {
                $this->processFileUpload($this->request->files()->getAll(), $page, [$page->files()->get($filename)->mimeType()], FileSystem::name($filename), true);
            } catch (TranslatedException $e) {
                $this->panel->notify($this->translate('upload.error', $this->translate($e->getLanguageString())), 'error');
                return $this->redirect($this->generateRoute('panel.files.index', ['page' => $routeParams->get('page'), 'filename' => $filename]));
            }
        }

        $this->updateLastModifiedTime($page);

        $this->panel->notify($this->translate('panel.uploader.uploaded'), 'success');
        return $this->redirect($this->generateRoute('panel.files.index', ['page' => $routeParams->get('page'), 'filename' => $filename]));
    }

    /**
     * Update file metadata
     */
    private function updateFileMetadata(File $file, FieldCollection $fieldCollection): void
    {
        $data = $file->data();

        $scheme = $file->scheme();

        $defaults = $scheme->fields()->pluck('default');

        foreach ($fieldCollection as $field) {
            if ($field->isEmpty() || (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value())) {
                unset($data[$field->name()]);
                continue;
            }

            $data[$field->name()] = $field->value();
        }

        $metaFile = $file->path() . $this->config->get('system.files.metadataExtension');

        if ($data === [] && FileSystem::exists($metaFile)) {
            FileSystem::delete($metaFile);
            return;
        }

        FileSystem::write($metaFile, Yaml::encode($data));
    }

    /**
     * Process page uploads
     *
     * @param array<UploadedFile> $files
     * @param list<string>        $mimeTypes
     */
    private function processFileUpload(array $files, Page $page, ?array $mimeTypes = null, ?string $name = null, bool $overwrite = false): void
    {
        $fileUploader = $this->app->getService(FileUploader::class);

        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing page path');
        }

        foreach ($files as $file) {
            $fileUploader->upload($file, $page->contentPath(), $name, overwrite: $overwrite, allowedMimeTypes: $mimeTypes);
        }
    }

    /**
     * Update last modified time of the given page
     */
    private function updateLastModifiedTime(Page $page): void
    {
        if ($page->contentFile()?->path() !== null) {
            FileSystem::touch($page->contentFile()->path());
        }
    }

    /**
     * Get previous and next file helper
     *
     * @return array{previousFile: ?File, nextFile: ?File}
     */
    private function getPreviousAndNextFile(FileCollection $fileCollection, File $file): array
    {
        $fileIndex = $fileCollection->indexOf($file);

        return [
            'previousFile' => $fileCollection->nth($fileIndex - 1),
            'nextFile'     => $fileCollection->nth($fileIndex + 1),
        ];
    }
}

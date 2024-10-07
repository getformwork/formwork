<?php

namespace Formwork\Panel\Controllers;

use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\FileUploader;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\RequestData;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Images\Image;
use Formwork\Pages\Page;
use Formwork\Panel\ContentHistory\ContentHistory;
use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Site;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;
use UnexpectedValueException;

class PagesController extends AbstractController
{
    /**
     * Valid page slug regex
     */
    protected const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i';

    /**
     * Page prefix date format
     */
    protected const DATE_NUM_FORMAT = 'Ymd';

    protected const IGNORED_FIELD_NAMES = ['content', 'template', 'parent'];

    /**
     * Pages@index action
     */
    public function index(): Response
    {
        $this->ensurePermission('pages.index');

        $this->modal('newPage');

        $this->modal('deletePage');

        $pages = $this->site()->pages();

        $indexOffset = $pages->indexOf($this->site()->indexPage());

        if ($indexOffset !== null) {
            $pages->moveItem($indexOffset, 0);
        }

        return new Response($this->view('pages.index', [
            'title'     => $this->translate('panel.pages.pages'),
            'pagesTree' => $this->view('pages.tree', [
                'pages'           => $pages,
                'includeChildren' => true,
                'class'           => 'pages-tree-root',
                'parent'          => '.',
                'orderable'       => $this->user()->permissions()->has('pages.reorder'),
                'headers'         => true,
            ]),
        ]));
    }

    /**
     * Pages@create action
     */
    public function create(): RedirectResponse
    {
        $this->ensurePermission('pages.create');

        $requestData = $this->request->input();

        $fields = $this->modal('newPage')->fields();

        try {
            $fields->setValues($requestData)->validate();
        } catch (ValidationException) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotCreate.varMissing'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        // Let's create the page
        try {
            $page = $this->createPage($fields);
            $this->panel()->notify($this->translate('panel.pages.page.created'), 'success');
        } catch (TranslatedException $e) {
            $this->panel()->notify($e->getTranslatedMessage(), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if ($page->route() === null) {
            throw new UnexpectedValueException('Unexpected missing page route');
        }

        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($page->route(), '/')]));
    }

    /**
     * Pages@edit action
     */
    public function edit(RouteParams $routeParams): Response
    {
        $this->ensurePermission('pages.edit');

        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotEdit.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if ($routeParams->has('language')) {
            if (empty($this->config->get('system.languages.available'))) {
                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($page->route(), '/')]));
            }

            $language = $routeParams->get('language');

            if (!in_array($language, $this->config->get('system.languages.available'), true)) {
                $this->panel()->notify($this->translate('panel.pages.page.cannotEdit.invalidLanguage', $language), 'error');
                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }
                return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $this->site()->languages()->default()]));
            }

            if ($page->languages()->available()->has($language)) {
                $page->setLanguage($language);
            }
        } elseif ($page->language() !== null) {
            if ($page->route() === null) {
                throw new UnexpectedValueException('Unexpected missing page route');
            }
            // Redirect to proper language
            return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $page->language()]));
        }

        // Load page fields
        $fields = $page->scheme()->fields();

        switch ($this->request->method()) {
            case RequestMethod::GET:
                // Load data from the page itself
                $data = $page->data();

                // Validate fields against data
                $fields->setValues($data);

                break;

            case RequestMethod::POST:
                // Load data from POST variables
                $data = $this->request->input();

                try {
                    // Validate fields against data
                    $fields->setValuesFromRequest($this->request, null)->validate();

                    $forceUpdate = false;

                    if ($this->request->query()->has('publish')) {
                        $fields->setValues(['published' => Constraint::isTruthy($this->request->query()->get('publish'))]);
                        $forceUpdate = true;
                    }

                    // Update the page
                    $page = $this->updatePage($page, $data, $fields, force: $forceUpdate);

                    $this->panel()->notify($this->translate('panel.pages.page.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->panel()->notify($e->getTranslatedMessage(), 'error');
                }

                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }

                // Redirect to avoid ERR_CACHE_MISS
                if ($routeParams->has('language')) {
                    return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => $page->route(), 'language' => $routeParams->get('language')]));
                }
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $page->route()]));
        }

        $this->modal('images');

        $this->modal('changes');

        $this->modal('slug');

        $this->modal('deletePage');

        $this->modal('deleteFile');

        $this->modal('renameFile');

        $contentHistory = $page->contentPath()
            ? new ContentHistory($page->contentPath())
            : null;

        return new Response($this->view('pages.editor', [
            'title'           => $this->translate('panel.pages.editPage', $page->title()),
            'page'            => $page,
            'fields'          => $page->fields(),
            'templates'       => $this->site()->templates()->keys(),
            'parents'         => $this->site()->descendants()->sortBy('relativePath'),
            'currentLanguage' => $routeParams->get('language', $page->language()?->code()),
            'history'         => $contentHistory,
        ]));
    }

    /**
     * Pages@preview action
     */
    public function preview(RouteParams $routeParams): Response
    {
        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotPreview.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        $this->site()->setCurrentPage($page);

        // Load data from POST variables
        $requestData = $this->request->input();

        // Validate fields against data
        $page->fields()->setValues($requestData)->validate();

        if ($page->template()->name() !== ($template = $requestData->get('template'))) {
            $page->reload(['template' => $this->site()->templates()->get($template)]);
        }

        if ($page->parent() !== ($parent = $this->resolveParent($requestData->get('parent')))) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotPreview.parentChanged'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        return new Response($page->render(), $page->responseStatus(), $page->headers());
    }

    /**
     * Pages@reorder action
     */
    public function reorder(): JsonResponse
    {
        $this->ensurePermission('pages.reorder');

        $requestData = $this->request->input();

        if (!$requestData->hasMultiple(['page', 'before', 'parent'])) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $parent = $this->resolveParent($requestData->get('parent'));
        if (!$parent->hasChildren()) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $pageCollection = $parent->children();
        $keys = $pageCollection->keys();

        $from = Arr::indexOf($keys, $requestData->get('page'));
        $to = Arr::indexOf($keys, $requestData->get('before'));

        if ($from === null || $to === null) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $pageCollection->moveItem($from, $to);

        foreach ($pageCollection->values() as $i => $page) {
            $name = basename((string) $page->relativePath());
            $newName = preg_replace(Page::NUM_REGEX, $i + 1 . '-', $name)
                ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));

            if ($newName !== $name) {
                $this->changePageName($page, $newName);
            }
        }

        return JsonResponse::success($this->translate('panel.pages.page.moved'));
    }

    /**
     * Pages@delete action
     */
    public function delete(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('pages.delete');

        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if ($routeParams->has('language')) {
            $language = $routeParams->get('language');
            if ($page->languages()->available()->has($language)) {
                $page->setLanguage($language);
            } else {
                $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.invalidLanguage', $language), 'error');
                return $this->redirectToReferer(default: '/pages/');
            }
        }

        if (!$page->isDeletable()) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.notDeletable'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if ($page->contentPath() !== null) {
            // Delete just the content file only if there are more than one language
            if ($page->contentFile() !== null && $routeParams->has('language') && count($page->languages()->available()) > 1) {
                FileSystem::delete($page->contentFile()->path());
            } else {
                FileSystem::delete($page->contentPath(), recursive: true);
            }
        }

        $this->panel()->notify($this->translate('panel.pages.page.deleted'), 'success');

        // Try to redirect to referer unless it's to Pages@edit
        if ($this->request->referer() !== null && !Str::startsWith(Uri::normalize($this->request->referer()), Uri::make(['path' => $this->panel()->uri('/pages/' . $routeParams->get('page') . '/edit/')]))) {
            return $this->redirectToReferer(default: '/pages/');
        }
        return $this->redirect($this->generateRoute('panel.pages'));
    }

    /**
     * Pages@uploadFile action
     */
    public function uploadFile(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('pages.uploadFiles');

        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotUploadFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$this->request->files()->isEmpty()) {
            try {
                $this->processPageUploads($this->request->files()->getAll(), $page);
            } catch (TranslatedException $e) {
                $this->panel()->notify($this->translate('upload.error', $e->getTranslatedMessage()), 'error');
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
            }
        }

        $this->panel()->notify($this->translate('panel.uploader.uploaded'), 'success');
        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
    }

    /**
     * Pages@deleteFile action
     */
    public function deleteFile(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('pages.deleteFiles');

        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDeleteFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$page->files()->has($routeParams->get('filename'))) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDeleteFile.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        FileSystem::delete($page->contentPath() . $routeParams->get('filename'));

        $this->panel()->notify($this->translate('panel.pages.page.fileDeleted'), 'success');
        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
    }

    /**
     * Pages@renameFile action
     */
    public function renameFile(RouteParams $routeParams, Request $request): RedirectResponse
    {
        $this->ensurePermission('pages.renameFiles');

        $page = $this->site()->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$page->files()->has($routeParams->get('filename'))) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        $name = Str::slug(FileSystem::name($request->input()->get('filename')));
        $extension = FileSystem::extension($routeParams->get('filename'));

        $newName = $name . '.' . $extension;

        $previousName = $routeParams->get('filename');

        if ($newName !== $previousName) {
            if ($page->files()->has($newName)) {
                $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.fileAlreadyExists'), 'error');
            } else {
                FileSystem::move($page->contentPath() . $previousName, $page->contentPath() . $newName);
                $this->panel()->notify($this->translate('panel.pages.page.fileRenamed'), 'success');
            }
        }

        $previousFileRoute = $this->generateRoute('panel.pages.file', ['page' => $routeParams->get('page'), 'filename' => $previousName]);

        if (Str::removeEnd((string) Uri::path($request->referer()), '/') === $this->site()->uri($previousFileRoute)) {
            return $this->redirect($this->generateRoute('panel.pages.file', ['page' => $routeParams->get('page'), 'filename' => $newName]));
        }

        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
    }

    /**
     * Pages@replaceFile action
     */
    public function replaceFile(RouteParams $routeParams): RedirectResponse
    {
        $this->ensurePermission('pages.replaceFiles');

        $page = $this->site()->findPage($routeParams->get('page'));

        $filename = $routeParams->get('filename');

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotReplaceFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$page->files()->has($filename)) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotReplaceFile.fileNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$this->request->files()->isEmpty()) {
            $files = $this->request->files()->getAll();

            if (count($files) > 1) {
                $this->panel()->notify($this->translate('panel.pages.page.cannotReplaceFile.multipleFiles'), 'error');
                return $this->redirectToReferer(default: '/pages/');
            }

            try {
                $this->processPageUploads($this->request->files()->getAll(), $page, [$page->files()->get($filename)->mimeType()], FileSystem::name($filename), true);
            } catch (TranslatedException $e) {
                $this->panel()->notify($this->translate('upload.error', $e->getTranslatedMessage()), 'error');
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
            }
        }

        $this->panel()->notify($this->translate('panel.uploader.uploaded'), 'success');
        return $this->redirectToReferer(default: '/pages/');
    }

    /**
     * Pages@file action
     */
    public function file(RouteParams $routeParams): Response
    {
        $this->ensurePermission('pages.file');

        $page = $this->site()->findPage($routeParams->get('page'));

        $filename = $routeParams->get('filename');

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotGetFileInfo.pageNotFound'), 'error');
            return $this->redirectToReferer(default: '/pages/');
        }

        if (!$page->files()->has($filename)) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotGetFileInfo.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $routeParams->get('page')]));
        }

        $files = $page->files();
        $file = $files->get($filename);
        $fileIndex = $files->indexOf($file);

        $this->modal('renameFile');
        $this->modal('deleteFile');

        return new Response($this->view('pages.file', [
            'title'        => $file->name(),
            'page'         => $page,
            'file'         => $file,
            'previousFile' => $files->nth($fileIndex - 1),
            'nextFile'     => $files->nth($fileIndex + 1),
        ]));
    }

    /**
     * Create a new page
     */
    protected function createPage(FieldCollection $fieldCollection): Page
    {
        try {
            $parent = $this->resolveParent($fieldCollection->get('parent')->value());
        } catch (RuntimeException) {
            throw new TranslatedException('Parent page not found', 'panel.pages.page.cannotCreate.invalidParent');
        }

        // Validate page slug
        if (!$this->validateSlug($fieldCollection->get('slug')->value())) {
            throw new TranslatedException('Invalid page slug', 'panel.pages.page.cannotCreate.invalidSlug');
        }

        $route = $parent->route() . $fieldCollection->get('slug')->value() . '/';

        // Ensure there isn't a page with the same route
        if ($this->site()->findPage($route) !== null) {
            throw new TranslatedException('A page with the same route already exists', 'panel.pages.page.cannotCreate.alreadyExists');
        }

        // Validate page template
        if (!$this->site()->templates()->has($fieldCollection->get('template'))) {
            throw new TranslatedException('Invalid page template', 'panel.pages.page.cannotCreate.invalidTemplate');
        }

        $scheme = $this->app->schemes()->get('pages.' . $fieldCollection->get('template')->value());

        $path = FileSystem::joinPaths(
            (string) $parent->contentPath(),
            $this->makePageNum($parent, $scheme->options()->get('num')) . '-' . $fieldCollection->get('slug')->value(),
            '/'
        );

        FileSystem::createDirectory($path, recursive: true);

        $language = $this->site()->languages()->default();

        $filename = $fieldCollection->get('template')->value();
        $filename .= $language !== null ? '.' . $language : '';
        $filename .= $this->config->get('system.pages.content.extension');

        FileSystem::createFile($path . $filename);

        $contentData = [
            'title'     => $fieldCollection->get('title')->value(),
            'published' => false,
        ];

        $fileContent = Str::wrap(Yaml::encode($contentData), '---' . PHP_EOL);

        FileSystem::write($path . $filename, $fileContent);

        $contentHistory = new ContentHistory($path);

        $contentHistory->update(ContentHistoryEvent::Created, $this->user()->username(), time());
        $contentHistory->save();

        return $this->site()->retrievePage($path);
    }

    /**
     * Update a page
     */
    protected function updatePage(Page $page, RequestData $requestData, FieldCollection $fieldCollection, bool $force = false): Page
    {
        if ($page->contentFile() === null) {
            throw new RuntimeException('Unexpected missing content file');
        }

        // Load current page frontmatter
        $frontmatter = $page->contentFile()->frontmatter();

        // Preserve the title if not given
        if (!empty($requestData->get('title'))) {
            $frontmatter['title'] = $requestData->get('title');
        }

        // Get page defaults
        $defaults = $page->defaults();

        // Handle data from fields
        foreach ($fieldCollection as $field) {
            // Remove empty and default values
            if (
                $field->isEmpty()
                || (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value())
                || in_array($field->name(), self::IGNORED_FIELD_NAMES, true)
            ) {
                unset($frontmatter[$field->name()]);
                continue;
            }

            if ($field->type() === 'upload') {
                $uploadedFiles = $field->is('multiple') ? $field->value() : [$field->value()];
                $this->processPageUploads($uploadedFiles, $page, $field->acceptMimeTypes());
                continue;
            }

            // Set frontmatter value
            $frontmatter[$field->name()] = $field->value();
        }

        $content = $requestData->has('content') ? str_replace("\r\n", "\n", $requestData->get('content')) : $page->data()['content'];

        $language = $requestData->get('language');

        // Validate language
        if (!empty($language) && !in_array($language, $this->config->get('system.languages.available'), true)) {
            throw new TranslatedException('Invalid page language', 'panel.pages.page.cannotEdit.invalidLanguage');
        }

        if ($page->contentFile() === null) {
            throw new RuntimeException('Unexpected missing content file');
        }

        $differ = $frontmatter !== $page->contentFile()->frontmatter() || $content !== $page->data()['content'] || $language !== $page->language();

        if ($force || $differ) {
            $filename = $requestData->get('template');
            $filename .= empty($language) ? '' : '.' . $language;
            $filename .= $this->config->get('system.pages.content.extension');

            $fileContent = Str::wrap(Yaml::encode($frontmatter), '---' . PHP_EOL) . $content;

            if ($page->contentPath() === null) {
                throw new UnexpectedValueException('Unexpected missing page path');
            }

            if ($this->site()->contentPath() === null) {
                throw new UnexpectedValueException('Unexpected missing site path');
            }

            FileSystem::write($page->contentPath() . $filename, $fileContent);
            FileSystem::touch($this->site()->contentPath());

            $contentHistory = new ContentHistory($page->contentPath());

            $contentHistory->update(ContentHistoryEvent::Edited, $this->user()->username(), time());
            $contentHistory->save();

            // Update page with the new data
            $page->reload();

            // Set correct page language if it has changed
            if (!empty($language) && $language !== $page->language()?->code()) {
                $page->setLanguage($language);
            }

            if ($page->contentFile() === null) {
                throw new RuntimeException('Unexpected missing content file');
            }

            // Check if page number has to change

            $timestamp = isset($page->data()['publishDate'])
                ? Date::toTimestamp($page->data()['publishDate'])
                : $page->contentFile()->lastModifiedTime();

            if ($page->scheme()->options()->get('num') === 'date' && $page->num() !== ($num = (int) date(self::DATE_NUM_FORMAT, $timestamp))) {
                if ($page->relativePath() === null) {
                    throw new UnexpectedValueException('Unexpected missing page relative path');
                }

                $name = preg_replace(Page::NUM_REGEX, $num . '-', basename($page->relativePath()))
                    ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));

                try {
                    $page = $this->changePageName($page, $name);
                } catch (RuntimeException) {
                    throw new TranslatedException('Cannot change page num', 'panel.pages.page.cannotChangeNum');
                }
            }
        }

        // Check if parent page has to change
        try {
            if ($page->parent() !== ($parent = $this->resolveParent($requestData->get('parent')))) {
                $page = $this->changePageParent($page, $parent);
            }
        } catch (RuntimeException) {
            throw new TranslatedException('Invalid parent page', 'panel.pages.page.cannotEdit.invalidParent');
        }

        // Check if page template has to change
        if ($page->template()->name() !== ($template = $requestData->get('template'))) {
            if (!$this->site()->templates()->has($template)) {
                throw new TranslatedException('Invalid page template', 'panel.pages.page.cannotEdit.invalidTemplate');
            }
            $page = $this->changePageTemplate($page, $template);
        }

        // Check if page slug has to change
        if ($page->slug() !== ($slug = $requestData->get('slug'))) {
            if (!$this->validateSlug($slug)) {
                throw new TranslatedException('Invalid page slug', 'panel.pages.page.cannotEdit.invalidSlug');
            }
            // Don't change index and error pages slug
            if ($page->isIndexPage() || $page->isErrorPage()) {
                throw new TranslatedException('Cannot change slug of index or error pages', 'panel.pages.page.cannotEdit.indexOrErrorPageSlug');
            }
            if ($this->site()->findPage($page->parent()?->route() . $slug . '/') !== null) {
                throw new TranslatedException('A page with the same route already exists', 'panel.pages.page.cannotEdit.alreadyExists');
            }
            $page = $this->changePageName($page, ltrim($page->num() . '-', '-') . $slug);
        }

        return $page;
    }

    /**
     * Process page uploads
     *
     * @param array<UploadedFile> $files
     * @param list<string>        $mimeTypes
     */
    protected function processPageUploads(array $files, Page $page, ?array $mimeTypes = null, ?string $name = null, bool $overwrite = false): void
    {
        $mimeTypes ??= Arr::map($this->config->get('system.files.allowedExtensions'), fn(string $ext) => MimeType::fromExtension($ext));

        $fileUploader = new FileUploader($mimeTypes);

        foreach ($files as $file) {
            if (!$file->isUploaded()) {
                throw new TranslatedException(sprintf('Cannot upload file "%s"', $file->fieldName()), $file->getErrorTranslationString());
            }
            if ($page->contentPath() === null) {
                throw new UnexpectedValueException('Unexpected missing page path');
            }
            $uploadedFile = $fileUploader->upload($file, $page->contentPath(), $name, $overwrite);
            // Process JPEG and PNG images according to system options (e.g. quality)
            if ($this->config->get('system.uploads.processImages') && in_array($uploadedFile->mimeType(), ['image/jpeg', 'image/png'], true)) {
                $image = new Image($uploadedFile->path(), $this->config->get('system.images'));
                $image->save();
            }
        }

        $page->reload();
    }

    /**
     * Make a page num according to 'date' or default mode
     *
     * @param string $mode 'date' for pages with a publish date
     */
    protected function makePageNum(Page|Site $parent, ?string $mode): string
    {
        return (string) match ($mode) {
            'date'  => date(self::DATE_NUM_FORMAT),
            default => 1 + max([0, ...$parent->children()->everyItem()->num()->values()])
        };
    }

    /**
     * Change the name of a page
     */
    protected function changePageName(Page $page, string $name): Page
    {
        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing page path');
        }
        $directory = dirname($page->contentPath());
        $destination = FileSystem::joinPaths($directory, $name, DS);
        FileSystem::moveDirectory($page->contentPath(), $destination);
        return $this->site()->retrievePage($destination);
    }

    /**
     * Change the parent of a page
     */
    protected function changePageParent(Page $page, Page|Site $parent): Page
    {
        if ($parent->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing parent page path');
        }

        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing page path');
        }

        if ($page->contentRelativePath() === null) {
            throw new UnexpectedValueException('Unexpected missing page relative path');
        }

        $destination = FileSystem::joinPaths($parent->contentPath(), basename($page->contentRelativePath()), DS);

        FileSystem::moveDirectory($page->contentPath(), $destination);
        return $this->site()->retrievePage($destination);
    }

    /**
     * Change page template
     */
    protected function changePageTemplate(Page $page, string $template): Page
    {
        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing page path');
        }

        if ($page->contentFile() === null) {
            throw new UnexpectedValueException('Unexpected missing content file');
        }

        $destination = $page->contentPath() . $template . $this->config->get('system.pages.content.extension');
        FileSystem::move($page->contentFile()->path(), $destination);
        $page->reload();
        return $page;
    }

    /**
     * Resolve parent page helper
     *
     * @param string $parent Page URI or '.' for site
     */
    protected function resolveParent(string $parent): Page|Site
    {
        if ($parent === '.') {
            return $this->site();
        }
        return $this->site()->findPage($parent) ?? throw new RuntimeException('Invalid parent');
    }

    /**
     * Validate page slug helper
     */
    protected function validateSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_REGEX, $slug);
    }
}

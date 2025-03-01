<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\File;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\JsonResponse;
use Formwork\Http\RequestData;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Page;
use Formwork\Pages\PageFactory;
use Formwork\Panel\ContentHistory\ContentHistory;
use Formwork\Panel\ContentHistory\ContentHistoryEvent;
use Formwork\Router\RouteParams;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use UnexpectedValueException;

final class PagesController extends AbstractController
{
    /**
     * Pages@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('pages.index')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $pages = $this->site->pages();

        $indexOffset = $pages->indexOf($this->site->indexPage());

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
                'orderable'       => $this->panel->user()->permissions()->has('pages.reorder'),
                'headers'         => true,
            ]),
        ]));
    }

    /**
     * Pages@create action
     */
    public function create(PageFactory $pageFactory): Response
    {
        if (!$this->hasPermission('pages.create')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $requestData = $this->request->input();

        $fields = $this->modal('newPage')->fields();

        try {
            $fields->setValues($requestData)->validate();

            // Let's create the page
            $page = $this->createPage($fields, $pageFactory);
            $this->panel->notify($this->translate('panel.pages.page.created'), 'success');
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate($e->getLanguageString()), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        } catch (InvalidValueException $e) {
            $identifier = $e->getIdentifier() ?? 'varMissing';
            $this->panel->notify($this->translate('panel.pages.page.cannotCreate.' . $identifier), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
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
        if (!$this->hasPermission('pages.edit')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotEdit.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
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
                $this->panel->notify($this->translate('panel.pages.page.cannotEdit.invalidLanguage', $language), 'error');
                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }
                return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $this->site->languages()->default()]));
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

                    $this->panel->notify($this->translate('panel.pages.page.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->panel->notify($this->translate($e->getLanguageString()), 'error');
                } catch (InvalidValueException $e) {
                    $identifier = $e->getIdentifier() ?? 'varMissing';
                    $this->panel->notify($this->translate('panel.pages.page.cannotEdit.' . $identifier), 'error');
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

        $this->modal('images')->setFieldsModel($page);

        $contentHistory = $page->contentPath()
            ? new ContentHistory($page->contentPath())
            : null;

        return new Response($this->view('pages.editor', [
            'title'           => $this->translate('panel.pages.editPage', (string) $page->title()),
            'page'            => $page,
            'fields'          => $page->fields(),
            'currentLanguage' => $routeParams->get('language', $page->language()?->code()),
            'history'         => $contentHistory,
            ...$this->getPreviousAndNextPage($page),
        ]));
    }

    /**
     * Pages@preview action
     */
    public function preview(RouteParams $routeParams): Response
    {
        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotPreview.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        $this->site->setCurrentPage($page);

        // Load data from POST variables
        $requestData = $this->request->input();

        // Validate fields against data
        $page->fields()->setValues($requestData)->validate();

        if ($page->template()->name() !== ($template = $requestData->get('template'))) {
            $page->reload(['template' => $this->site->templates()->get($template)]);
        }

        if ($page->parent() !== ($this->resolveParent($requestData->get('parent')))) {
            $this->panel->notify($this->translate('panel.pages.page.cannotPreview.parentChanged'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        return new Response($page->render(), $page->responseStatus(), $page->headers());
    }

    /**
     * Pages@reorder action
     */
    public function reorder(): JsonResponse|Response
    {
        if (!$this->hasPermission('pages.reorder')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $requestData = $this->request->input();

        if (!$requestData->hasMultiple(['page', 'before', 'parent'])) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $parent = $this->resolveParent($requestData->get('parent'));
        if (!$parent->hasChildren()) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'), ResponseStatus::InternalServerError);
        }

        $pageCollection = $parent->children();
        $keys = $pageCollection->keys();

        $from = Arr::indexOf($keys, $requestData->get('page'));
        $to = Arr::indexOf($keys, $requestData->get('before'));

        if ($from === null || $to === null) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'), ResponseStatus::InternalServerError);
        }

        $pageCollection->moveItem($from, $to);

        foreach ($pageCollection->filterBy('orderable')->values() as $i => $page) {
            $num = $i + 1;
            if ($num !== $page->num()) {
                $page->set('num', $num);
                $page->save();
            }
        }

        return JsonResponse::success($this->translate('panel.pages.page.moved'));
    }

    /**
     * Pages@delete action
     */
    public function delete(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('pages.delete')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotDelete.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if ($routeParams->has('language')) {
            $language = $routeParams->get('language');
            if ($page->languages()->available()->has($language)) {
                $page->setLanguage($language);
            } else {
                $this->panel->notify($this->translate('panel.pages.page.cannotDelete.invalidLanguage', $language), 'error');
                return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
            }
        }

        if (!$page->isDeletable()) {
            $this->panel->notify($this->translate('panel.pages.page.cannotDelete.notDeletable'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if ($page->contentPath() !== null) {
            // Delete just the content file only if there are more than one language
            if ($page->contentFile() !== null && $routeParams->has('language') && count($page->languages()->available()) > 1) {
                FileSystem::delete($page->contentFile()->path());
            } else {
                FileSystem::delete($page->contentPath(), recursive: true);
            }
        }

        $this->panel->notify($this->translate('panel.pages.page.deleted'), 'success');

        // Try to redirect to referer unless it's to Pages@edit
        if ($this->request->referer() !== null && !Str::startsWith(Uri::normalize($this->request->referer()), Uri::make(['path' => $this->panel->uri('/pages/' . $routeParams->get('page') . '/edit/')], $this->request->baseUri()))) {
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }
        return $this->redirect($this->generateRoute('panel.pages'));
    }

    /**
     * Pages@upload action
     */
    public function upload(RouteParams $routeParams): Response|JsonResponse
    {
        if (!$this->hasPermission('pages.uploadFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            return JsonResponse::error($this->translate('panel.files.cannotUpload.pageNotFound'), ResponseStatus::InternalServerError);
        }

        $uploadedFiles = [];

        if (!$this->request->files()->isEmpty()) {
            try {
                $uploadedFiles = $this->processPageUploads($this->request->files()->getAll(), $page);
            } catch (TranslatedException $e) {
                return JsonResponse::error($this->translate('upload.error', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
            }
        }

        $this->updateLastModifiedTime($page);

        return JsonResponse::success($this->translate('panel.uploader.uploaded'), data: Arr::map($uploadedFiles, fn(File $file) => [
            'name'      => $file->name(),
            'size'      => $file->size(),
            'type'      => $file->type(),
            'mimeType'  => $file->mimeType(),
            'hash'      => $file->hash(),
            'uri'       => $file->uri(),
            'thumbnail' => match ($file->type()) {
                'image' => $file->square(300, 'contain')->uri(), // @phpstan-ignore method.notFound
                'video' => $file->uri(),
                default => null,
            },
        ]), );
    }

    /**
     * Pages@deleteFile action
     */
    public function deleteFile(RouteParams $routeParams): JsonResponse|Response
    {
        if (!$this->hasPermission('pages.deleteFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        $filename = $this->request->input()->get('filename');

        if ($page === null || !$page->files()->has($filename)) {
            return JsonResponse::error($this->translate('panel.files.cannotDelete.fileNotFound'), ResponseStatus::InternalServerError);
        }

        FileSystem::delete($page->contentPath() . $this->request->input()->get('filename'));

        $this->updateLastModifiedTime($page);

        return JsonResponse::success($this->translate('panel.files.deleted'));
    }

    /**
     * Pages@renameFile action
     */
    public function renameFile(RouteParams $routeParams): JsonResponse|Response
    {
        if (!$this->hasPermission('pages.renameFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $previousName = $this->request->input()->get('filename');

        $page = $this->site->findPage($routeParams->get('page'));

        $fields = $this->modal('renameFileItem')->fields();

        $fields->setValues($this->request->input())->validate();

        $data = $fields->everyItem()->value();

        if ($page === null || !$page->files()->has($previousName)) {
            return JsonResponse::error($this->translate('panel.files.cannotRename.fileNotFound'), ResponseStatus::InternalServerError);
        }

        $name = Str::slug(FileSystem::name($data->get('filename')));
        $extension = FileSystem::extension($previousName);

        $newName = $name . '.' . $extension;

        if ($newName !== $previousName) {
            if ($page->files()->has($newName)) {
                $message = $this->translate('panel.files.cannotRename.fileAlreadyExists');
            } else {
                FileSystem::move($page->contentPath() . $previousName, $page->contentPath() . $newName);
                $this->updateLastModifiedTime($page);
            }
        }

        return JsonResponse::success($message ?? $this->translate('panel.files.renamed'), data: ['filename' => $newName]);
    }

    /**
     * Pages@replaceFile action
     */
    public function replaceFile(RouteParams $routeParams): Response|JsonResponse
    {
        if (!$this->hasPermission('pages.replaceFiles')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        $filename = $this->request->input()->get('filename');

        if ($page === null || !$page->files()->has($filename)) {
            return JsonResponse::error($this->translate('panel.files.cannotReplace.fileNotFound'), ResponseStatus::InternalServerError);
        }

        if (!$this->request->files()->isEmpty()) {
            $files = $this->request->files()->getAll();

            if (count($files) > 1) {
                return JsonResponse::error($this->translate('panel.files.cannotReplace.multipleFiles'), ResponseStatus::InternalServerError);
            }

            try {
                $this->processPageUploads($this->request->files()->getAll(), $page, [$page->files()->get($filename)->mimeType()], FileSystem::name($filename), true);
            } catch (TranslatedException $e) {
                return JsonResponse::error($this->translate('upload.error', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
            }
        }

        $this->updateLastModifiedTime($page);

        $file = $page->files()->get($filename);

        $thumbnail = match ($file->type()) {
            'image' => $file->square(300, 'contain')->uri(),
            'video' => $file->uri(),
            default => null,
        };

        return JsonResponse::success($this->translate('panel.uploader.uploaded'), data: [
            'size'      => $file->size(),
            'type'      => $file->type(),
            'thumbnail' => $thumbnail,
        ]);
    }

    /**
     * Create a new page
     */
    private function createPage(FieldCollection $fieldCollection, PageFactory $pageFactory): Page
    {
        $page = $pageFactory->make(['site' => $this->site, 'published' => false]);

        $data = $fieldCollection->everyItem()->value()->toArray();

        $page->setMultiple($data);

        $page->save($this->site->languages()->default());

        if ($page->contentPath()) {
            $contentHistory = new ContentHistory($page->contentPath());
            $contentHistory->update(ContentHistoryEvent::Created, $this->panel->user()->username(), time());
            $contentHistory->save();
        }

        return $page;
    }

    /**
     * Update a page
     */
    private function updatePage(Page $page, RequestData $requestData, FieldCollection $fieldCollection, bool $force = false): Page
    {
        foreach ($fieldCollection as $field) {
            if ($field->type() === 'upload') {
                if (!$field->isEmpty()) {
                    $uploadedFiles = $field->is('multiple') ? $field->value() : [$field->value()];
                    $this->processPageUploads($uploadedFiles, $page, $field->acceptMimeTypes());
                    $this->updateLastModifiedTime($page);
                }
                $fieldCollection->remove($field->name());
            }
        }

        $previousData = $page->data();

        /** @var array<string, mixed> */
        $data = [...$fieldCollection->everyItem()->value()->toArray(), 'slug' => $requestData->get('slug')];

        $page->setMultiple($data);
        $page->save($requestData->get('language'));

        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing content file');
        }

        if ($previousData !== $page->data() || $force) {
            $contentHistory = new ContentHistory($page->contentPath());
            $contentHistory->update(ContentHistoryEvent::Edited, $this->panel->user()->username(), time());
            $contentHistory->save();
        }

        return $page;
    }

    /**
     * Process page uploads
     *
     * @param array<UploadedFile> $files
     * @param list<string>        $mimeTypes
     *
     * @return array<File>
     */
    private function processPageUploads(array $files, Page $page, ?array $mimeTypes = null, ?string $name = null, bool $overwrite = false): array
    {
        $fileUploader = $this->app->getService(FileUploader::class);

        if ($page->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing page path');
        }

        $uploadedFiles = [];

        foreach ($files as $file) {
            $uploadedFiles[] = $fileUploader->upload($file, $page->contentPath(), $name, overwrite: $overwrite, allowedMimeTypes: $mimeTypes);
        }

        $page->reload();

        return $uploadedFiles;
    }

    /**
     * Resolve parent page helper
     *
     * @param string $parent Page URI or '.' for site
     */
    private function resolveParent(string $parent): Page|Site
    {
        if ($parent === '.') {
            return $this->site;
        }
        return $this->site->findPage($parent) ?? throw new UnexpectedValueException('Invalid parent');
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
     * Get previous and next page helper
     *
     * @return array{previousPage: ?Page, nextPage: ?Page}
     */
    private function getPreviousAndNextPage(Page $page): array
    {
        $inclusiveSiblings = $page->inclusiveSiblings();

        if ($page->parent()?->scheme()->options()->get('children.reverse')) {
            $inclusiveSiblings = $inclusiveSiblings->reverse();
        }

        $indexOffset = $inclusiveSiblings->indexOf($this->site->indexPage());

        if ($indexOffset !== null) {
            $inclusiveSiblings->moveItem($indexOffset, 0);
        }

        $pageIndex = $inclusiveSiblings->indexOf($page);

        return [
            'previousPage' => $inclusiveSiblings->nth($pageIndex - 1),
            'nextPage'     => $inclusiveSiblings->nth($pageIndex + 1),
        ];
    }
}

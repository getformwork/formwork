<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\File;
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
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
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
        if (!$this->hasPermission('panel.pages.tree')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return $this->redirect($this->generateRoute('panel.pages.tree'));
    }

    /**
     * Pages@tree action
     *
     * @since 2.1.0
     */
    public function tree(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.pages.tree')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $parent = $routeParams->has('page')
            ? $this->site->findPage($routeParams->get('page'))
            : $this->site;

        $childrenSubtree = $parent?->scheme()->options()->get('children.subtree', false);

        if ($parent === null || !$parent->hasChildren() || (!$parent->isSite() && !$childrenSubtree)) {
            return $this->forward(ErrorsController::class, 'notFound');
        }

        $pageCollection = $parent->children();
        $indexOffset = $pageCollection->indexOf($this->site->indexPage());

        if ($indexOffset !== null) {
            $pageCollection->moveItem($indexOffset, 0);
        }

        $this->modal('newPage')->setFieldsModel($parent);

        // Check if grid display is enabled in parent scheme
        $gridDisplayEnabled = $parent->scheme()->options()->get('children.subtreeGridDisplay', false);

        // Get view mode from query parameter or default to tree
        $viewMode = $this->request->query()->get('view', 'tree');

        // Validate view mode - only allow card view if grid display is enabled
        if (!in_array($viewMode, ['tree', 'card']) || ($viewMode === 'card' && !$gridDisplayEnabled)) {
            $viewMode = 'tree';
        }

        $pagesContent = $viewMode === 'card' && $gridDisplayEnabled
            ? $this->view('@panel.pages.cards', [
                'pages' => $pageCollection,
            ])
            : $this->view('@panel.pages.tree', [
                'pages'           => $pageCollection,
                'parent'          => $parent,
                'root'            => $parent,
                'includeChildren' => true,
                'orderable'       => $this->panel->user()->permissions()->has('panel.pages.reorder'),
                'headers'         => true,
                'class'           => 'pages-tree-root',
            ]);

        return new Response($this->view('@panel.pages.index', [
            'title'              => $this->translate('panel.pages.pages'),
            'parent'             => $parent,
            'pagesTree'          => $pagesContent,
            'viewMode'           => $viewMode,
            'gridDisplayEnabled' => $gridDisplayEnabled,
        ]));
    }

    /**
     * Pages@create action
     */
    public function create(PageFactory $pageFactory): Response
    {
        if (!$this->hasPermission('panel.pages.create')) {
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
     * Pages@duplicate action
     *
     * @since 2.2.0
     */
    public function duplicate(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.pages.duplicate')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $requestData = $this->request->input();

        $fields = $this->modal('duplicatePage')->fields();

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotDuplicate.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if (!$page->isDuplicable()) {
            $this->panel->notify($this->translate('panel.pages.page.cannotDuplicate.notDuplicable'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        try {
            $fields->setValues($requestData)->validate();

            // Let's duplicate the page
            $duplicatePage = $this->duplicatePage($page, $fields);
            $this->panel->notify($this->translate('panel.pages.page.created'), 'success');
        } catch (TranslatedException $e) {
            $this->panel->notify($this->translate($e->getLanguageString()), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        } catch (InvalidValueException $e) {
            $identifier = $e->getIdentifier() ?? 'varMissing';
            $this->panel->notify($this->translate('panel.pages.page.cannotCreate.' . $identifier), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if ($duplicatePage->route() === null) {
            throw new UnexpectedValueException('Unexpected missing page route');
        }

        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($duplicatePage->route(), '/')]));
    }

    /**
     * Pages@edit action
     */
    public function edit(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.pages.edit')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotEdit.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if ($routeParams->has('language')) {
            if (!$this->site->languages()->hasMultiple()) {
                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($page->route(), '/')]));
            }

            $language = $routeParams->get('language');

            if (!$this->site->languages()->available()->has($language)) {
                $this->panel->notify($this->translate('panel.pages.page.cannotEdit.invalidLanguage', $language), 'error');
                if ($page->route() === null) {
                    throw new UnexpectedValueException('Unexpected missing page route');
                }
                return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $this->site->languages()->default()]));
            }

            if ($page->languages()->available()->has($language)) {
                $page->set('language', $language);
            }
        } elseif ($this->site->languages()->hasMultiple() && $page->language() !== null) {
            if ($page->route() === null) {
                throw new UnexpectedValueException('Unexpected missing page route');
            }
            // Redirect to proper language
            return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $page->language()]));
        }

        $createNew = $this->request->query()->has('createNew');

        // Clone the page fields to work with a separate copy
        // This allows us to:
        // 1. Remove upload fields after processing (they shouldn't be saved to page data)
        // 2. Validate user input without modifying the original page fields
        // 3. Keep the original page fields intact for rendering the form on validation errors
        $fieldCollection = $page->fields()->deepClone();

        $valid = false;

        switch ($this->request->method()) {
            case RequestMethod::GET:
                // Load data from the page itself
                $data = $page->data();

                $fieldCollection->setValues($data);

                $valid = $fieldCollection->isValid();

                break;

            case RequestMethod::POST:
                // Load data from POST variables
                $data = $this->request->input();

                $fieldCollection->setValuesFromRequest($this->request, null);

                if (!($valid = $fieldCollection->isValid())) {
                    $this->panel->notify($this->translate('panel.pages.page.cannotEdit.invalidFields'), 'error');
                    break;
                }

                try {
                    $forceUpdate = false;

                    if ($this->request->query()->has('publish')) {
                        $fieldCollection->setValues(['published' => Constraint::isTruthy($this->request->query()->get('publish'))]);
                        $forceUpdate = true;
                    }

                    // Update the page
                    $page = $this->updatePage($page, $data, $fieldCollection, force: $forceUpdate);

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

                $query = [];

                if ($createNew) {
                    $query[] = 'createNew';
                }

                // Redirect to avoid ERR_CACHE_MISS
                if ($routeParams->has('language')) {
                    return $this->redirect(Uri::make(['query' => implode('&', $query)], $this->generateRoute('panel.pages.edit.lang', ['page' => $page->route(), 'language' => $routeParams->get('language')])));
                }
                return $this->redirect(Uri::make(['query' => implode('&', $query)], $this->generateRoute('panel.pages.edit', ['page' => $page->route()])));
        }

        $this->modal('newPage')->setFieldsModel($page->parent() ?? $this->site);
        $this->modal('newPage')->open($createNew);
        $this->modal('images')->setFieldsModel($page);

        $contentHistory = $page->contentPath()
            ? new ContentHistory($page->contentPath())
            : null;

        $responseStatus = ($valid || $this->request->method() === RequestMethod::GET) ? ResponseStatus::OK : ResponseStatus::UnprocessableEntity;

        return new Response($this->view('@panel.pages.editor', [
            'title'           => $this->translate('panel.pages.editPage', (string) $page->title()),
            'page'            => $page,
            'fields'          => $fieldCollection,
            'currentLanguage' => $routeParams->get('language', $page->language()?->code()),
            'history'         => $contentHistory,
            ...$this->getPreviousAndNextPage($page),
        ]), $responseStatus);
    }

    /**
     * Pages@preview action
     */
    public function preview(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('panel.pages.preview')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            $this->panel->notify($this->translate('panel.pages.page.cannotPreview.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        $this->site->setCurrentPage($page);

        // Load data from POST variables
        $requestData = $this->request->input();

        // Store original page values
        $originalValues = $page->getMultiple(['published', 'cacheable']);

        // Validate fields against data
        $page->fields()->setValues($requestData, null)->validate();

        if ($page->template()->name() !== ($template = $requestData->get('template'))) {
            $page->reload(['template' => $this->site->templates()->get($template)]);
        }

        if ($page->parent() !== ($this->resolveParent($requestData->get('parent')))) {
            $this->panel->notify($this->translate('panel.pages.page.cannotPreview.parentChanged'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        // Set page as published and non-cacheable for preview
        $page->setMultiple(['published' => true, 'cacheable' => false]);

        try {
            $response = new Response($page->render(), $page->responseStatus(), $page->headers());
        } finally {
            // Restore original page values to avoid side effects
            $page->setMultiple($originalValues);
        }

        return $response;
    }

    /**
     * Pages@reorder action
     */
    public function reorder(): JsonResponse|Response
    {
        if (!$this->hasPermission('panel.pages.reorder')) {
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
        if (!$this->hasPermission('panel.pages.delete')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null) {
            if ($this->request->isXmlHttpRequest()) {
                return JsonResponse::error($this->translate('panel.pages.page.cannotDelete.pageNotFound'), ResponseStatus::InternalServerError);
            }
            $this->panel->notify($this->translate('panel.pages.page.cannotDelete.pageNotFound'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        if ($routeParams->has('language')) {
            $language = $routeParams->get('language');
            if ($page->languages()->available()->has($language)) {
                $page->set('language', $language);
            } else {
                if ($this->request->isXmlHttpRequest()) {
                    return JsonResponse::error($this->translate('panel.pages.page.cannotDelete.invalidLanguage', $language), ResponseStatus::InternalServerError);
                }
                $this->panel->notify($this->translate('panel.pages.page.cannotDelete.invalidLanguage', $language), 'error');
                return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
            }
        }

        if (!$page->isDeletable()) {
            if ($this->request->isXmlHttpRequest()) {
                return JsonResponse::error($this->translate('panel.pages.page.cannotDelete.notDeletable'), ResponseStatus::InternalServerError);
            }
            $this->panel->notify($this->translate('panel.pages.page.cannotDelete.notDeletable'), 'error');
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }

        $page->delete(allLanguages: !$routeParams->has('language'));

        if ($this->request->isXmlHttpRequest()) {
            return JsonResponse::success($this->translate('panel.pages.page.deleted'));
        }
        $this->panel->notify($this->translate('panel.pages.page.deleted'), 'success');

        // Try to redirect to referer unless it's to Pages@edit
        if ($this->request->referer() !== null && !Str::startsWith(Uri::normalize(Str::append($this->request->referer(), '/')), Uri::make(['path' => $this->panel->uri('/pages/' . $routeParams->get('page') . '/edit/')], $this->request->baseUri()))) {
            return $this->redirectToReferer(default: $this->generateRoute('panel.pages'), base: $this->panel->panelRoot());
        }
        return $this->redirect($this->generateRoute('panel.pages'));
    }

    /**
     * Pages@upload action
     */
    public function upload(RouteParams $routeParams): Response|JsonResponse
    {
        if (!$this->hasPermission('panel.files.upload')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $page = $this->site->findPage($routeParams->get('page'));

        if ($page === null || $page->contentPath() === null) {
            return JsonResponse::error($this->translate('panel.files.cannotUpload.pageNotFound'), ResponseStatus::InternalServerError);
        }

        $fieldCollection = $page->fields()->filterBy('type', 'upload');
        $fieldCollection->setValuesFromRequest($this->request, null)->validate();

        $uploadedFiles = [];

        foreach ($fieldCollection as $field) {
            try {
                $files = $field->isMultiple() ? $field->value() : [$field->value()];
                foreach ($files as $file) {
                    $uploadedFiles[] = $this->fileUploader->upload(
                        $file,
                        $page->contentPath(),
                        $field->filename(),
                        $field->acceptMimeTypes(),
                        $field->overwrite(),
                    );
                }
            } catch (TranslatedException $e) {
                return JsonResponse::error($this->translate('upload.error', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
            }
        }

        $this->updateLastModifiedTime($page);

        return JsonResponse::success(
            $this->translate('panel.uploader.uploaded'),
            data: Arr::map($uploadedFiles, fn(File $file) => [
                'name'             => $file->name(),
                'size'             => $file->size(),
                'lastModifiedTime' => Date::formatTimestamp(
                    $file->lastModifiedTime(),
                    $this->config->get('system.date.datetimeFormat'),
                    $this->translations->getCurrent()
                ),
                'type'      => $file->type(),
                'mimeType'  => $file->mimeType(),
                'hash'      => $file->hash(),
                'uri'       => $file->uri(),
                'thumbnail' => match ($file->type()) {
                    'image' => $file->square(300, 'contain')->uri(), // @phpstan-ignore method.notFound
                    'video' => $file->uri(),
                    default => null,
                },
                'actions' => Arr::map([
                    'info'    => $this->router->generate('panel.files.edit', ['model' => $page->getModelIdentifier(), 'id' => $page->route(), 'filename' => $file->name()]),
                    'rename'  => $this->router->generate('panel.files.rename', ['model' => $page->getModelIdentifier(), 'id' => $page->route(), 'filename' => $file->name()]),
                    'replace' => $this->router->generate('panel.files.replace', ['model' => $page->getModelIdentifier(), 'id' => $page->route(), 'filename' => $file->name()]),
                    'delete'  => $this->router->generate('panel.files.delete', ['model' => $page->getModelIdentifier(), 'id' => $page->route(), 'filename' => $file->name()]),
                ], fn(string $route): string => Uri::make([], Path::join([$this->request->root(), $route]))),
            ]),
        );
    }

    /**
     * Create a new page
     */
    private function createPage(FieldCollection $fieldCollection, PageFactory $pageFactory): Page
    {
        $page = $pageFactory->make(['site' => $this->site, 'published' => false]);

        $data = $fieldCollection->everyItem()->value()->toArray();

        $page->setMultiple($data);

        $language = $this->site->languages()->hasMultiple()
            ? $this->site->languages()->default()
            : null;

        $page->save($language);

        if ($page->contentPath()) {
            $contentHistory = new ContentHistory($page->contentPath());
            $contentHistory->update(ContentHistoryEvent::Created, $this->panel->user()->username(), time());
            $contentHistory->save();
        }

        return $page;
    }

    /**
     * Duplicate a page
     */
    private function duplicatePage(Page $page, FieldCollection $fieldCollection): Page
    {
        $data = [...$fieldCollection->everyItem()->value()->toArray(), 'published' => false];

        $duplicatePage = $page->duplicate($data);

        if ($duplicatePage->contentPath()) {
            $contentHistory = new ContentHistory($duplicatePage->contentPath());
            $contentHistory->update(ContentHistoryEvent::Created, $this->panel->user()->username(), time());
            $contentHistory->save();
        }

        return $duplicatePage;
    }

    /**
     * Update a page
     */
    private function updatePage(Page $page, RequestData $requestData, FieldCollection $fieldCollection, bool $force = false): Page
    {
        // Process upload fields first
        foreach ($fieldCollection as $field) {
            if ($field->type() === 'upload') {
                if (!$field->isEmpty()) {
                    $files = $field->isMultiple() ? $field->value() : [$field->value()];
                    foreach ($files as $file) {
                        $this->fileUploader->upload(
                            $file,
                            $field->destination() ?? $page->contentPath(),
                            $field->filename(),
                            $field->acceptMimeTypes(),
                            $field->overwrite(),
                        );
                    }
                    $this->updateLastModifiedTime($page);
                    // Reload the page to refresh its internal state after file upload
                    // Note: This reloads $page->fields() but doesn't affect our $fieldCollection
                    $page->reload();
                }
                // Remove upload field from the collection - upload fields should not be saved to page data
                // They are only used for file uploads, not for storing values in the frontmatter
                $fieldCollection->remove($field->name());
            }
        }

        $previousData = $page->data();

        // Extract validated values from remaining fields (upload fields have been removed)
        // These are the user's submitted values that were validated earlier
        /** @var array<string, mixed> */
        $data = [...$fieldCollection->extract('value'), 'slug' => $requestData->get('slug')];

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

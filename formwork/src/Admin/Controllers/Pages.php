<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Exceptions\TranslatedException;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Uploader;
use Formwork\Utils\JSONResponse;
use Formwork\Utils\Session;
use Formwork\Core\Page;
use Formwork\Core\Site;
use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Files\Image;
use Formwork\Languages\LanguageCodes;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use RuntimeException;

class Pages extends AbstractController
{
    /**
     * Valid page slug regex
     *
     * @var string
     */
    protected const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i';

    /**
     * Page prefix date format
     *
     * @var string
     */
    protected const DATE_NUM_FORMAT = 'Ymd';

    /**
     * Pages@index action
     */
    public function index(): void
    {
        $this->ensurePermission('pages.index');

        $this->modal('newPage', [
            'templates' => $this->site()->templates(),
            'pages'     => $this->site()->descendants()->sort('path')
        ]);

        $this->modal('deletePage');

        $this->view('admin', [
            'title'   => $this->label('pages.pages'),
            'content' => $this->view('pages.index', [
                'pagesList' => $this->view('pages.list', [
                    'pages'    => $this->site()->pages(),
                    'subpages' => true,
                    'class'    => 'pages-list-top',
                    'parent'   => '.',
                    'sortable' => $this->user()->permissions()->has('pages.reorder'),
                    'headers'  => true
                ], true)
            ], true)
        ]);
    }

    /**
     * Pages@create action
     */
    public function create(): void
    {
        $this->ensurePermission('pages.create');

        $data = new DataGetter(HTTPRequest::postData());

        // Let's create the page
        try {
            $page = $this->createPage($data);
            Session::set('FORMWORK_PAGE_TO_PUBLISH', $page->route());
            $this->notify($this->label('pages.page.created'), 'success');
        } catch (TranslatedException $e) {
            $this->notify($e->getTranslatedMessage(), 'error');
            $this->redirectToReferer(302, '/pages/');
        }

        $this->redirect('/pages/' . trim($page->route(), '/') . '/edit/');
    }

    /**
     * Pages@edit action
     */
    public function edit(RouteParams $params): void
    {
        $this->ensurePermission('pages.edit');

        $page = $this->site()->findPage($params->get('page'));

        $this->ensurePageExists($page, 'pages.page.cannot-edit.page-not-found');

        if ($params->has('language')) {
            if (empty(Formwork::instance()->config()->get('languages.available'))) {
                $this->redirect('/pages/' . trim($page->route(), '/') . '/edit/');
            }

            $language = $params->get('language');

            if (!in_array($language, Formwork::instance()->config()->get('languages.available'), true)) {
                $this->notify($this->label('pages.page.cannot-edit.invalid-language', $language), 'error');
                $this->redirect('/pages/' . trim($page->route(), '/') . '/edit/language/' . $this->site()->languages()->default() . '/');
            }

            if ($page->hasLanguage($language)) {
                $page->setLanguage($language);
            }
        } elseif ($page->language() !== null) {
            // Redirect to proper language
            $this->redirect('/pages/' . trim($page->route(), '/') . '/edit/language/' . $page->language() . '/');
        }

        // Check if page has to be published on next save
        if (Session::has('FORMWORK_PAGE_TO_PUBLISH')) {
            if ($page->route() === Session::get('FORMWORK_PAGE_TO_PUBLISH')) {
                $page->set('published', true);
            }
            Session::remove('FORMWORK_PAGE_TO_PUBLISH');
        }

        // Load page fields
        $fields = new Fields($page->template()->scheme()->get('fields'));

        switch (HTTPRequest::method()) {
            case 'GET':
                // Load data from the page itself
                $data = new DataGetter($page->data());

                // Validate fields against data
                $fields->validate($data);

                break;

            case 'POST':
                // Load data from POST variables
                $data = new DataGetter(HTTPRequest::postData());

                // Validate fields against data
                $fields->validate($data);

                // Update the page
                try {
                    $page = $this->updatePage($page, $data, $fields);
                    $this->notify($this->label('pages.page.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->notify($e->getTranslatedMessage(), 'error');
                }

                if (HTTPRequest::hasFiles()) {
                    try {
                        $this->processPageUploads($page);
                    } catch (TranslatedException $e) {
                        $this->notify($this->label('uploader.error', $e->getTranslatedMessage()), 'error');
                    }
                }

                // Redirect if page route has changed
                if ($params->get('page') !== ($route = trim($page->route(), '/'))) {
                    $this->redirect('/pages/' . $route . '/edit/');
                }

                break;
        }

        $this->modal('changes');

        $this->modal('slug');

        $this->modal('images', [
            'page' => $page
        ]);

        $this->modal('deletePage');

        $this->modal('deleteFile');

        $this->view('admin', [
            'title'   => $this->label('pages.edit-page', $page->title()),
            'content' => $this->view('pages.editor', [
                'page'               => $page,
                'fields'             => $fields->render(true),
                'templates'          => $this->site()->templates(),
                'parents'            => $this->site()->descendants()->sort('path'),
                'currentLanguage'    => $params->get('language', $page->language()),
                'availableLanguages' => $this->availableSiteLanguages()
            ], true)
        ]);
    }

    /**
     * Pages@reorder action
     */
    public function reorder(): void
    {
        $this->ensurePermission('pages.reorder');

        $data = new DataGetter(HTTPRequest::postData());

        if (!$data->hasMultiple(['parent', 'from', 'to'])) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        if (!is_numeric($data->get('from')) || !is_numeric($data->get('to'))) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        $parent = $this->resolveParent($data->get('parent'));
        if ($parent === null || !$parent->hasChildren()) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        $pages = $parent->children()->toArray();

        $from = max(0, $data->get('from'));
        $to = max(0, $data->get('to'));
        if ($to === $from) {
            exit;
        }

        array_splice($pages, $to, 0, array_splice($pages, $from, 1));

        foreach ($pages as $i => $page) {
            $name = $page->name();
            if ($name === null) {
                continue;
            }
            $newName = preg_replace(Page::NUM_REGEX, $i + 1 . '-', $name);
            if ($newName !== $name) {
                $this->changePageName($page, $newName);
            }
        }

        JSONResponse::success($this->label('pages.page.moved'))->send();
    }

    /**
     * Pages@delete action
     */
    public function delete(RouteParams $params): void
    {
        $this->ensurePermission('pages.delete');

        $page = $this->site()->findPage($params->get('page'));

        $this->ensurePageExists($page, 'pages.page.cannot-delete.page-not-found');

        if ($params->has('language')) {
            $language = $params->get('language');
            if ($page->hasLanguage($language)) {
                $page->setLanguage($language);
            } else {
                $this->notify($this->label('pages.page.cannot-delete.invalid-language', $language), 'error');
                $this->redirectToReferer(302, '/pages/');
            }
        }

        if (!$page->isDeletable()) {
            $this->notify($this->label('pages.page.cannot-delete.not-deletable'), 'error');
            $this->redirectToReferer(302, '/pages/');
        }

        // Delete just the content file only if there are more than one language
        if ($params->has('language') && count($page->availableLanguages()) > 1) {
            FileSystem::delete($page->path() . $page->filename());
        } else {
            FileSystem::delete($page->path(), true);
        }

        $this->notify($this->label('pages.page.deleted'), 'success');

        // Don't redirect to referer if it's to Pages@edit
        if (!Str::startsWith(Uri::normalize(HTTPRequest::referer()), Uri::make(['path' => $this->uri('/pages/' . $params->get('page') . '/edit/')]))) {
            $this->redirectToReferer(302, '/pages/');
        } else {
            $this->redirect('/pages/');
        }
    }

    /**
     * Pages@uploadFile action
     */
    public function uploadFile(RouteParams $params): void
    {
        $this->ensurePermission('pages.upload_files');

        $page = $this->site()->findPage($params->get('page'));

        $this->ensurePageExists($page, 'pages.page.cannot-upload-file.page-not-found');

        if (HTTPRequest::hasFiles()) {
            try {
                $this->processPageUploads($page);
            } catch (TranslatedException $e) {
                $this->notify($this->label('uploader.error', $e->getTranslatedMessage()), 'error');
                $this->redirect('/pages/' . $params->get('page') . '/edit/');
            }
        }

        $this->notify($this->label('uploader.uploaded'), 'success');
        $this->redirect('/pages/' . $params->get('page') . '/edit/');
    }

    /**
     * Pages@deleteFile action
     */
    public function deleteFile(RouteParams $params): void
    {
        $this->ensurePermission('pages.delete_files');

        $page = $this->site()->findPage($params->get('page'));

        $this->ensurePageExists($page, 'pages.page.cannot-delete-file.page-not-found');

        if (!$page->files()->has($params->get('filename'))) {
            $this->notify($this->label('pages.page.cannot-delete-file.file-not-found'), 'error');
            $this->redirect('/pages/' . $params->get('page') . '/edit/');
        }

        FileSystem::delete($page->path() . $params->get('filename'));

        $this->notify($this->label('pages.page.file-deleted'), 'success');
        $this->redirect('/pages/' . $params->get('page') . '/edit/');
    }

    /**
     * Create a new page
     */
    protected function createPage(DataGetter $data): Page
    {
        // Ensure no required data is missing
        if (!$data->hasMultiple(['title', 'slug', 'template', 'parent'])) {
            throw new TranslatedException('Missing required POST data', 'pages.page.cannot-create.var-missing');
        }

        $parent = $this->resolveParent($data->get('parent'));

        if ($parent === null) {
            throw new TranslatedException('Parent page not found', 'pages.page.cannot-create.invalid-parent');
        }

        // Validate page slug
        if (!$this->validateSlug($data->get('slug'))) {
            throw new TranslatedException('Invalid page slug', 'pages.page.cannot-create.invalid-slug');
        }

        $route = $parent->route() . $data->get('slug') . '/';

        // Ensure there isn't a page with the same route
        if ($this->site()->findPage($route)) {
            throw new TranslatedException('A page with the same route already exists', 'pages.page.cannot-create.already-exists');
        }

        // Validate page template
        if (!$this->site()->hasTemplate($data->get('template'))) {
            throw new TranslatedException('Invalid page template', 'pages.page.cannot-create.invalid-template');
        }

        $scheme = $this->scheme($data->get('template'));

        $path = $parent->path() . $this->makePageNum($parent, $scheme->get('num')) . '-' . $data->get('slug') . DS;

        FileSystem::createDirectory($path, true);

        $language = $this->site()->languages()->default();

        $filename = $data->get('template');
        $filename .= empty($language) ? '' : '.' . $language;
        $filename .= Formwork::instance()->config()->get('content.extension');

        FileSystem::createFile($path . $filename);

        $frontmatter = [
            'title'     => $data->get('title'),
            'published' => false
        ];

        $fileContent = Str::wrap(YAML::encode($frontmatter), '---' . PHP_EOL);

        FileSystem::write($path . $filename, $fileContent);

        return new Page($path);
    }

    /**
     * Update a page
     */
    protected function updatePage(Page $page, DataGetter $data, Fields $fields): Page
    {
        // Ensure no required data is missing
        if (!$data->hasMultiple(['title', 'content'])) {
            throw new TranslatedException('Missing required POST data', 'pages.page.cannot-edit.var-missing');
        }

        // Load current page frontmatter
        $frontmatter = $page->frontmatter();

        // Preserve the title if not given
        if (!empty($data->get('title'))) {
            $frontmatter['title'] = $data->get('title');
        }

        // Get page defaults
        $defaults = $page->defaults();

        // Handle data from fields
        foreach ($fields as $field) {
            $default = array_key_exists($field->name(), $defaults) && $field->value() === $defaults[$field->name()];

            // Remove empty and default values
            if ($field->isEmpty() || $default) {
                unset($frontmatter[$field->name()]);
                continue;
            }

            // Set frontmatter value
            $frontmatter[$field->name()] = $field->value();
        }

        $content = str_replace("\r\n", "\n", $data->get('content'));

        $language = $data->get('language');

        // Validate language
        if (!empty($language) && !in_array($language, Formwork::instance()->config()->get('languages.available'), true)) {
            throw new TranslatedException('Invalid page language', 'pages.page.cannot-edit.invalid-language');
        }

        $differ = $frontmatter !== $page->frontmatter() || $content !== $page->rawContent() || $language !== $page->language();

        if ($differ) {
            $filename = $data->get('template');
            $filename .= empty($language) ? '' : '.' . $language;
            $filename .= Formwork::instance()->config()->get('content.extension');

            $fileContent = Str::wrap(YAML::encode($frontmatter), '---' . PHP_EOL) . $content;

            FileSystem::write($page->path() . $filename, $fileContent);
            FileSystem::touch(Formwork::instance()->config()->get('content.path'));

            // Update page with the new data
            $page->reload();

            // Set correct page language if it has changed
            if ($language !== $page->language()) {
                $page->setLanguage($language);
            }

            // Check if page number has to change
            if (!empty($page->date()) && $page->template()->scheme()->get('num') === 'date' && $page->num() !== (int) $page->date(self::DATE_NUM_FORMAT)) {
                $name = preg_replace(Page::NUM_REGEX, $page->date(self::DATE_NUM_FORMAT) . '-', $page->name());
                try {
                    $page = $this->changePageName($page, $name);
                } catch (RuntimeException $e) {
                    throw new TranslatedException('Cannot change page num', 'pages.page.cannot-change-num');
                }
            }
        }

        // Check if parent page has to change
        if ($page->parent() !== ($parent = $this->resolveParent($data->get('parent')))) {
            if ($parent === null) {
                throw new TranslatedException('Invalid parent page', 'pages.page.cannot-edit.invalid-parent');
            }
            $page = $this->changePageParent($page, $parent);
        }

        // Check if page template has to change
        if ($page->template()->name() !== ($template = $data->get('template'))) {
            if (!$this->site()->hasTemplate($template)) {
                throw new TranslatedException('Invalid page template', 'pages.page.cannot-edit.invalid-template');
            }
            $page = $this->changePageTemplate($page, $template);
        }

        // Check if page slug has to change
        if ($page->slug() !== ($slug = $data->get('slug'))) {
            if (!$this->validateSlug($slug)) {
                throw new TranslatedException('Invalid page slug', 'pages.page.cannot-edit.invalid-slug');
            }
            // Don't change index and error pages slug
            if ($page->isIndexPage() || $page->isErrorPage()) {
                throw new TranslatedException('Cannot change slug of index or error pages', 'pages.page.cannot-edit.index-or-error-page-slug');
            }
            if ($this->site()->findPage($page->parent()->route() . $slug . '/')) {
                throw new TranslatedException('A page with the same route already exists', 'pages.page.cannot-edit.already-exists');
            }
            $page = $this->changePageName($page, ltrim($page->num() . '-', '-') . $slug);
        }

        return $page;
    }

    /**
     * Process page uploads
     */
    protected function processPageUploads(Page $page): void
    {
        $uploader = new Uploader($page->path());
        $uploader->upload();
        $page->reload();

        foreach ($uploader->uploadedFiles() as $file) {
            $file = $page->files()->get($file);

            // Process JPEG and PNG images according to system options (e.g. quality)
            if (Formwork::instance()->config()->get('images.process_uploads') && in_array($file->mimeType(), ['image/jpeg', 'image/png'], true)) {
                $image = new Image($file->path());
                $image->saveOptimized();
                $page->reload();
            }
        }
    }

    /**
     * Ensure a page exists
     */
    protected function ensurePageExists(?Page $page, string $errorLanguageString): void
    {
        if ($page === null) {
            $this->notify($this->label($errorLanguageString), 'error');
            $this->redirectToReferer(302, '/pages/');
        }
    }

    /**
     * Make a page num according to 'date' or default mode
     *
     * @param Page|Site $parent
     * @param string    $mode   'date' for pages with a publish date
     */
    protected function makePageNum($parent, ?string $mode): string
    {
        if (!$parent instanceof Page && !$parent instanceof Site) {
            throw new InvalidArgumentException(__METHOD__ . '() accepts only instances of ' . Page::class . ' or ' . Site::class . ' as $parent argument');
        }
        switch ($mode) {
            case 'date':
                $num = date(self::DATE_NUM_FORMAT);
            break;
            default:
                $num = 0;
                foreach ($parent->children() as $child) {
                    $num = max($num, $child->num());
                }
                $num++;
            break;
        }
        return $num;
    }

    /**
     * Change the name of a page
     */
    protected function changePageName(Page $page, string $name): Page
    {
        $directory = dirname($page->path());
        $destination = FileSystem::joinPaths($directory, $name, DS);
        FileSystem::moveDirectory($page->path(), $destination);
        return new Page($destination);
    }

    /**
     * Change the parent of a page
     *
     * @param Page|Site $parent
     */
    protected function changePageParent(Page $page, $parent): Page
    {
        if (!$parent instanceof Page && !$parent instanceof Site) {
            throw new InvalidArgumentException(__METHOD__ . '() accepts only instances of ' . Page::class . ' or ' . Site::class . ' as $parent argument');
        }
        $destination = FileSystem::joinPaths($parent->path(), $page->name(), DS);
        FileSystem::moveDirectory($page->path(), $destination);
        return new Page($destination);
    }

    /**
     * Change page template
     */
    protected function changePageTemplate(Page $page, string $template): Page
    {
        $destination = $page->path() . $template . Formwork::instance()->config()->get('content.extension');
        FileSystem::move($page->path() . $page->filename(), $destination);
        $page->reload();
        return $page;
    }

    /**
     * Resolve parent page helper
     *
     * @param string $parent Page URI or '.' for site
     *
     * @return Page|Site|null
     */
    protected function resolveParent(string $parent)
    {
        if ($parent === '.') {
            return $this->site();
        }
        return $this->site()->findPage($parent);
    }

    /**
     * Validate page slug helper
     */
    protected function validateSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_REGEX, $slug);
    }

    /**
     * Return an array containing the available site languages as keys with proper labels as values
     */
    protected function availableSiteLanguages(): array
    {
        $languages = [];
        foreach (Formwork::instance()->config()->get('languages.available') as $code) {
            $languages[$code] = LanguageCodes::hasCode($code) ? LanguageCodes::codeToNativeName($code) . ' (' . $code . ')' : $code;
        }
        return $languages;
    }
}

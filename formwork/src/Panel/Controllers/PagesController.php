<?php

namespace Formwork\Panel\Controllers;

use Exception;
use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\FieldCollection;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\RequestData;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Images\Image;
use Formwork\Pages\Page;
use Formwork\Pages\Site;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Uploader;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;

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

        $this->modal('newPage', [
            'templates' => $this->site()->templates()->keys(),
            'pages'     => $this->site()->descendants()->sortBy('relativePath'),
        ]);

        $this->modal('deletePage');

        $pages = $this->site()->pages();

        $indexOffset = $pages->indexOf($this->site()->indexPage());

        if ($indexOffset !== null) {
            $pages->moveItem($indexOffset, 0);
        }

        return new Response($this->view('pages.index', [
            'title'     => $this->translate('panel.pages.pages'),
            'pagesList' => $this->view('pages.list', [
                'pages'     => $pages,
                'subpages'  => true,
                'class'     => 'pages-list-root',
                'parent'    => '.',
                'orderable' => $this->user()->permissions()->has('pages.reorder'),
                'headers'   => true,
            ]),
        ]));
    }

    /**
     * Pages@create action
     */
    public function create(): RedirectResponse
    {
        $this->ensurePermission('pages.create');

        $data = $this->request->input();

        // Let's create the page
        try {
            $page = $this->createPage($data);
            $this->panel()->notify($this->translate('panel.pages.page.created'), 'success');
        } catch (TranslatedException $e) {
            $this->panel()->notify($e->getTranslatedMessage(), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($page->route(), '/')]));
    }

    /**
     * Pages@edit action
     */
    public function edit(RouteParams $params): Response
    {
        $this->ensurePermission('pages.edit');

        $page = $this->site()->findPage($params->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotEdit.pageNotFound'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        if ($params->has('language')) {
            if (empty($this->config->get('system.languages.available'))) {
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => trim($page->route(), '/')]));
            }

            $language = $params->get('language');

            if (!in_array($language, $this->config->get('system.languages.available'), true)) {
                $this->panel()->notify($this->translate('panel.pages.page.cannotEdit.invalidLanguage', $language), 'error');
                return $this->redirect($this->generateRoute('panel.pages.edit.lang', ['page' => trim($page->route(), '/'), 'language' => $this->site()->languages()->default()]));

            }

            if ($page->languages()->available()->has($language)) {
                $page->setLanguage($language);
            }
        } elseif ($page->language() !== null) {
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

                // Validate fields against data
                $fields->setValues($data, null)->validate();

                // Update the page
                try {
                    $page = $this->updatePage($page, $data, $fields);
                    $this->panel()->notify($this->translate('panel.pages.page.edited'), 'success');
                } catch (TranslatedException $e) {
                    $this->panel()->notify($e->getTranslatedMessage(), 'error');
                }

                if (!$this->request->files()->isEmpty()) {
                    try {
                        $this->processPageUploads($this->request->files()->get('uploadedFile', []), $page);
                        $page->reload();

                    } catch (TranslatedException $e) {
                        $this->panel()->notify($this->translate('panel.uploader.error', $e->getTranslatedMessage()), 'error');
                    }
                }

                // Redirect if page route has changed
                if ($params->get('page') !== ($route = trim($page->route(), '/'))) {
                    return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $route]));
                }

                break;
        }

        $this->modal('changes');

        $this->modal('slug');

        $this->modal('images', [
            'page' => $page,
        ]);

        $this->modal('deletePage');

        $this->modal('deleteFile');

        $this->modal('renameFile');

        return new Response($this->view('pages.editor', [
            'title'           => $this->translate('panel.pages.editPage', $page->title()),
            'page'            => $page,
            'fields'          => $fields,
            'templates'       => $this->site()->templates()->keys(),
            'parents'         => $this->site()->descendants()->sortBy('relativePath'),
            'currentLanguage' => $params->get('language', $page->language()?->code()),
        ]));
    }

    /**
     * Pages@reorder action
     */
    public function reorder(): JsonResponse
    {
        $this->ensurePermission('pages.reorder');

        $data = $this->request->input();

        if (!$data->hasMultiple(['page', 'before', 'parent'])) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $parent = $this->resolveParent($data->get('parent'));
        if (!$parent->hasChildren()) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $pages = $parent->children();
        $keys = $pages->keys();

        $from = Arr::indexOf($keys, $data->get('page'));
        $to = Arr::indexOf($keys, $data->get('before'));

        if ($from === null || $to === null) {
            return JsonResponse::error($this->translate('panel.pages.page.cannotMove'));
        }

        $pages->moveItem($from, $to);

        foreach ($pages->values() as $i => $page) {
            $name = basename($page->relativePath());
            $newName = preg_replace(Page::NUM_REGEX, $i + 1 . '-', $name);
            if ($newName !== $name) {
                $this->changePageName($page, $newName);
            }
        }

        return JsonResponse::success($this->translate('panel.pages.page.moved'));
    }

    /**
     * Pages@delete action
     */
    public function delete(RouteParams $params): RedirectResponse
    {
        $this->ensurePermission('pages.delete');

        $page = $this->site()->findPage($params->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.pageNotFound'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        if ($params->has('language')) {
            $language = $params->get('language');
            if ($page->languages()->available()->has($language)) {
                $page->setLanguage($language);
            } else {
                $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.invalidLanguage', $language), 'error');
                return $this->redirectToReferer(default:  '/pages/');
            }
        }

        if (!$page->isDeletable()) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDelete.notDeletable'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        // Delete just the content file only if there are more than one language
        if ($params->has('language') && count($page->languages()->available()) > 1) {
            FileSystem::delete($page->contentFile()->path());
        } else {
            FileSystem::delete($page->path(), recursive: true);
        }

        $this->panel()->notify($this->translate('panel.pages.page.deleted'), 'success');

        // Don't redirect to referer if it's to Pages@edit
        if (!Str::startsWith(Uri::normalize($this->request->referer()), Uri::make(['path' => $this->panel()->uri('/pages/' . $params->get('page') . '/edit/')]))) {
            return $this->redirectToReferer(default:  '/pages/');
        }
        return $this->redirect($this->generateRoute('panel.pages'));
    }

    /**
     * Pages@uploadFile action
     */
    public function uploadFile(RouteParams $params): RedirectResponse
    {
        $this->ensurePermission('pages.uploadFiles');

        $page = $this->site()->findPage($params->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotUploadFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        if (!$this->request->files()->isEmpty()) {
            try {
                $this->processPageUploads($this->request->files()->getAll(), $page);
            } catch (TranslatedException $e) {
                $this->panel()->notify($this->translate('panel.uploader.error', $e->getTranslatedMessage()), 'error');
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));

            }
        }

        $this->panel()->notify($this->translate('panel.uploader.uploaded'), 'success');
        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));
    }

    /**
     * Pages@deleteFile action
     */
    public function deleteFile(RouteParams $params): RedirectResponse
    {
        $this->ensurePermission('pages.deleteFiles');

        $page = $this->site()->findPage($params->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDeleteFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        if (!$page->files()->has($params->get('filename'))) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotDeleteFile.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));
        }

        FileSystem::delete($page->path() . $params->get('filename'));

        $this->panel()->notify($this->translate('panel.pages.page.fileDeleted'), 'success');
        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));

    }

    /**
     * Pages@renameFile action
     */
    public function renameFile(RouteParams $params, Request $request): RedirectResponse
    {
        $this->ensurePermission('pages.renameFiles');

        $page = $this->site()->findPage($params->get('page'));

        if ($page === null) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.pageNotFound'), 'error');
            return $this->redirectToReferer(default:  '/pages/');
        }

        if (!$page->files()->has($params->get('filename'))) {
            $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));
        }

        $name = Str::slug(FileSystem::name($request->input()->get('filename')));
        $extension = FileSystem::extension($params->get('filename'));

        $newName = $name . '.' . $extension;

        $previousName = $params->get('filename');

        if ($newName !== $previousName) {
            if ($page->files()->has($newName)) {
                $this->panel()->notify($this->translate('panel.pages.page.cannotRenameFile.fileAlreadyExists'), 'error');
                return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));
            }

            FileSystem::move($page->path() . $previousName, $page->path() . $newName);
            $this->panel()->notify($this->translate('panel.pages.page.fileRenamed'), 'success');
        }

        return $this->redirect($this->generateRoute('panel.pages.edit', ['page' => $params->get('page')]));
    }

    /**
     * Create a new page
     */
    protected function createPage(RequestData $data): Page
    {
        // Ensure no required data is missing
        if (!$data->hasMultiple(['title', 'slug', 'template', 'parent'])) {
            throw new TranslatedException('Missing required POST data', 'panel.pages.page.cannotCreate.varMissing');
        }

        try {
            $parent = $this->resolveParent($data->get('parent'));
        } catch (RuntimeException) {
            throw new TranslatedException('Parent page not found', 'panel.pages.page.cannotCreate.invalidParent');
        }

        // Validate page slug
        if (!$this->validateSlug($data->get('slug'))) {
            throw new TranslatedException('Invalid page slug', 'panel.pages.page.cannotCreate.invalidSlug');
        }

        $route = $parent->route() . $data->get('slug') . '/';

        // Ensure there isn't a page with the same route
        if ($this->site()->findPage($route)) {
            throw new TranslatedException('A page with the same route already exists', 'panel.pages.page.cannotCreate.alreadyExists');
        }

        // Validate page template
        if (!$this->site()->templates()->has($data->get('template'))) {
            throw new TranslatedException('Invalid page template', 'panel.pages.page.cannotCreate.invalidTemplate');
        }

        $scheme = $this->app->schemes()->get('pages.' . $data->get('template'));

        $path = $parent->path() . $this->makePageNum($parent, $scheme->options()->get('num')) . '-' . $data->get('slug') . '/';

        FileSystem::createDirectory($path, recursive: true);

        $language = $this->site()->languages()->default();

        $filename = $data->get('template');
        $filename .= empty($language) ? '' : '.' . $language;
        $filename .= $this->config->get('system.content.extension');

        FileSystem::createFile($path . $filename);

        $contentData = [
            'title'     => $data->get('title'),
            'published' => false,
        ];

        $fileContent = Str::wrap(Yaml::encode($contentData), '---' . PHP_EOL);

        FileSystem::write($path . $filename, $fileContent);

        return $this->site()->retrievePage($path);
    }

    /**
     * Update a page
     */
    protected function updatePage(Page $page, RequestData $data, FieldCollection $fields): Page
    {
        // Ensure no required data is missing
        if (!$data->hasMultiple(['title', 'content'])) {
            throw new TranslatedException('Missing required POST data', 'panel.pages.page.cannotEdit.varMissing');
        }

        // Load current page frontmatter
        $frontmatter = $page->contentFile()->frontmatter();

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
            if ($field->isEmpty() || $default || in_array($field->name(), self::IGNORED_FIELD_NAMES, true)) {
                unset($frontmatter[$field->name()]);
                continue;
            }

            // Set frontmatter value
            $frontmatter[$field->name()] = $field->value();
        }

        $content = str_replace("\r\n", "\n", $data->get('content'));

        $language = $data->get('language');

        // Validate language
        if (!empty($language) && !in_array($language, $this->config->get('system.languages.available'), true)) {
            throw new TranslatedException('Invalid page language', 'panel.pages.page.cannotEdit.invalidLanguage');
        }

        $differ = $frontmatter !== $page->contentFile()->frontmatter() || $content !== $page->data()['content'] || $language !== $page->language();

        if ($differ) {
            $filename = $data->get('template');
            $filename .= empty($language) ? '' : '.' . $language;
            $filename .= $this->config->get('system.content.extension');

            $fileContent = Str::wrap(Yaml::encode($frontmatter), '---' . PHP_EOL) . $content;

            FileSystem::write($page->path() . $filename, $fileContent);
            FileSystem::touch($this->site()->path());

            // Update page with the new data
            $page->reload();

            // Set correct page language if it has changed
            if (!empty($language) && $language !== $page->language()?->code()) {
                $page->setLanguage($language);
            }

            // Check if page number has to change

            $timestamp = isset($page->data()['publishDate'])
                ? Date::toTimestamp($page->data()['publishDate'])
                : $page->contentFile()->lastModifiedTime();

            if ($page->scheme()->options()->get('num') === 'date' && $page->num() !== ($num = (int) date(self::DATE_NUM_FORMAT, $timestamp))) {
                $name = preg_replace(Page::NUM_REGEX, $num . '-', basename($page->relativePath()));
                try {
                    $page = $this->changePageName($page, $name);
                } catch (RuntimeException $e) {
                    throw new TranslatedException('Cannot change page num', 'panel.pages.page.cannotChangeNum');
                }
            }
        }

        // Check if parent page has to change
        try {
            if ($page->parent() !== ($parent = $this->resolveParent($data->get('parent')))) {
                $page = $this->changePageParent($page, $parent);
            }
        } catch (RuntimeException) {
            throw new TranslatedException('Invalid parent page', 'panel.pages.page.cannotEdit.invalidParent');
        }

        // Check if page template has to change
        if ($page->template()->name() !== ($template = $data->get('template'))) {
            if (!$this->site()->templates()->has($template)) {
                throw new TranslatedException('Invalid page template', 'panel.pages.page.cannotEdit.invalidTemplate');
            }
            $page = $this->changePageTemplate($page, $template);
        }

        // Check if page slug has to change
        if ($page->slug() !== ($slug = $data->get('slug'))) {
            if (!$this->validateSlug($slug)) {
                throw new TranslatedException('Invalid page slug', 'panel.pages.page.cannotEdit.invalidSlug');
            }
            // Don't change index and error pages slug
            if ($page->isIndexPage() || $page->isErrorPage()) {
                throw new TranslatedException('Cannot change slug of index or error pages', 'panel.pages.page.cannotEdit.indexOrErrorPageSlug');
            }
            if ($this->site()->findPage($page->parent()->route() . $slug . '/')) {
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
     */
    protected function processPageUploads(array $files, Page $page): void
    {
        $uploader = new Uploader($this->config);

        foreach ($files as $file) {
            if (!$file->isUploaded()) {
                throw new Exception(sprintf('Cannot upload file "%s"', $file->fieldName()));
            }
            $uploadedFile = $uploader->upload($file, $page->path());
            // Process JPEG and PNG images according to system options (e.g. quality)
            if ($this->config->get('system.uploads.processImages') && in_array($uploadedFile->mimeType(), ['image/jpeg', 'image/png'], true)) {
                $image = new Image($uploadedFile->path(), $this->config->get('system.images'));
                $image->save();
            }
        }
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
        $directory = dirname($page->path());
        $destination = FileSystem::joinPaths($directory, $name, DS);
        FileSystem::moveDirectory($page->path(), $destination);
        return $this->site()->retrievePage($destination);
    }

    /**
     * Change the parent of a page
     */
    protected function changePageParent(Page $page, Page|Site $parent): Page
    {
        $destination = FileSystem::joinPaths($parent->path(), basename($page->relativePath()), DS);
        FileSystem::moveDirectory($page->path(), $destination);
        return $this->site()->retrievePage($destination);
    }

    /**
     * Change page template
     */
    protected function changePageTemplate(Page $page, string $template): Page
    {
        $destination = $page->path() . $template . $this->config->get('system.content.extension');
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

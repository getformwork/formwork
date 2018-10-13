<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Uploader;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Core\Page;
use Formwork\Core\Site;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use RuntimeException;

class Pages extends AbstractController
{
    const DATE_NUM_FORMAT = 'Ymd';

    public function index()
    {
        $this->ensurePermission('pages.index');

        $this->modal('newPage', array(
            'templates' => $this->site()->templates(),
            'pages' => $this->site()->descendants()->sort('path')
        ));

        $this->modal('deletePage');

        $this->view('admin', array(
            'title' => $this->label('pages.pages'),
            'content' => $this->view('pages.index', array(
                'pagesList' => $this->view('pages.list', array(
                    'pages' => $this->site()->pages(),
                    'subpages' => true,
                    'class' => 'pages-list-top',
                    'parent' => '.',
                    'sortable' => 'true'
                ), false)
            ), false)
        ));
    }

    public function create()
    {
        $this->ensurePermission('pages.create');

        $data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        if (!$data->has(array('title', 'slug', 'template', 'parent'))) {
            $this->notify($this->label('pages.page.cannot-create.var-missing'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        // Ensure there isn't a page with the same uri
        if ($this->site()->findPage($data->get('slug'))) {
            $this->notify($this->label('pages.page.cannot-create.already-exists'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        $parent = $this->resolveParent($data->get('parent'));

        if (is_null($parent)) {
            $this->notify($this->label('pages.page.cannot-create.invalid-parent'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        $scheme = $this->scheme($data->get('template'));

        $path = $parent->path() . $this->makePageNum($parent, $scheme->get('num')) . '-' . $data->get('slug') . DS;

        // Let's create the page
        try {
            $newPage = $this->createPage($path, $data->get('template'), $data->get('title'));
            $this->notify($this->label('pages.page.created'), 'success');
            $this->redirect('/pages/' . trim($newPage->slug(), '/') . '/edit/', 302, true);
        } catch (RuntimeException $e) {
            $this->notify($this->label('pages.page.cannot-create'), 'error');
            $this->redirect('/pages/', 302, true);
        }
    }

    public function edit(RouteParams $params)
    {
        $this->ensurePermission('pages.edit');

        $page = $this->site()->findPage($params->get('page'));

        // Ensure the page exists
        if (!$page) {
            $this->notify($this->label('pages.page.cannot-edit.page-missing'), 'error');
            $this->redirect('/pages/', 302, true);
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

                // Ensure no required data is missing
                if (!$data->has(array('title', 'content'))) {
                    $this->notify($this->label('pages.page.cannot-edit.var-missing'), 'error');
                    $this->redirect('/pages/' . $params->get('page') . '/edit/', 302, true);
                }

                // Update the page
                $page = $this->updatePage($page, $data, $fields);

                break;
        }

        $this->modal('changes');

        $this->modal('images', array(
            'page' => $page
        ));

        $this->modal('deletePage');

        $this->modal('deleteFile');

        $this->view('admin', array(
            'title' => $this->label('pages.edit-page', $page->title()),
            'content' => $this->view('pages.editor', array(
                'page' => $page,
                'fields' => $this->fields($fields, false),
                'templates' => $this->site()->templates(),
                'parents' => $this->site()->descendants()->sort('path'),
                'datePickerOptions' => array(
                    'dayLabels' => $this->label('date.weekdays.short'),
                    'monthLabels' => $this->label('date.months.long'),
                    'weekStarts' => $this->option('date.week_starts'),
                    'todayLabel' => $this->label('date.today'),
                    'format' => strtr(
                        $this->option('date.format'),
                        array('Y' => 'YYYY', 'm' => 'MM', 'd' => 'DD', 'H' => 'hh', 'i' => 'mm', 's' => 'ss', 'A' => 'a')
                    )
                )
            ), false)
        ));
    }

    public function reorder()
    {
        $this->ensurePermission('pages.reorder');

        $data = new DataGetter(HTTPRequest::postData());

        if (!$data->has(array('parent', 'from', 'to'))) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        if (!is_numeric($data->get('from')) || !is_numeric($data->get('to'))) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        $parent = $this->resolveParent($data->get('parent'));
        if (is_null($parent) || !$parent->hasChildren()) {
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
            $id = $page->id();
            if (is_null($id)) {
                continue;
            }
            $newId = preg_replace(Page::NUM_REGEX, $i + 1 . '-', $id);
            if ($newId !== $id) {
                $this->changePageId($page, $newId);
            }
        }

        JSONResponse::success($this->label('pages.page.moved'))->send();
    }

    public function delete(RouteParams $params)
    {
        $this->ensurePermission('pages.delete');

        try {
            $page = $this->site()->findPage($params->get('page'));
            if (!$page) {
                throw new LocalizedException('Page ' . $params->get('page') . ' not found', 'pages.page.not-found');
            }
            if (!$page->isDeletable()) {
                throw new LocalizedException('Page ' . $page . ' is not deletable', 'pages.page.cannot-delete.not-deletable');
            }

            FileSystem::delete($page->path(), true);

            $this->notify($this->label('pages.page.deleted'), 'success');
            $this->redirect('/pages/');
        } catch (LocalizedException $e) {
            $this->notify($this->label('pages.page.cannot-delete', $e->getLocalizedMessage()), 'error');
            $this->redirectToReferer(302, true, '/pages/');
        }
    }

    public function uploadFile(RouteParams $params)
    {
        $this->ensurePermission('pages.upload_file');

        if (HTTPRequest::hasFiles()) {
            try {
                $page = $this->site()->findPage($params->get('page'));
                if (!$page) {
                    throw new LocalizedException('Page ' . $params->get('page') . ' not found', 'pages.page.not-found');
                }

                $uploader = new Uploader($page->path());
                $uploader->upload();

                $this->notify($this->label('uploader.uploaded'), 'success');
                $this->redirect('/pages/' . $params->get('page') . '/edit/');
            } catch (LocalizedException $e) {
                $this->notify($this->label('uploader.error', $e->getLocalizedMessage()), 'error');
                $this->redirect('/pages/' . $params->get('page') . '/edit/');
            }
        }
    }

    public function deleteFile(RouteParams $params)
    {
        $this->ensurePermission('pages.delete_file');

        try {
            $page = $this->site()->findPage($params->get('page'));
            if (!$page) {
                throw new LocalizedException('Page ' . $params->get('page') . ' not found', 'pages.page.not-found');
            }
            if (!$page->files()->has($params->get('filename'))) {
                throw new LocalizedException('File not found', 'pages.page.cannot-delete-file.file-not-found');
            }

            FileSystem::delete($page->path() . $params->get('filename'));

            $this->notify($this->label('pages.page.file-deleted'), 'success');
            $this->redirect('/pages/' . $params->get('page') . '/edit/');
        } catch (LocalizedException $e) {
            $this->notify($this->label('pages.page.cannot-delete-file', $e->getLocalizedMessage()), 'error');
            $this->redirect('/pages/');
        }
    }

    protected function createPage($path, $template, $title)
    {
        FileSystem::createDirectory($path, true);
        $filename = $template . $this->option('content.extension');
        FileSystem::createFile($path . $filename);
        $frontmatter = array(
            'title' => $title
        );
        $fileContent = '---' . PHP_EOL;
        $fileContent .= YAML::encode($frontmatter);
        $fileContent .= '---' . PHP_EOL;
        FileSystem::write($path . $filename, $fileContent);
        return new Page($path);
    }

    protected function updatePage(Page $page, DataGetter $data, Fields $fields)
    {
        // Load current page frontmatter
        $frontmatter = $page->frontmatter();

        // Preserve the title if not given
        if (!empty($data->get('title'))) {
            $frontmatter['title'] = $data->get('title');
        }

        // Handle data from fields
        foreach ($fields as $field) {
            $empty = is_null($field->value()) || $field->value() === '';
            $default = isset($page->defaults()[$field->name()]) && $field->value() === $page->defaults()[$field->name()];

            // Remove empty and default values
            if ($empty || $default) {
                unset($frontmatter[$field->name()]);
                continue;
            }

            // Set frontmatter value
            $frontmatter[$field->name()] = $field->value();
        }

        $content = str_replace("\r\n", "\n", $data->get('content'));

        $differ = $frontmatter !== $page->frontmatter() || $content !== $page->rawContent();

        if ($differ) {
            $fileContent = '---' . PHP_EOL;
            $fileContent .= YAML::encode($frontmatter);
            $fileContent .= '---' . PHP_EOL;
            $fileContent .= $data->get('content');
            FileSystem::write($page->path() . $page->filename(), $fileContent);
            FileSystem::touch($this->option('content.path'));

            // Update page with the new data
            $page->reload();

            // Check if page number has to change
            if (!empty($page->date()) && $page->template()->scheme()->get('num') === 'date') {
                if ($page->num() !== $page->date(self::DATE_NUM_FORMAT)) {
                    $newId = preg_replace(Page::NUM_REGEX, $page->date(self::DATE_NUM_FORMAT) . '-', $page->id());
                    try {
                        $this->changePageId($page, $newId);
                    } catch (RuntimeException $e) {
                        $this->notify($this->label('pages.page.cannot-change-num'), 'error');
                        $this->redirect('/pages/' . trim($page->slug(), '/') . '/edit/', 302, true);
                    }
                }
            }
        }

        $this->notify($this->label('pages.page.edited'), 'success');

        if ($page->parent() !== ($newParent = $this->resolveParent($data->get('parent')))) {
            $page = $this->changePageParent($page, $newParent);
            $this->redirect('/pages/' . trim($page->slug(), '/') . '/edit/', 302, true);
        }

        if ($page->template()->name() !== ($newTemplate = $data->get('template'))) {
            $this->changePageTemplate($page, $newTemplate);
            $this->redirect('/pages/' . trim($page->slug(), '/') . '/edit/', 302, true);
        }

        return $page;
    }

    protected function makePageNum($parent, $mode)
    {
        if (!($parent instanceof Page || $parent instanceof Site)) {
            throw new InvalidArgumentException(__METHOD__ . ' accepts only instances of ' . Page::class . ' or ' . Site::class . ' as $parent argument');
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

    protected function changePageId(Page $page, $id)
    {
        $directory = FileSystem::dirname($page->path());
        $destination = $directory . DS . $id . DS;
        FileSystem::moveDirectory($page->path(), $destination);
        return new Page($destination);
    }

    protected function changePageParent(Page $page, $parent)
    {
        $destination = $parent->path() . FileSystem::basename($page->path()) . DS;
        FileSystem::moveDirectory($page->path(), $destination);
        return new Page($destination);
    }

    protected function changePageTemplate(Page $page, $template)
    {
        $destination = $page->path() . $template . $this->option('content.extension');
        FileSystem::move($page->path() . $page->filename(), $destination);
    }

    protected function resolveParent($parent)
    {
        if ($parent === '.') {
            return $this->site();
        }
        return $this->site()->findPage($parent);
    }
}

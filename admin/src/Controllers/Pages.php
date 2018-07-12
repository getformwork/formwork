<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Uploader;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Data\DataGetter;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use Exception;
use Spyc;

class Pages extends AbstractController
{
    const DATE_NUM_FORMAT = 'Ymd';

    protected $site;

    protected $page;

    protected $fields;

    public function __construct()
    {
        parent::__construct();
        $this->site = Formwork::instance()->site();
    }

    public function list()
    {
        Admin::instance()->ensureLogin();
        $list = $this->view(
            'pages.list',
            array(
                'pages' => $this->site->pages(),
                'subpages' => true,
                'class' => 'pages-list-top',
                'parent' => '.',
                'sortable' => 'true'
            ),
            false
        );

        $content = $this->view(
            'pages.index',
            array(
                'pagesList' => $list,
                'csrfToken' => CSRFToken::get()
            ),
            false
        );

        $modals[] = $this->view(
            'modals.newPage',
            array(
                'templates' => $this->site->templates(),
                'pages' => $this->site->descendants()->sort('path'),
                'csrfToken' => CSRFToken::get()
            ),
            false
        );

        $modals[] = $this->view(
            'modals.deletePage',
            array(
                'csrfToken' => CSRFToken::get()
            ),
            false
        );

        $this->view('admin', array(
            'location' => 'pages',
            'content' => $content,
            'modals' => implode($modals),
            'csrfToken' => CSRFToken::get()
        ));
    }

    public function new(RouteParams $params)
    {
        Admin::instance()->ensureLogin();
        $this->data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        foreach (array('title', 'slug', 'template', 'parent') as $var) {
            if (!$this->data->has($var)) {
                $this->notify($this->label('pages.page.cannot-create.var-missing', $var), 'error');
                $this->redirect('/pages/', 302, true);
            }
        }

        // Ensure there isn't a page with the same uri
        if ($this->site->findPage($this->data->get('slug'))) {
            $this->notify($this->label('pages.page.cannot-create.already-exists'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        $parent = $this->resolveParent($this->data->get('parent'));

        if (is_null($parent)) {
            $this->notify($this->label('pages.page.cannot-create.invalid-parent'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        $scheme = $this->scheme($this->data->get('template'));

        $path = $parent->path() . $this->makePageNum($parent, $scheme->get('num')) . '-' . $this->data->get('slug') . DS;

        // Let's create the page
        try {
            $newPage = $this->createPage($path, $this->data->get('template'), $this->data->get('title'));
            $this->notify($this->label('pages.page.created'), 'success');
            $this->redirect('/pages/' . trim($newPage->slug(), '/') . '/edit/', 302, true);
        } catch (Exception $e) {
            $this->notify($this->label('pages.page.cannot-create'), 'error');
            $this->redirect('/pages/', 302, true);
        }
    }

    public function edit(RouteParams $params)
    {
        Admin::instance()->ensureLogin();

        $this->page = $this->site->findPage($params->get('page'));

        // Ensure the page exists
        if (!$this->page) {
            $this->notify($this->label('pages.page.cannot-edit.page-missing'), 'error');
            $this->redirect('/pages/', 302, true);
        }

        // Load page fields
        $this->fields = new Fields($this->page->template()->scheme()->get('fields'));

        switch (HTTPRequest::method()) {
            case 'GET':
                // Load data from the page itself
                $this->data = new DataGetter($this->page->data());

                // Validate fields against data
                $this->fields->validate($this->data);

                break;

            case 'POST':
                // Load data from POST variables
                $this->data = new DataGetter(HTTPRequest::postData());

                // Validate fields against data
                $this->fields->validate($this->data);

                // Ensure no required data is missing
                foreach (array('title', 'content') as $var) {
                    if (!$this->data->has($var)) {
                        $this->notify($this->label('pages.page.cannot-edit.var-missing', $var), 'error');
                        $this->redirect('/pages/' . $params->get('page') . '/edit/', 302, true);
                    }
                }

                // Update the page
                $this->page = $this->updatePage($this->page, $this->data);

                break;
        }

        $modals[] = $this->view(
            'modals.images',
            array(
                'csrfToken' => CSRFToken::get(),
                'page' => $this->page
            ),
            false
        );

        $modals[] = $this->view(
            'modals.deleteFile',
            array(
                'csrfToken' => CSRFToken::get()
            ),
            false
        );

        $modals[] = $this->view(
            'modals.changes',
            array(),
            false
        );

        $adminData = array(
            'location' => 'pages',
            'content' => $this->view(
                'pages.editor',
                array(
                    'csrfToken' => CSRFToken::get(),
                    'page' => $this->page
                ),
                false
            ),
            'modals' => implode($modals)
        );

        $this->view('admin', $adminData);
    }

    public function reorder()
    {
        Admin::instance()->ensureLogin();

        $this->data = new DataGetter(HTTPRequest::postData());

        foreach (array('parent', 'from', 'to') as $var) {
            if (!$this->data->has($var)) {
                JSONResponse::error($this->label('pages.page.cannot-move'))->send();
            }
        }

        if (!is_numeric($this->data->get('from')) || !is_numeric($this->data->get('to'))) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        $parent = $this->resolveParent($this->data->get('parent'));
        if (is_null($parent) || !$parent->hasChildren()) {
            JSONResponse::error($this->label('pages.page.cannot-move'))->send();
        }

        $pages = $parent->children()->toArray();

        $from = max(0, $this->data->get('from'));
        $to = max(0, $this->data->get('to'));
        if ($to == $from) {
            return;
        }

        array_splice($pages, $to, 0, array_splice($pages, $from, 1));

        foreach ($pages as $i => $page) {
            $id = $page->id();
            if (is_null($id)) {
                continue;
            }
            $newId = preg_replace('/^(\d+)-/', $i + 1 . '-', $id);
            if ($newId != $id) {
                $this->changePageId($page, $newId);
            }
        }

        JSONResponse::success($this->label('pages.page.moved'))->send();
    }

    public function delete(RouteParams $params)
    {
        Admin::instance()->ensureLogin();
        try {
            $page = $this->site->findPage($params->get('page'));
            if (!$page) {
                throw new Exception($this->label('pages.page.not-found'));
            }
            if (!$page->isDeletable()) {
                throw new Exception($this->label('pages.page.cannot-delete.not-deletable'));
            }

            FileSystem::delete($page->path(), true);

            $this->notify($this->label('pages.page.deleted'), 'success');
            $this->redirect('/pages/');
        } catch (Exception $e) {
            $this->notify($this->label('pages.page.cannot-delete', $e->getMessage()), 'error');
            if (!is_null(HTTPRequest::referer()) && HTTPRequest::referer() != HTTPRequest::uri()) {
                Header::redirect(HTTPRequest::referer(), 302, true);
            } else {
                $this->redirect('/pages/');
            }
        }
    }

    public function uploadFile(RouteParams $params)
    {
        if (HTTPRequest::hasFiles()) {
            try {
                $page = $this->site->findPage($params->get('page'));
                if (!$page) {
                    throw new Exception($this->label('pages.page.not-found'));
                }

                $uploader = new Uploader($page->path());
                $uploader->upload();

                $this->notify($this->label('uploader.uploaded'), 'success');
                $this->redirect('/pages/' . $params->get('page') . '/edit/');
            } catch (Exception $e) {
                $this->notify($this->label('uploader.error', $e->getMessage()), 'error');
                $this->redirect('/pages/' . $params->get('page') . '/edit/');
            }
        }
    }

    public function deleteFile(RouteParams $params)
    {
        Admin::instance()->ensureLogin();
        try {
            $page = $this->site->findPage($params->get('page'));
            if (!$page) {
                throw new Exception($this->label('pages.page.not-found'));
            }
            if (!$page->files()->has($params->get('filename'))) {
                throw new Exception('Invalid file name');
            }

            FileSystem::delete($page->path() . $params->get('filename'));

            $this->notify($this->label('pages.page.file-deleted'), 'success');
            $this->redirect('/pages/' . $params->get('page') . '/edit/');
        } catch (Exception $e) {
            $this->notify($this->label('pages.page.cannot-delete-file', $e->getMessage()), 'error');
            $this->redirect('/pages/');
        }
    }

    protected function createPage($path, $template, $title)
    {
        FileSystem::createDirectory($path);
        $filename = $template . Formwork::instance()->option('content.extension');
        FileSystem::createFile($path . $filename);
        $frontmatter = array(
            'title' => $title
        );
        $fileContent = Spyc::YAMLDump($frontmatter, false, 0);
        $fileContent .= '---' . PHP_EOL;
        FileSystem::write($path . $filename, $fileContent);
        return new Page($path);
    }

    protected function updatePage(Page $page, DataGetter $data)
    {
        // Load current page frontmatter
        $frontmatter = $page->frontmatter();

        // Preserve the title if not given
        if (!empty($data->get('title'))) {
            $frontmatter['title'] = $data->get('title');
        }

        // Handle data from fields
        foreach ($this->fields as $field) {
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
            $fileContent = Spyc::YAMLDump($frontmatter, false, 0);
            $fileContent .= '---' . PHP_EOL;
            $fileContent .= $data->get('content');
            FileSystem::write($page->path() . $page->filename(), $fileContent);
            FileSystem::touch(Formwork::instance()->option('content.path'));

            // Update page with the new data
            $page->reload();

            // Check if page number has to change
            if (!empty($page->date()) && $page->template()->scheme()->get('num') == 'date') {
                if ($page->num() != $page->date(self::DATE_NUM_FORMAT)) {
                    $newId = preg_replace('/^(\d+-)/', $page->date(self::DATE_NUM_FORMAT) . '-', $page->id());
                    try {
                        $this->changePageId($page, $newId);
                    } catch (Exception $e) {
                        $this->notify($this->label('pages.page.cannot-change-num'), 'error');
                        $this->redirect('/pages/' . trim($page->slug(), '/') . '/edit/', 302, true);
                    }
                }
            }
        }

        $this->notify($this->label('pages.page.edited'), 'success');
        return $page;
    }

    protected function makePageNum(Page $parent, $mode)
    {
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

    protected function field($fieldName, $render = true)
    {
        $field = $this->fields->get($fieldName);
        return parent::field($field, $render);
    }

    protected function resolveParent($parent)
    {
        if ($parent == '.') {
            return $this->site;
        }
        return $this->site->findPage($parent);
    }
}

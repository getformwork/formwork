<?php

namespace Formwork\Core;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;

class Site extends Page {

    public static $storage;

    protected $templates;

    protected $templatesPath;

    public function defaults() {
        return array(
            'lang'          => 'en',
            'title'         => 'Formwork'
        );
    }

    public function __construct($data) {
        $this->path = Formwork::instance()->option('content.path');
        $this->templatesPath = Formwork::instance()->option('templates.path');
        $this->uri = HTTPRequest::root();
        $this->data = array_merge($this->defaults(), $data);
        $this->loadTemplates();
    }

    public function uri($path = null) {
        if (is_null($path)) return $this->uri;
        return $this->uri . ltrim($path, '/');
    }

    protected function loadTemplates() {
        foreach (FileSystem::list($this->templatesPath) as $item) {
            $path = $this->templatesPath() . $item;
            if (FileSystem::isFile($path)) {
                $templates[FileSystem::name($item)] = $path;
            }
        }
        $this->templates = $templates;
    }

    public function templates() {
        return array_keys($this->templates);
    }

    public function hasTemplate($template) {
        return in_array($template, $this->templates());
    }

    public function modifiedSince($time) {
        return FileSystem::directoryModifiedSince($this->path, $time);
    }

    public function parent() {
        return null;
    }

    public function parents() {
        return null;
    }

    public function pages() {
        return $this->children();
    }

    public function alias($uri) {
        if ($this->has('aliases')) {
            $uri = trim($uri, '/');
            if (isset($this->data['aliases'][$uri])) return $this->data['aliases'][$uri];
        }
    }

    public function errorPage($render = false) {
        $errorPage = $this->findPage(Formwork::instance()->option('pages.error'));
        if ($render) {
            Header::status(404);
            $errorPage->render();
            exit;
        }
        return $errorPage;
    }

    public function isSite() {
        return true;
    }

    public function __toString() {
        return 'site';
    }

    public function findPage($page) {
        if ($page == '/') return $this->findPage(Formwork::instance()->option('pages.index'));

        $components = explode('/', trim($page, '/'));
        $path = Formwork::instance()->option('content.path');

        foreach ($components as $component) {
            $found = false;
            foreach (FileSystem::listDirectories($path) as $dir) {
                if (preg_replace('/^\d+-/', '', $dir) === $component) {
                    $path = $path . $dir . DS;
                    $found = true;
                    break;
                }
            }
            if (!$found) return null;
        }

        if (isset(static::$storage[$path])) {
            $page = static::$storage[$path];
        } else {
            $page = new Page($path);
            static::$storage[$path] = $page;
        }

        return !$page->empty() ? $page : null;
    }

}

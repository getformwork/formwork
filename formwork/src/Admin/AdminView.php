<?php

namespace Formwork\Admin;

use Formwork\Assets;
use Formwork\Formwork;
use Formwork\Parsers\PHP;
use Formwork\View\View;

class AdminView extends View
{
    /**
     * @inheritdoc
     */
    protected const TYPE = 'admin view';

    /**
     * @inheritdoc
     */
    protected static $helpers = [];

    /**
     * View assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * @inheritdoc
     */
    public function path(): string
    {
        return Formwork::instance()->config()->get('views.paths.admin');
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if ($this->assets !== null) {
            return $this->assets;
        }
        return $this->assets = new Assets(ADMIN_PATH . 'assets' . DS, Admin::instance()->realUri('/assets/'));
    }

    /**
     * @inheritdoc
     */
    protected function defaults(): array
    {
        return [
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site(),
            'admin'    => Admin::instance()
        ];
    }

    /**
     * @inheritdoc
     */
    protected function helpers(): array
    {
        return PHP::parseFile(ADMIN_PATH . 'helpers.php') + parent::helpers();
    }
}

<div id="newPageModal" class="modal" aria-labelledby="newPageModalLabel">
    <div class="modal-container">
        <form action="<?= $panel->uri('/pages/new/') ?>" method="post">
            <div class="modal-header">
                <div class="caption" id="newPageModalLabel"><?= $this->translate('panel.pages.newPage') ?></div>
            </div>
            <div class="modal-content">
                <label class="form-label form-label-required" for="page-title"><?= $this->translate('panel.pages.newPage.title') ?>:</label>
                <input class="form-input" id="page-title" type="text" required name="title" autofocus>
                <label class="form-label form-label-required" for="page-slug"><?= $this->translate('panel.pages.newPage.slug') ?>:</label>
                <span class="form-label-suggestion">(<?= $this->translate('panel.pages.newPage.slugSuggestion') ?>)</span>
                <input class="form-input" id="page-slug" type="text" required name="slug">
                <label class="form-label form-label-required" for="page-parent"><?= $this->translate('panel.pages.newPage.parent') ?>:</label>
                <select class="form-select" id="page-parent" name="parent">
                    <option value="." selected><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
                    <?php foreach ($pages as $page): ?>
                        <?php $scheme = $app->schemes()->get('pages.' . $page->template()->name()) ?>
                        <?php if (!$scheme->options()->get('children', true)): ?>
                            <?php continue ?>
                        <?php endif ?>
                        <option value="<?= $page->route() ?>" <?php if ($scheme->options()->has('children.templates')): ?> data-allowed-templates="<?= implode(', ', $scheme->options()->get('children.templates')) ?>" <?php endif ?>><?= str_repeat('— ', $page->level() - 1) . $page->title() ?></option>
                    <?php endforeach ?>
                </select>
                <label class="form-label" form-label-required" for="page-template"><?= $this->translate('panel.pages.newPage.template') ?>:</label>
                <select class="form-select" id="page-template" name="template">
                    <?php foreach ($templates as $template): ?>
                        <?php $scheme = $app->schemes()->get('pages.' . $template) ?>
                        <option value="<?= $template ?>" <?php if ($scheme->isDefault()): ?> selected<?php endif ?>><?= $scheme->title() ?></option>
                    <?php endforeach ?>
                </select>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" data-dismiss="newPageModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button button-accent button-right"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            </div>
        </form>
    </div>
</div>

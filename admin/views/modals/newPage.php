<div id="newPageModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->translate('pages.new-page') ?></h3>
        <form action="<?= $admin->uri('/pages/new/') ?>" method="post">
            <label class="label-required" for="page-title"><?= $this->translate('pages.new-page.title') ?>:</label>
            <input id="page-title" type="text" required name="title" autofocus>
            <label class="label-required" for="page-slug"><?= $this->translate('pages.new-page.slug') ?>:</label>
            <span class="label-suggestion">(<?= $this->translate('pages.new-page.slug-suggestion') ?>)</span>
            <input id="page-slug" type="text" required name="slug">
            <label class="label-required" for="page-parent"><?= $this->translate('pages.new-page.parent') ?>:</label>
            <select id="page-parent" name="parent">
                <option value="." selected><?= $this->translate('pages.new-page.site') ?> (/)</option>
<?php
                foreach ($pages as $page):
                    $scheme = $admin->scheme($page->template()->name());
                    if (!$scheme->get('children', true)) continue;
?>
                <option value="<?= $page->route() ?>"<?php if ($scheme->has('children.templates')): ?> data-allowed-templates="<?= implode(', ', $scheme->get('children.templates'))?>"<?php endif; ?>><?= str_repeat('— ', $page->level() - 1) . $page->title() ?></option>
<?php
                endforeach;
?>
            </select>
            <label class="label-required" for="page-template"><?= $this->translate('pages.new-page.template') ?>:</label>
            <select id="page-template" name="template">
<?php
            foreach ($templates as $template):
                $scheme = $admin->scheme($template);
?>
                <option value="<?= $template ?>"<?php if ($scheme->isDefault()): ?> selected<?php endif; ?>><?= $scheme->title() ?></option>
<?php
            endforeach;
?>
            </select>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            <div class="separator"></div>
            <button type="button" data-dismiss="newPageModal"><?= $this->translate('modal.action.cancel') ?></button>
            <button type="submit" class="button-accent button-right"><i class="i-check"></i> <?= $this->translate('modal.action.continue') ?></button>
        </form>
    </div>
</div>

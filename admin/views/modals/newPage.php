<div id="newPageModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->label('pages.new-page') ?></h3>
        <form action="<?= $this->uri('/pages/new/') ?>" method="post">
            <label class="label-required" for="page-title"><?= $this->label('pages.new-page.title') ?>:</label>
            <input id="page-title" type="text" required name="title" autofocus>
            <label class="label-required" for="page-slug"><?= $this->label('pages.new-page.uri') ?>:</label>
            <span class="label-suggestion">(<?= $this->label('pages.new-page.uri-suggestion') ?>)</span>
            <input id="page-slug" type="text" required name="slug">
            <label class="label-required" for="page-parent"><?= $this->label('pages.new-page.parent') ?>:</label>
            <select id="page-parent" name="parent">
                <option value="." selected><?= $this->label('pages.new-page.site') ?> (/)</option>
<?php
                foreach ($pages as $page):
                    $scheme = $this->scheme($page->template()->name());
                    if (!$scheme->get('pages', true)) continue;
?>
                <option value="<?= $page->route() ?>"<?php if ($scheme->has('pages')): ?> data-allowed-templates="<?= implode(', ', $scheme->get('pages'))?>"<?php endif; ?>><?= str_repeat('— ', $page->level() - 1) . $page->title() ?></option>
<?php
                endforeach;
?>
            </select>
            <label class="label-required" for="page-template"><?= $this->label('pages.new-page.template') ?>:</label>
            <select id="page-template" name="template">
<?php
            foreach ($templates as $template):
                $scheme = $this->scheme($template);
?>
                <option value="<?= $template ?>"<?php if ($scheme->isDefault()): ?> selected<?php endif; ?>><?= $scheme->title() ?></option>
<?php
            endforeach;
?>
            </select>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            <div class="separator"></div>
            <button type="button" data-dismiss="newPageModal"><?= $this->label('modal.action.cancel') ?></button>
            <button class="button-accent button-right"><i class="i-check"></i> <?= $this->label('modal.action.continue') ?></button>
        </form>
    </div>
</div>

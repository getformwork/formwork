<?php $this->layout('panel') ?>
<form method="post" data-form="page-editor-form" enctype="multipart/form-data">
    <div class="header">
        <div class="min-w-0 flex-grow-1">
            <div class="header-title"><?= $this->icon($page->get('icon', 'page')) ?> <?= $page->title() ?></div>
            <div class="flex">
                <div class="page-status-<?= $page->status() ?> mr-2"><?= $this->icon('circle-small-fill') ?></div>
                <?php if (!$page->isIndexPage() && !$page->isErrorPage()): ?>
                    <div class="page-route page-route-changeable min-w-0">
                        <button type="button" class="page-slug-change truncate max-w-100" data-command="change-slug" title="<?= $this->translate('panel.pages.changeSlug') ?>"><?= $page->route() ?> <?= $this->icon('pencil') ?></button>
                    </div>
                <?php else: ?>
                    <div class="page-route"><?= $page->route() ?></div>
                <?php endif ?>
            </div>
        </div>
        <input type="hidden" id="slug" name="slug" value="<?= $page->slug() ?>">
        <?php if ($currentLanguage): ?>
            <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
        <?php endif ?>
        <div>
            <a class="button button-link<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif ?>" role="button" <?php if ($page->published() && $page->routable()): ?>href="<?= $page->uri(includeLanguage: $currentLanguage ?: true) ?>" <?php endif ?> target="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.preview') ?>" aria-label="<?= $this->translate('panel.pages.preview') ?>"><?= $this->icon('eye') ?></a>
            <?php if ($panel->user()->permissions()->has('pages.delete')): ?>
                <button type="button" class="button-link" data-modal="deletePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" title="<?= $this->translate('panel.pages.deletePage') ?>" aria-label="<?= $this->translate('panel.pages.deletePage') ?>" <?php if (!$page->isDeletable()): ?> disabled<?php endif ?>><?= $this->icon('trash') ?></button>
            <?php endif ?>
            <?php if (!$site->languages()->available()->isEmpty()): ?>
                <div class="dropdown">
                    <button type="button" class="dropdown-button caret button-accent" data-dropdown="languages-dropdown"><?= $this->icon('translate') ?> <?= $this->translate('panel.pages.languages') ?><?php if ($currentLanguage): ?> <span class="badge"><?= $currentLanguage ?></span><?php endif ?></button>
                    <div class="dropdown-menu" id="languages-dropdown">
                        <?php foreach ($site->languages()->available() as $language): ?>
                            <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $language . '/') ?>" class="dropdown-item"><?= $page->languages()->available()->has($language) ? $this->translate('panel.pages.languages.editLanguage', $language->nativeName() . ' (' . $language->code() . ')') : $this->translate('panel.pages.languages.editLanguage', $language->nativeName() . ' (' . $language->code() . ')') ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
            <button type="submit" class="button-accent mb-0" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.save') ?></button>
        </div>
    </div>
    <div>
        <?php $this->insert('fields', ['fields' => $fields]) ?>
    </div>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
</form>

<?php $this->layout('panel') ?>
<form method="post" data-form="page-editor-form" enctype="multipart/form-data">
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.pages.page') ?></h3>
    <div>
        <?php if (!$page->isIndexPage() && !$page->isErrorPage()): ?>
            <div class="page-route page-route-changeable">
                <button type="button" class="page-slug-change" data-command="change-slug" title="<?= $this->translate('panel.pages.change-slug') ?>"><?= $page->route() ?><?= $this->icon('pencil') ?></button>
            </div>
        <?php else: ?>
            <div class="page-route"><span><?= $page->route() ?></span></div>
        <?php endif; ?>
    </div>
    <?php $this->insert('fields', ['fields' => $fields]) ?>
    <input type="hidden" id="slug" name="slug" value="<?= $page->slug() ?>">
<?php
    if ($currentLanguage):
?>
    <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
<?php
    endif;
?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <div style="text-align: right;">
        <a class="button button-link<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif; ?>" role="button" <?php if ($page->published() && $page->routable()): ?>href="<?= $panel->pageUri($page, $currentLanguage ?: true) ?>"<?php endif; ?> target="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.preview') ?>" aria-label="<?= $this->translate('panel.pages.preview') ?>"><?= $this->icon('eye') ?></a>
<?php
        if ($panel->user()->permissions()->has('pages.delete')):
?>
        <button type="button" class="button-link" data-modal="deletePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" title="<?= $this->translate('panel.pages.delete-page') ?>" aria-label="<?= $this->translate('panel.pages.delete-page') ?>" <?php if (!$page->isDeletable()): ?> disabled<?php endif; ?>><?= $this->icon('trash') ?></button>
<?php
        endif;
?>
<?php
        if ($availableLanguages):
?>
        <div class="dropdown">
            <button type="button" class="dropdown-button button-accent" data-dropdown="languages-dropdown"><?= $this->icon('translate') ?> <?= $this->translate('panel.pages.languages') ?><?php if ($currentLanguage): ?> <span class="page-language"><?= $currentLanguage ?></span><?php endif; ?></button>
            <div class="dropdown-menu" id="languages-dropdown">
<?php
            foreach ($availableLanguages as $languageCode => $languageLabel):
?>
                <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $languageCode . '/') ?>" class="dropdown-item"><?= $page->hasLanguage($languageCode) ? $this->translate('panel.pages.languages.edit-language', $languageLabel) : $this->translate('panel.pages.languages.add-language', $languageLabel); ?></a>
<?php
            endforeach;
?>
        </div>
<?php
    endif;
?>
        <button type="submit" class="button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.save') ?></button>
    </div>
</div>
</form>

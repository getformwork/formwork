<?php $this->layout('panel') ?>

<?php $this->modals()->addMultiple(['changes', 'deletePage', 'duplicatePage']) ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon($page->icon()) ?>
            <?= $this->insert('_pages.info', ['page' => $page]) ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->escape($page->title()) ?></div>
            <div class="panel-header-page-meta">
                <?= $this->insert('_pages/status', ['page' => $page]) ?>
                <span class="panel-header-page-route"><?= $this->escape($page->canonicalRoute() ?? $page->route()) ?></span>
            </div>
        </div>
    </div>

    <div class="panel-header-actions">
        <?php if (isset($previousPage) || isset($nextPage)) : ?>
            <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !isset($previousPage) || !$previousPage]) ?>" role="button" <?php if (isset($previousPage) && $previousPage) : ?>href="<?= $panel->uri('/pages/' . trim($previousPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.previous') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !isset($nextPage) || !$nextPage]) ?>" role="button" <?php if (isset($nextPage) && $nextPage) : ?>href="<?= $panel->uri('/pages/' . trim($nextPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.next') ?>"><?= $this->icon('chevron-right') ?></a>
        <?php endif ?>
        <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->published() || !$page->routable()]) ?>" role="button" <?php if ($page->published() && $page->routable()) : ?>href="<?= $page->uri(includeLanguage: isset($currentLanguage) ? ($currentLanguage ?: true) : true) ?>" <?php endif ?> target="formwork-view-page-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.viewPage') ?>"><?= $this->icon('arrow-right-up-box') ?></a>
        
        <button type="submit" class="<?= $this->classes(['button', 'button-link']) ?>" data-command="preview" form="page-editor-form" formaction="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/preview/') ?>" formtarget="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.preview') ?>"><?= $this->icon('eye') ?></button>
        <div class="dropdown mb-0">
            <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.pages.page.actions') ?>" data-dropdown="actions-dropdown"><?= $this->icon('ellipsis-v') ?></button>
            <div class="dropdown-menu" id="actions-dropdown">
                <?php if ($panel->user()->permissions()->has('panel.pages.duplicate')) : ?>
                    <button type="button" class="dropdown-item" data-modal="duplicatePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/duplicate/') ?>" data-duplicate-title="<?= $this->escapeAttr($this->translate('panel.pages.duplicatePage.title', $page->title())) ?>" <?php if (!$page->isDuplicable()) : ?> disabled<?php endif ?>><?= $this->icon('duplicate') ?> <?= $this->translate('panel.pages.duplicatePage') ?></button>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('panel.pages.delete')) : ?>
                    <button type="button" class="dropdown-item" data-modal="deletePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" <?php if (!$page->isDeletable()) : ?> disabled<?php endif ?>><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deletePage') ?></button>
                <?php endif ?>
            </div>
        </div>
        <?php if ($site->languages()->hasMultiple()) : ?>
            <div class="dropdown">
                <button type="button" class="button dropdown-button caret button-accent" data-dropdown="languages-dropdown"><?= $this->icon('translate') ?> <?= $this->translate('panel.pages.languages') ?><?php if ($currentLanguage) : ?> <span class="badge badge-blue"><?= $currentLanguage ?></span><?php endif ?></button>
                <div class="dropdown-menu" id="languages-dropdown">
                    <?php foreach ($site->languages()->available() as $language) : ?>
                        <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $language . '/') ?>" class="dropdown-item"><?= $page->languages()->available()->has($language) ? $this->translate('panel.pages.languages.editLanguage', $language->nativeName() . ' (' . $language->code() . ')') : $this->translate('panel.pages.languages.addLanguage', $language->nativeName() . ' (' . $language->code() . ')') ?></a>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>
        <div class="dropdown mb-0">
            <div class="button-group">
                <?php if ($history?->isJustCreated()): ?>
                    <button type="submit" class="button button-accent" form="page-editor-form" formaction="?publish=true"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.publish') ?></button>
                <?php else: ?>
                    <button type="submit" class="button button-accent mb-0" form="page-editor-form"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.save') ?></button>
                <?php endif ?>
                <button type="button" class="button button-accent dropdown-button caret" data-dropdown="dropdown-save-options"></button>
            </div>
            <div class="dropdown-menu" id="dropdown-save-options">
                <?php if ($history?->isJustCreated()): ?>
                    <button type="submit" class="dropdown-item" form="page-editor-form" formaction="?publish=false"><?= $this->translate('panel.pages.saveOnly') ?></button>
                <?php endif ?>
                <button type="submit" class="dropdown-item" form="page-editor-form" formaction="?createNew"><?= $this->translate('panel.pages.saveAndCreateNew') ?></button>
            </div>
        </div>
    </div>
</header>

<form method="post" id="page-editor-form" data-form="page-editor-form" enctype="multipart/form-data">
    <input type="submit" <?= $this->attr(['hidden' => true, 'aria-hidden' => 'true', 'tabindex' => -1, 'data-command' => 'save', 'formaction' => $history?->isJustCreated() ? '?publish=false' : null]) ?>>
    <?php if ($currentLanguage) : ?>
        <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
    <?php endif ?>
    <?php $this->insert('fields', ['fields' => $fields]) ?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?php if ($history !== null && !$history->items()->isEmpty()): ?>
        <div class="text-size-sm text-color-gray-medium"><?= $this->icon('clock-rotate-left') ?>
            <?= $this->translate(
                'panel.pages.history.event.' . $history->lastItem()->event()->value,
                '<a href="' . $panel->uri('/users/' . $history->lastItem()->user() . '/profile/') . '">' . $history->lastItem()->user() . '</a>',
                '<span title="' . $this->datetime($history->lastItem()->time()) . '">' . $this->timedistance($history->lastItem()->time()) . '</span>'
            ) ?>
        </div>
    <?php endif ?>
</form>
<?php $this->layout('@panel.panel') ?>

<?php $this->modals()->addMultiple(['changes', 'deletePage', 'duplicatePage']) ?>

<form method="post" data-form="page-editor-form" enctype="multipart/form-data">
    <input type="submit" <?= $this->attr(['hidden' => true, 'aria-hidden' => 'true', 'tabindex' => -1, 'data-command' => 'save', 'formaction' => $history?->isJustCreated() ? '?publish=false' : null]) ?>>
    <header class="panel-header">
        <div class="min-w-0 flex-grow-1">
            <div class="flex">
                <div class="header-icon">
                    <?= $this->icon($page->icon()) ?>
                    <?= $this->insert('@panel._pages.info', ['page' => $page]) ?>
                </div>
                <div class="header-title truncate">
                    <?= $this->escape($page->title()) ?>
                </div>
            </div>
            <div class="flex">
                <div class="mr-2"><?= $this->insert('@panel._pages.status', ['page' => $page]) ?></div>
                <div class="page-route truncate"><?= $this->escape($page->canonicalRoute() ?? $page->route()) ?></div>
            </div>
        </div>
        <?php if ($currentLanguage) : ?>
            <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
        <?php endif ?>
        <div>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousPage]) ?>" role="button" <?php if ($previousPage) : ?>href="<?= $panel->uri('/pages/' . trim($previousPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.previous') ?>" aria-label="<?= $this->translate('panel.pages.previous') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextPage]) ?>" role="button" <?php if ($nextPage) : ?>href="<?= $panel->uri('/pages/' . trim($nextPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.next') ?>" aria-label="<?= $this->translate('panel.pages.next') ?>"><?= $this->icon('chevron-right') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->published() || !$page->routable()]) ?>" role="button" <?php if ($page->published() && $page->routable()) : ?>href="<?= $page->uri(includeLanguage: $currentLanguage ?: true) ?>" <?php endif ?> target="formwork-view-page-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.viewPage') ?>" aria-label="<?= $this->translate('panel.pages.viewPage') ?>"><?= $this->icon('arrow-right-up-box') ?></a>
            <button type="submit" class="<?= $this->classes(['button', 'button-link']) ?>" data-command="preview" formaction="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/preview/') ?>" formtarget="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.preview') ?>" aria-label="<?= $this->translate('panel.pages.preview') ?>"><?= $this->icon('eye') ?></button>
            <div class="dropdown mb-0">
                <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.pages.page.actions') ?>" aria-label="<?= $this->translate('panel.pages.page.actions') ?>" data-dropdown="actions-dropdown"><?= $this->icon('ellipsis-v') ?></button>
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
                        <button type="submit" class="button button-accent" formaction="?publish=true"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.publish') ?></button>
                    <?php else: ?>
                        <button type="submit" class="button button-accent mb-0"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.save') ?></button>
                    <?php endif ?>
                    <button type="button" class="button button-accent dropdown-button caret" data-dropdown="dropdown-save-options"></button>
                </div>
                <div class="dropdown-menu" id="dropdown-save-options">
                    <?php if ($history?->isJustCreated()): ?>
                        <button type="submit" class="dropdown-item" formaction="?publish=false"><?= $this->translate('panel.pages.saveOnly') ?></button>
                    <?php endif ?>
                    <button type="submit" class="dropdown-item" formaction="?createNew"><?= $this->translate('panel.pages.saveAndCreateNew') ?></button>
                </div>
            </div>
        </div>
    </header>
    <?php $this->insert('@panel.fields', ['fields' => $fields]) ?>
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

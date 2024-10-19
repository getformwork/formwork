<?php $this->layout('panel') ?>
<form method="post" data-form="page-editor-form" enctype="multipart/form-data">
    <input type="submit" <?= $this->attr(['hidden' => true, 'aria-hidden' => 'true', 'data-command' => 'save', 'formaction' => $history?->isJustCreated() ? '?publish=false' : null]) ?>>
    <div class="header">
        <div class="min-w-0 flex-grow-1">
            <div class="flex">
                <div class="page-icon mr-3">
                    <?= $this->icon($page->icon()) ?>
                </div>
                <div class="header-title truncate">
                    <?= $this->escape($page->title()) ?>
                </div>
            </div>
            <div class="flex">
                <div><?= $this->insert('_pages/status', ['page' => $page]) ?></div>
                <?php if (!$page->isIndexPage() && !$page->isErrorPage()) : ?>
                    <div class="page-route page-route-changeable min-w-0">
                        <button type="button" class="button page-slug-change truncate max-w-100" data-command="change-slug" title="<?= $this->translate('panel.pages.changeSlug') ?>"><span class="page-route-inner"><?= $page->route() ?></span> <?= $this->icon('pencil') ?></button>
                    </div>
                <?php else : ?>
                    <div class="page-route"><?= $page->route() ?></div>
                <?php endif ?>
            </div>
        </div>
        <input type="hidden" id="slug" name="slug" value="<?= $page->slug() ?>">
        <?php if ($currentLanguage) : ?>
            <input type="hidden" id="language" name="language" value="<?= $currentLanguage ?>">
        <?php endif ?>
        <div>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousPage]) ?>" role="button" <?php if ($previousPage) : ?>href="<?= $panel->uri('/pages/' . trim($previousPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.previous') ?>" aria-label="<?= $this->translate('panel.pages.previous') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextPage]) ?>" role="button" <?php if ($nextPage) : ?>href="<?= $panel->uri('/pages/' . trim($nextPage->route(), '/') . '/edit/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.next') ?>" aria-label="<?= $this->translate('panel.pages.next') ?>"><?= $this->icon('chevron-right') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->published() || !$page->routable()]) ?>" role="button" <?php if ($page->published() && $page->routable()) : ?>href="<?= $page->uri(includeLanguage: $currentLanguage ?: true) ?>" <?php endif ?> target="formwork-view-page-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.viewPage') ?>" aria-label="<?= $this->translate('panel.pages.viewPage') ?>"><?= $this->icon('arrow-right-up-box') ?></a>
            <button type="submit" class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->routable()]) ?>" data-command="preview" formaction="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/preview/') ?>" formtarget=" formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.preview') ?>" aria-label="<?= $this->translate('panel.pages.preview') ?>"><?= $this->icon('eye') ?></button>
            <?php if ($panel->user()->permissions()->has('pages.delete')) : ?>
                <button type="button" class="button button-link" data-modal="deletePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/' . ($currentLanguage ? 'language/' . $currentLanguage . '/' : '')) ?>" title="<?= $this->translate('panel.pages.deletePage') ?>" aria-label="<?= $this->translate('panel.pages.deletePage') ?>" <?php if (!$page->isDeletable()) : ?> disabled<?php endif ?>><?= $this->icon('trash') ?></button>
            <?php endif ?>
            <?php if (!$site->languages()->available()->isEmpty()) : ?>
                <div class="dropdown">
                    <button type="button" class="button dropdown-button caret button-accent" data-dropdown="languages-dropdown"><?= $this->icon('translate') ?> <?= $this->translate('panel.pages.languages') ?><?php if ($currentLanguage) : ?> <span class="badge badge-blue"><?= $currentLanguage ?></span><?php endif ?></button>
                    <div class="dropdown-menu" id="languages-dropdown">
                        <?php foreach ($site->languages()->available() as $language) : ?>
                            <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $language . '/') ?>" class="dropdown-item"><?= $page->languages()->available()->has($language) ? $this->translate('panel.pages.languages.editLanguage', $language->nativeName() . ' (' . $language->code() . ')') : $this->translate('panel.pages.languages.addLanguage', $language->nativeName() . ' (' . $language->code() . ')') ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
            <?php if ($history?->isJustCreated()): ?>
                <div class="dropdown mb-0">
                    <div class="button-group">
                        <button type="submit" class="button button-accent" formaction="?publish=true"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.publish') ?></button>
                        <button type="button" class="button button-accent dropdown-button caret" data-dropdown="dropdown-save-options"></button>
                    </div>
                    <div class="dropdown-menu" id="dropdown-save-options">
                        <button type="submit" class="dropdown-item" formaction="?publish=false"><?= $this->translate('panel.pages.saveOnly') ?></button>
                    </div>
                </div>
            <?php else: ?>
                <button type="submit" class="button button-accent mb-0"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.pages.save') ?></button>
            <?php endif ?>
        </div>
    </div>
    <div>
        <?php $this->insert('fields', ['fields' => $fields]) ?>
    </div>
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

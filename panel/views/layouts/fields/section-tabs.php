<div class="tabs" role="tablist">
    <?php foreach ($layout->tabs() as $tab): ?>
        <button type="button" class="<?= $this->classes(['button', 'button-link', 'tabs-tab', 'active' => $tab === $layout->tabs()->first()]) ?>" id="tab-<?= $this->escapeAttr($tab->name()) ?>" role="tab" aria-controls="tabpanel-<?= $this->escapeAttr($tab->name()) ?>" aria-selected="<?= $tab === $layout->tabs()->first() ? 'true' : 'false' ?>" tabindex="<?= $tab === $layout->tabs()->first() ? '0' : '-1' ?>" data-tab="<?= $this->escapeAttr($tab->name()) ?>"><?= $this->escape($tab->label()) ?></button>
    <?php endforeach ?>
</div>
<div class="tabs-content">
    <?= $this->content() ?>
</div>
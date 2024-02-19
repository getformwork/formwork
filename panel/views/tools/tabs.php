<div class="tabs">
    <?php foreach ($tabs as $tab): ?>
        <?php if ($panel->user()->permissions()->has('tools.' . $tab)): ?>
            <a class="tabs-tab<?= ($tab === $current) ? ' active' : '' ?>" href="<?= $panel->uri('/tools/' . $tab . '/') ?>"><?= $this->translate('panel.tools.' . $tab) ?></a>
        <?php endif ?>
    <?php endforeach ?>
</div>

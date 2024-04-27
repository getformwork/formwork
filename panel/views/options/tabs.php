<div class="tabs">
    <?php foreach ($tabs as $tab) : ?>
        <?php if ($panel->user()->permissions()->has('options.' . $tab)) : ?>
            <a class="tabs-tab<?= ($tab === $current) ? ' active' : '' ?>" href="<?= $panel->uri('/options/' . $tab . '/') ?>"><?= $this->translate('panel.options.' . $tab) ?></a>
        <?php endif ?>
    <?php endforeach ?>
</div>
<div class="tabs">
    <?php foreach ($tabs as $tab) : ?>
        <?php if ($panel->user()->permissions()->has("panel.options.{$tab}")) : ?>
            <a class="<?= $this->classes(['tabs-tab', 'active' => $tab === $current]) ?>" href="<?= $panel->uri("/options/{$tab}/") ?>"><?= $this->translate("panel.options.{$tab}") ?></a>
        <?php endif ?>
    <?php endforeach ?>
</div>
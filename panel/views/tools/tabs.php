<div class="tabs">
    <?php foreach ($tabs as $tab) : ?>
        <?php if ($panel->user()->permissions()->has("panel.tools.{$tab}")) : ?>
            <a class="<?= $this->classes(['tabs-tab', 'active' => $tab === $current]) ?>" href="<?= $panel->uri("/tools/{$tab}/") ?>"><?= $this->translate("panel.tools.{$tab}") ?></a>
        <?php endif ?>
    <?php endforeach ?>
</div>
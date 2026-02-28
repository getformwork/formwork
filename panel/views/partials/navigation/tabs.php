<div class="tabs">
    <?php foreach ($items as $item) : ?>
        <?php if ($item->visible() && ($item->permissions() === null || $panel->user()->permissions()->has($item->permissions()))) : ?>
            <a class="<?= $this->classes(['tabs-tab', 'active' => $item->id() === $current]) ?>" href="<?= $panel->uri($item->uri()) ?>">
                <?php if ($item->icon()) : ?>
                    <?= $this->icon($item->icon()) ?>
                <?php endif ?>
                <?= $this->escape($item->label()) ?>
                <?php if ($item->badge()) : ?>
                    <span class="badge"><?= $item->badge() ?></span>
                <?php endif ?>
            </a>
        <?php endif ?>
    <?php endforeach ?>
</div>

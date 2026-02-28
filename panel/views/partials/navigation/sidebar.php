<?php foreach ($items as $item) : ?>
    <?php if ($item->visible() && ($item->permissions() === null || $panel->user()->permissions()->has($item->permissions()))) : ?>
        <li class="<?= $this->classes(['active' => $location === $item->id()]) ?>">
            <a href="<?= $panel->uri($item->uri()) ?>">
                <?php if ($item->icon()) : ?>
                    <?= $this->icon($item->icon()) ?>
                <?php endif ?>
                <?= $this->escape($item->label()) ?>
                <?php if ($item->badge()) : ?>
                    <span class="badge"><?= $item->badge() ?></span>
                <?php endif ?>
            </a>
        </li>
    <?php endif ?>
<?php endforeach ?>

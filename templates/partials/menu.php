<nav class="menu">
    <div class="container">
        <a class="menu-header" href="<?= $site->uri() ?>"><?= $this->escape($site->title()) ?></a>
        <button type="button" class="menu-toggle" data-toggle="main-menu" aria-expanded="false">&#9776;</button>
        <div class="menu-list menu-collapse" id="main-menu">
            <?php foreach ($site->children()->filter('visible') as $item) : ?>
                <a class="menu-item<?php if ($item->isCurrent()) : ?> active<?php endif; ?>" href="<?= $item->uri() ?>"><?= $this->escape($item->get('menu', $item->title())) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
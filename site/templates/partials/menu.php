<nav class="menu">
    <div class="container">
        <a class="menu-header" href="<?= $site->uri() ?>"><?= $site->title() ?></a>
        <button type="button" class="button menu-toggle" data-toggle="main-menu" aria-expanded="false">&#9776;</button>
        <div class="menu-list menu-collapse" id="main-menu">
            <?php foreach ($site->children()->published()->listed() as $item) : ?>
                <a class="<?= $this->classes(['menu-item', 'active' => $item->isCurrent()]) ?>" href="<?= $item->uri() ?>"><?= $item->get('menu', $item->title()) ?></a>
            <?php endforeach ?>
        </div>
    </div>
</nav>
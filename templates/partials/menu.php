<nav class="menu">
    <div class="container">
        <a class="menu-header" href="<?= $site->uri() ?>"><?= $site->title() ?></a>
        <button type="button" class="menu-toggle" data-toggle="main-menu" aria-expanded="false">&#9776;</button>
        <div class="menu-list menu-collapse" id="main-menu">
        <?php foreach ($site->children()->filter('visible') as $page): ?>
            <a class="menu-item<?php if ($page->isCurrent()): ?> active<?php endif; ?>" href="<?= $page->uri() ?>"><?= $page->get('menu', $page->title()) ?></a>
        <?php endforeach; ?>
        </div>
    </div>
</nav>

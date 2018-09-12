<!DOCTYPE html>
<html lang="<?= $site->lang() ?>">
<head>
    <title><?php if (isset($page)): ?><?= $page->title() ?> | <?php endif; ?><?= $site->title() ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="<?= $site->uri('/templates/assets/css/style.min.css') ?>">
    <script src="<?= $site->uri('/templates/assets/js/script.min.js') ?>"></script>
</head>
<body>
    <nav class="menu">
        <div class="container">
                <a class="menu-header" href="<?= $site->uri() ?>"><?= $site->title() ?></a>
                <button class="menu-toggle" data-toggle="main-menu" aria-expanded="false">&#9776;</button>
                <div class="menu-list menu-collapse" id="main-menu">
<?php
                    foreach ($site->children()->filter('visible') as $page):
?>
                    <a class="menu-item<?php if ($page->current()): ?> active<?php endif; ?>" href="<?= $page->uri() ?>"><?= $page->get('menu', $page->title()) ?></a>
<?php
                    endforeach;
?>
                </div>
            </div>
    </nav>

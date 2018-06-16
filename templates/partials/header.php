<!DOCTYPE html>
<html lang="<?= $site->lang() ?>">
<head>
    <title><?php if (isset($page)): ?><?= $page->title() ?> | <?php endif; ?><?= $site->title() ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="<?= $site->uri('/assets/css/style.min.css') ?>">
</head>
<body>
    <nav class="menu">
        <div class="container">
                <a class="menu-header" href="<?= $site->uri() ?>"><?= $site->title() ?></a>
                <div class="menu-list">
<?php
                    foreach($site->children()->filter('visible') as $page):
?>
                    <a class="menu-item<?php if ($page->current()): ?> active<?php endif; ?>" href="<?= $page->uri() ?>"><?= $page->get('menu', $page->title()) ?></a>
<?php
                    endforeach;
?>
                </div>
            </div>
    </nav>

<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
<?php
    if (isset($csrfToken)):
?>
    <meta name="csrf-token" content="<?= $csrfToken ?>">
<?php
    endif;
?>
<?php
    if ($notification = $this->notification()):
?>
    <meta name="notification" content="<?= $notification['text']?>" data-type="<?= $notification['type']?>" data-interval="5000">
<?php
    endif;
?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->uri('images/icon.svg') ?>">
    <link rel="alternate icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri($colorScheme === 'dark' ? 'css/admin-dark.min.css' : 'css/admin.min.css', true) ?>">
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>"></script>
    <script>Formwork.config = <?= Formwork\Parsers\JSON::encode($appConfig) ?>;</script>
</head>
<body>
    <button type="button" class="toggle-navigation hide-from-s"><i class="i-bars"></i></button>
    <div class="sidebar show-from-s">
        <div class="logo"><a href="<?= $this->uri('/dashboard/') ?>"><img src="<?= $this->assets()->uri('images/icon.svg') ?>" alt=""> Formwork</a></div>
        <a href="<?= $this->uri('/users/' . $admin->user()->username() . '/profile/') ?>">
            <div class="admin-user-card">
                <div class="admin-user-avatar">
                    <img src="<?= $admin->user()->avatar()->uri() ?>" alt="">
                </div>
                <div class="admin-user-details">
                    <div class="admin-user-fullname"><?= $this->escape($admin->user()->fullname()) ?></div>
                    <div class="admin-user-username"><?= $this->escape($admin->user()->username()) ?></div>
                </div>
            </div>
        </a>
        <div class="sidebar-wrapper">
            <h3 class="caption"><?= $this->label('admin.manage') ?></h3>
            <ul class="sidebar-navigation">
<?php
                if ($admin->user()->permissions()->has('dashboard')):
?>
                <li class="<?= ($location === 'dashboard') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/dashboard/') ?>"><?= $this->label('dashboard.dashboard') ?></a>
                </li>
<?php
                endif;

                if ($admin->user()->permissions()->has('pages')):
?>
                <li class="<?= ($location === 'pages') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/pages/') ?>"><?= $this->label('pages.pages') ?></a>
                </li>
<?php
                endif;

                if ($admin->user()->permissions()->has('options')):
?>
                <li class="<?= ($location === 'options') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/options/') ?>"><?= $this->label('options.options') ?></a>
                </li>
<?php
                endif;

                if ($admin->user()->permissions()->has('users')):
?>
                <li class="<?= ($location === 'users') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/users/') ?>"><?= $this->label('users.users') ?></a>
                </li>
<?php
                endif;
?>
                <li>
                    <a href="<?= $this->uri('/logout/') ?>"><?= $this->label('login.logout') ?></a>
                </li>
            </ul>
        </div>
    </div>
    <div class="title-bar">
        <span class="panel-title"><?= $this->label('admin.panel') ?></span>
        <a href="<?= $this->siteUri() ?>" class="view-site" target="_blank"><span class="show-from-xs"><?= $this->label('admin.view-site') ?></span> <i class="i-external-link-square"></i></a>
    </div>
    <main class="main">
        <?= $content ?>
    </main>
    <?= $modals ?>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title><?php if (!empty($title)): ?><?= $title ?> | <?php endif; ?>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
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
    <link rel="shortcut icon" href="<?= $this->assets()->uri('images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->assets()->uri('css/admin.min.css', true) ?>">
    <script src="https://code.jquery.com/jquery-3.4.0.min.js"></script>
    <script src="<?= $this->assets()->uri('js/app.min.js', true) ?>"></script>
    <script src="<?= $this->assets()->uri('js/vendor.min.js', true) ?>"></script>
</head>
<body>
    <button type="button" class="toggle-navigation hide-from-s"><i class="i-bars"></i></button>
    <div class="title-bar">
        <span class="panel-title"><?= $this->label('admin.panel') ?></span>
        <a href="<?= $this->siteUri() ?>" class="view-site" target="_blank"><span class="show-from-xs"><?= $this->label('admin.view-site') ?></span> <i class="i-external-link-square"></i></a>
    </div>
    <div class="sidebar show-from-s">
        <div class="logo"><a href="<?= $this->uri('/dashboard/') ?>">Formwork</a></div>
        <a href="<?= $this->uri('/users/' . $this->user()->username() . '/profile/') ?>">
            <div class="admin-user-card">
                <div class="admin-user-avatar">
                    <img src="<?= $this->user()->avatar()->uri() ?>" alt="">
                </div>
                <div class="admin-user-details">
                    <div class="admin-user-fullname"><?= $this->escape($this->user()->fullname()) ?></div>
                    <div class="admin-user-username"><?= $this->escape($this->user()->username()) ?></div>
                </div>
            </div>
        </a>
        <div class="sidebar-wrapper">
            <h3 class="caption"><?= $this->label('admin.manage') ?></h3>
            <ul class="sidebar-navigation">
<?php
                if ($this->user()->permissions()->has('dashboard')):
?>
                <li class="<?= ($location === 'dashboard') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/dashboard/') ?>"><?= $this->label('dashboard.dashboard') ?></a>
                </li>
<?php
                endif;

                if ($this->user()->permissions()->has('pages')):
?>
                <li class="<?= ($location === 'pages') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/pages/') ?>"><?= $this->label('pages.pages') ?></a>
                </li>
<?php
                endif;

                if ($this->user()->permissions()->has('options')):
?>
                <li class="<?= ($location === 'options') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/options/') ?>"><?= $this->label('options.options') ?></a>
                </li>
<?php
                endif;

                if ($this->user()->permissions()->has('users')):
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
    <main class="main">
        <?= $content ?>
    </main>
    <?= $this->modals() ?>
</body>
</html>

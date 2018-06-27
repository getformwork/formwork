<!DOCTYPE html>
<html>
<head>
    <title>Formwork Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" href="<?= $this->uri('/assets/images/icon.png') ?>">
    <link rel="stylesheet" href="<?= $this->uri('/assets/css/admin.min.css') ?>">
    <script src="http://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="<?= $this->uri('/assets/js/app.min.js') ?>"></script>
    <script src="<?= $this->uri('/assets/js/chartist.min.js') ?>"></script>
    <script src="<?= $this->uri('/assets/js/sortable.min.js') ?>"></script>
</head>
<body<?php if(isset($csrfToken)): ?> data-csrf-token="<?= $csrfToken ?>"<?php endif; ?>>
    <button class="toggle-navigation hide-from-s"><i class="i-bars"></i></button>
    <div class="title-bar"><?= $this->label('admin.panel') ?>
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
                    <div class="admin-user-fullname"><?= $this->user()->fullname() ?></div>
                    <div class="admin-user-username"><?= $this->user()->username() ?></div>
                </div>
            </div>
        </a>
        <div class="sidebar-wrapper">
            <h3 class="caption"><?= $this->label('admin.manage') ?></h3>
            <ul class="sidebar-navigation">
                <li class="<?= ($location == 'dashboard') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/dashboard/') ?>"><?= $this->label('dashboard.dashboard') ?></a>
                </li>
                <li class="<?= ($location == 'pages') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/pages/') ?>"><?= $this->label('pages.pages') ?></a>
                </li>
                <li class="<?= ($location == 'options') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/options/') ?>"><?= $this->label('options.options') ?></a>
                </li>
                <li class="<?= ($location == 'users') ? 'active' : '' ?>">
                    <a href="<?= $this->uri('/users/') ?>"><?= $this->label('users.users') ?></a>
                </li>
                <li>
                    <a href="<?= $this->uri('/logout/') ?>"><?= $this->label('login.logout') ?></a>
                </li>
            </ul>
        </div>
    </div>
    <main class="main">
        <?= $content ?>
    </main>
<?php
    if ($notification = $this->notification()):
?>
    <script>Notification('<?= implode("', '", $notification) ?>', 5000);</script>
<?php
    endif;
?>
    <?= isset($modals) ? $modals : '' ?>
</body>
</html>

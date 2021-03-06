<button type="button" class="toggle-navigation hide-from-s"><?= $this->icon('list') ?></button>
<div class="sidebar show-from-s">
    <div class="logo"><a href="<?= $admin->uri('/dashboard/') ?>"><img src="<?= $this->assets()->uri('images/icon.svg') ?>" alt=""> Formwork</a></div>
    <a href="<?= $admin->uri('/users/' . $admin->user()->username() . '/profile/') ?>">
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
        <h3 class="caption"><?= $this->translate('admin.manage') ?></h3>
        <ul class="sidebar-navigation">
<?php
            if ($admin->user()->permissions()->has('dashboard')):
?>
            <li class="<?= ($location === 'dashboard') ? 'active' : '' ?>">
                <a href="<?= $admin->uri('/dashboard/') ?>"><?= $this->translate('admin.dashboard.dashboard') ?></a>
            </li>
<?php
            endif;

            if ($admin->user()->permissions()->has('pages')):
?>
            <li class="<?= ($location === 'pages') ? 'active' : '' ?>">
                <a href="<?= $admin->uri('/pages/') ?>"><?= $this->translate('admin.pages.pages') ?></a>
            </li>
<?php
            endif;

            if ($admin->user()->permissions()->has('options')):
?>
            <li class="<?= ($location === 'options') ? 'active' : '' ?>">
                <a href="<?= $admin->uri('/options/') ?>"><?= $this->translate('admin.options.options') ?></a>
            </li>
<?php
            endif;

            if ($admin->user()->permissions()->has('users')):
?>
            <li class="<?= ($location === 'users') ? 'active' : '' ?>">
                <a href="<?= $admin->uri('/users/') ?>"><?= $this->translate('admin.users.users') ?></a>
            </li>
<?php
            endif;
?>
            <li>
                <a href="<?= $admin->uri('/logout/') ?>"><?= $this->translate('admin.login.logout') ?></a>
            </li>
        </ul>
    </div>
</div>

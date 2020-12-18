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

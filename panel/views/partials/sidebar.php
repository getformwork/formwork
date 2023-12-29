<button type="button" class="toggle-navigation hide-from-s"><?= $this->icon('list') ?></button>
<div class="sidebar show-from-s">
    <div class="logo"><a href="<?= $panel->uri('/dashboard/') ?>"><img src="<?= $this->assets()->uri('images/icon.svg') ?>" alt=""> Formwork</a> <span class="show-from-s" style="color: #707e89; font-size: 0.75rem;"><?= $app::VERSION ?></span></div>
    <a href="<?= $panel->uri('/users/' . $panel->user()->username() . '/profile/') ?>">
        <div class="panel-user-card">
            <div class="panel-user-avatar">
                <img src="<?= $panel->user()->image()->uri() ?>" alt="">
            </div>
            <div class="panel-user-details">
                <div class="panel-user-fullname"><?= $this->escape($panel->user()->fullname()) ?></div>
                <div class="panel-user-username"><?= $this->escape($panel->user()->username()) ?></div>
            </div>
        </div>
    </a>
    <div class="sidebar-wrapper">
        <h3 class="caption mb-8"><?= $this->translate('panel.manage') ?></h3>
        <ul class="sidebar-navigation">
<?php
            if ($panel->user()->permissions()->has('dashboard')):
                ?>
            <li class="<?= ($location === 'dashboard') ? 'active' : '' ?>">
                <a href="<?= $panel->uri('/dashboard/') ?>"><?= $this->translate('panel.dashboard.dashboard') ?></a>
            </li>
<?php
            endif
?>
<?php
if ($panel->user()->permissions()->has('pages')):
    ?>
            <li class="<?= ($location === 'pages') ? 'active' : '' ?>">
                    <a href="<?= $panel->uri('/pages/') ?>"><?= $this->translate('panel.pages.pages') ?></a>
                    <span class="badge"><?= $app->site()->descendants()->count() ?></span>
            </li>
<?php
endif
?>
<?php
if ($panel->user()->permissions()->has('options')):
    ?>
            <li class="<?= ($location === 'options') ? 'active' : '' ?>">
                <a href="<?= $panel->uri('/options/') ?>"><?= $this->translate('panel.options.options') ?></a>
            </li>
<?php
endif
?>
<?php
if ($panel->user()->permissions()->has('users')):
    ?>
            <li class="<?= ($location === 'users') ? 'active' : '' ?>">
                <a href="<?= $panel->uri('/users/') ?>"><?= $this->translate('panel.users.users') ?></a>
                <span class="badge"><?= $panel->users()->count() ?></span>
            </li>
<?php
endif
?>
<?php
if ($panel->user()->permissions()->has('statistics')):
    ?>
            <li class="<?= ($location === 'statistics') ? 'active' : '' ?>">
                <a href="<?= $panel->uri('/statistics/') ?>"><?= $this->translate('panel.statistics.statistics') ?></a>
            </li>
<?php
endif
?>
            <li>
                <a href="<?= $panel->uri('/logout/') ?>"><?= $this->translate('panel.login.logout') ?></a>
            </li>
        </ul>
    </div>
</div>

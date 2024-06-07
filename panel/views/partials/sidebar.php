<button type="button" class="button button-link sidebar-toggle hide-from-md" aria-label="<?= $this->translate('panel.navigation.toggle') ?>"><?= $this->icon('bars') ?></button>
<div class="sidebar show-from-md">
    <div class="logo"><a href="<?= $panel->uri('/dashboard/') ?>"><img src="<?= $this->assets()->uri('images/icon.svg') ?>" alt=""> Formwork</a> <span class="show-from-md text-color-gray-medium text-size-xs"><?= $app::VERSION ?></span></div>
    <a href="<?= $panel->uri('/users/' . $panel->user()->username() . '/profile/') ?>">
        <div class="panel-user-card">
            <div class="panel-user-image">
                <img src="<?= $panel->user()->image()->uri() ?>" alt="<?= $panel->user()->username() ?>">
            </div>
            <div class="panel-user-details">
                <div class="panel-user-fullname"><?= $this->escape($panel->user()->fullname()) ?></div>
                <div class="panel-user-username"><?= $this->escape($panel->user()->username()) ?></div>
            </div>
        </div>
    </a>
    <nav class="sidebar-wrapper">
        <div class="caption mb-8"><?= $this->translate('panel.manage') ?></div>
        <ul class="sidebar-navigation">
            <?php foreach ($navigation as $id => ['label' => $label, 'uri' => $uri, 'permissions' => $permissions, 'badge' => $badge]) : ?>
                <?php if ($panel->user()->permissions()->has($permissions)) : ?>
                    <li class="<?= ($location === $id) ? 'active' : '' ?>">
                        <a href="<?= $panel->uri($uri) ?>"><?= $this->escape($label) ?></a>
                        <?php if ($badge) : ?>
                            <span class="badge"><?= $badge ?></span>
                        <?php endif ?>
                    </li>
                <?php endif ?>
            <?php endforeach ?>
        </ul>
    </nav>
</div>
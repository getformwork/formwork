<button type="button" class="button button-link sidebar-toggle hide-from-md" aria-label="<?= $this->translate('panel.navigation.toggle') ?>"><?= $this->icon('bars') ?></button>
<div class="sidebar show-from-md">
    <div class="logo">
        <a href="<?= $panel->uri('/dashboard/') ?>">
            <img src="<?= $this->assets()->get('images/icon.svg')->uri(includeVersion: true) ?>" alt=""> 
            Formwork
        </a>
    </div>
    
    <a href="<?= $panel->uri('/users/' . $panel->user()->username() . '/profile/') ?>">
        <div class="panel-user-card">
            <div class="panel-user-image">
                <?= $this->insert('_user-image', ['user' => $panel->user()]) ?>
            </div>
            <div class="panel-user-details">
                <div class="panel-user-fullname"><?= $this->escape($panel->user()->fullname()) ?></div>
                <div class="panel-user-username"><?= $this->escape($panel->user()->username()) ?></div>
            </div>
        </div>
    </a>
    
    <nav class="sidebar-wrapper">
        <ul class="sidebar-navigation">
            <?php foreach ($panel->navigation() as $item) : ?>
                <?php if ($item->permissions() === null || $panel->user()->permissions()->has($item->permissions())) : ?>
                    <li class="<?= $this->classes(['active' => $location === $item->id()]) ?>">
                        <a href="<?= $panel->uri($item->uri()) ?>">
                            <?php if ($item->icon()) : ?>
                                <?= $this->icon($item->icon()) ?>
                            <?php endif ?>
                            <span><?= $this->escape($item->label()) ?></span>
                            <?php if ($item->badge()) : ?>
                                <span class="badge badge-accent ml-auto"><?= $item->badge() ?></span>
                            <?php endif ?>
                        </a>
                    </li>
                <?php endif ?>
            <?php endforeach ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <ul class="sidebar-footer-nav">
            <li>
                <a href="<?= $panel->uri('/logout/') ?>">
                    <?= $this->icon('arrow-left-circle') ?>
                    <span><?= $this->translate('panel.login.logout') ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>

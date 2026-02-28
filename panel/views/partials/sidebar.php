<button type="button" class="button button-link sidebar-toggle hide-from-md" aria-label="<?= $this->translate('panel.navigation.toggle') ?>"><?= $this->icon('bars') ?></button>
<div class="sidebar show-from-md">
    <div class="logo"><a href="<?= $panel->uri('/dashboard/') ?>"><img src="<?= $this->assets()->get('@panel/images/icon.svg')->uri(includeVersion: true) ?>" alt=""> Formwork</a> <span class="show-from-md text-color-gray-medium text-size-xs ml-2"><?= $app::VERSION ?></span></div>
    <a href="<?= $panel->uri("/users/{$panel->user()->username()}/profile/") ?>">
        <div class="panel-user-card">
            <div class="panel-user-image">
                <?= $this->insert('@panel._user-image', ['user' => $panel->user()]) ?>
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
            <?php $this->insert('@panel.partials.navigation.sidebar', ['items' => $panel->navigation()]) ?>
        </ul>
    </nav>
</div>

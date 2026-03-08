<?php $this->layout('@panel.panel') ?>

<?php $this->modals()->addMultiple(['newUser', 'deleteUser']) ?>

<div class="header">
    <div class="header-icon"><?= $this->icon('users') ?></div>
    <div class="header-title"><?= $this->translate('panel.users.users') ?> <span class="badge"><?= $users->count() ?></span></div>
    <div>
        <?php if ($panel->user()->permissions()->has('panel.users.create')) : ?>
            <button type="button" class="button button-accent" data-modal="newUserModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.users.newUser') ?></button>
        <?php endif ?>
    </div>
</div>

<section class="section">
    <div class="users-list-headers" aria-hidden="true">
        <div class="users-headers-cell user-fullname truncate"><?= $this->translate('user.fullname') ?></div>
        <div class="users-headers-cell user-username truncate"><?= $this->translate('user.username') ?></div>
        <div class="users-headers-cell user-email truncate show-from-md"><?= $this->translate('user.email') ?></div>
        <div class="users-headers-cell user-last-access truncate show-from-sm"><?= $this->translate('panel.user.lastAccess') ?></div>
        <div class="users-headers-cell user-actions"><span class="show-from-xs mr-6"><?= $this->translate('panel.user.actions') ?></span></div>
    </div>
    <div class="users-list">
        <?php foreach ($users as $user) : ?>
            <div class="users-item">
                <div class="users-item-cell user-fullname">
                    <?= $this->insert('@panel._user-image', ['user' => $user, 'class' => 'user-image']) ?>
                    <a href="<?= $panel->uri("/users/{$user->username()}/profile/") ?>"><?= $this->escape($user->fullname()) ?></a>
                </div>
                <div class="users-item-cell user-username truncate"><?= $this->escape($user->username()) ?></div>
                <div class="users-item-cell user-email truncate show-from-md"><?= $this->escape($user->email()) ?></div>
                <div class="users-item-cell user-last-access truncate show-from-sm"><?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?></div>
                <div class="users-item-cell user-actions">
                    <div class="dropdown">
                        <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.user.actions') ?>" aria-label="<?= $this->translate('panel.user.actions') ?>" data-dropdown="dropdown-user-<?= $user->username() ?>"><?= $this->icon('ellipsis-v') ?></button>
                        <div class="dropdown-menu" id="dropdown-user-<?= $user->username() ?>">
                            <button type="button" class="dropdown-item" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri("/users/{$user->username()}/delete/") ?>" <?php if (!$panel->user()->canDeleteUser($user)) : ?>disabled<?php endif ?>><?= $this->icon('trash') ?> <?= $this->translate('panel.users.deleteUser') ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</section>
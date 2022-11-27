<?php $this->layout('panel') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.users.users') ?></h3>
    <button type="button" data-modal="newUserModal"<?php if (!$panel->user()->permissions()->has('users.create')): ?> disabled<?php endif; ?>><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.users.new-user') ?></button>
    <div class="separator"></div>
    <div class="users-list-headers" aria-hidden="true">
        <div class="users-headers-cell user-username"><?= $this->translate('panel.user.username') ?></div>
        <div class="users-headers-cell user-fullname"><?= $this->translate('panel.user.fullname') ?></div>
        <div class="users-headers-cell user-email"><?= $this->translate('panel.user.email') ?></div>
        <div class="users-headers-cell user-last-access"><?= $this->translate('panel.user.last-access') ?></div>
        <div class="users-headers-cell user-actions"><?= $this->translate('panel.user.actions') ?></div>
    </div>
    <div class="users-list">
<?php foreach ($users as $user): ?>
        <div class="users-item">
            <div class="users-item-cell user-username">
                <?= $this->icon('user') ?>
                <a href="<?= $panel->uri('/users/' . $user->username() . '/profile/') ?>"><?= $this->escape($user->username()) ?></a>
            </div>
            <div class="users-item-cell user-fullname"><?= $this->escape($user->fullname()) ?></div>
            <div class="users-item-cell user-email" data-overflow-tooltip="true"><?= $this->escape($user->email()) ?></div>
            <div class="users-item-cell user-last-access" data-overflow-tooltip="true"><?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?></div>
            <div class="users-item-cell user-actions">
                <button type="button" class="button-link" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('panel.users.delete-user') ?>" aria-label="<?= $this->translate('panel.users.delete-user') ?>" <?php if (!$panel->user()->canDeleteUser($user)): ?>disabled<?php endif; ?>><?= $this->icon('trash') ?></button>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>

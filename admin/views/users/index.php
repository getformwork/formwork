<?php $this->layout('admin') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('admin.users.users') ?></h3>
    <button type="button" data-modal="newUserModal"<?php if (!$admin->user()->permissions()->has('users.create')): ?> disabled<?php endif; ?>><i class="i-plus-circle"></i> <?= $this->translate('admin.users.new-user') ?></button>
    <div class="separator"></div>
    <div class="users-list-headers" aria-hidden="true">
        <div class="users-headers-cell user-username"><?= $this->translate('admin.user.username') ?></div>
        <div class="users-headers-cell user-fullname"><?= $this->translate('admin.user.fullname') ?></div>
        <div class="users-headers-cell user-email"><?= $this->translate('admin.user.email') ?></div>
        <div class="users-headers-cell user-last-access"><?= $this->translate('admin.user.last-access') ?></div>
        <div class="users-headers-cell user-actions"><?= $this->translate('admin.user.actions') ?></div>
    </div>
    <div class="users-list">
<?php foreach ($users as $user): ?>
        <div class="users-item">
            <div class="users-item-cell user-username">
                <a href="<?= $admin->uri('/users/' . $user->username() . '/profile/') ?>"><?= $this->escape($user->username()) ?></a>
            </div>
            <div class="users-item-cell user-fullname"><?= $this->escape($user->fullname()) ?></div>
            <div class="users-item-cell user-email" data-overflow-tooltip="true"><?= $this->escape($user->email()) ?></div>
            <div class="users-item-cell user-last-access" data-overflow-tooltip="true"><?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?></div>
            <div class="users-item-cell user-actions">
                <button type="button" class="button-link" data-modal="deleteUserModal" data-modal-action="<?= $admin->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('admin.users.delete-user') ?>" aria-label="<?= $this->translate('admin.users.delete-user') ?>" <?php if (!$admin->user()->canDeleteUser($user)): ?>disabled<?php endif; ?>><i class="i-trash"></i></button>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>

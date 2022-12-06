<?php $this->layout('panel') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.users.user') ?></h3>
    <div class="user-summary">
        <div class="user-summary-avatar">
            <img src="<?= $user->avatar()->uri() ?>">
        </div>
        <div class="user-summary-data">
            <h3><?= $this->escape($user->fullname()) ?></h3>
            <?= $this->escape($user->username()) ?><br>
            <a href="mailto:<?= $user->email() ?>"><?= $this->escape($user->email()) ?></a><br>
            <?= $this->translate('panel.user.lastAccess') ?>: <?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?>
        </div>
    </div>
</div>
<?php if ($panel->user()->canChangeOptionsOf($user)): ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.users.options') ?></h3>
    <form method="post" enctype="multipart/form-data" data-form="user-profile-form">
        <?php $this->insert('fields', ['fields' => $fields]) ?>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        <button type="submit" class="button-accent button-right" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
        <button type="button" class="button-link button-right" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('panel.users.deleteUser') ?>" aria-label="<?= $this->translate('panel.users.deleteUser') ?>" <?php if (!$panel->user()->canDeleteUser($user)): ?>disabled<?php endif; ?>><?= $this->icon('trash') ?></button>
    </form>
</div>
<?php endif; ?>

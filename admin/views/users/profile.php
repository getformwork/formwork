<?php $this->layout('admin') ?>
<div class="component">
    <h3 class="caption"><?= $this->label('users.user') ?></h3>
    <div class="user-summary">
        <div class="user-summary-avatar">
            <img src="<?= $user->avatar()->uri() ?>">
        </div>
        <div class="user-summary-data">
            <h3><?= $this->escape($user->fullname()) ?></h3>
            <?= $this->escape($user->username()) ?><br>
            <a href="mailto:<?= $user->email() ?>"><?= $this->escape($user->email()) ?></a><br>
            <?= $this->label('user.last-access') ?>: <?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?>
        </div>
    </div>
</div>
<?php if ($admin->user()->canChangeOptionsOf($user)): ?>
<div class="component">
    <h3 class="caption"><?= $this->label('users.options') ?></h3>
    <form method="post" enctype="multipart/form-data" data-form="user-profile-form">
        <?= $fields ?>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        <button type="submit" class="button-accent button-right" data-command="save"><i class="i-check"></i> <?= $this->label('modal.action.save') ?></button>
        <button type="button" class="button-link button-right" data-modal="deleteUserModal" data-modal-action="<?= $this->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->label('users.delete-user') ?>" aria-label="<?= $this->label('users.delete-user') ?>" <?php if (!$admin->user()->canDeleteUser($user)): ?>disabled<?php endif; ?>><i class="i-trash"></i></button>
    </form>
</div>
<?php endif; ?>

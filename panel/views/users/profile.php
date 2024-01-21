<?php $this->layout('panel') ?>
<form method="post" enctype="multipart/form-data" data-form="user-profile-form">

    <div class="header">
        <div class="header-title"><?= $this->translate('panel.users.user') ?></div>
        <div>
            <button type="button" class="button-link" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('panel.users.deleteUser') ?>" aria-label="<?= $this->translate('panel.users.deleteUser') ?>" <?php if (!$panel->user()->canDeleteUser($user)): ?>disabled<?php endif ?>><?= $this->icon('trash') ?></button>
            <button type="submit" class="button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
        </div>
    </div>

    <section class="section">
        <div class="user-summary">
            <div class="user-summary-avatar">
                <img src="<?= $user->image()->uri() ?>" alt="<? $panel->user()->username() ?>">
            </div>
            <div class="user-summary-data">
                <h3><?= $this->escape($user->fullname()) ?></h3>
                <?= $this->escape($user->username()) ?><br>
                <a href="mailto:<?= $user->email() ?>"><?= $this->escape($user->email()) ?></a><br>
                <?= $this->translate('panel.user.lastAccess') ?>: <?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?>
            </div>
        </div>
    </section>
    <?php if ($panel->user()->canChangeOptionsOf($user)): ?>
        <?php $this->insert('fields', ['fields' => $fields]) ?>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?php endif ?>
</form>

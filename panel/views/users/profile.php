<?php $this->layout('panel') ?>
<form method="post" enctype="multipart/form-data" data-form="user-profile-form">
    <div class="header">
        <div class="header-title"><?= $this->translate('panel.users.user') ?></div>
        <?php if ($panel->user()->canChangeOptionsOf($user)) : ?>
            <div>
                <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousUser]) ?>" role="button" <?php if ($previousUser) : ?>href="<?= $panel->uri('/users/' . $previousUser->username() . '/profile/') ?>" <?php endif ?> title="<?= $this->translate('panel.users.previousUser') ?>" aria-label="<?= $this->translate('panel.users.previousUser') ?>"><?= $this->icon('chevron-left') ?></a>
                <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextUser]) ?>" role="button" <?php if ($nextUser) : ?>href="<?= $panel->uri('/users/' . $nextUser->username() . '/profile/') ?>" <?php endif ?> title="<?= $this->translate('panel.users.nextUser') ?>" aria-label="<?= $this->translate('panel.users.nextUser') ?>"><?= $this->icon('chevron-right') ?></a>
                <button type="button" class="button button-link" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('panel.users.deleteUser') ?>" aria-label="<?= $this->translate('panel.users.deleteUser') ?>" <?php if (!$panel->user()->canDeleteUser($user)) : ?>disabled<?php endif ?>><?= $this->icon('trash') ?></button>
                <button type="submit" class="button button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
            </div>
        <?php endif ?>
    </div>
    <section class="section user-summary">
        <div class="user-summary-image">
            <img src="<?= $user->image()->uri() ?>" alt="<?= $panel->user()->username() ?>">
            <?php if ($panel->user()->canChangeOptionsOf($user) && !$user->hasDefaultImage()) : ?>
                <div class="dropdown">
                    <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.user.image.actions') ?>" data-dropdown="dropdown-user-image"><?= $this->icon('ellipsis-v') ?></button>
                    <div class="dropdown-menu" id="dropdown-user-image">
                        <a class="dropdown-item" data-modal="deleteUserImageModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/image/delete/') ?>"><?= $this->icon('user-image-slash') ?> <?= $this->translate('panel.user.image.delete') ?></a>
                    </div>
                </div>
            <?php endif ?>
        </div>
        <div class="user-summary-data">
            <div class="h3 mb-0"><?= $this->escape($user->fullname()) ?></div>
            <div class="text-color-gray-medium mb-4"><?= $this->escape($user->username()) ?></div>
            <div class="mb-2"><a href="mailto:<?= $user->email() ?>"><?= $this->escape($user->email()) ?></a></div>
            <div class="text-size-sm mb-2"><?= $this->translate('panel.user.role') ?>: <?= $user->role()->title() ?></div>
            <div class="text-size-sm"><?= $this->translate('panel.user.lastAccess') ?>: <?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?></div>
        </div>
    </section>
    <?php if ($panel->user()->canChangeOptionsOf($user)) : ?>
        <?php $this->insert('fields', ['fields' => $fields]) ?>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?php endif ?>
</form>
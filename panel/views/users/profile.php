<?php $this->layout('panel') ?>

<?php $this->modals()->addMultiple(['changes', 'deleteUser', 'deleteUserImage']) ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon('user') ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->translate('user.user') ?></div>
        </div>
    </div>
    <?php if ($panel->user()->canChangeOptionsOf($user)) : ?>
        <div class="panel-header-actions">
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousUser]) ?>" role="button" <?php if ($previousUser) : ?>href="<?= $panel->uri('/users/' . $previousUser->username() . '/profile/') ?>" <?php endif ?> title="<?= $this->translate('panel.users.previousUser') ?>" aria-label="<?= $this->translate('panel.users.previousUser') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextUser]) ?>" role="button" <?php if ($nextUser) : ?>href="<?= $panel->uri('/users/' . $nextUser->username() . '/profile/') ?>" <?php endif ?> title="<?= $this->translate('panel.users.nextUser') ?>" aria-label="<?= $this->translate('panel.users.nextUser') ?>"><?= $this->icon('chevron-right') ?></a>
            <button type="button" class="button button-link" data-modal="deleteUserModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->translate('panel.users.deleteUser') ?>" aria-label="<?= $this->translate('panel.users.deleteUser') ?>" <?php if (!$panel->user()->canDeleteUser($user)) : ?>disabled<?php endif ?>><?= $this->icon('trash') ?></button>
            <button type="submit" form="user-profile-form" class="button button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
        </div>
    <?php endif ?>
</header>

<form method="post" enctype="multipart/form-data" data-form="user-profile-form" id="user-profile-form">
    
    <!-- User Profile Card -->
    <section class="section">
        <div class="row">
            <div class="col-md-1-3">
                <div style="text-align: center;">
                    <div class="user-summary-image" style="position: relative; display: inline;">
                        <?= $this->insert('_user-image', ['user' => $user]) ?>
                        <?php if ($panel->user()->canChangeOptionsOf($user) && $user->image()) : ?>
                            <div class="dropdown" style="position: absolute; top: 0.5rem; right: 0.5rem;">
                                <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.user.image.actions') ?>" data-dropdown="dropdown-user-image"><?= $this->icon('ellipsis-v') ?></button>
                                <div class="dropdown-menu" id="dropdown-user-image">
                                    <button type="button" class="dropdown-item" data-modal="deleteUserImageModal" data-modal-action="<?= $panel->uri('/users/' . $user->username() . '/image/delete/') ?>"><?= $this->icon('user-image-slash') ?> <?= $this->translate('panel.user.image.delete') ?></button>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <div class="col-md-2-3">
                <div class="user-summary-data">
                    <div class="h3 mb-2"><?= $this->escape($user->fullname()) ?></div>
                    <div class="text-color-gray-medium mb-4"><?= $this->icon('user') ?> <?= $this->escape($user->username()) ?></div>
                    
                    <div class="row mb-4">
                        <div class="col-md-1-2">
                            <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
                                <div class="text-size-sm text-color-gray-medium mb-1"><?= $this->icon('envelope') ?> <?= $this->translate('user.email') ?></div>
                                <div><a href="mailto:<?= $user->email() ?>"><?= $this->escape($user->email()) ?></a></div>
                            </div>
                        </div>
                        <div class="col-md-1-2">
                            <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2);">
                                <div class="text-size-sm text-color-gray-medium mb-1"><?= $this->icon('tag') ?> <?= $this->translate('user.role') ?></div>
                                <div><?= $user->role()->title() ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2);">
                        <div class="text-size-sm text-color-gray-medium mb-1"><?= $this->icon('clock') ?> <?= $this->translate('panel.user.lastAccess') ?></div>
                        <div><?= is_null($user->lastAccess()) ? '&infin;' : $this->datetime($user->lastAccess()) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- User Options -->
    <?php if ($panel->user()->canChangeOptionsOf($user)) : ?>
        <section class="section">
            <div class="section-header">
                <div class="caption"><?= $this->translate('panel.users.options') ?></div>
            </div>
            <?php $this->insert('fields', ['fields' => $fields]) ?>
        </section>
        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?php endif ?>
</form>
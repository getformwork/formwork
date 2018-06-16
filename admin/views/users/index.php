        <div class="component">
            <h3 class="caption"><?= $this->label('users.users') ?></h3>
            <button data-modal="newUserModal"><i class="i-plus-circle"></i> <?= $this->label('users.new-user') ?></button>
            <div class="separator"></div>
            <div class="users-list">
<?php
            foreach ($users as $user):
?>
                <div class="users-item">
                    <div class="users-item-cell user-username">
                        <a href="<?= $this->uri('/users/' . $user->username() . '/profile/') ?>"><?= $user->username() ?></a>
                    </div>
                    <div class="users-item-cell user-fullname"><?= $user->fullname() ?></div>
                    <div class="users-item-cell user-email overflow-title"><?= $user->email() ?></div>
                    <div class="users-item-cell user-last-access overflow-title"><?= is_null($user->lastAccess()) ? '&infin;' : date($this->option('date.format') . ' ' . $this->option('date.hour_format'), $user->lastAccess()) ?></div>
                    <div class="users-item-cell user-actions">
<?php
                    if (!$user->logged()):
?>
                        <button data-modal="deleteUserModal" data-modal-action="<?= $this->uri('/users/' . $user->username() . '/delete/') ?>" title="<?= $this->label('users.delete-user') ?>"><i class="i-trash"></i></button>
<?php
                    endif;
?>
                    </div>
                </div>
<?php
            endforeach;
?>
            </div>
        </div>

<div id="newUserModal" class="modal" aria-labelledby="newUserModalLabel">
    <div class="modal-container">
        <form action="<?= $panel->uri('/users/new/') ?>" method="post">
            <div class="modal-header">
                <div class="caption" id="newUserModalLabel"><?= $this->translate('panel.users.newUser') ?></div>
            </div>
            <div class="modal-content">
                <label class="form-label form-label-required" for="fullname"><?= $this->translate('panel.user.fullname') ?>:</label>
                <input class="form-input" id="fullname" type="text" required name="fullname" autofocus>
                <label class="form-label form-label-required" for="username"><?= $this->translate('panel.user.username') ?>:</label>
                <span class="form-label-suggestion">(<?= $this->translate('panel.users.newUser.usernameSuggestion') ?>)</span>
                <input class="form-input" id="username" type="text" required name="username" pattern="^[a-zA-Z0-9_-]{3,20}$" title="<?= ucfirst($this->translate('panel.users.newUser.usernameSuggestion')) ?>" maxlength="20" autocomplete="false">
                <label class="form-label form-label-required" for="password"><?= $this->translate('panel.user.password') ?>:</label>
                <span class="form-label-suggestion">(<?= $this->translate('panel.users.newUser.passwordSuggestion') ?>)</span>
                <input class="form-input" id="password" type="password" required name="password" pattern="^.{8,}$" title="<?= ucfirst($this->translate('panel.users.newUser.passwordSuggestion')) ?>" autocomplete="new-password">
                <label class="form-label form-label-required" for="email"><?= $this->translate('panel.user.email') ?>:</label>
                <input class="form-input" id="email" type="email" required name="email">
                <label class="form-label form-label-required" for="language"><?= $this->translate('panel.user.language') ?>:</label>
                <select class="form-select" id="language" name="language">
                    <?php foreach ($panel->availableTranslations() as $key => $value) : ?>
                        <option value="<?= $key ?>" <?php if ($key === $panel->user()->language()) : ?> selected<?php endif ?>><?= $value ?></option>
                    <?php endforeach ?>
                </select>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" data-dismiss="newUserModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button button-accent button-right"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            </div>
        </form>
    </div>
</div>
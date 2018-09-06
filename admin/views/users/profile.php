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
                        <?= $this->label('user.last-access') ?>: <?= is_null($user->lastAccess()) ? '&infin;' : date($this->option('date.format') . ' ' . $this->option('date.hour_format'), $user->lastAccess()) ?>
                    </div>
                </div>
            </div>
            <div class="component">
                <h3 class="caption"><?= $this->label('users.options') ?></h3>
                <form method="post" enctype="multipart/form-data" data-form="user-profile-form" >
                    <div class="container-full">
                        <div class="row">
                            <div class="col-m-1-3">
                                <div class="label-required"><?= $this->label('user.fullname') ?></div>
                            </div>
                            <div class="col-m-2-3">
                                <input value="<?= $user->fullname() ?>" name="fullname" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-m-1-3">
                                <div class="label-required"><?= $this->label('user.email') ?></div>
                            </div>
                            <div class="col-m-2-3">
                                <input type="email" value="<?= $user->email() ?>" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-m-1-3">
                                <div><?= $this->label('user.password') ?></div>
                            </div>
                            <div class="col-m-2-3">
                                <input type="password" value="" name="password"<?= !$user->logged() ? ' disabled' : '' ?> autocomplete="new-password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-m-1-3">
                                <div class="label-required"><?= $this->label('user.language') ?></div>
                            </div>
                            <div class="col-m-2-3">
                                <select name="language">
<?php
                                foreach ($this->languages() as $key => $value):
?>
                                    <option value="<?= $key ?>"<?= ($key == $user->language()) ? ' selected' : '' ?>><?= $value ?></option>
<?php
                                endforeach;
?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-m-1-3">
                                <div><?= $this->label('user.avatar') ?></div>
                            </div>
                            <div class="col-m-2-3">
                                <input class="file-input" id="file-uploader" type="file" name="uploaded-file" accept="<?= implode(', ', $this->formwork()->option('files.allowed_extensions')) ?>">
                                <label for="file-uploader" class="file-input-label">
                                    <span><?= $this->label('pages.files.upload-label') ?></span>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
                        <button class="button-accent button-right" type="submit" tabindex="4" data-command="save"><i class="i-check"></i> <?= $this->label('modal.action.save') ?></button>
                    </div>
                </form>
            </div>
        </div>

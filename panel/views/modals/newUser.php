<div id="newUserModal" class="modal" aria-labelledby="newUserModalLabel">
    <div class="modal-container">
        <form action="<?= $panel->uri('/users/new/') ?>" method="post">
            <div class="modal-header">
                <div class="caption" id="newUserModalLabel"><?= $this->translate('panel.users.newUser') ?></div>
            </div>
            <div class="modal-content">
                <?php foreach ($fields as $field) : ?>
                    <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
                <?php endforeach ?>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" data-dismiss="newUserModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button button-accent button-right"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            </div>
        </form>
    </div>
</div>
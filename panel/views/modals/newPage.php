<div id="newPageModal" class="modal" aria-labelledby="newPageModalLabel">
    <div class="modal-container">
        <form action="<?= $panel->uri('/pages/new/') ?>" method="post">
            <div class="modal-header">
                <div class="caption" id="newPageModalLabel"><?= $this->translate('panel.pages.newPage') ?></div>
            </div>
            <div class="modal-content">
                <?php foreach (Formwork\App::instance()->schemes()->get('modals.newPage')->fields() as $field) : ?>
                    <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
                <?php endforeach ?>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" data-dismiss="newPageModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button button-accent button-right"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            </div>
        </form>
    </div>
</div>
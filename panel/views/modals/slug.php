<div id="slugModal" class="modal">
    <div class="modal-container">
        <div class="modal-header"><h3 class="caption"><?= $this->translate('panel.pages.changeSlug') ?></h3></div>
        <div class="modal-content">
            <label for="page-slug"><?= $this->translate('panel.pages.newPage.slug') ?>:</label>
            <span class="label-suggestion">(<?= $this->translate('panel.pages.newPage.slugSuggestion') ?>)</span>
            <input id="page-slug" type="text" name="slug" autofocus>
        </div>
        <div class="modal-footer">
            <button type="button" data-dismiss="slugModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="button" class="button-accent button-right" data-command="continue"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            <button type="button" class="button-link button-right" data-command="generate-slug" title="<?= $this->translate('panel.pages.changeSlug.generate') ?>"><?= $this->icon('bolt') ?></button>
        </div>
    </div>
</div>

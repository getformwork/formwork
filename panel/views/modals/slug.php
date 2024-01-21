<div id="slugModal" class="modal" aria-labelledby="slugModalLabel">
    <div class="modal-container">
        <div class="modal-header"><h3 class="caption" id="slugModalLabel"><?= $this->translate('panel.pages.changeSlug') ?></h3></div>
        <div class="modal-content">
            <label class="form-label" for="page-slug"><?= $this->translate('panel.pages.newPage.slug') ?>:</label>
            <span class="form-label-suggestion">(<?= $this->translate('panel.pages.newPage.slugSuggestion') ?>)</span>
            <input class="form-input" id="page-slug" type="text" name="slug" autofocus>
        </div>
        <div class="modal-footer">
            <button type="button" class="button" data-dismiss="slugModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="button" class="button button-accent button-right" data-command="continue"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
            <button type="button" class="button button-link button-right" data-command="generate-slug" title="<?= $this->translate('panel.pages.changeSlug.generate') ?>"><?= $this->icon('bolt') ?></button>
        </div>
    </div>
</div>

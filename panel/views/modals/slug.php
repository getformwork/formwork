<div id="slugModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->translate('panel.pages.change-slug') ?></h3>
        <label for="page-slug"><?= $this->translate('panel.pages.new-page.slug') ?>:</label>
        <span class="label-suggestion">(<?= $this->translate('panel.pages.new-page.slug-suggestion') ?>)</span>
        <input id="page-slug" type="text" name="slug" autofocus>
        <div class="separator"></div>
        <button type="button" data-dismiss="slugModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right" data-command="continue"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
        <button type="button" class="button-link button-right" data-command="generate-slug" title="<?= $this->translate('panel.pages.change-slug.generate') ?>"><?= $this->icon('bolt') ?></button>
    </div>
</div>

<div id="slugModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->translate('admin.pages.change-slug') ?></h3>
        <label for="page-slug"><?= $this->translate('admin.pages.new-page.slug') ?>:</label>
        <span class="label-suggestion">(<?= $this->translate('admin.pages.new-page.slug-suggestion') ?>)</span>
        <input id="page-slug" type="text" name="slug" autofocus>
        <div class="separator"></div>
        <button type="button" data-dismiss="slugModal"><?= $this->translate('admin.modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right" data-command="continue"><?= $this->translate('admin.modal.action.continue') ?></button>
        <button type="button" class="button-link button-right" data-command="generate-slug" title="<?= $this->translate('admin.pages.change-slug.generate') ?>"><i class="i-bolt"></i></button>
    </div>
</div>

<div id="slugModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->label('pages.change-slug') ?></h3>
        <label for="page-slug"><?= $this->label('pages.new-page.slug') ?>:</label>
        <span class="label-suggestion">(<?= $this->label('pages.new-page.slug-suggestion') ?>)</span>
        <input id="page-slug" type="text" name="slug" autofocus>
        <div class="separator"></div>
        <button data-dismiss="slugModal"><?= $this->label('modal.action.cancel') ?></button>
        <button class="button-accent button-right" data-command="continue"><?= $this->label('modal.action.continue') ?></button>
        <button class="button-link button-right" data-command="generate-slug" title="<?= $this->label('pages.change-slug.generate') ?>"><i class="i-bolt"></i></button>
    </div>
</div>

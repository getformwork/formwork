<div id="changesModal" class="modal">
	<div class="modal-content">
		<form>
			<h3 class="caption"><?= $this->label('pages.changes.detected') ?></h3>
			<p class="modal-text"><?= $this->label('pages.changes.detected.prompt') ?></p>
			<button type="button" data-dismiss="changesModal"><?= $this->label('modal.action.cancel') ?></button>
			<button type="button" class="button-accent button-right button-continue" data-href="#"><?= $this->label('pages.changes.continue') ?></button>
		</form>
	</div>
</div>

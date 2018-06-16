<div id="imagesModal" class="modal">
	<div class="modal-content modal-size-large">
		<h3 class="caption"><?= $this->label('modal.images.title') ?></h3>
			<select class="image-picker">
<?php
			foreach ($page->images() as $image):
?>
				<option value="<?= $this->pageUri($page) . $image ?>"><?= $image ?></option>
<?php
			endforeach;
?>
			</select>
			<button type="button" data-dismiss="imagesModal"><?= $this->label('modal.action.cancel') ?></button>
			<button class="button-accent button-right image-picker-confirm" data-dismiss="imagesModal"><?= $this->label('modal.action.continue') ?></button>
	</div>
</div>

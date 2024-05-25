<div id="<?= $modal->id() ?>Modal" class="modal" aria-labelledby="<?= $modal->id() ?>ModalLabel">
    <div class="<?= $this->classes(['modal-container', 'modal-size-large' => $modal->size() === 'large']) ?>">
        <form <?php if ($modal->action()) : ?>action="<?= $panel->uri($modal->action()) ?>" <?php endif ?> method="post">
            <div class="modal-header">
                <div class="caption" id="<?= $modal->id() ?>ModalLabel"><?= $this->escape($modal->title()) ?></div>
            </div>
            <div class="modal-content">
                <?php if ($modal->message()) : ?>
                    <p class="modal-text"><?= $this->escape($modal->message()) ?></p>
                <?php endif ?>
                <?php foreach ($modal->fields() as $field) : ?>
                    <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
                <?php endforeach ?>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <?php foreach ($modal->buttons() as $button) : ?>
                    <button <?= $this->attr([
                                'type' => $button->formType(),
                                'class' => $this->classes([
                                    'button',
                                    'button-' . $button->variant(),
                                    'button-right' => $button->align() === 'right',
                                ]),
                                'data-dismiss' => $button->action() === 'dismiss' ? $modal->id() : null,
                                'data-command' => $button->action() === 'command' ? $button->command() : null,
                                'title' => $button->variant() === 'link' ? $button->label() : null,
                            ]) ?>>
                        <?php if ($button->icon()) : ?><?= $this->icon($button->icon()) ?> <?php endif; ?><?php if ($button->variant() !== 'link') : ?><?= $this->escape($button->label()) ?> <?php endif ?>
                    </button>
                <?php endforeach ?>
            </div>
        </form>
    </div>
</div>
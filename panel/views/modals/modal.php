<div id="<?= $modal->id() ?>" class="<?= $this->classes(['modal', 'open' => $modal->isOpen()]) ?>" aria-labelledby="<?= $modal->id() ?>Label">
    <div class="<?= $this->classes(['modal-container', 'modal-size-large' => $modal->size() === 'large']) ?>">
        <?php if ($modal->hasForm()): ?>
            <form <?= $this->attr([
                        'method' => 'post',
                        'action' => $modal->action() ? $panel->uri($modal->action()) : null,
                        'target' => $modal->target() ? $modal->target() : null,
                        'enctype' => !$modal->fields()->filterBy('type', 'upload')->isEmpty() ? 'multipart/form-data' : null,
                    ]) ?>>
            <?php endif ?>
            <div class="modal-header">
                <div class="caption" id="<?= $modal->id() ?>Label"><?= $this->escape($modal->title()) ?></div>
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
            <?php if ($modal->hasForm()): ?>
            </form>
        <?php endif ?>
    </div>
</div>
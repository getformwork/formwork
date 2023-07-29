<div>
    <label class="input-checkbox-label">
        <input <?= $this->attr([
            'type'     => 'checkbox',
            'class'    => 'input-checkbox',
            'id'       => $field->name(),
            'name'     => $field->formName(),
            'checked'  => $field->value() == true,
            'required' => $field->isRequired(),
            'disabled' => $field->isDisabled(),
            'hidden'   => $field->isHidden(),
        ]) ?>>
        <span class="input-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
<?= $this->insert('fields.partials.description') ?>

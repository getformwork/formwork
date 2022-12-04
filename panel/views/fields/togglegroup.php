<?= $this->layout('fields.field') ?>
<div>
    <fieldset <?= $this->attr([
        'id'          => $field->name(),
        'class'       => 'input-togglegroup',
        'disabled'    => $field->isDisabled(),
        'hidden'      => $field->isHidden()
    ]) ?>>
    <?php foreach ((array) $field->get('options') as $value => $label): ?>
        <label>
            <input <?= $this->attr([
                'type'    => 'radio',
                'name'    => $field->formName(),
                'value'   => $value,
                'checked' => $value == $field->value()
            ]) ?>>
            <span><?= $label ?></span>
        </label>
    <?php endforeach; ?>
    </fieldset>
</div>

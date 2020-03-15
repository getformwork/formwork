<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'        => 'email',
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'value'       => $field->value(),
    'placeholder' => $field->placeholder(),
    'required'    => $field->get('required'),
    'disabled'    => $field->get('disabled')
]) ?>>

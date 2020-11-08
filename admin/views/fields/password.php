<?= $this->insert('fields.label') ?>
<input <?= $this->attr([
    'type'         => 'password',
    'id'           => $field->name(),
    'name'         => $field->formName(),
    'value'        => $field->value(),
    'placeholder'  => $field->placeholder(),
    'minlength'    => $field->get('min'),
    'maxlength'    => $field->get('max'),
    'pattern'      => $field->get('pattern'),
    'autocomplete' => $field->get('autocomplete'),
    'required'     => $field->isRequired(),
    'disabled'     => $field->isDisabled()
]) ?>>

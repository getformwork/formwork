<?php $this->layout('fields.field') ?>
<select <?= $this->attr([
    'class'    => 'form-select',
    'id'       => $field->name(),
    'name'     => $field->formName(),
    'required' => $field->isRequired(),
    'disabled' => $field->isDisabled(),
    'hidden'   => $field->isHidden(),
]) ?>>
<?php
    if (!$field->isRequired()):
        ?>
    <option value="" <?php if ($field->value() === ''): ?> selected<?php endif ?>><?= $this->translate('page.none') ?></option>
<?php
    endif
?>
<?php
if ($field->get('allowSite')):
    ?>
    <option value="." <?php if ($field->value() === '.'): ?> selected<?php endif ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
<?php
endif
?>
<?php
foreach ($field->collection() as $page):
    ?>
    <option value="<?= $page->route() ?>"<?php if ($page->route() === $field->value()): ?> selected<?php endif ?>><?= str_repeat('— ', $page->level() - 1) . $page->title() ?></option>
<?php
endforeach
?>
</select>

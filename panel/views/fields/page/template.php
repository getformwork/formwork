<?= $this->layout('fields.field') ?>
<select id="page-template" name="template">
<?php
foreach ($templates as $template):
    $scheme = $formwork->schemes()->get('pages.' . $template);
?>
    <option value="<?= $template ?>"<?php if ($page->template()->name() === $template): ?> selected<?php endif; ?>><?= $scheme->title() ?></option>
<?php
endforeach;
?>
</select>

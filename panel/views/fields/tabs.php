<div class="tabs">
<?php foreach ($field->get('fields') as $tab): ?>
    <a <?= $this->attr([
                'class'    => ['tabs-tab', $tab->get('active') ? 'active' : ''],
                'data-tab' => $tab->name()
            ]) ?>><?= $tab->label() ?></a>
<?php endforeach; ?>
</div>
<?php $this->insert('fields', ['fields' => $field->get('fields')]) ?>

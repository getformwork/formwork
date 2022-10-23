<div class="sections">
<?php
    foreach ($field->get('fields') as $section):
?>
    <div <?= $this->attr(['class' => ['section',  $section->is('collapsible') ? 'collapsible' : '', $section->is('collapsed') ? 'collapsed' : '']]) ?>>
        <div class="section-header">
<?php
        if ($section->is('collapsible')):
?>
            <span class="section-toggle"><?= $this->icon('chevron-up') ?></span>
<?php
        endif;
?>
            <?= $section->label() ?>
        </div>
        <div class="section-content" style="padding: 0 .5rem;">
            <?php $this->insert('fields', ['fields' => $section->get('fields')]) ?>
        </div>
    </div>
<?php
    endforeach;
?>
</div>

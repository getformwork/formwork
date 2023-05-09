<div class="sections">
<?php
    foreach ($sections as $section):
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
        <div class="section-content pr-4 pl-4">
<?php
            foreach ($fields->getMultiple($section->get('fields', [])) as $field):
                ?>
    <?php if ($field->isVisible()): ?>
        <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
    <?php endif; ?>
<?php
            endforeach;
        ?>
        </div>
    </div>
<?php
    endforeach;
?>
</div>


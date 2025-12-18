<?php if (!$layout->tabs()->isEmpty()): ?>
    <?php $this->layout('fields.section-tabs') ?>
<?php endif ?>

<?php foreach ($layout->sections()->groupBy('tab', $layout->tabs()->first()?->name()) as $tabName => $sections): ?>
    <?php $this->define('sections') ?>
    <div class="sections">
        <?php foreach ($sections as $section) : ?>
            <section class="<?= $this->classes(['section',  'collapsible' => $section->is('collapsible'), 'collapsed' => $section->is('collapsed')]) ?>" id="section-<?= $this->escapeAttr($section->name()) ?>">
                <div class="section-header">
                    <?php if ($section->is('collapsible')) : ?>
                        <button type="button" class="button section-toggle mr-2" title="<?= $this->translate('panel.sections.toggle') ?>" aria-label="<?= $this->translate('panel.sections.toggle') ?>"><?= $this->icon('chevron-up') ?></button>
                    <?php endif ?>
                    <span class="caption"><?= $this->escape($section->label()) ?></span>
                </div>
                <div class="section-content">
                    <div class="row">
                        <?php foreach ($fields->getMultiple($section->get('fields', [])) as $field) : ?>
                            <?php if ($field->isVisible()) : ?>
                                <div class="<?= $this->classes(['col-md-' . $field->get('width', '12-12')]) ?>">
                                    <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
                                </div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                </div>
            </section>
        <?php endforeach ?>
    </div>
    <?php $this->end() ?>
    <?php if (!$layout->tabs()->isEmpty()): ?>
        <div class="<?= $this->classes(['tabs-panel', 'visible' => $layout->tabs()->first()->name() === $tabName]) ?>" role="tabpanel" aria-labelledby="tab-<?= $this->escapeAttr($tabName) ?>" data-tab="<?= $this->escapeAttr($tabName) ?>">
            <?= $this->block('sections') ?>
        </div>
    <?php else: ?>
        <?= $this->block('sections') ?>
    <?php endif ?>
<?php endforeach ?>
<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
</div>

<?= $tabs ?>

<div>
    <?php foreach ($info as $section => $data) : ?>
        <section class="section collapsible">
            <div class="section-header">
                <button type="button" class="button section-toggle mr-2" title="<?= $this->translate('panel.sections.toggle') ?>" aria-label="<?= $this->translate('panel.sections.toggle') ?>"><?= $this->icon('chevron-up') ?></button>
                <span class="caption"><?= $this->escape($section) ?></span>
            </div>
            <div class="section-content">
                <table class="table info-data">
                    <?php foreach ($data as $key => $value) : ?>
                        <tr>
                            <td class="table-cell info-data-key"><?= $this->escape($key) ?></td>
                            <td class="table-cell info-data-value"><?= $this->escape($value) ?></td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
        </section>
    <?php endforeach ?>
</div>
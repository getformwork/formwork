<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.tools.tools') ?></div>
</div>

<?= $tabs ?>

<div>
    <section class="section">
        <div class="row text-align-center">
            <?php foreach ($formwork as $key => $value) : ?>
                <div class="col-sm">
                    <div class="text-size-xl text-bold"><?= $this->escape((string) $value) ?></div>
                    <span class="text-size-sm"><?= $this->escape($key) ?></span>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <?php foreach ($warnings as $warning) : ?>
        <section class="section flex">
            <div class="text-color-warning mr-6"><?= $this->icon('exclamation-triangle') ?></div>
            <?= $this->markdown($warning) ?>
        </section>
    <?php endforeach ?>

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
                            <td class="table-cell info-data-value"><?= $this->escape((string) $value) ?></td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
        </section>
    <?php endforeach ?>
</div>
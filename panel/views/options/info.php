<?php $this->layout('panel') ?>

<div class="header">
        <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
</div>

<?= $tabs ?>

<div>
<?php
    foreach ($info as $section => $data):
        ?>
    <section class="section collapsible">
        <div class="section-header">
            <span class="section-toggle"><?= $this->icon('chevron-up') ?></span>
            <span class="caption"><?= $section ?></span>
        </div>
        <div class="section-content">
        <table class="info-data">
    <?php
                    foreach ($data as $key => $value):
                        ?>
            <tr>
                <td class="info-data-key"><?= $key ?></td>
                <td class="info-data-value"><?= $value ?></td>
            </tr>
    <?php
                    endforeach
            ?>
        </table>
        </div>
    </section>
<?php
    endforeach
?>
</div>

<?php $this->layout('panel') ?>

<div class="header">
        <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
</div>

<?= $tabs ?>

<div class="component">
<?php
    foreach ($info as $section => $data):
?>
    <div class="section-header"><?= $section ?></div>
    <table class="info-data">
<?php
        foreach ($data as $key => $value):
?>
        <tr>
            <td class="info-data-key"><?= $key ?></td>
            <td class="info-data-value"><?= $value ?></td>
        </tr>
<?php
        endforeach;
?>
    </table>
<?php
    endforeach;
?>
</div>

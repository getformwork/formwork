<?php $this->layout('panel') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.options.options') ?></h3>
    <?= $tabs ?>
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

<div class="tabs" style="margin-top:-1.5rem">
<?php
    foreach ($tabs as $tab):
        if ($panel->user()->permissions()->has('options.' . $tab)) :
?>
    <a class="tabs-tab<?= ($tab === $current) ? ' active' : '' ?>" href="<?= $panel->uri('/options/' . $tab . '/') ?>"><?= $this->translate('panel.options.' . $tab) ?></a>
<?php
        endif;
    endforeach;
?>
</div>

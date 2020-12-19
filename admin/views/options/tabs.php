<div class="tabs">
<?php
    foreach ($tabs as $tab):
        if ($admin->user()->permissions()->has('options.' . $tab)) :
?>
    <a class="tabs-tab<?= ($tab === $current) ? ' active' : '' ?>" href="<?= $admin->uri('/options/' . $tab . '/') ?>"><?= $this->translate('options.' . $tab) ?></a>
<?php
        endif;
    endforeach;
?>
</div>

<div class="tabs">
    <a class="tabs-tab<?= $tab === 'system' ? ' active' : '' ?>" href="<?= $this->uri('/options/system/') ?>"><?= $this->label('options.system') ?></a>
    <a class="tabs-tab<?= $tab === 'site' ? ' active' : '' ?>" href="<?= $this->uri('/options/site/') ?>"><?= $this->label('options.site') ?></a>
    <a class="tabs-tab<?= $tab === 'updates' ? ' active' : '' ?>" href="<?= $this->uri('/options/updates/') ?>"><?= $this->label('options.updates') ?></a>
    <a class="tabs-tab<?= $tab === 'info' ? ' active' : '' ?>" href="<?= $this->uri('/options/info/') ?>"><?= $this->label('options.info') ?></a>
</div>

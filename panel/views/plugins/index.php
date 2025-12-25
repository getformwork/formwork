<?php $this->layout('@panel.panel') ?>

<div class="header">
    <div class="header-icon"><?= $this->icon('puzzle-piece') ?></div>
    <div class="header-title"><?= $this->translate('panel.plugins.plugins') ?> <span class="badge"><?= count($plugins) ?></span></div>
</div>

<section class="section">
    <div class="plugins-list-headers" aria-hidden="true">
        <div class="plugins-headers-cell plugin-title truncate"><?= $this->translate('plugin.title') ?></div>
        <div class="plugins-headers-cell plugin-description truncate show-from-md"><?= $this->translate('plugin.description') ?></div>
        <div class="plugins-headers-cell plugin-status"><span class="show-from-xs mr-6"><?= $this->translate('plugin.status') ?></span></div>
    </div>
    <div class="plugins-list">
        <?php foreach ($plugins as $plugin) : ?>
            <div class="plugins-item">
                <div class="plugins-item-cell plugin-title truncate">
                    <a href="<?= $panel->uri("/plugins/{$plugin->id()}/") ?>"><?= $this->escape($plugin->manifest()->title() ?? $plugin->name()) ?></a>
                    <?php if ($plugin->manifest()->version()): ?><span><?= $this->escape($plugin->manifest()->version()) ?></span><?php endif ?>
                </div>
                <div class="plugins-item-cell plugin-description truncate show-from-md"><?php if ($plugin->manifest()->description()): ?><?= $this->escape($plugin->manifest()->description()) ?><?php endif ?></div>
                <div class="plugins-item-cell plugin-status">
                    <fieldset class="form-togglegroup">
                        <label class="form-label">
                            <input class="form-input plugin-status-toggle" type="radio" name="<?= $plugin->id() ?>[enabled]" value="1" <?= $plugin->isEnabled() ? 'checked' : '' ?> data-action="<?= $panel->uri("/plugins/{$plugin->id()}/enable/") ?>"><span><?= $this->translate('plugin.status.enabled') ?></span>
                        </label>
                        <label class="form-label">
                            <input class="form-input plugin-status-toggle" type="radio" name="<?= $plugin->id() ?>[enabled]" value="0" <?= !$plugin->isEnabled() ? 'checked' : '' ?> data-action="<?= $panel->uri("/plugins/{$plugin->id()}/disable/") ?>"><span><?= $this->translate('plugin.status.disabled') ?></span>
                        </label>
                    </fieldset>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</section>
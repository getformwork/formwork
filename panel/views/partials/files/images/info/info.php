<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.dimensions') ?>:</div>
    <?= $this->translate('panel.files.info.image.dimensions.widthByHeightPixels', $file->info()->width(), $file->info()->height()) ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.resolution') ?>:</div>
    <?= round(max($file->info()->width() * $file->info()->height() / 1e6, 0.1), 1) ?> MP
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.colorSpace') ?>:</div>
    <?= $file->info()->colorSpace()->value ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.colorDepth') ?>:</div>
    <?= $file->info()->colorDepth() ?> bit
</div>
<?php if ($file->info()->colorSpace()->value === 'PALETTE') : ?>
    <div class="col-sm-1-2 col-md-1-4 mb-4">
        <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.colorNumber') ?>:</div>
        <?= $file->info()->colorNumber() ?>
    </div>
<?php endif ?>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.colorProfile') ?>:</div>
    <?php if ($file->hasColorProfile() && $file->getColorProfile()->name()) : ?>
        <?= $this->escape($file->getColorProfile()->name()) ?>
    <?php elseif ($file->hasExifData() && $file->getExifData()->colorSpace()) : ?>
        <?= $this->escape($file->getExifData()->colorSpace()) ?> (EXIF)
    <?php else : ?>
        –
    <?php endif ?>
</div>
<?php if ($file->info()->isAnimation()) : ?>
    <div class="col-sm-1-2 col-md-1-4 mb-4">
        <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.framesCount') ?>:</div>
        <?= $this->translate('panel.files.info.image.frames', $file->info()->animationFrames()) ?>
    </div>
    <div class="col-sm-1-2 col-md-1-4 mb-4">
        <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.repeatsCount') ?>:</div>
        <?php if ($file->info()->animationRepeatCount() > 0) : ?><?= $this->translate('panel.files.info.image.repeats', $file->info()->animationRepeatCount()) ?><?php else : ?>∞<?php endif ?>
    </div>
<?php endif ?>
<?php if ($file->hasExifData()) : ?>
    <?php $this->insert('_files/images/info/exif', ['exif' => $file->getExifData()]) ?>
<?php endif ?>

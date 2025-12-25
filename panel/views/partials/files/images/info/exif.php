<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.creationDateAndTime') ?>:</div>
    <?= $this->escape($exif->dateTimeOriginal() ?? '-') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.camera') ?>:</div>
    <?= $this->escape($exif->makeAndModel() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.lensModel') ?>:</div>
    <?= $this->escape($exif->lensModel() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.focalLength') ?>:</div>
    <?= $this->escape($exif->focalLength() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.exposureTime') ?>:</div>
    <?= $this->escape($exif->exposureTime() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.aperture') ?>:</div>
    <?= $this->escape($exif->aperture() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.sensitivity') ?>:</div>
    <?= $this->escape($exif->photographicSensitivity() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.exposureCompensation') ?>:</div>
    <?= $this->escape($exif->exposureCompensation() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.exposureProgram') ?>:</div>
    <?= $this->escape($exif->exposureProgram() ?? '–') ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.whiteBalance') ?>:</div>
    <?php if ($exif->hasAutoWhiteBalance() === true) : ?>AWB<?php else : ?>–<?php endif ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.flash') ?>:</div>
    <?php if ($exif->hasFlashFired() !== null) : ?> <?= $exif->hasFlashFired() ? $this->icon('camera-flash') : $this->icon('camera-no-flash') ?><?php else : ?>–<?php endif ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.image.exif.meteringMode') ?>:</div>
    <?php if ($exif->meteringMode() !== null) : ?><?= $this->icon("camera-metering-{$exif->meteringMode()}") ?><?php else : ?>–<?php endif ?>
</div>
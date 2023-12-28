<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        dirname(__DIR__),
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::NAMING,
        SetList::INSTANCEOF,
    ]);

    $rectorConfig->skip([
        __DIR__ . '/views',
        dirname(__DIR__) . '/panel/node_modules',
        dirname(__DIR__) . '/panel/views',
        dirname(__DIR__) . '/site/templates',
        dirname(__DIR__) . '/vendor',
        ChangeSwitchToMatchRector::class,
        ClosureToArrowFunctionRector::class,
        CompactToVariablesRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};

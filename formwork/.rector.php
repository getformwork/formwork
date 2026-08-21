<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveDefaultValueFromAssignedPropertyRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true, naming: true, instanceOf: true, privatization: true)
    ->withImportNames(importShortClasses: false)
    ->withPaths([
        dirname(__DIR__),
    ])
    ->withSkip([
        __DIR__ . '/views',
        dirname(__DIR__) . '/cache',
        dirname(__DIR__) . '/panel/node_modules',
        dirname(__DIR__) . '/panel/views',
        dirname(__DIR__) . '/site/templates',
        dirname(__DIR__) . '/site/plugins',
        dirname(__DIR__) . '/vendor',
        ChangeSwitchToMatchRector::class,
        ClosureToArrowFunctionRector::class,
        CompactToVariablesRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        ReadOnlyPropertyRector::class,
        RemoveDefaultValueFromAssignedPropertyRector::class,
        RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
        RenamePropertyToMatchTypeRector::class,
        SafeDeclareStrictTypesRector::class,
    ]);

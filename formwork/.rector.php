<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassConstFetch\VariableConstFetchToClassConstFetchRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\FunctionLike\FunctionLikeToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;

return RectorConfig::configure()
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true, naming: true, instanceOf: true)
    ->withImportNames(importShortClasses: false)
    ->withPaths([
        dirname(__DIR__),
    ])
    ->withRules([
        PrivatizeFinalClassPropertyRector::class,
        PrivatizeFinalClassMethodRector::class,
    ])
    ->withSkip([
        __DIR__ . '/views',
        dirname(__DIR__) . '/cache',
        dirname(__DIR__) . '/panel/node_modules',
        dirname(__DIR__) . '/panel/views',
        dirname(__DIR__) . '/site/templates',
        dirname(__DIR__) . '/vendor',
        AddOverrideAttributeToOverriddenMethodsRector::class,
        ChangeSwitchToMatchRector::class,
        ClosureToArrowFunctionRector::class,
        CompactToVariablesRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        FunctionLikeToFirstClassCallableRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
        RenamePropertyToMatchTypeRector::class,
        VariableConstFetchToClassConstFetchRector::class,
    ]);

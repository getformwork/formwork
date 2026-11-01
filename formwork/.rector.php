<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\DirnameDirConcatStringToDirectStringPathRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector;
use Rector\CodingStyle\Rector\FuncCall\StrictInArrayRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Property\RemoveDefaultValueFromAssignedPropertyRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\WithCallbackIdenticalToStandaloneAssertsRector;
use Rector\PHPUnit\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureNeverReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        phpunitCodeQuality: true,
        phpunitMockToStub: true,
        phpunitNarrowAsserts: true,
    )
    ->withComposerBased(
        phpunit: true,
    )
    ->withImportNames(
        importShortClasses: false,
    )
    ->withPaths([
        dirname(__DIR__),
    ])
    ->withRules([
        StrictInArrayRector::class,
        StrictArraySearchRector::class,
    ])
    ->reportUnusedSkips()
    ->withSkip([
        __DIR__ . '/views',
        dirname(__DIR__) . '/cache',
        dirname(__DIR__) . '/panel/node_modules',
        dirname(__DIR__) . '/panel/views',
        dirname(__DIR__) . '/site/templates',
        dirname(__DIR__) . '/site/plugins',
        dirname(__DIR__) . '/tests/Unit/*/Fixtures/*',
        dirname(__DIR__) . '/vendor',
        AddArrowFunctionReturnTypeRector::class,
        AddClosureNeverReturnTypeRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        ChangeSwitchToMatchRector::class,
        ClosureToArrowFunctionRector::class,
        ClosureReturnTypeRector::class,
        CompactToVariablesRector::class,
        DeclareStrictTypesTestsRector::class,
        DirnameDirConcatStringToDirectStringPathRector::class,
        ExplicitNullableObjectInstanceRector::class,
        ReadOnlyPropertyRector::class,
        RemoveAlwaysTrueIfConditionRector::class => [
            dirname(__DIR__) . '/formwork/src/Users/Utils/Password.php',
        ],
        RemoveDefaultValueFromAssignedPropertyRector::class,
        RemovePhpVersionIdCheckRector::class,
        SafeDeclareStrictTypesRector::class,
        WithCallbackIdenticalToStandaloneAssertsRector::class,
    ]);

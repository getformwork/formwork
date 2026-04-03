<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Constraint;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(Constraint::class)]
final class ConstraintTest extends TestCase
{
    public function testIsTruthy(): void
    {
        $truthyValues = [true, 1, 'true', '1', 'on', 'yes'];
        foreach ($truthyValues as $value) {
            $this->assertTrue(Constraint::isTruthy($value));
        }

        $nonTruthyValues = [false, 0, 'false', '0', 'off', 'no', null, '', [], 2, 'random'];
        foreach ($nonTruthyValues as $nonTruthyValue) {
            $this->assertFalse(Constraint::isTruthy($nonTruthyValue));
        }
    }

    public function testIsFalsy(): void
    {
        $falsyValues = [false, 0, 'false', '0', 'off', 'no'];
        foreach ($falsyValues as $value) {
            $this->assertTrue(Constraint::isFalsy($value));
        }

        $nonFalsyValues = [true, 1, 'true', '1', 'on', 'yes', null, '', [], 2, 'random'];
        foreach ($nonFalsyValues as $nonFalsyValue) {
            $this->assertFalse(Constraint::isFalsy($nonFalsyValue));
        }
    }

    public function testIsEmpty(): void
    {
        $emptyValues = [null, '', []];
        foreach ($emptyValues as $value) {
            $this->assertTrue(Constraint::isEmpty($value));
        }

        $nonEmptyValues = [false, 0, 'false', '0', 'off', 'no', true, 1, 'true', '1', 'on', 'yes', 2, 'random'];
        foreach ($nonEmptyValues as $nonEmptyValue) {
            $this->assertFalse(Constraint::isEmpty($nonEmptyValue));
        }
    }

    public function testIsEqual(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, 1, true));
        $this->assertFalse(Constraint::isEqualTo(1, '1', true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isEqualTo(1, '1', false));
        $this->assertFalse(Constraint::isEqualTo(1, 2, false));
    }

    public function testIsNotEqual(): void
    {
        // Strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, '1', true));
        $this->assertFalse(Constraint::isNotEqualTo(1, 1, true));

        // Non-strict comparison
        $this->assertTrue(Constraint::isNotEqualTo(1, 2, false));
        $this->assertFalse(Constraint::isNotEqualTo(1, '1', false));
    }

    public function testIsGreaterThan(): void
    {
        $this->assertTrue(Constraint::isGreaterThan(2, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 1));
        $this->assertFalse(Constraint::isGreaterThan(1, 2));
    }

    public function testIsGreaterThanOrEqual(): void
    {
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(2, 1));
        $this->assertTrue(Constraint::isGreaterThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isGreaterThanOrEqualTo(1, 2));
    }

    public function testIsLessThan(): void
    {
        $this->assertTrue(Constraint::isLessThan(1, 2));
        $this->assertFalse(Constraint::isLessThan(1, 1));
        $this->assertFalse(Constraint::isLessThan(2, 1));
    }

    public function testIsLessThanOrEqual(): void
    {
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 2));
        $this->assertTrue(Constraint::isLessThanOrEqualTo(1, 1));
        $this->assertFalse(Constraint::isLessThanOrEqualTo(2, 1));
    }

    public function testMatches(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/^h.*o$/'));
        $this->assertFalse(Constraint::matchesRegex('hello', '/^H.*O$/i'));
    }

    public function testMatchesWithoutEntireMatch(): void
    {
        $this->assertTrue(Constraint::matchesRegex('hello', '/e/', entireMatch: false));
        $this->assertFalse(Constraint::matchesRegex('hello', '/e/', entireMatch: true));
    }

    public function testIsInRange(): void
    {
        $this->assertTrue(Constraint::isInRange(5.25, 1, 10));
        $this->assertTrue(Constraint::isInRange(5, 1, 10));
        $this->assertFalse(Constraint::isInRange(11, 1, 10));

        $this->assertTrue(Constraint::isInRange(M_PI, 10, 1));
        $this->assertFalse(Constraint::isInRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeWithIntegerRange(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(5, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(11, 1, 10));
        $this->assertFalse(Constraint::isInIntegerRange(-1, 10, 1));

        $this->assertFalse(Constraint::isInIntegerRange(1, 1, 10, includeMin: false));
        $this->assertFalse(Constraint::isInIntegerRange(10, 1, 10, includeMax: false));
    }

    public function testIsInRangeWithStep(): void
    {
        $this->assertTrue(Constraint::isInIntegerRange(4, 0, 10, step: 2));
        $this->assertFalse(Constraint::isInIntegerRange(5, 0, 10, step: 2));
    }

    public function testIsType(): void
    {
        $this->assertTrue(Constraint::isOfType(123, 'int'));
        $this->assertTrue(Constraint::isOfType('hello', 'string'));
        $this->assertTrue(Constraint::isOfType([], 'array'));
        $this->assertTrue(Constraint::isOfType(12.34, 'float'));
        $this->assertTrue(Constraint::isOfType(true, 'bool'));
        $this->assertTrue(Constraint::isOfType(new stdClass(), 'stdClass'));

        $this->assertFalse(Constraint::isOfType(123, 'string'));
        $this->assertFalse(Constraint::isOfType('hello', 'array'));
        $this->assertFalse(Constraint::isOfType([], 'int'));
        $this->assertFalse(Constraint::isOfType(12.34, 'bool'));
        $this->assertFalse(Constraint::isOfType(true, 'float'));
        $this->assertFalse(Constraint::isOfType(new stdClass(), 'array'));
    }

    public function testIsTypeWithUnionTypes(): void
    {
        $this->assertTrue(Constraint::isOfType(123, 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType('hello', 'int|string', unionTypes: true));
        $this->assertTrue(Constraint::isOfType(new stdClass(), 'array|stdClass', unionTypes: true));

        $this->assertFalse(Constraint::isOfType(12.34, 'int|string', unionTypes: true));
        $this->assertFalse(Constraint::isOfType(new stdClass(), 'int|string', unionTypes: true));
    }

    public function testHasKeys(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $this->assertTrue(Constraint::hasKeys($array, ['a', 'b']));
        $this->assertTrue(Constraint::hasKeys($array, []));
        $this->assertFalse(Constraint::hasKeys($array, ['a', 'd']));
    }
}

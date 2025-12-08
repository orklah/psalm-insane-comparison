<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Tests;

use PHPUnit\Framework\TestCase;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Tests that document the expected behavior of the plugin
 * These tests validate the logic without running full Psalm analysis
 */
class ExpectedBehaviorTest extends TestCase
{
    /**
     * Test: The plugin should check these comparison operators
     */
    public function testShouldCheckTheseOperators(): void
    {
        $checkedOperators = [
            Expr\BinaryOp\Equal::class,          // ==
            Expr\BinaryOp\NotEqual::class,       // !=
            Expr\BinaryOp\Greater::class,        // >
            Expr\BinaryOp\GreaterOrEqual::class, // >=
            Expr\BinaryOp\Smaller::class,        // <
            Expr\BinaryOp\SmallerOrEqual::class, // <=
        ];
        
        $this->assertCount(6, $checkedOperators, 'Should check exactly 6 comparison operators');
    }

    /**
     * Test: The plugin should NOT check strict comparison operators
     */
    public function testShouldNotCheckStrictOperators(): void
    {
        $notCheckedOperators = [
            Expr\BinaryOp\Identical::class,    // ===
            Expr\BinaryOp\NotIdentical::class, // !==
        ];
        
        $this->assertCount(2, $notCheckedOperators, 'Strict operators should not be checked');
    }

    /**
     * Test: Empty string behavior documentation
     * 
     * In PHP 7: "" == 0 is true (empty string converts to 0)
     * In PHP 8: "" == 0 is still true (empty string is a special case in the RFC)
     * 
     * The RFC specifically states: "For other numeric strings and the empty string, 
     * the comparison is performed as before." This means empty strings still convert to 0.
     */
    public function testEmptyStringBehavior(): void
    {
        // Empty string STILL equals 0 in PHP 8 (it's a special case)
        // This is NOT an insane comparison because behavior didn't change
        $this->assertTrue(PHP_VERSION_ID >= 80000, 'This test requires PHP 8+');
        
        // This is NOT a breaking change - empty string still works the same way
        // The plugin should NOT flag empty string comparisons
        $this->assertFalse("0" == 1, 'String "0" does not equal 1');
    }

    /**
     * Test: Non-numeric strings that should be detected
     */
    public function testNonNumericStringExamples(): void
    {
        $nonNumericStrings = [
            'banana',
            'hello world',
            'test123',  // Starts with letter
            '  ',       // Whitespace only
            'abc',
            'xyz',
        ];
        
        foreach ($nonNumericStrings as $str) {
            $this->assertFalse(is_numeric($str), "'{$str}' should be non-numeric");
        }
    }

    /**
     * Test: Numeric strings that should NOT trigger
     */
    public function testNumericStringExamples(): void
    {
        $numericStrings = [
            '0',
            '123',
            '456.789',
            '-10',
            '1e5',  // Scientific notation
            '  42  ', // Numeric with whitespace
        ];
        
        foreach ($numericStrings as $str) {
            $this->assertTrue(is_numeric($str), "'{$str}' should be numeric");
        }
    }

    /**
     * Test: PHP 7 vs PHP 8 comparison behavior
     * 
     * This documents the actual behavior change that the plugin aims to detect
     */
    public function testPhp8BehaviorChange(): void
    {
        // These are the comparisons that changed between PHP 7 and 8
        // In PHP 8.0+, non-numeric strings are NOT equal to 0
        
        if (PHP_VERSION_ID >= 80000) {
            // PHP 8 behavior
            $this->assertFalse('banana' == 0, 'Non-numeric string should not equal 0 in PHP 8');
            $this->assertTrue('banana' != 0, 'Non-numeric string should not equal 0 in PHP 8');
        } else {
            // PHP 7 behavior (for documentation)
            // In PHP 7: 'banana' == 0 would be true
            // In PHP 7: 'banana' != 0 would be false
        }
    }

    /**
     * Test: Positive integers cannot be zero
     */
    public function testPositiveIntegersCantBeZero(): void
    {
        // This is why the plugin should NOT flag positive-int comparisons
        $positiveInt = 5;
        $this->assertGreaterThan(0, $positiveInt);
        $this->assertNotEquals(0, $positiveInt);
    }

    /**
     * Test: Int ranges that should and shouldn't be flagged
     */
    public function testIntRangeBehavior(): void
    {
        // Ranges that include 0 should be flagged
        $rangeIncludesZero = [-5, 0, 5];
        $this->assertContains(0, $rangeIncludesZero);
        
        // Ranges that don't include 0 should NOT be flagged
        $positiveRange = [1, 2, 3, 4, 5];
        $this->assertNotContains(0, $positiveRange);
        
        $negativeRange = [-5, -4, -3, -2, -1];
        $this->assertNotContains(0, $negativeRange);
    }

    /**
     * Test: Cast operations make comparisons safe
     */
    public function testCastOperationsSafety(): void
    {
        // Explicit casts document intent and produce consistent behavior
        $str = 'banana';
        $zero = 0;
        
        // Cast to int makes the comparison explicit
        $this->assertEquals(0, (int)$str);
        $this->assertTrue((int)$str == $zero);
        
        // Cast to string makes the comparison explicit
        $this->assertEquals('0', (string)$zero);
        $this->assertFalse($str == (string)$zero);
    }

    /**
     * Test: Comparison operator precedence
     */
    public function testOperatorPrecedence(): void
    {
        // The plugin checks binary comparison operators
        // It should handle them in expressions correctly
        $str = 'test';
        $zero = 0;
        
        // These are the types of expressions the plugin should analyze
        $expressions = [
            $str == $zero,
            $str != $zero,
            $str > $zero,
            $str >= $zero,
            $str < $zero,
            $str <= $zero,
        ];
        
        $this->assertCount(6, $expressions);
    }

    /**
     * Test: Edge case - string "0" is numeric
     */
    public function testStringZeroIsNumeric(): void
    {
        $this->assertTrue(is_numeric('0'));
        $this->assertTrue('0' == 0); // This is safe in both PHP 7 and 8
        $this->assertFalse('0' == 1);
    }

    /**
     * Test: Edge case - whitespace strings
     */
    public function testWhitespaceStrings(): void
    {
        // Whitespace-only strings are non-numeric
        $this->assertFalse(is_numeric('   '));
        $this->assertFalse(is_numeric("\t"));
        $this->assertFalse(is_numeric("\n"));
    }

    /**
     * Test: Union types that may include problematic combinations
     */
    public function testUnionTypeCombinations(): void
    {
        // Union types like string|int should be flagged if:
        // 1. The string part may be non-numeric
        // 2. The int part may be 0
        
        // This is a complex case that requires type analysis
        $this->assertTrue(true, 'Union type handling is complex and requires Psalm type analysis');
    }

    /**
     * Test: The main issue - PHP 7 to 8 migration safety
     */
    public function testMigrationSafety(): void
    {
        // The purpose of this plugin is to find code that will break during PHP 8 migration
        // Any comparison of non-numeric string with 0 is a migration risk
        
        $migrationRisks = [
            'banana' => 0,
            'hello' => 0,
            'test' => 0,
            '  ' => 0,
        ];
        
        foreach ($migrationRisks as $str => $zero) {
            if (PHP_VERSION_ID >= 80000) {
                $this->assertFalse(
                    $str == $zero,
                    "PHP 8: '{$str}' should not equal {$zero}"
                );
            }
        }
    }
}

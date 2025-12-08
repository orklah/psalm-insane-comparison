# Test Suite for Psalm Insane Comparison Plugin

This directory contains comprehensive tests for the `psalm-insane-comparison` plugin.

## Overview

The test suite validates that the plugin correctly detects "insane comparisons" - comparisons between strings and the integer 0 that behave differently between PHP 7 and PHP 8 due to [RFC: Saner string to number comparisons](https://wiki.php.net/rfc/string_to_number_comparison).

## Test Files

### PluginTest.php
Unit tests for the `Plugin` class that verify:
- Plugin can be instantiated
- Plugin implements `PluginEntryPointInterface`
- Plugin is callable
- Plugin has the required `__invoke` method
- Hook class exists and is properly structured

### InsaneComparisonTest.php
Unit tests for the `InsaneComparisonAnalyzer` hook that verify:
- Hook implements `AfterExpressionAnalysisInterface`
- InsaneComparison issue extends `PluginIssue`
- Method signatures are correct
- Method visibility is appropriate
- Return types are correct

### IntegrationTest.php
Integration tests that run Psalm on actual PHP code to verify detection:

**Should Detect (Expected Failures in PHP 8):**
- `"banana" == 0` - non-numeric string compared to zero
- `"test" != 0` - with inequality operator
- `"test" > 0` - with greater than operator
- `"test" >= 0` - with greater or equal operator
- `"test" < 0` - with less than operator
- `"test" <= 0` - with less or equal operator
- `0 == "banana"` - string on right side
- `"hello" == 0` - literal string comparison

**Should NOT Detect (Safe Comparisons):**
- `numeric-string == 0` - numeric strings are safe
- `"banana" == positive-int` - positive integers can't be 0
- `"banana" === 0` - strict comparison
- `(int)"banana" == 0` - with cast
- `"banana" == (string)0` - with cast
- `"banana" == int<1, 100>` - int range excluding 0

## Running the Tests

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test File
```bash
./vendor/bin/phpunit tests/PluginTest.php
./vendor/bin/phpunit tests/InsaneComparisonTest.php
./vendor/bin/phpunit tests/IntegrationTest.php
```

### Run with Detailed Output
```bash
./vendor/bin/phpunit --testdox
```

### Run with Coverage (requires Xdebug)
```bash
./vendor/bin/phpunit --coverage-text
```

## Test Philosophy

These tests are written to verify **what the plugin should do**, not just what it currently does. This means:

1. **Failing tests are expected** - They help identify bugs and areas for improvement
2. **Tests document expected behavior** - Each test describes the intended functionality
3. **Integration tests validate real-world usage** - They run actual Psalm analysis on code samples
4. **Unit tests validate structure** - They ensure the plugin follows Psalm's conventions

## Understanding Test Results

### Green Tests ✓
These features are working correctly according to the specification.

### Red Tests ✗
These indicate either:
- A bug in the plugin code that needs fixing
- A limitation in the current implementation
- An edge case that needs to be handled

Failing tests are valuable - they show us where the code needs improvement!

## What Should the Plugin Detect?

According to the [PHP RFC](https://wiki.php.net/rfc/string_to_number_comparison), the plugin should detect comparisons where:

1. One operand is (or may be) a **non-numeric string**
2. The other operand is (or may be) the **integer 0**
3. A **non-strict comparison operator** is used (`==`, `!=`, `>`, `>=`, `<`, `<=`)

### Examples of Insane Comparisons

```php
// PHP 7: true, PHP 8: false
"banana" == 0

// PHP 7: false, PHP 8: true  
"banana" != 0

// These are all insane comparisons in PHP 7
"test" > 0   // true in PHP 7, false in PHP 8
"test" >= 0  // true in PHP 7, false in PHP 8
"test" < 0   // false in PHP 7, true in PHP 8
"test" <= 0  // false in PHP 7, true in PHP 8
```

### What Should NOT Trigger

```php
// Numeric strings - behave the same in PHP 7 and 8
"123" == 0  // false in both

// Strict comparison - always worked correctly
"banana" === 0  // false in both

// Positive integers can't be 0
$positiveInt == "banana"  // Safe

// With casts - explicit conversion
(int)"banana" == 0  // Safe, explicit
"banana" == (string)0  // Safe, explicit

// Int ranges excluding 0
int<1, 100> == "banana"  // Safe, can't be 0
```

## Contributing

When adding new tests:
1. Write a descriptive test name that explains the scenario
2. Add a docblock explaining what should happen
3. Include sample code that demonstrates the behavior
4. Consider both positive and negative test cases
5. Test edge cases and boundary conditions

## Known Issues

- Integration tests may be skipped if Psalm version requirements are not met
- Some edge cases around empty strings and whitespace may need refinement
- Union types with both string and int may need special handling

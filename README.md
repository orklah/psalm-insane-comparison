# psalm-insane-comparison
A [Psalm](https://github.com/vimeo/psalm) plugin to detect code susceptible to change behaviour with the introduction of [PHP RFC: Saner string to number comparisons](https://wiki.php.net/rfc/string_to_number_comparison)

Installation:

```console
$ composer require --dev orklah/psalm-insane-comparison
$ vendor/bin/psalm-plugin enable orklah/psalm-insane-comparison
```

Usage:

Run your usual Psalm command:
```console
$ vendor/bin/psalm
```

Explanation:

Before PHP8, comparison between a non-empty-string and the literal int 0 resulted in `true`. This is no longer the case with the [PHP RFC: Saner string to number comparisons](https://wiki.php.net/rfc/string_to_number_comparison).
```php
$a = 'banana';
$b = 0;
if($a == $b){
    echo 'PHP 7 will display this';
}
else{
    echo 'PHP 8 will display this instead';
}
```
This plugin helps identify those case to check them before migrating.

You can solve this issue in a lot of ways:
- use strict equality:
```php
$a = 'banana';
$b = 0;
if($a === $b){
    echo 'This is impossible';
}
else{
    echo 'PHP 7 and 8 will both display this';
}
```
- use a cast to make both operands the same type:
```php
$a = 'banana';
$b = 0;
if((int)$a == $b){
    echo 'PHP 7 and 8 will both display this';
}
else{
    echo 'This is impossible';
}
```
```php
$a = 'banana';
$b = 0;
if($a == (string)$b){
    echo 'This is impossible';
}
else{
    echo 'PHP 7 and 8 will both display this';
}
```
- Make psalm understand you're working with positive-ints when the int operand is not a literal:
```php
$a = 'banana';
/** @var positive-int $b */
if($a == $b){
    echo 'This is impossible';
}
else{
    echo 'PHP 7 and 8 will both display this';
}
```
- Make psalm understand you're working with numeric-strings when the string operand is not a literal:
```php
/** @var numeric-string $a */
$b = 0;
if($a == $b){
    echo 'PHP 7 and 8 will both display this depending on the value of $a';
}
else{
    echo 'PHP 7 and 8 will both display this depending on the value of $a';
}
```

## Development

### Running Tests

The plugin includes a comprehensive test suite:

```console
$ composer test              # Run all tests
$ composer test-detailed     # Run tests with detailed output
$ composer test-coverage     # Run tests with coverage report
```

Or directly with PHPUnit:
```console
$ ./vendor/bin/phpunit
$ ./vendor/bin/phpunit --testdox
$ ./vendor/bin/phpunit tests/PluginTest.php
```

### Test Suite

The test suite includes:
- **Unit Tests**: Validate plugin structure and hook implementation
- **Expected Behavior Tests**: Document PHP 7 vs PHP 8 comparison behavior
- **Integration Tests**: Test real-world scenarios with Psalm analysis

See [tests/README.md](tests/README.md) for detailed test documentation.

### What Gets Detected

The plugin will report `InsaneComparison` issues for:
- Non-numeric strings compared to 0 with `==`, `!=`, `>`, `>=`, `<`, `<=`
- General `string` or `int` types that may include problematic values

The plugin will NOT report issues for:
- Strict comparisons (`===`, `!==`)
- Numeric strings (e.g., `"123"`)
- `numeric-string` type annotations
- `positive-int` type (can't be 0)
- Int ranges excluding 0 (e.g., `int<1, 100>`)
- Comparisons with explicit casts
- Empty strings (behavior unchanged in PHP 8)

## Contributing

Contributions are welcome! Please:
1. Run the test suite before submitting PRs
2. Add tests for new functionality
3. Update documentation as needed

## License

MIT

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

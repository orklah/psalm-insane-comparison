<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests that run Psalm on actual PHP files to verify
 * the plugin detects insane comparisons correctly.
 */
class IntegrationTest extends TestCase
{
    /** @var string */
    private $testFilesDir;
    
    /** @var string */
    private $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__);
        $this->testFilesDir = sys_get_temp_dir() . '/psalm-insane-comparison-test-' . uniqid();
        mkdir($this->testFilesDir, 0777, true);
        
        // Create a psalm.xml config for tests
        $psalmConfig = <<<XML
<?xml version="1.0"?>
<psalm
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="." />
    </projectFiles>
    <plugins>
        <pluginClass class="Orklah\\PsalmInsaneComparison\\Plugin"/>
    </plugins>
</psalm>
XML;
        file_put_contents($this->testFilesDir . '/psalm.xml', $psalmConfig);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->deleteDirectory($this->testFilesDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function runPsalm(string $code): array
    {
        $testFile = $this->testFilesDir . '/test.php';
        file_put_contents($testFile, $code);
        
        $psalmBinary = $this->projectRoot . '/vendor/bin/psalm';
        $command = sprintf(
            '%s --config=%s --no-cache --root=%s %s 2>&1',
            escapeshellarg($psalmBinary),
            escapeshellarg($this->testFilesDir . '/psalm.xml'),
            escapeshellarg($this->projectRoot),
            escapeshellarg($testFile)
        );
        
        exec($command, $output, $returnCode);
        $outputString = implode("\n", $output);
        
        // Check if Psalm version issue
        if (strpos($outputString, 'Psalm requires a PHP version') !== false) {
            $this->markTestSkipped('Psalm version requirement not met: ' . $outputString);
        }
        
        // Check for actual issues reported
        if (strpos($outputString, 'InsaneComparison') !== false) {
            return ['has_issue' => true];
        }
        
        // Parse JSON output if available
        $jsonStart = strpos($outputString, '{');
        if ($jsonStart !== false) {
            $jsonString = substr($outputString, $jsonStart);
            $result = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($result)) {
                return $result;
            }
        }
        
        return [];
    }

    private function hasInsaneComparisonIssue(array $result): bool
    {
        if (!isset($result) || empty($result)) {
            return false;
        }
        
        // Quick check for our simplified result
        if (isset($result['has_issue'])) {
            return $result['has_issue'];
        }
        
        // Check for InsaneComparison in the results
        $allIssues = [];
        
        // Psalm output format varies, try different structures
        if (isset($result['issues'])) {
            $allIssues = $result['issues'];
        } elseif (isset($result[0]['type'])) {
            $allIssues = $result;
        }
        
        foreach ($allIssues as $issue) {
            if (isset($issue['type']) && strpos($issue['type'], 'InsaneComparison') !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Test: String compared to literal 0 should trigger InsaneComparison
     */
    public function testDetectsStringEqualsZero(): void
    {
        $code = <<<'PHP'
<?php
$a = "banana";
$b = 0;
if ($a == $b) {
    echo "PHP 7 behavior";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertTrue(
            $this->hasInsaneComparisonIssue($result),
            'Should detect insane comparison: "banana" == 0'
        );
    }

    /**
     * Test: Numeric string should NOT trigger
     */
    public function testDoesNotDetectNumericString(): void
    {
        $code = <<<'PHP'
<?php
/** @var numeric-string */
$a = "123";
$b = 0;
if ($a == $b) {
    echo "Safe comparison";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertFalse(
            $this->hasInsaneComparisonIssue($result),
            'Should NOT detect issue with numeric-string'
        );
    }

    /**
     * Test: Positive int should NOT trigger
     */
    public function testDoesNotDetectPositiveInt(): void
    {
        $code = <<<'PHP'
<?php
$a = "banana";
/** @var positive-int */
$b = 5;
if ($a == $b) {
    echo "Safe comparison";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertFalse(
            $this->hasInsaneComparisonIssue($result),
            'Should NOT detect issue with positive-int'
        );
    }

    /**
     * Test: Strict comparison should NOT trigger
     */
    public function testDoesNotDetectStrictComparison(): void
    {
        $code = <<<'PHP'
<?php
$a = "banana";
$b = 0;
if ($a === $b) {
    echo "Never true";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertFalse(
            $this->hasInsaneComparisonIssue($result),
            'Should NOT detect issue with strict comparison (===)'
        );
    }

    /**
     * Test: All comparison operators should trigger
     */
    public function testDetectsAllComparisonOperators(): void
    {
        $operators = ['==', '!=', '>', '>=', '<', '<='];
        
        foreach ($operators as $operator) {
            $code = <<<PHP
<?php
\$a = "banana";
\$b = 0;
if (\$a $operator \$b) {
    echo "test";
}
PHP;
            
            $result = $this->runPsalm($code);
            $this->assertTrue(
                $this->hasInsaneComparisonIssue($result),
                "Should detect insane comparison with operator: $operator"
            );
        }
    }

    /**
     * Test: Int cast should prevent detection
     */
    public function testDoesNotDetectWithCast(): void
    {
        $code = <<<'PHP'
<?php
$a = "banana";
$b = 0;
if ((int)$a == $b) {
    echo "Safe with cast";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertFalse(
            $this->hasInsaneComparisonIssue($result),
            'Should NOT detect issue when variable is cast to int'
        );
    }

    /**
     * Test: Literal non-numeric string should trigger
     */
    public function testDetectsLiteralString(): void
    {
        $code = <<<'PHP'
<?php
if ("hello" == 0) {
    echo "PHP 7 behavior";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertTrue(
            $this->hasInsaneComparisonIssue($result),
            'Should detect insane comparison with literal string'
        );
    }

    /**
     * Test: String on right side should also trigger
     */
    public function testDetectsStringOnRight(): void
    {
        $code = <<<'PHP'
<?php
$a = 0;
$b = "banana";
if ($a == $b) {
    echo "PHP 7 behavior";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertTrue(
            $this->hasInsaneComparisonIssue($result),
            'Should detect insane comparison when string is on right'
        );
    }

    /**
     * Test: Int range excluding 0 should NOT trigger
     */
    public function testDoesNotDetectIntRangeExcludingZero(): void
    {
        $code = <<<'PHP'
<?php
$a = "banana";
/** @var int<1, 100> */
$b = 5;
if ($a == $b) {
    echo "Safe";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertFalse(
            $this->hasInsaneComparisonIssue($result),
            'Should NOT detect issue with int range excluding 0'
        );
    }

    /**
     * Test: Empty string compared to 0 should trigger InsaneComparison
     * 
     * This test documents that empty strings SHOULD be flagged because their
     * behavior changed between PHP 7 and PHP 8:
     * - PHP 7: "" == 0 is true
     * - PHP 8: "" == 0 is false
     * 
     * @group failing
     */
    public function testDetectsEmptyStringEqualsZero(): void
    {
        $code = <<<'PHP'
<?php
if ("" == 0) {
    echo "PHP 7 displays this";
}
PHP;
        
        $result = $this->runPsalm($code);
        $this->assertTrue(
            $this->hasInsaneComparisonIssue($result),
            'Should detect insane comparison: "" == 0 (behavior changed in PHP 8)'
        );
    }
}

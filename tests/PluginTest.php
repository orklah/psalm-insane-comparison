<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Tests;

use Orklah\PsalmInsaneComparison\Hooks\InsaneComparisonAnalyzer;
use Orklah\PsalmInsaneComparison\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * Test that the plugin successfully registers the hook
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that InsaneComparisonAnalyzer implements the correct interface
     */
    public function testHookImplementsCorrectInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                InsaneComparisonAnalyzer::class,
                \Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface::class
            ),
            'InsaneComparisonAnalyzer should implement AfterExpressionAnalysisInterface'
        );
    }

    /**
     * Test that the Plugin implements PluginEntryPointInterface
     */
    public function testPluginImplementsEntryPointInterface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                Plugin::class,
                \Psalm\Plugin\PluginEntryPointInterface::class
            ),
            'Plugin should implement PluginEntryPointInterface'
        );
    }

    /**
     * Test that the Plugin is callable
     */
    public function testPluginIsCallable(): void
    {
        $plugin = new Plugin();
        $this->assertTrue(
            is_callable($plugin),
            'Plugin should be callable'
        );
    }

    /**
     * Test that the Plugin has the __invoke method
     */
    public function testPluginHasInvokeMethod(): void
    {
        $this->assertTrue(
            method_exists(Plugin::class, '__invoke'),
            'Plugin should have __invoke method'
        );
    }
    
    /**
     * Test that the InsaneComparisonAnalyzer class exists
     */
    public function testHookClassExists(): void
    {
        $this->assertTrue(
            class_exists(InsaneComparisonAnalyzer::class),
            'InsaneComparisonAnalyzer class should exist'
        );
    }
}


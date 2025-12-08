<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Tests;

use Orklah\PsalmInsaneComparison\Hooks\InsaneComparison;
use Orklah\PsalmInsaneComparison\Hooks\InsaneComparisonAnalyzer;
use Orklah\PsalmInsaneComparison\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the InsaneComparisonAnalyzer hook
 * 
 * These tests verify the plugin detects insane comparisons that would
 * behave differently between PHP 7 and PHP 8.
 */
class InsaneComparisonTest extends TestCase
{
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
     * Test that InsaneComparison extends PluginIssue
     */
    public function testInsaneComparisonExtendsPluginIssue(): void
    {
        $this->assertTrue(
            is_subclass_of(
                InsaneComparison::class,
                \Psalm\Issue\PluginIssue::class
            ),
            'InsaneComparison should extend PluginIssue'
        );
    }

    /**
     * Test that the afterExpressionAnalysis method exists
     */
    public function testAfterExpressionAnalysisMethodExists(): void
    {
        $this->assertTrue(
            method_exists(InsaneComparisonAnalyzer::class, 'afterExpressionAnalysis'),
            'InsaneComparisonAnalyzer should have afterExpressionAnalysis method'
        );
    }

    /**
     * Test that the afterExpressionAnalysis method is static
     */
    public function testAfterExpressionAnalysisIsStatic(): void
    {
        $reflection = new \ReflectionMethod(
            InsaneComparisonAnalyzer::class,
            'afterExpressionAnalysis'
        );
        
        $this->assertTrue(
            $reflection->isStatic(),
            'afterExpressionAnalysis should be a static method'
        );
    }

    /**
     * Test that the afterExpressionAnalysis method is public
     */
    public function testAfterExpressionAnalysisIsPublic(): void
    {
        $reflection = new \ReflectionMethod(
            InsaneComparisonAnalyzer::class,
            'afterExpressionAnalysis'
        );
        
        $this->assertTrue(
            $reflection->isPublic(),
            'afterExpressionAnalysis should be public'
        );
    }

    /**
     * Test that the afterExpressionAnalysis method has the correct signature
     */
    public function testAfterExpressionAnalysisSignature(): void
    {
        $reflection = new \ReflectionMethod(
            InsaneComparisonAnalyzer::class,
            'afterExpressionAnalysis'
        );
        
        $parameters = $reflection->getParameters();
        
        $this->assertCount(
            1,
            $parameters,
            'afterExpressionAnalysis should accept exactly 1 parameter'
        );
        
        $this->assertEquals(
            'event',
            $parameters[0]->getName(),
            'First parameter should be named $event'
        );
        
        $parameterType = $parameters[0]->getType();
        $this->assertNotNull($parameterType, 'Parameter should have a type');
        $this->assertEquals(
            'Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent',
            $parameterType->getName(),
            'Parameter type should be AfterExpressionAnalysisEvent'
        );
    }

    /**
     * Test that the afterExpressionAnalysis method returns the correct type
     */
    public function testAfterExpressionAnalysisReturnType(): void
    {
        $reflection = new \ReflectionMethod(
            InsaneComparisonAnalyzer::class,
            'afterExpressionAnalysis'
        );
        
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType, 'Method should have a return type');
        
        // The return type should be ?bool (nullable bool)
        $this->assertEquals(
            'bool',
            $returnType->getName(),
            'Return type should be bool'
        );
        
        $this->assertTrue(
            $returnType->allowsNull(),
            'Return type should allow null'
        );
    }
}

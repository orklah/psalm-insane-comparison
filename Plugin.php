<?php
namespace Orklah\PsalmInsaneComparison;

use Orklah\PsalmInsaneComparison\Hooks\InsaneComparisonAnalyzer;
use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    /** @return void */
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        if(class_exists(InsaneComparisonAnalyzer::class)){
            $psalm->registerHooksFromClass(InsaneComparisonAnalyzer::class);
        }
    }
}

<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Hooks;

use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TPositiveInt;

/** 
 * @api
 */
class InsaneComparisonAnalyzer implements AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        $statements_source = $event->getStatementsSource();
        $codebase = $event->getCodebase();
        if (!$expr instanceof Expr\BinaryOp\Equal 
            && !$expr instanceof Expr\BinaryOp\NotEqual 
            && !$expr instanceof Expr\BinaryOp\Greater 
            && !$expr instanceof Expr\BinaryOp\GreaterOrEqual
            && !$expr instanceof Expr\BinaryOp\Smaller 
            && !$expr instanceof Expr\BinaryOp\SmallerOrEqual
           ) {
            return null;
        }

        $left_type = $statements_source->getNodeTypeProvider()->getType($expr->left);
        $right_type = $statements_source->getNodeTypeProvider()->getType($expr->right);

        if ($left_type === null || $right_type === null) {
            return null;
        }

        //on one hand, we're searching for literal 0
        $literal_0 = Type::getInt(false, 0);
        $left_contain_0 = false;
        $right_contain_0 = false;
        $other_operand = null;
        $int_operand = null;

        if (UnionTypeComparator::isContainedBy(
            $codebase,
            $literal_0,
            $left_type,
            true,
            true)
        ) {
            //Left type may contain 0
            $int_operand = $left_type;
            $other_operand = $right_type;
            $left_contain_0 = true;
        }

        if (UnionTypeComparator::isContainedBy(
            $codebase,
            $literal_0,
            $right_type,
            true,
            true)
        ) {
            //Right type may contain 0
            $int_operand = $right_type;
            $other_operand = $left_type;
            $right_contain_0 = true;
        }

        if (!$left_contain_0 && !$right_contain_0) {
            // Not interested
            return null;
        } elseif ($left_contain_0 && $right_contain_0) {
            //This is pretty inconclusive
            return null;
        }
        assert($other_operand !== null);

        //On the other hand, we're searching for any non-numeric non-empty string
        if (!$other_operand->hasString()) {
            //we can stop here, there's no string in here
            return null;
        }
        assert($int_operand !== null);

        $string_operand = $other_operand;

        $eligible_int = null;
        foreach ($int_operand->getAtomicTypes() as $possibly_int) {
            if ($possibly_int instanceof TLiteralInt && $possibly_int->value === 0) {
                $eligible_int = $possibly_int;
                break;
            } elseif ($possibly_int instanceof TPositiveInt) {
                //not interested
                continue;
            } elseif ($possibly_int instanceof TIntRange 
                && ($possibly_int->min_bound > 0
                || $possibly_int->max_bound < 0
            )) {
                //not interested
                continue;
            } elseif ($possibly_int instanceof Type\Atomic\TInt) {
                // we found a general Int, it may contain 0
                $eligible_int = $possibly_int;
                break;
            }
        }

        $eligible_string = null;
        foreach ($string_operand->getAtomicTypes() as $possibly_string) {
            if ($possibly_string instanceof TLiteralString) {
                if(!is_numeric($possibly_string->value)) {
                    $eligible_string = $possibly_string;
                    break;
                }
                continue;
            } elseif ($possibly_string instanceof Type\Atomic\TNumericString) {
                // not interested
                continue;
            } elseif ($possibly_string instanceof Type\Atomic\TString) {
                $eligible_string = $possibly_string;
                break;
            }
        }

        if ($eligible_int !== null && $eligible_string !== null) {
            if (IssueBuffer::accepts(
                new InsaneComparison(
                    'Possible Insane Comparison between ' . $eligible_string->getKey() . ' and ' . $eligible_int->getKey(),
                    new CodeLocation($statements_source, $expr)
                ),
                $statements_source->getSuppressedIssues()
            )
            ) {
                // continue
            }
        }

        return null;
    }
}


/** @api */
class InsaneComparison extends PluginIssue
{
}

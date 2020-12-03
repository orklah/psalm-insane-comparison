<?php

declare(strict_types=1);

namespace Orklah\PsalmInsaneComparison\Hooks;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TTraitString;

class InsaneComparisonAnalyzer implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(
        Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): ?bool {
        if(!$expr instanceof Expr\BinaryOp\Equal && !$expr instanceof Expr\BinaryOp\NotEqual){
            return true;
        }

        $left_type = $statements_source->getNodeTypeProvider()->getType($expr->left);
        $right_type = $statements_source->getNodeTypeProvider()->getType($expr->right);

        if($left_type === null || $right_type === null){
            return true;
        }

        if($left_type->isString() && $right_type->isInt()){
            $string_type = $left_type;
            $int_type = $right_type;
        } elseif($left_type->isInt() && $right_type->isString()) {
            $string_type = $right_type;
            $int_type = $left_type;
        }
        else{
            //probably false negatives here because lots of union types get through?
            return true;
        }

        if(
            $int_type instanceof TPositiveInt ||
            ($int_type instanceof TLiteralInt && $int_type->value !== 0)
        ){
            // not interested, we search for literal 0
            return true;
        }

        if(
            $string_type instanceof TNumericString ||
            ($string_type instanceof TLiteralString && !preg_match('#[a-zA-Z]#', $string_type->value[0] ?? '')) ||
            ($string_type instanceof TSingleLetter && !preg_match('#[a-zA-Z]#', $string_type->value[0] ?? ''))
        ){
            // not interested, we search strings that begins with a letter
            return true;
        }

        if (IssueBuffer::accepts(
            new InsaneComparison(
                'Possible Insane Comparison between ' . $string_type->getKey() . ' and ' . $int_type->getKey(),
                new CodeLocation($statements_source, $expr)
            ),
            $statements_source->getSuppressedIssues()
        )
        ) {
            // continue
        }

        return true;
    }
}


class InsaneComparison extends PluginIssue
{
}

<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

final class NoUnneededBlockFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Remove overcomplete blocks.',
            [
                new CodeSample(
                    '<?php
(--$a);
((--$a));
$a = (($a + $a));
$b = [(1), (2)];
foo(($b));
foo(($b), ($b), ($b));
(--$a) ?>
'
                ),
                new CodeSample(
                    '<?php
{}
{{ ++$a }}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(['(', '{']);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = \count($tokens) - 1; $index > 0; --$index) {
            if ($tokens[$index]->equals('}')) {
                $blockStart = $tokens->findBlockStart(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
                $blockEnd = $index;

                if ($this->isOverCompleteCurlyBraceBlock($tokens, $blockStart, $blockEnd)) {
                    $this->removeOverCompleteBlock($tokens, $blockStart, $blockEnd);
                }
            } elseif ($tokens[$index]->equals(')')) {
                $blockStart = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
                $blockEnd = $index;

                if ($this->isOverCompleteParenthesisBraceBlock($tokens, $blockStart, $blockEnd)) {
                    $this->removeOverCompleteBlock($tokens, $blockStart, $blockEnd);
                }
            }
        }
    }

    private function isOverCompleteCurlyBraceBlock(Tokens $tokens, $blockStartIndex, $blockEndIndex)
    {
        $nextTokenIndex = $tokens->getNextMeaningfulToken($blockEndIndex);
        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($blockStartIndex)];

        if (null === $nextTokenIndex) {
            return $prevToken->equalsAny([';', [T_OPEN_TAG]]);
        }

        if ($prevToken->equalsAny([';', '{', ':', '}', [T_OPEN_TAG]])) {
            return true;
        }

        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int    $blockStartIndex
     * @param int    $blockEndIndex
     *
     * @return bool
     */
    private function isOverCompleteParenthesisBraceBlock(Tokens $tokens, $blockStartIndex, $blockEndIndex)
    {
        $nextToken = $tokens[$tokens->getNextMeaningfulToken($blockEndIndex)];
        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($blockStartIndex)];

        if ($prevToken->equals('(')) {
            return $nextToken->equalsAny([')', ',']);
        }

        if ($prevToken->equalsAny([';', '{', [T_OPEN_TAG]])) {
            return $nextToken->equalsAny([';', [T_CLOSE_TAG]]);
        }

        if ($prevToken->equals(',')) {
            return $nextToken->equalsAny([')', ',', [CT::T_ARRAY_SQUARE_BRACE_CLOSE]]);
        }

        if ($prevToken->equals([CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
            return $nextToken->equalsAny([',', [CT::T_ARRAY_SQUARE_BRACE_CLOSE]]);
        }

        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int    $blockStartIndex
     * @param int    $blockEndIndex
     */
    private function removeOverCompleteBlock(Tokens $tokens, $blockStartIndex, $blockEndIndex)
    {
        $tokens->clearTokenAndMergeSurroundingWhitespace($blockEndIndex);
        $tokens->clearTokenAndMergeSurroundingWhitespace($blockStartIndex);
    }
}

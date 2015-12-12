<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class UselessReturnFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        $functionTokens = $tokens->findGivenKind(T_FUNCTION);
        foreach ($functionTokens as $index => $functionToken) {
            $index = $tokens->getNextTokenOfKind($index, array(';', '{'));
            if ($tokens[$index]->equals('{')) {
                $this->fixFunction($tokens, $index, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index));
            }
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'There should not be an empty return statement at the end of a function.';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run before ReturnFixer, ExtraEmptyLinesFixer, WhitespacyLinesFixer and after EmptyReturnFixer and DuplicateSemicolonFixer.
        return -18;
    }

    /**
     * @param Tokens $tokens
     * @param int    $start  Token index of the opening brace token of the function.
     * @param int    $end    Token index of the closing brace token of the function.
     */
    private function fixFunction(Tokens $tokens, $start, $end)
    {
        for ($index = $end; $index > $start; --$index) {
            if (!$tokens[$index]->isGivenKind(T_RETURN)) {
                continue;
            }

            $nextAt = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$nextAt]->equals(';')) {
                continue;
            }

            if ($tokens->getNextMeaningfulToken($nextAt) !== $end) {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            $tokens->clearTokenAndMergeSurroundingWhitespace($nextAt);
        }
    }
}

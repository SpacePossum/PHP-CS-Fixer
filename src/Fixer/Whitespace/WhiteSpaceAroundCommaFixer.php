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

namespace PhpCsFixer\Fixer\Whitespace;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class WhiteSpaceAroundCommaFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(',');
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = count($tokens) - 1; $index > 0; --$index) {
            if (!$tokens[$index]->equals(',')) {
                continue;
            }

            $index = $this->moveComma($tokens, $index);
            $this->fixWhiteSpaceAfterComma($tokens, $index);
        }

        //echo $tokens->generateCode();die;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Fix whitespace around \',\'.';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // FIXME
        // after  : empty comment, list_commas, multiline_array_trailing_comma, no_trailing_comma_in_list_call, single_array_no_trailing_comma
        // before : trailing white space on line, extra empty line, white spacy line
        return 0;
    }

    /**
     * @param Tokens $tokens
     * @param int    $index  Token index of a comma
     *
     * @return int
     */
    private function moveComma(Tokens $tokens, $index)
    {
        $previousMeaningFul = $tokens->getPrevMeaningfulToken($index);
        if ($previousMeaningFul === $index - 1 || $tokens[$previousMeaningFul]->isGivenKind(T_END_HEREDOC)) {
            return $index;
        }

        $tokens->insertAt($previousMeaningFul + 1, new Token(','));
        $tokens->clearTokenAndMergeSurroundingWhitespace($index + 1);

        return $previousMeaningFul + 1;
    }

    /**
     * @param Tokens $tokens
     * @param int    $index  Token index of a comma
     */
    private function fixWhiteSpaceAfterComma(Tokens $tokens, $index)
    {
        $notSpacedTypes = array(')', ']', ',');
        if ($tokens[$index + 1]->equalsAny($notSpacedTypes)) {
            return;
        }

        if (!$tokens[$index + 1]->isWhitespace()) {
            $tokens->insertAt($index + 1, new Token(array(T_WHITESPACE, ' ')));

            return;
        }

        if (false !== strpos($tokens[$index + 1]->getContent(), "\n")) {
            return;
        }

        $next = $tokens->getNextNonWhitespace($index + 1);
        if ($tokens[$next]->isComment()) {
            if (false === strpos($tokens[$next + 1]->getContent(), "\n")) {
                $tokens[$index + 1]->setContent(' ');
            }

            return;
        }

        if ($tokens[$next]->equalsAny($notSpacedTypes)) {
            $tokens->clearRange($index + 1, $next - 1);

            return;
        }

        $tokens[$index + 1]->setContent(' ');
    }
}

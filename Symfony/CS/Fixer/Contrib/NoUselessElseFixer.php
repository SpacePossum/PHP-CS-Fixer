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

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\AbstractAlignFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NoUselessElseFixer extends AbstractAlignFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_ELSE)) {
                continue;
            }

            // `else if` vs. `else` check
            if ($tokens[$tokens->getNextMeaningfulToken($index)]->isGivenKind(T_IF)) {
                continue;
            }

            // clean up `else` if it is an empty statement
            $this->fixEmptyElse($tokens, $index);
            if ($token->isEmpty()) {
                continue;
            }

            // clean up `else` if possible
            $this->fixElse($tokens, $index);
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'There should not be useless else cases.';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run before DuplicateSemicolonFixer, WhitespacyLinesFixer, ExtraEmptyLinesFixer and BracesFixer.
        return 9;
    }

    /**
     * @param Tokens $tokens
     * @param int    $index  T_ELSE index
     */
    private function fixElse(Tokens $tokens, $index)
    {
        $previousBlockStart = $index;
        do {
            // Check if all 'if', 'else if ' and 'elseif' blocks above this 'else'.
            // If these always end this 'else' is overcomplete.

            $previousBlock = $this->getPreviousBlock($tokens, $previousBlockStart);

            $previousBlockStart = $previousBlock[0];
            $previousBlockEnd = $previousBlock[1];

            // short 'if' detection
            $previous = $previousBlockEnd;

            if ($tokens[$previous]->equals('}')) {
                $previous = $tokens->getPrevMeaningfulToken($previous);
            }

            // 'if' block doesn't end with semicolon, keep 'else'
            if (!$tokens[$previous]->equals(';')) {
                return;
            }

            // empty 'if' block, keep 'else'
            $previous = $tokens->getPrevMeaningfulToken($previous);
            if ($tokens[$previous]->equalsAny(array('{', '}'))) {
                return;
            }

            // 'break;' 'continue;' 'exit;' 'die;' 'return;' before 'else'
            if ($tokens[$previous]->isGivenKind(array(T_BREAK, T_CONTINUE, T_RETURN))) {
                continue; // delete candidate
            }

            // check for exit condition is short if, for example:
            // if (true === $a) $b = true === $a ? 1 : die;
            if ($tokens[$previous]->isGivenKind(array(T_EXIT))) {
                $previous = $tokens->getPrevMeaningfulToken($previous);
                if ($tokens[$previous]->equals(':')) {
                    return;
                }

                continue; // delete candidate
            }

            $candidateIndex = $tokens->getTokenOfKindSibling(
                $previous,
                -1,
                array(
                    ';',
                    '}',
                    array(T_BREAK),
                    array(T_CONTINUE),
                    array(T_EXIT),
                    array(T_GOTO),
                    array(T_RETURN),
                    array(T_THROW),
                )
            );

            if (null === $candidateIndex || $tokens[$candidateIndex]->equalsAny(array(';', '}'))) {
                return;
            }
        } while (!$tokens[$previousBlockStart]->isGivenKind(T_IF));

        // if we made it to here the 'else' can be removed
        $this->clearElse($tokens, $index);
    }

    /**
     * Return the first and last token index of the previous block.
     *
     * [0] First is either T_IF, T_ELSE or T_ELSEIF
     * [1] Last is either '}' or ';' / T_CLOSE_TAG for short notation blocks
     *
     * @param Tokens $tokens
     * @param int    $index  T_IF, T_ELSE, T_ELSEIF
     *
     * @return int[]
     */
    private function getPreviousBlock(Tokens $tokens, $index)
    {
        $close = $previous = $tokens->getPrevMeaningfulToken($index);
        // short 'if' detection
        if ($tokens[$close]->equals('}')) {
            $previous = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $close, false);
        }

        $open = $tokens->getPrevTokenOfKind($previous, array(array(T_IF), array(T_ELSE), array(T_ELSEIF)));
        if ($tokens[$open]->isGivenKind(T_IF)) {
            $elseCandidate = $tokens->getPrevMeaningfulToken($open);
            if ($tokens[$elseCandidate]->isGivenKind(T_ELSE)) {
                $open = $elseCandidate;
            }
        }

        return array($open, $close);
    }

    /**
     * @param Tokens $tokens
     * @param int    $index  T_ELSE index
     */
    private function fixEmptyElse(Tokens $tokens, $index)
    {
        $next = $tokens->getNextMeaningfulToken($index);
        if ($tokens[$next]->equals('{')) {
            $close = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $next);
            if (1 === $close - $next) { // '{}'
                $this->clearElse($tokens, $index);
            } elseif ($tokens->getNextMeaningfulToken($next) === $close) { // '{/**/}'
                $this->clearElse($tokens, $index);
            }

            return;
        }

        // short `else`
        $end = $tokens->getNextTokenOfKind($index, array(';', array(T_CLOSE_TAG)));
        if ($next === $end) {
            $this->clearElse($tokens, $index);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $index  index of T_ELSE
     */
    private function clearElse(Tokens $tokens, $index)
    {
        $tokens->clearTokenAndMergeSurroundingWhitespace($index);

        // clear T_ELSE and the '{' '}' if there are any
        $next = $tokens->getNextMeaningfulToken($index);
        if (!$tokens[$next]->equals('{')) {
            return;
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $next));
        $tokens->clearTokenAndMergeSurroundingWhitespace($next);
    }
}

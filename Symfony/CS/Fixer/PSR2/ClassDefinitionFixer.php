<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\PSR2;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Token;
use Symfony\CS\Tokenizer\Tokens;

/**
 * Fixer for part of the rules defined in PSR2 ¶4.1 Extends and Implements.
 *
 * @author SpacePossum
 */
final class ClassDefinitionFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);
        for ($index = $tokens->getSize() - 1; $index > 0;--$index) {
            if (!$tokens[$index]->isClassy()) {
                continue;
            }

            $this->fixClassDefinition($tokens, $index, $tokens->getNextTokenOfKind($index, array('{')));
        }

        return $tokens->generateCode();
    }

    /**
     * @param Tokens $tokens
     * @param int    $start  Class definition token start index
     * @param int    $end    Class definition token end index
     */
    private function fixClassDefinition(Tokens $tokens, $start, $end)
    {
        // check if there is a `implements` part in the definition, since there are rules for it in PSR 2.
        $implementsInfo = $this->detectImplements($tokens, $start, $end);

        // 4.1 The extends and implements keywords MUST be declared on the same line as the class name.
        if ($implementsInfo['numberOfInterfaces'] > 1 && $implementsInfo['multiLine']) {
            $end += $this->ensureWhiteSpaceSeparation($tokens, $start, $implementsInfo['implementsAt']);
            $end += $this->fixMultiLineImplements($tokens, $implementsInfo['implementsAt'], $end);
        } else {
            $end += $this->ensureWhiteSpaceSeparation($tokens, $start, $end);
        }

        // 4.1 The opening brace for the class MUST go on its own line;
        $this->ensureLineBreakBeforeToken($tokens, $end);
    }

    /**
     * Fix spacing between lines following `implements`.
     *
     * PSR2 4.1 Lists of implements MAY be split across multiple lines, where each subsequent line is indented once.
     * When doing so, the first item in the list MUST be on the next line, and there MUST be only one interface per line.
     *
     * @param Tokens $tokens
     * @param int    $implementsAt
     * @param int    $end
     *
     * @return int number tokens inserted by the method before the end token
     */
    private function fixMultiLineImplements(Tokens $tokens, $implementsAt, $end)
    {
        $added = 0;
        // implements should be followed by a line break, but we allow a comments before that,
        // the lines after 'implements' are always build up as (comment|whitespace)*T_STRING{1}(comment|whitespace)*','
        // after fixing it must be (whitespace indent)(comment)*T_STRING{1}(comment)*','
        for ($c = $end - 1; $c > $implementsAt - 1; --$c) {
            if ($tokens[$c]->isWhitespace()) {
                if ($tokens[$c + 1]->equals(',')) {
                    $tokens[$c]->clear();
                } elseif ($tokens[$c + 1]->isComment() && !$tokens[$c]->equals(' ')) {
                    $tokens[$c]->setContent(' ');
                }
            }

            if ($tokens[$c]->isGivenKind(T_STRING)) {
                if ($tokens[$c - 1]->isWhitespace()) {
                    if ("\n" === substr($tokens[$c - 2]->getContent(), -1)) {
                        $expect = '    ';
                    } else {
                        $expect = "\n    ";
                    }

                    if (!$tokens[$c - 1]->equals($expect)) {
                        $tokens[$c - 1]->setContent($expect);
                    }
                } elseif ("\n" === substr($tokens[$c - 1]->getContent(), -1)) {
                    $tokens->insertAt($c, new Token(array(T_WHITESPACE, '    ')));
                    ++$added;
                } else {
                    $tokens->insertAt($c, new Token(array(T_WHITESPACE, "\n    ")));
                    ++$added;
                }
            }
        }

        return $added;
    }

    /**
     * Ensure there is linebreak before token at given index.
     *
     * @param Tokens $tokens
     * @param int    $tokenIndex
     *
     * @return int number tokens inserted by the method before the end token
     */
    private function ensureLineBreakBeforeToken(Tokens $tokens, $tokenIndex)
    {
        if (false !== strpos($tokens[$tokenIndex - 1]->getContent(), "\n")) {
            return 0;
        }

        if ($tokens[$tokenIndex - 1]->isWhitespace()) {
            $tokens[$tokenIndex - 1]->setContent("\n");

            return 0;
        }

        $tokens->insertAt($tokenIndex, new Token(array(T_WHITESPACE, "\n")));

        return 1;
    }

    /**
     * Make sure the tokens are separated by a single space.
     *
     * @param Tokens $tokens
     * @param int    $start
     * @param int    $end
     *
     * @return int number tokens inserted by the method before the end token
     */
    private function ensureWhiteSpaceSeparation(Tokens $tokens, $start, $end)
    {
        $insertCount = 0;
        for ($i = $end; $i > $start; --$i) {
            if ($tokens[$i]->isWhitespace()) {
                if (!$tokens[$i]->equals(' ')) {
                    $tokens[$i]->setContent(' ');
                }
                continue;
            }

            if ($tokens[$i - 1]->isWhitespace() || "\n" === substr($tokens[$i - 1]->getContent(), -1)) {
                continue;
            }

            if ($tokens[$i - 1]->isComment() || $tokens[$i]->isComment()) {
                $tokens->insertAt($i, new Token(array(T_WHITESPACE, ' ')));
                ++$insertCount;
                continue;
            }
        }

        return $insertCount;
    }

    /**
     * Returns an array with `implements` data.
     *
     * Returns array:
     * int  'implementsAt'       index of the Token of type T_IMPLEMENTS for the definition, or 0
     * int  'numberOfInterfaces'
     * bool 'multiLine'
     *
     * @param Tokens $tokens
     * @param int    $start
     * @param int    $end
     *
     * @return array
     */
    private function detectImplements(Tokens $tokens, $start, $end)
    {
        $implementsInfo = array('implementsAt' => 0, 'numberOfInterfaces' => 0, 'multiLine' => false);
        $implements = $tokens->findGivenKind(T_IMPLEMENTS, $start, $end);
        if (count($implements) < 1) {
            return $implementsInfo;
        }

        $implementsInfo['implementsAt'] = key($implements);
        for ($j = $implementsInfo['implementsAt'] + 1; $j < $end; ++$j) {
            if ($tokens[$j]->isGivenKind(T_STRING)) {
                ++$implementsInfo['numberOfInterfaces'];
                continue;
            }

            if (!$implementsInfo['multiLine'] && ($tokens[$j]->isWhitespace() || $tokens[$j]->isComment()) && false !== strpos($tokens[$j]->getContent(), "\n")) {
                $implementsInfo['multiLine'] = true;
            }
        }

        return $implementsInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'White space around the key words of a class, trait or interfaces definition should be one space.';
    }
}

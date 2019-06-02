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

namespace PhpCsFixer\Fixer\NamespaceNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶3.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class BlankLineAfterNamespaceFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There MUST be one blank line after the namespace declaration.',
            [
                new CodeSample("<?php\nnamespace Sample\\Sample;\n\n\n\$a;\n"),
                new CodeSample("<?php\nnamespace Sample\\Sample;\nClass Test{}\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run after the NoUnusedImportsFixer
        return -20;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_NAMESPACE);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $lastIndex = $tokens->count() - 1;

        for ($index = $lastIndex; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_NAMESPACE)) {
                continue;
            }

            $semicolonIndex = $tokens->getNextTokenOfKind($index, [';', '{', [T_CLOSE_TAG]]);
            $semicolonToken = $tokens[$semicolonIndex];

            if (!isset($tokens[$semicolonIndex + 1]) || !$semicolonToken->equals(';')) {
                continue;
            }

            $indexToEnsureBlankLine = $this->getIndexToEnsureBlankLine($tokens, $semicolonIndex);
            $tokenToEnsureBlankLine = $tokens[$indexToEnsureBlankLine];

            if ($tokenToEnsureBlankLine->isWhitespace()) {
                $tokens[$indexToEnsureBlankLine] = $this->getTokenToInsert($tokenToEnsureBlankLine->getContent(), $indexToEnsureBlankLine === $lastIndex);
            } else {
                $tokens->insertAt($indexToEnsureBlankLine, $this->getTokenToInsert('', false)); // TODO: add test to fail on the "false"
            }
        }
    }

    private function getIndexToEnsureBlankLine(Tokens $tokens, $index)
    {
        $indexToEnsureBlankLine = $index;
        $nextIndex = $tokens->getNonEmptySibling($indexToEnsureBlankLine, 1);

        while (null !== $nextIndex) {
            $token = $tokens[$nextIndex];

            if ($token->isWhitespace()) {
                if (1 === Preg::match('/\R/', $token->getContent())) {
                    return $nextIndex;
                }
                $nextNextIndex = $tokens->getNonEmptySibling($nextIndex, 1);

                if (!$tokens[$nextNextIndex]->isComment()) {
                    return $nextIndex;
                }
            }

            if (!$token->isWhitespace() && !$token->isComment()) {
                return $nextIndex;
            }

            $indexToEnsureBlankLine = $nextIndex;
            $nextIndex = $tokens->getNonEmptySibling($indexToEnsureBlankLine, 1);
        }
    }

    private function getTokenToInsert($currentContent, $isLastIndex)
    {
        $ending = $this->whitespacesConfig->getLineEnding();

        if (1 === Preg::match('/^.*\R( *)$/s', $currentContent, $matches)) {
            $content = $matches[1];
        } else {
            $content = ltrim($currentContent);
        }

        return new Token([
            T_WHITESPACE,
            ($isLastIndex ? $ending : $ending.$ending).$content,
        ]);
    }
}

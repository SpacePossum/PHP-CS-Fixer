<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Symfony;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * Fix inline tags and make inheritdoc tag always inline.
 */
final class PhpdocInlineTagFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $content = $token->getContent();

            // move `@` inside tag, for example @{tag} -> {@tag}, left trim spaces between '{' and '@'
            // replace multiple '{'/'}' and characters between '{' and the inline tag word or @
            $content = preg_replace(
                '#@*{[{ ]*@*[ ]*(example|id|internal|inheritdoc|link|source|toc|tutorial)([^}]*)[}]*#',
                '{@$1$2}',
                $content
            );

            // always make inheritdoc inline using with '{' '}' when needed
            $content = preg_replace(
                '#([^{])@(inheritdoc)([^}])#',
                '$1{@$2}$3',
                $content);

            $token->setContent($content);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Fix PHPDoc inline tags, make inheritdoc always inline.';
    }
}

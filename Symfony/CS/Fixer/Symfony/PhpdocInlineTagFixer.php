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

            // Move `@` inside tag, for example @{tag} -> {@tag}, replace multiple '{'/'}',
            // remove spaces between '{' and '@', remove 's' at the end of tag word
            $content = preg_replace(
                '#(@+[{]+|[{]+[ \t]*@+)[ \t]*(example|id|internal|inheritdoc|link|source|toc|tutorial)[s]*([^}]*)([}]*)#i',
                '{@$2$3}',
                $content
            );

            // always make inheritdoc inline using with '{' '}' when needed, remove trailing 's',
            // make sure lowercase.
            $content = preg_replace(
                '#([^{])@inheritdoc[s]*([^}])#i',
                '$1{@inheritdoc}$2',
                $content
            );

            // At this point, all tags that are fixable by this fixer are in the format
            // '{@(tag name)[ ]*}'. Make sure the tags are written in lower case, remove
            // white space between end of tag text and '}'
            $content = preg_replace_callback(
                '#{@(example|id|internal|inheritdoc|link|source|toc|tutorial)([^}]*)([}]*)#i',
                function (array $matches) {
                    return '{@'.strtolower($matches[1]).rtrim($matches[2]).'}';
                },
                $content
            );

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

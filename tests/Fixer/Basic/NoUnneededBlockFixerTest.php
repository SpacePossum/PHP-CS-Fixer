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

namespace PhpCsFixer\Tests\Fixer\Basic;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Basic\NoUnneededBlockFixer
 */
final class NoUnneededBlockFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            // `{` cases
            [
                '<?php ;  $a = 1; ;',
                '<?php ; { $a = 1; };',
            ],
            [
                '<?php ;  $a = 1; ',
                '<?php ; { $a = 1; }',
            ],
            [
                '<?php function A(){ echo 1; }',
                '<?php function A(){{ echo 1; }}',
            ],
            [
                '<?php switch($a){ case 1: echo 1; break; }',
                '<?php switch($a){ case 1: {echo 1; break;} }',
            ],
            [
                '<?php  $a = 1;   ?>',
                '<?php { $a = 1; }  ?>',
            ],
            [
                '<?php  $a = 1;   $d = 4;  ?>',
                '<?php { $a = 1; } { $d = 4; } ?>',
            ],
            [
                '<?php $d = 1;        /* foo */ ;',
                '<?php {$d = 1; }       /* foo */ ;',
            ],
            [
                '<?php  $e = 5; // last meaningful token',
                '<?php  {$e = 5; }// last meaningful token',
            ],
            // '(' cases
            [
                '<?php $b = (++$a) + 2;',
                '<?php $b = ((++$a)) + 2;',
            ],
            [
                '<?php --$a; --$b; --$c ?>',
                '<?php (--$a); (--$b); (--$c) ?>',
            ],
            [
                '<?php foo($a);',
                '<?php foo(($a));',
            ],
            [
                '<?php foo($a, $b);',
                '<?php foo(($a), $b);',
            ],
            [
                '<?php foo($a, $b);',
                '<?php foo($a, ($b));',
            ],
            [
                '<?php foo($a, $b, $c);',
                '<?php foo($a, ($b), $c);',
            ],
            [
                '<?php $b = [1];',
                '<?php $b = [(1)];',
            ],
            [
                '<?php $b = [1, 2];',
                '<?php $b = [(1), 2];',
            ],
            [
                '<?php $b = [1, 2];',
                '<?php $b = [1, (2)];',
            ],
            [
                '<?php $b = [1, 2, 3];',
                '<?php $b = [1, (2), 3];',
            ],
            // mixed cases
            [
                '<?php $a = 0;  ++$a ;',
                '<?php $a = 0; { (++$a) ;}',
            ],
            // do not fix cases
            [
                '<?php

function A($a, $b, $c)
{
    return $a ? $b : ($c ?: 1);
}',
            ],
            [
                file_get_contents(__FILE__),
            ],
        ];
    }
}

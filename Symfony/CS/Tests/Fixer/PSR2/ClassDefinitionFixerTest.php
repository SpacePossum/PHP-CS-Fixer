<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tests\Fixer\PSR2;

use Symfony\CS\Tests\Fixer\AbstractFixerTestBase;


/**
 * @internal
 */
final class ClassDefinitionFixerTest extends AbstractFixerTestBase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideCases()
    {
        return array(
            array(
                '<?php
interface Test extends /*a*/ /*b*/
    TestInterface1, /* test */
    TestInterface2, // test
 // test
    TestInterface3, /**/
    TestInterface4,
    TestInterface5, /**/
    TestInterface6
{
}',
                '<?php
interface Test
extends
  /*a*/    /*b*/TestInterface1   ,  /* test */
    TestInterface2   ,   // test
    '.'

// test
TestInterface3, /**/     TestInterface4   ,
      TestInterface5    ,    '.'
        /**/TestInterface6 {
}',
            ),
            array(
                '<?php
class Test extends TestInterface8 implements /*a*/ /*b*/
    TestInterface1, /* test */
    TestInterface2, // test
 // test
    TestInterface3, /**/
    TestInterface4,
    TestInterface5, /**/
    TestInterface6
{
}',
                '<?php
class Test
extends
    TestInterface8
  implements  /*a*/    /*b*/TestInterface1   ,  /* test */
    TestInterface2   ,   // test
    '.'

// test
TestInterface3, /**/     TestInterface4   ,
      TestInterface5    ,    '.'
        /**/TestInterface6 {
}',
            ),
            array(
                '<?php
class /**/ Test123 extends /**/ \RuntimeException implements TestZ
{
}',
                '<?php
class/**/Test123
extends  /**/        \RuntimeException    implements

TestZ{
}',
            ),
            array(
                '<?php
class /**/ Test125 //aaa
extends /*

*/ //
\Exception //
{}',
                '<?php
class/**/Test125 //aaa
extends  /*

*/
//
\Exception        //
{}',
            ),
            array(
                '<?php
class Test124 extends \Exception
{}',
                '<?php
class
Test124

extends
\Exception {}',
            ),
        );
    }
}

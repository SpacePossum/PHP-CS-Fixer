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

namespace Symfony\CS\Tests\Fixer\Contrib;

use Symfony\CS\Tests\Fixer\AbstractFixerTestBase;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author SpacePossum
 *
 * @internal
 */
final class NoUselessElseFixerTest extends AbstractFixerTestBase
{
    /**
     * @dataProvider provideFixIfElseIfElseCases
     */
    public function testFixIfElseIfElse($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixIfElseIfElseCases()
    {
        $expected =
            '<?php
                while(true) {
                    while(true) {
                        if ($provideFixIfElseIfElseCases) {
                            return;
                        } elseif($a1) {
                            if ($b) {echo 1; die;}  echo 2;
                            return 1;
                        } elseif($b) {
                            %s
                        }  '.'
                            echo 2;
                        '.'
                    }
                }
            ';

        $input =
            '<?php
                while(true) {
                    while(true) {
                        if ($provideFixIfElseIfElseCases) {
                            return;
                        } elseif($a1) {
                            if ($b) {echo 1; die;} else {echo 2;}
                            return 1;
                        } elseif($b) {
                            %s
                        } else {
                            echo 2;
                        }
                    }
                }
            ';

        $cases = $this->generateCases($expected, $input);

        $expected =
            '<?php
                while(true) {
                    while(true) {
                        if($a) {
                            echo 1;
                        } elseif($b) {
                            %s
                        } else {
                            echo 3;
                        }
                    }
                }
            ';

        $cases = array_merge($cases, $this->generateCases($expected));

        $expected =
            '<?php
                while(true) {
                    while(true) {
                        if ($a) {
                            echo 1;
                        } elseif  ($a1) {
                            echo 2;
                        } elseif  ($b) {
                            echo $b+1; //
                            /* test */
                            %s
                        } else {
                            echo 3;
                        }
                    }
                }
            ';

        $cases = array_merge($cases, $this->generateCases($expected));

        $cases[] = array(
            '<?php
                if ($a)
                    echo 1;
                else if($b)
                    echo 2;
                elseif($c)
                    echo 3;
                    if ($a) {

                    }elseif($d) {
                        return 1;
                    }
                else
                    echo 4;
            ', );

        $cases[] = array(
            '<?php
                if ($a)
                    echo 1;
                else if($b) {
                    echo 2;
                } elseif($c) {
                    echo 3;
                    if ($d) {
                        echo 4;
                    } elseif($e)
                        return 1;
                } else
                    echo 4;
            ', );

        return $cases;
    }

    /**
     * @dataProvider provideFixIfElseCases
     */
    public function testFixIfElse($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixIfElseCases()
    {
        $expected =
            '<?php
                while(true) {
                    while(true) {
                        if ($a) {
                            %s
                        }  '.'
                            echo 1;
                        '.'
                    }
                }
            ';

        $input =
            '<?php
                while(true) {
                    while(true) {
                        if ($a) {
                            %s
                        } else {
                            echo 1;
                        }
                    }
                }
            ';

        $cases = $this->generateCases($expected, $input);

        // short 'if' statements
        $expected =
            '<?php
                while(true) {
                    while(true) {
                        if ($a)
                            %s
                        '.'
                            echo 1;
                    }
                }
            ';

        $input =
            '<?php
                while(true) {
                    while(true) {
                        if ($a)
                            %s
                        else
                            echo 1;
                    }
                }
            ';

        $cases = array_merge($cases, $this->generateCases($expected, $input));

        // short and not short combined
        $cases[] = array(
            '<?php
                if ($a)
                    return;
                 '.'
                    echo 1;
                '.'
            ',
            '<?php
                if ($a)
                    return;
                else {
                    echo 1;
                }
            ',
        );

        $cases[] = array(
            '<?php
                if ($a) {
                    GOTO jump;
                }  '.'
                    echo 1;
                '.'

                jump:
            ',
            '<?php
                if ($a) {
                    GOTO jump;
                } else {
                    echo 1;
                }

                jump:
            ',
        );

        return $cases;
    }

    /**
     * @param string      $expected
     * @param string|null $input
     *
     * @return array<string, string>
     */
    private function generateCases($expected, $input = null)
    {
        $cases = array();
        foreach (
            array(
                'exit;',
                'exit();',
                'exit(1);',
                'die;',
                'die();',
                'die(1);',
                'break;',
                'break 2;',
                'break (2);',
                'continue;',
                'continue 2;',
                'continue (2);',
                'return;',
                'return 1;',
                'return (1);',
                'return "a";',
                'return 8+2;',
                'return null;',
                'return sum(1+8*6, 2);',
                'throw $e;',
                'throw ($e);',
                'throw new \Exception;',
                'throw new \Exception();',
                'throw new \Exception((string)12+1);',
            ) as $case) {
            if (null === $input) {
                $cases[] = array(sprintf($expected, $case));
                $cases[] = array(sprintf($expected, strtoupper($case)));
                $cases[] = array(sprintf($expected, strtolower($case)));
            } else {
                $cases[] = array(sprintf($expected, $case), sprintf($input, $case));
                $cases[] = array(sprintf($expected, strtoupper($case)), sprintf($input, strtoupper($case)));
                $cases[] = array(sprintf($expected, strtolower($case)), sprintf($input, strtolower($case)));
            }
        }

        return $cases;
    }

    /**
     * @dataProvider provideFixNestedIfs
     */
    public function testFixNestedIfs($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixNestedIfs()
    {
        return array(
            array(
                '<?php
                    if ($x) {
                        if ($y) {
                            return 1;
                        }  '.'
                            return 2;
                        '.'
                    }  '.'
                        return 3;
                    '.'
                ',
                '<?php
                    if ($x) {
                        if ($y) {
                            return 1;
                        } else {
                            return 2;
                        }
                    } else {
                        return 3;
                    }
                ',
            ),
        );
    }

    /**
     * @dataProvider provideBefore54FixCases
     */
    public function testBefore54Fix($expected, $input = null)
    {
        if (PHP_VERSION_ID >= 50400) {
            $this->markTestSkipped('PHP lower than 5.4 is required.');
        }

        $this->makeTest($expected, $input);
    }

    public function provideBefore54FixCases()
    {
        $expected =
            '<?php
                $a = 1; $b = 0;
                while(true) {
                    while(true) {
                        ++$b;
                        if ($b > $a) {
                            %s %%s;
                        }  //
                            echo 2;
                        //
                    }
                }
            ';

        $input =
            '<?php
                $a = 1; $b = 0;
                while(true) {
                    while(true) {
                        ++$b;
                        if ($b > $a) {
                            %s %%s;
                        } else {//
                            echo 2;
                        }//
                    }
                }
            ';

        $cases = array();
        foreach (array('continue', 'break') as $stop) {
            $expectedTemplate = sprintf($expected, $stop);
            $inputTemplate = sprintf($input, $stop);
            foreach (array('1+1', '$a', '(1+1)', '($a)') as $value) {
                $cases[] = array(
                    sprintf($expectedTemplate, $value),
                    sprintf($inputTemplate, $value),
                );
            }
        }

        return $cases;
    }

    /**
     * @dataProvider provideFixEmptyElseCases
     */
    public function testFixEmptyElse($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixEmptyElseCases()
    {
        return array(
            array(
                '<?php
                    if (false)
                        echo 1;
                    '.'
                ',
                '<?php
                    if (false)
                        echo 1;
                    else{}
                ',
            ),
            array(
                '<?php
                    if (true)
                        echo 2;
                    '.'
                    ?><?php
                echo 2;
                ',
                '<?php
                    if (true)
                        echo 2;
                    else
                    ?><?php
                echo 2;
                ',
            ),
            array(
                '<?php
if (true)
    echo 4;
?><?php echo 5;',
                '<?php
if (true)
    echo 4;
else?><?php echo 5;',
            ),
            array(
                '<?php if($a){}',
                '<?php if($a){}else{}',
            ),
            array(
                '<?php if($a){ $a = ($b); }  ',
                '<?php if($a){ $a = ($b); } else {}',
            ),
            array(
                '<?php if ($a) {;}   if ($a) {;}  /**/ if($a){}',
                '<?php if ($a) {;} else {} if ($a) {;} else {/**/} if($a){}else{}',
            ),
            array(
                '<?php
                    if /**/($a) /**/{ //
                        /**/
                        /**/return/**/1/**/;
                        //
                    }/**/  /**/
                        /**/
                        //
                    /**/
                ',
                '<?php
                    if /**/($a) /**/{ //
                        /**/
                        /**/return/**/1/**/;
                        //
                    }/**/ else /**/{
                        /**/
                        //
                    }/**/
                ',
            ),
            array(
                '<?php
                    if ($a) {
                        if ($b) {
                            if ($c) {
                            } elseif ($d) {
                                return;
                            }  //
                            //
                            return;
                        }  //
                        //
                        return;
                    }  //
                    //
                ',
                '<?php
                    if ($a) {
                        if ($b) {
                            if ($c) {
                            } elseif ($d) {
                                return;
                            } else {//
                            }//
                            return;
                        } else {//
                        }//
                        return;
                    } else {//
                    }//
                ',
            ),
        );
    }

    /**
     * @dataProvider provideNegativeCases
     */
    public function testNegativeCases($expected)
    {
        $this->makeTest($expected);
    }

    public function provideNegativeCases()
    {
        return array(
            array(
                '<?php
                    if ($a0) {
                        //
                    } else {
                        echo 0;
                    }
                ',
            ),
            array(
                '<?php
                    if (false)
                        echo 1;
                    else

                    echo 1;
                ',
            ),
            array(
                '<?php if($a2){;} else {echo 2;}',
            ),
            array(
                '<?php if ($a3) {test();} else {echo 3;}',
            ),
            array(
                '<?php if ($a4) {$b = function () {};} else {echo 4;}',
            ),
            array(
                '<?php if ($a5) {$b = function () use ($a){};} else {echo 5;}',
            ),
            array(
                '<?php
                    $a = true; // 6
                    if (true === $a)
                        $b = true === $a ? 1 : die;
                    else
                        echo 4;

                    echo "end";
                ',
            ),
            array(
                '<?php
                    if (false)
                        die;
                    elseif (true)
                        if(true)echo 777;else die;
                    else if (true)
                        die;
                    elseif (false)
                        die;
                    else
                        echo 7;
                ',
            ),
        );
    }

    /**
     * @dataProvider provideBlockDetectionCases
     */
    public function testBlockDetection(array $expected, $source, $index)
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($source);

        $fixer = $this->getFixer();
        $method = new \ReflectionMethod($fixer, 'getPreviousBlock');
        $method->setAccessible(true);

        $result = $method->invoke($fixer, $tokens, $index);

        $this->assertSame($expected, $result);
    }

    public function provideBlockDetectionCases()
    {
        $cases = array();

        $source = '<?php
                    if ($a)
                        echo 1;
                    elseif ($a) ///
                        echo 2;
                    else if ($b) /**/ echo 3;
                    else
                        echo 4;
                    ';
        $cases[] = array(array(2, 11), $source, 13);
        $cases[] = array(array(13, 24), $source, 26);
        $cases[] = array(array(13, 24), $source, 26);
        $cases[] = array(array(26, 39), $source, 41);

        $source = '<?php
                    if ($a) {
                        if ($b) {

                        }
                        echo 1;
                    } elseif (true) {
                        echo 2;
                    } else if (false) {
                        echo 3;
                    } elseif ($1) {
                        echo 4;
                    } else
                        echo 1;
                    ';
        $cases[] = array(array(2, 25), $source, 27);
        $cases[] = array(array(27, 40), $source, 42);
        if (!defined('HHVM_VERSION')) {
            // HHVM 3.6.x tokenizes in a different way
            $cases[] = array(array(59, 73), $source, 74);
        }

        return $cases;
    }
}

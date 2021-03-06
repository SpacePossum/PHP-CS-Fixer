<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\DoctrineAnnotation;

use PhpCsFixer\Tests\AbstractDoctrineAnnotationFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\AbstractDoctrineAnnotationFixer
 * @covers \PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer
 */
final class DoctrineAnnotationArrayAssignmentFixerTest extends AbstractDoctrineAnnotationFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFixWithEqual(string $expected, ?string $input = null): void
    {
        $this->fixer->configure(['operator' => '=']);
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): array
    {
        $cases = $this->createTestCases([
            ['
/**
 * @Foo
 */'],
            ['
/**
 * @Foo()
 */'],
            ['
/**
 * @Foo(bar="baz")
 */'],
            [
                '
/**
 * @Foo(bar="baz")
 */',
            ],
            [
                '
/**
 * @Foo({bar="baz"})
 */',
                '
/**
 * @Foo({bar:"baz"})
 */',
            ],
            [
                '
/**
 * @Foo({bar="baz"})
 */',
                '
/**
 * @Foo({bar:"baz"})
 */',
            ],
            [
                '
/**
 * @Foo({bar = "baz"})
 */',
                '
/**
 * @Foo({bar : "baz"})
 */',
            ],
            ['
/**
 * See {@link http://help Help} or {@see BarClass} for details.
 */'],
        ]);

        $cases[] = [
            '<?php

/**
* @see \User getId()
*/
',
        ];

        return $cases;
    }

    /**
     * @dataProvider provideFixWithColonCases
     */
    public function testFixWithColon(string $expected, ?string $input = null): void
    {
        $this->fixer->configure(['operator' => ':']);
        $this->doTest($expected, $input);
    }

    public function provideFixWithColonCases(): array
    {
        return $this->createTestCases([
            ['
/**
 * @Foo
 */'],
            ['
/**
 * @Foo()
 */'],
            ['
/**
 * @Foo(bar:"baz")
 */'],
            [
                '
/**
 * @Foo(bar:"baz")
 */',
            ],
            [
                '
/**
 * @Foo({bar:"baz"})
 */',
                '
/**
 * @Foo({bar="baz"})
 */',
            ],
            [
                '
/**
 * @Foo({bar : "baz"})
 */',
                '
/**
 * @Foo({bar = "baz"})
 */',
            ],
            [
                '
/**
 * @Foo(foo="bar", {bar:"baz"})
 */',
                '
/**
 * @Foo(foo="bar", {bar="baz"})
 */',
            ],
            ['
/**
 * See {@link http://help Help} or {@see BarClass} for details.
 */'],
        ]);
    }
}

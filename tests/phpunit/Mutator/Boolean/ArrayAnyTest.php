<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\Tests\Mutator\Boolean;

use Infection\Mutator\Boolean\ArrayAll;
use Infection\Testing\BaseMutatorTestCase;
use const PHP_VERSION_ID;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ArrayAll::class)]
final class ArrayAnyTest extends BaseMutatorTestCase
{
    /**
     * @param string|string[] $expected
     */
    #[DataProvider('mutationsProvider')]
    public function test_it_can_mutate(string $input, $expected = []): void
    {
        $this->assertMutatesInput($input, $expected);
    }

    public static function mutationsProvider(): iterable
    {
        yield 'It mutates correctly when provided with a variable' => [
            <<<'PHP'
                <?php

                $anyPositive = array_any($numbers, fn ($number) => $number > 0);
                PHP
            ,
            <<<'PHP'
                <?php

                $anyPositive = true;
                PHP
            ,
        ];

        yield 'It mutates correctly when provided with an array' => [
            <<<'PHP'
                <?php

                $anyPositive = array_any(['A', 1, 'C'], fn ($number) => $number > 0);
                PHP
            ,
            <<<'PHP'
                <?php

                $anyPositive = true;
                PHP,
        ];

        yield 'It mutates correctly when provided with a constant' => [
            <<<'PHP'
                <?php

                $anyPositive = array_any(\Class_With_Const::Const, fn ($number) => $number > 0);
                PHP
            ,
            <<<'PHP'
                <?php

                $anyPositive = true;
                PHP,
        ];

        yield 'It mutates correctly when a backslash is in front of array_any' => [
            <<<'PHP'
                <?php

                $anyPositive = \array_any(['A', 1, 'C'], fn ($number) => $number > 0);
                PHP
            ,
            <<<'PHP'
                <?php

                $anyPositive = true;
                PHP,
        ];

        yield 'It does not mutate other array_ calls' => [
            <<<'PHP'
                <?php

                $a = array_map('strtolower', ['A', 'B', 'C']);
                PHP,
        ];

        yield 'It does not mutate functions named array_any' => [
            <<<'PHP'
                <?php

                function array_any($text, $other)
                {
                }
                PHP,
        ];

        yield 'It mutates correctly within if statements' => [
            <<<'PHP'
                <?php

                if (array_any(['A', 1, 'C'], fn ($number) => $number > 0)) {
                    return true;
                }
                PHP
            ,
            <<<'PHP'
                <?php

                if (true) {
                    return true;
                }
                PHP,
        ];

        yield 'It mutates correctly when array_any is wrongly capitalized' => [
            <<<'PHP'
                <?php

                $a = array_any(['A', 1, 'C'], 'is_int');
                PHP
            ,
            <<<'PHP'
                <?php

                $a = true;
                PHP,
        ];

        yield 'It mutates correctly when array_any uses another function as input' => [
            <<<'PHP'
                <?php

                $a = array_any($foo->bar(), 'is_int');
                PHP
            ,
            <<<'PHP'
                <?php

                $a = true;
                PHP,
        ];

        yield 'It mutates correctly when provided with a more complex situation' => [
            <<<'PHP'
                <?php

                $a = array_any(array_filter(['A', 1, 'C'], function($char): bool {
                    return !is_int($char);
                }), 'is_int');
                PHP
            ,
            <<<'PHP'
                <?php

                $a = true;
                PHP,
        ];

        yield 'It does not break when provided with a variable function name' => [
            <<<'PHP'
                <?php

                $a = 'array_any';

                $b = $a([1, 2, 3], 'is_int');
                PHP
            ,
        ];

        if (PHP_VERSION_ID >= 80400) {
            yield 'It mutates correctly when provided inside getter PHP 8.4 syntax' => [
                <<<'PHP'
                    <?php

                    new class
                    {
                        private array $numbers = [];
                        public function __construct(array $numbers)
                        {
                            $this->numbers = $numbers;
                        }

                        public bool $anyPositive { get => array_any(fn (int $number) => $number > 0); }
                    };
                    PHP
                ,
                <<<'PHP'
                    <?php

                    new class
                    {
                        private array $numbers = [];
                        public function __construct(array $numbers)
                        {
                            $this->numbers = $numbers;
                        }
                        public bool $anyPositive {
                            get => true;
                        }
                    };
                    PHP,
            ];
        }
    }
}
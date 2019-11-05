<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Context;

use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\Tests\Unit\System\Modelaction\TestCase;

final class ModelActionContextTest extends TestCase
{
    private function createRandomListIdentifierOfLength(int $length): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return \substr(\str_replace(['+', '/', '='], '', \base64_encode(\random_bytes($length))), 0, $length);
    }

    public function provideValidListIdentifiers(): iterable
    {
        yield [null];
        yield [''];

        foreach (\range(1, 50) as $length) {
            yield [$this->createRandomListIdentifierOfLength($length)];
        }
    }

    /**
     * @dataProvider provideValidListIdentifiers
     * @param mixed $validListIdentifier
     */
    public function testAllowsInstantiationUsingValidArguments($validListIdentifier): void
    {
        $context = new ModelActionContext($validListIdentifier);
        $this->assertInstanceOf(ModelActionContext::class, $context);
    }

    public function provideInvalidListIdentifiers(): iterable
    {
        yield [true];
        yield [false];
        yield [0];
        yield [123];
        yield [1.23];
        yield [NAN];
        yield [INF];
        yield [[]];
        yield [new \stdClass()];
    }

    /**
     * @dataProvider provideInvalidListIdentifiers
     * @param mixed $invalidListIdentifier
     */
    public function testPreventsInstantiationUsingInvalidArguments($invalidListIdentifier): void
    {
        $this->expectException(\Error::class);
        new ModelActionContext($invalidListIdentifier);
    }

    /**
     * @dataProvider provideValidListIdentifiers
     * @param string $listIdentifier
     */
    public function testGivesAStringRepresentationOfItsInternalValues(?string $listIdentifier): void
    {
        $context = new ModelActionContext($listIdentifier);
        $stringRepresentation = (string) $context;

        $this->assertRegExp('/\b' . \preg_quote($listIdentifier ?? '', '/') . '\b/', $stringRepresentation);
    }
}

<?php

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Context;

use Kajona\System\System\Modelaction\Context\ModelActionContextFactory;
use Kajona\System\Tests\Unit\System\Modelaction\TestCase;

final class ModelActionContextFactoryTest extends TestCase
{
    public function testCreatesEmptyModelActionContext(): void
    {
        $emptyModelActionContext = (new ModelActionContextFactory())->empty();
        $this->assertNull($emptyModelActionContext->getListIdentifier());
    }

    /**
     * @dataProvider provideModelActionListIdentifiers
     * @param string $listIdentifier
     */
    public function testCreatesModelActionContextForAGivenListIdentifier(string $listIdentifier): void
    {
        $modelActionContext = (new ModelActionContextFactory())->forListIdentifier($listIdentifier);
        $this->assertEquals($listIdentifier, $modelActionContext->getListIdentifier());
    }
}

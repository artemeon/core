<?php

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\System\Modelaction\ModelActionContextFactory;

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

<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

final class ModelActionContext
{
    /**
     * @var string|null
     */
    private $listIdentifier;

    public function __construct(?string $listIdentifier)
    {
        $this->listIdentifier = $listIdentifier;
    }

    public function getListIdentifier(): ?string
    {
        return $this->listIdentifier;
    }
}

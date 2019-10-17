<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use JsonSerializable;

final class ModelActionContext implements JsonSerializable
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

    public function jsonSerialize(): array
    {
        return [
            'listIdentifier' => $this->listIdentifier,
        ];
    }

    public function __toString(): string
    {
        return (string) \json_encode($this->jsonSerialize(), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }
}

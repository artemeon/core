<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Alert;

/**
 * Action to update the status icon on the current page
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class MessagingAlertActionUpdateStatus implements MessagingAlertActionInterface
{
    /**
     * @var string
     */
    private $systemId;

    /**
     * @var string
     */
    private $icon;

    /**
     * @param string $systemId
     * @param string $icon
     */
    public function __construct(string $systemId, string $icon)
    {
        $this->systemId = $systemId;
        $this->icon = $icon;
    }

    /**
     * @inheritDoc
     */
    public function getAsActionArray(): array
    {
        return [
            "type" => "update_status",
            "systemid" => $this->systemId,
            "icon" => $this->icon,
        ];
    }
}

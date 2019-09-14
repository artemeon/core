<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Validators;

use Kajona\System\System\ValidatorExtendedInterface;

/**
 * container validator to validate multiple container entries.
 *
 * @author bernhard.grabietz@artemeon.de
 * @since 7.2
 * @package module_system
 */
class ContainerValidator implements ValidatorExtendedInterface
{

    /**
     * @var array $entries
     */
    private $entries;

    /**
     * @var string[] $validationErrorMessages
     */
    private $validationErrorMessages = [];

    /**
     * ContainerValidator constructor.
     * @param array $containerEntries
     */
    public function __construct(array $containerEntries)
    {
        $this->entries = $containerEntries;
    }

     /**
     * Validates the entries passed by constructor.
     *
     * @param $value
     * @return bool
     */
    public function validate($value = null)
    {
        if (empty($this->entries)) {
            return true;
        }

        foreach ($this->entries as $entry) {
            if ($entry->validateValue()) {
                continue;
            }
            $this->validationErrorMessages[] = $entry->getValidationErrorMsg();
        }

        return empty($this->validationErrorMessages) ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function getValidationMessages()
    {
        return array_merge(...$this->validationErrorMessages);
    }
}

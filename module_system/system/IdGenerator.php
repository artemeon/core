<?php
/*"******************************************************************************************************
*   (c) 2010-2016 ARTEMEON                                                                              *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Exceptions\InvalidIdValueInIdFileException;
use Kajona\System\System\Exceptions\UnableToCreateIdFileException;
use Kajona\System\System\Exceptions\UnableToReadIdFileException;
use Kajona\System\System\Exceptions\UnableToWriteInIdFileException;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;

/**
 * Model for a idgenerator record object itself
 *
 * @package module_agp_commons
 * @author christoph.kappestein@artemeon.de
 * @targetTable agp_idgenerator.id
 *
 * @module system
 * @moduleId _system_modul_id_
 */
class IdGenerator extends Model implements ModelInterface
{

    private const ID_LOCK_FILE_PATH = _realpath_ . 'project/temp/idFile-%s.txt';

    /**
     * @var string
     * @tableColumn agp_idgenerator.generator_key
     * @tableColumnDatatype char20
     * @tableColumnIndex
     */
    private $strKey = '';

    /**
     * @var int
     * @tableColumn agp_idgenerator.generator_count
     * @tableColumnDatatype int
     */
    private $intCount = 0;

    /**
     * Generates an id for an specific key. Creates a new entry if the key does not exist
     *
     * @param string $key
     * @return integer
     * @throws Exception
     * @throws Lifecycle\ServiceLifeCycleUpdateException
     */
    public static function generateNextId(string $key): int
    {
        $filepath = \sprintf(self::ID_LOCK_FILE_PATH, $key);

        $ormObjectList = new OrmObjectlist();
        $ormObjectList->addWhereRestriction(new OrmPropertyCondition('strKey', OrmComparatorEnum::Equal(), $key));

        $lastGeneratorId = 0;
        if (empty($result = $ormObjectList->getObjectList(static::class))) {
            $idGenerator = new self();
            $idGenerator->setStrKey($key);
        } else {
            $idGenerator = \current($result);
            $lastGeneratorId = $idGenerator->getIntCount();
        }

        // Todo: what happens in case of errors?
        $id = self::calculateNextId($lastGeneratorId, $filepath);

        self::updateLastIdOfIdFile($filepath, $id);
        $idGenerator->setIntCount($id);
        ServiceLifeCycleFactory::getLifeCycle(\get_class($idGenerator))->update($idGenerator);

        return $id;
    }

    /**
     * @param int $lastGeneratorId
     * @param string $filepath
     * @return int
     * @throws InvalidIdValueInIdFileException
     * @throws UnableToCreateIdFileException
     * @throws UnableToReadIdFileException
     */
    private static function calculateNextId(int $lastGeneratorId, string $filepath): int
    {
        if (self::idFileExists($filepath)) {
            $lastFileId = self::getLastIdOfIdFile($filepath);
        } else {
            self::createIdFile($filepath);
            $lastFileId = 0;
        }

        if ($lastGeneratorId >= $lastFileId) {
            return $lastGeneratorId + 1;
        }

        return $lastFileId + 1;
    }

    /**
     * @param string $filepath
     * @return bool
     */
    private static function idFileExists(string $filepath): bool
    {
        return \file_exists($filepath);
    }

    /**
     * @param string $filepath
     * @throws UnableToCreateIdFileException
     */
    private static function createIdFile(string $filepath): void
    {
        if (!\touch($filepath)) {
            throw new UnableToCreateIdFileException();
        }
    }

    /**
     * @param string $filepath
     * @return int
     * @throws InvalidIdValueInIdFileException
     * @throws UnableToReadIdFileException
     */
    private static function getLastIdOfIdFile(string $filepath): int
    {
        $linesInFile = \file($filepath, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);

        if ($linesInFile === false) {
            throw new UnableToReadIdFileException();
        }

        if ($linesInFile === []) {
            return 0;
        }

        $lastIdValue = \end($linesInFile);

        if (!\ctype_digit($lastIdValue)) {
            throw new InvalidIdValueInIdFileException();
        }

        return (int) $lastIdValue;
    }

    /**
     * @param string $filepath
     * @param int $newId
     * @return void
     * @throws UnableToWriteInIdFileException
     */
    private static function updateLastIdOfIdFile(string $filepath, int $newId): void
    {
        if (\file_put_contents($filepath, (string) $newId, \LOCK_EX) === false) {
            throw new UnableToWriteInIdFileException();
        }
    }

    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin(). Alternatively, you may return an array containing
     *         [the image name, the alt-title]
     */
    public function getStrIcon(): string
    {
        return 'icon_workflow';
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription(): string
    {
        return (string) $this->intCount;
    }

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName(): string
    {
        return StringUtil::truncate($this->strKey, 150);
    }

    /**
     * @return string
     */
    public function getStrKey(): string
    {
        return $this->strKey;
    }

    /**
     * @param string $key
     */
    public function setStrKey(string $key): void
    {
        $this->strKey = $key;
    }

    /**
     * @return integer
     */
    public function getIntCount(): int
    {
        return $this->intCount;
    }

    /**
     * @param int $count
     */
    public function setIntCount(int $count): void
    {
        $this->intCount = $count;
    }

}

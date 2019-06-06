<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;

/**
 * SystemLockApiController
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class SystemLockApiController implements ApiControllerInterface
{
    /**
     * Returns whether the system is locked or not
     *
     * @api
     * @method GET
     * @path /systemlock
     * @authorization filetoken
     */
    public function getLocked()
    {
        return [
            "locked" => is_file($this->getLockFile()),
        ];
    }

    /**
     * Call which creates the lock file
     *
     * @api
     * @method POST
     * @path /systemlock
     * @authorization filetoken
     */
    public function enableLock()
    {
        $lockFile = $this->getLockFile();
        $return = touch($lockFile);

        return [
            "success" => $return,
        ];
    }

    /**
     * Call which removes the lock file
     *
     * @api
     * @method DELETE
     * @path /systemlock
     * @authorization filetoken
     */
    public function disableLock()
    {
        $lockFile = $this->getLockFile();

        if (is_file($lockFile)) {
            $return = unlink($lockFile);
        } else {
            $return = false;
        }

        return [
            "success" => $return,
        ];
    }

    /**
     * @return string
     */
    private function getLockFile()
    {
        return _realpath_."/kajona.lock";
    }
}


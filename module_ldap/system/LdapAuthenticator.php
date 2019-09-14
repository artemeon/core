<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Ldap\System;

use Kajona\Ldap\System\Usersources\UsersourcesUserLdap;
use Kajona\System\System\Database;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Usersources\UsersourcesUserInterface;
use Kajona\System\System\UserUser;

/**
 * Default Implementation of LdapAuthenticateInterface
 *
 * @author stefan.meyer@artemeon.de
 * @module ldap
 */
class LdapAuthenticator implements LdapAuthenticatorInterface
{
    /**
     * @inheritdoc
     */
    public function authenticateUser(UsersourcesUserInterface $objUser, $strPassword)
    {
        if ($objUser instanceof UsersourcesUserLdap) {
            foreach (Ldap::getAllInstances() as $objSingleLdap) {

                if ($objUser->getIntCfg() != $objSingleLdap->getIntCfgNr()) {
                    continue;
                }

                $objRealUser = new UserUser($objUser->getSystemid());

                $arrSingleUser = $objSingleLdap->getUserdetailsByName($objRealUser->getStrUsername());
                if ($arrSingleUser !== false && count($arrSingleUser) == 1) {
                    $arrSingleUser = $arrSingleUser[0];

                    $userName = $this->getBindUserName($arrSingleUser, $objSingleLdap);
                    $bitReturn = $objSingleLdap->authenticateUser($userName, $strPassword);

                    //synchronize the local data with the ldap-data
                    if ($objUser instanceof UsersourcesUserLdap) {
                        $objUser->setStrFamilyname($arrSingleUser["familyname"]);
                        $objUser->setStrGivenname($arrSingleUser["givenname"]);
                        $objUser->setStrEmail($arrSingleUser["mail"]);
                        $objUser->setStrDN($arrSingleUser["identifier"]);
                        $objUser->setIntCfg($objSingleLdap->getIntCfgNr());
                        ServiceLifeCycleFactory::getLifeCycle($objUser)->update($objUser);
                        Database::getInstance()->flushQueryCache();
                    }

                    return $bitReturn;
                }
            }
        }

        return false;
    }


    /**
     * @param array $arrSingleUser
     * @param Ldap $ldapCx
     * @return mixed
     */
    protected function getBindUserName(array $arrSingleUser, Ldap $ldapCx)
    {
        return $arrSingleUser["identifier"];
    }
}

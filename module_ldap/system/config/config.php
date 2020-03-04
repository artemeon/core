<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                                  *
********************************************************************************************************/

/*
    Config-file for the ldap-connector.
    The sample-file is created to match the structure of an ms active directory.

    There may be configured multiple ldap sources, each identified by the numerical array key.
    Do not change the key as soon as the provider os being used, otherwise mapped users and groups may be wrong.

    The ip or server name of the ldap server. this may either be plain, or by passing a connection string.
    In case you need ldaps, make sure to prepend ldaps:// and append the :port, pass null as the explicit ldap port
    Examples:
           $config[0]["ldap_server"] = "ldaps://192.168.60.200:636"; (port must be null)
           $config[0]["ldap_server"] = "ldap://192.168.60.200";      (port may be null)
           $config[0]["ldap_server"] = "192.168.60.200";             (port may be null)
*/


$config = array();

$config[0] = array();
//a readable name to identify the server within the GUI
$config[0]["ldap_alias"]                           = "Server 1";
//the ip or server name of the ldap server. this may either be plain, or by passing a connection string.
$config[0]["ldap_server"]                          = "192.168.60.206";
//port, must be null if the port is added to the connection string
$config[0]["ldap_port"]                            = 389;

//access configuration for the kernel in order to access the directory.
//could be anonymous or read only. e.g. used in order to find new users.
$config[0]["ldap_bind_anonymous"]                  = false;
$config[0]["ldap_bind_username"]                   = "ff@testad1.local";
$config[0]["ldap_bind_userpwd"]                    = "ff";

//the common identifier, used as a direct link to the object in the directory.
//in most cases, this is the combination of cn+objectCategory
$config[0]["ldap_common_identifier"]               = "distinguishedName";

//the search-base for users unknown to the system
$config[0]["ldap_user_base_dn"]                    = "OU=accounts,DC=testad1,DC=local";
//filter to reduce the list of results to the matching object-types
$config[0]["ldap_user_filter"]                     = "(&(objectClass=user)(objectCategory=person)(cn=*))";

//query to be used when searching a single person. the ?-character will be replaced by the searchterm
$config[0]["ldap_user_search_filter"]              = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=?))";
$config[0]["ldap_user_search_wildcard"]            = "(&(objectClass=user)(objectCategory=person) (|(userPrincipalName=?)(sn=?)(givenName=?)))";

//mapping of ldap-attributes to system-internal attributes.
$config[0]["ldap_user_attribute_username"]         = "userPrincipalName";
$config[0]["ldap_user_attribute_mail_fallback"]    = "userPrincipalName";
$config[0]["ldap_user_attribute_mail"]             = "mail";
$config[0]["ldap_user_attribute_familyname"]       = "sn";
$config[0]["ldap_user_attribute_givenname"]        = "givenName";

//restriction to filter groups out of a result-set
$config[0]["ldap_group_filter"]                    = "(objectClass=group)";

//query to check if a given user DN is member of a group. the ? char will be replaced by the users' DN
$config[0]["ldap_group_isUserMemberOf"]            = "(&(objectClass=group)(member=?))";

//the attribute mapping to the groups' members
$config[0]["ldap_group_attribute_member"]          = "member";

//if set to true, only users with at least a single group assignment will be returned. otherwise the user will be created on the fly in the database.
$config[0]["ldap_group_assignment_required"]       = false;




$config[1] = array();
//a readable name to identify the server within the GUI
$config[1]["ldap_alias"]                           = "Server 2";
$config[1]["ldap_server"]                          = "192.168.60.216";
$config[1]["ldap_port"]                            = 389;
$config[1]["ldap_bind_anonymous"]                  = false;
$config[1]["ldap_bind_username"]                   = "ldapbind@ad.artemeon.int";
$config[1]["ldap_bind_userpwd"]                    = "123";
$config[1]["ldap_common_identifier"]               = "distinguishedName";
$config[1]["ldap_user_base_dn"]                    = "OU=Anwender,DC=ad,DC=artemeon,DC=int";
$config[1]["ldap_user_filter"]                     = "(&(objectClass=user)(objectCategory=person)(cn=*))";
$config[1]["ldap_user_search_filter"]              = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=?))";
$config[1]["ldap_user_search_wildcard"]            = "(&(objectClass=user)(objectCategory=person) (|(userPrincipalName=?)(sn=?)(givenName=?)))";
$config[1]["ldap_user_attribute_username"]         = "userPrincipalName";
$config[1]["ldap_user_attribute_mail_fallback"]    = "userPrincipalName";
$config[1]["ldap_user_attribute_mail"]             = "mail";
$config[1]["ldap_user_attribute_familyname"]       = "sn";
$config[1]["ldap_user_attribute_givenname"]        = "givenName";
$config[1]["ldap_group_filter"]                    = "(objectClass=group)";
$config[1]["ldap_group_isUserMemberOf"]            = "(&(objectClass=group)(member=?))";
$config[1]["ldap_group_attribute_member"]          = "member";
$config[1]["ldap_group_assignment_required"]       = false;

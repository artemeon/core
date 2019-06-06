<?php
/*"******************************************************************************************************
 *   (c) 2018 ARTEMEON Core                                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 ********************************************************************************************************/

$config['mail_relay_enabled'] = false; //enables email-sending in general. if disabled, the mail subsystem
//drops mails, but behaves as if sending was successful
$config['mail_copy2file'] = false; //if enabled, outgoing mail are copied to /project/log/mail for debugging purposes

$config['smtp_enabled'] = false; //if disabled, the local mail() command and php setting will be used
$config['smtp_host'] = "mail.exmaple.com"; //with backup: mail1.test.com;mail2.test.com
$config['smtp_port'] = 25; //default: 25, tls: 587, ssl: 465
$config['smtp_encryption'] = ""; //one of '', 'ssl', 'tls'
$config['smtp_debug'] = 0; // 0: off, 1: +client, 2: +server 3: +connection.
// requires $debug['debuglogging_overwrite']['mail.log'] to be set to 3

$config['smtp_auth_enabled'] = false;
$config['smtp_auth_username'] = "";
$config['smtp_auth_password'] = "";

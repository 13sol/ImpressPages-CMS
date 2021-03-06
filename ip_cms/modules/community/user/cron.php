<?php
/**
 * @package	ImpressPages
 * @copyright	Copyright (C) 2011 ImpressPages LTD.
 * @license	GNU/GPL, see ip_license.html
 */

namespace Modules\community\user;

if (!defined('CMS')) exit;


require_once(__DIR__."/db.php");
require_once(MODULE_DIR."administrator/email_queue/module.php");


class Cron {

    function execute($options) {
        global $parametersMod;
        global $dbSite;
        global $log;
        global $site;
        if($options->firstTimeThisMonth) {
            if($parametersMod->getValue('community', 'user', 'options', 'delete_expired_users')) {
                $soonOutdated = Db::getUsersToWarn($parametersMod->getValue('community', 'user', 'options', 'expires_in'), $parametersMod->getValue('community', 'user', 'options', 'warn_before'), $parametersMod->getValue('community', 'user', 'options', 'warn_every'));

                $queue = new \Modules\administrator\email_queue\Module();

                $deleted = Db::deleteOutdatedUsers($parametersMod->getValue('community', 'user', 'options', 'expires_in'));

                foreach($deleted as $key => $user) {
                    $site->dispatchEvent('community', 'user', 'deleted_outdated_user', array('data'=>$user));
                    $emailTemplate = $parametersMod->getValue('standard', 'configuration', 'main_parameters', 'email_template', $user['language_id']);
                    $email = str_replace('[[content]]', $parametersMod->getValue('community', 'user', 'email_messages', 'text_user_deleted'), $emailTemplate);
                    $email = str_replace('[[date]]', substr($user['last_login'], 0, 10), $email);
                    $queue->addEmail($parametersMod->getValue('standard', 'configuration', 'main_parameters', 'email'), $parametersMod->getValue('standard', 'configuration', 'main_parameters', 'name'), $user['email'], '', $parametersMod->getValue('community', 'user', 'email_messages', 'subject_user_deleted'), $email, false, true);
                    $log->log('community/user', 'deleted account', 'Account information: '.implode(', ',$user));
                }


                $setWarned = array();

                foreach($soonOutdated as $key => $user) {
                    $site->dispatchEvent('community', 'user', 'warn_inactive_user', array('data'=>$user));
                    $emailTemplate = $parametersMod->getValue('standard', 'configuration', 'main_parameters', 'email_template', $user['language_id']);
                    $email = str_replace('[[content]]', $parametersMod->getValue('community', 'user', 'email_messages', 'text_account_will_expire'), $emailTemplate);
                    $email = str_replace('[[date]]', substr($user['valid_until'], 0, 10), $email);
                    $link = $site->generateUrl($user['language_id'], null, null, array("module_group"=>"community","module_name"=>"user", "action"=>"renew_registration", "id"=>$user['id']));
                    $email = str_replace('[[link]]', '<a href="'.$link.'">'.$link.'</a>', $email);
                    $queue->addEmail($parametersMod->getValue('standard', 'configuration', 'main_parameters', 'email'), $parametersMod->getValue('standard', 'configuration', 'main_parameters', 'name'), $user['email'], '', $parametersMod->getValue('community', 'user', 'email_messages', 'subject_account_will_expire'), $email, false, true);
                    $setWarned[] = $user['id'];
                    $log->log('community/user', 'account warned', 'Account information: '.implode(', ',$user));
                }

                Db::setWarned($setWarned);

                if((sizeof($deleted) > 0 || $soonOutdated > 0))
                $queue->send();
            }
        }
    }

}





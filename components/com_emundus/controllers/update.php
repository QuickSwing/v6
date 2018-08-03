<?php

/**
 * @package     Joomla
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2015 eMundus. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 * @author James Dean
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');


class EmundusControllerUpdate extends JControllerLegacy {

    public function __construct($config = array()) {
        require_once (JPATH_COMPONENT.DS.'models'.DS.'update.php');
        parent::__construct($config);
    }

    // Accept Update
    public function accept() {
        $jinput = JFactory::getApplication()->input;
        $version = $jinput->post->get('version', null);
        $oldVersion = $jinput->post->get('oldversion', null);
        $ignoreVersion = $jinput->post->get('ignoreversion', null);
        $m_update = new EmundusModelUpdate();

        // verify if version is not already set
        if($version != $oldVersion && $version != $ignoreVersion) {

            $config = JFactory::getConfig();

            $subject = "Mise à jour eMundus";

            $body = "Bonjour équipe eMundus,<br>
                    Vous avez reçu une demande de mise à jour eMundus v" . $version . " par " . JFactory::getUser()->name . " pour leur site " . JURI::base();
    
            // Get default mail sender info
            $mail_from_sys = $config->get('mailfrom');
            $mail_from_sys_name = $config->get('fromname');
    
            // Set sender
            $sender = [
                $mail_from_sys,
                $mail_from_sys_name
            ];
    
            $eMConfig = JComponentHelper::getParams('com_emundus');
            $emundusEmail = $eMConfig->get('emundus_email', 'support@emundus.fr');
            
            // Configure email sender
            $mailer = JFactory::getMailer();
            $mailer->setSender($sender);
            $mailer->addReplyTo($mail_from_sys, $mail_from_sys_name);
            $mailer->addRecipient($emundusEmail);
            $mailer->setSubject($subject);
            $mailer->isHTML(true);
            $mailer->Encoding = 'base64';
            $mailer->setBody($body);
            
            // Send and log the email.
            $send = $mailer->Send();
            
            if ($send !== true) {
                JLog::add($send->__toString(), JLog::ERROR, 'com_emundus');

                echo json_encode((object)[
                    'status' => false,
                    'msg' => "Internal error"
                ]);
                exit;

            } else {

                echo json_encode((object)[
                    'status' => $m_update->setIgnoreVal($version)
                ]);
                exit;

            }
        } else {

            echo json_encode((object)[
                'status' => false,
                'msg' => "Internal error"
            ]);
            exit;

        }
        
        
    }

/// Ignore Update 
    public function ignore() {
        $jinput = JFactory::getApplication()->input;
        $version = $jinput->post->get('version', null);
        $oldVersion = $jinput->post->get('oldversion', null);
        $ignoreVersion = $jinput->post->get('ignoreversion', null);
        $m_update = new EmundusModelUpdate();

        if($version != $oldVersion && $version != $ignoreVersion) {
            echo json_encode((object)[
                'status' => $m_update->setIgnoreVal($version)
            ]);
            exit;

        } else {

            echo json_encode((object)[
                'status' => false
            ]);
            exit;

        }
    }

}

    
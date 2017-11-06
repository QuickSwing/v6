<?php
defined( '_JEXEC' ) or die();
/**
 * @version 1: attachement_public.php 89 2017-11-06 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2017 eMundus SAS. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Envoi automatique d'un email à l'étudiant lors d'un envoie de formulaire de référence fait par le référent désigné par l'utilisateur.
 */

$eMConfig   = JComponentHelper::getParams('com_emundus');
$alert_new_attachment = $eMConfig->get('alert_new_attachment');

$mainframe  = JFactory::getApplication();
$jinput     = $mainframe->input;
$user_id    = $jinput->get('jos_emundus_reference_letter___user');

$mailer     = JFactory::getMailer();
$db         = JFactory::getDBO();
$baseurl    = JURI::base(true);

$key_id     = JRequest::getVar('keyid', null, 'get');
$sid        = JRequest::getVar('sid', null, 'get');

jimport('joomla.log.log');
JLog::addLogger(
    array(
        // Sets file name
        'text_file' => 'com_emundus.filerequest.php'
    ),
    // Sets messages of all log levels to be sent to the file
    JLog::ALL,
    // The log category/categories which should be recorded in this file
    // In this case, it's just the one category from our extension, still
    // we need to put it inside an array
    array('com_emundus')
);

try {

	$student = &JUser::getInstance($user_id);

	if (!isset($student)) {
		JLog::add("PLUGIN emundus-attachment_public [".$key_id."]: ".JText::_("ERROR_STUDENT_NOT_SET"), JLog::ERROR, 'com_emundus');
		header('Location: '.$baseurl.'index.php');
		exit();
    }

    $query = 'UPDATE #__emundus_files_request SET uploaded = 1 WHERE keyid = "'.$key_id.'"';
    $db->setQuery($query);
    $db->execute();

	// Récupération des données du mail
	$query = 'SELECT id, subject, emailfrom, name, message
					FROM #__emundus_setup_emails
					WHERE lbl = "reference_form_complete"';
	$db->setQuery($query);
	$obj = $db->loadObject();

	$patterns = array('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/','/\n/');
	$replacements = array($student->id, $student->name, $student->email, '<br />');

    // Mail au candidat
	$from           = $obj->emailfrom;
	$fromname       = $obj->name;
	$recipient[]    = $student->email;
	$subject        = $obj->subject;
	$body           = preg_replace($patterns, $replacements, $obj->message).'<br/>';
	$mode           = 1;
	$replyto        = $obj->emailfrom;
	$replytoname    = $obj->name;

    // setup mail
    $email_from_sys = $mainframe->getCfg('mailfrom');

    // If the email sender has the same domain as the system sender address.
    if (!empty($from) && substr(strrchr($from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1))
        $mail_from_address = $from;
    else
        $mail_from_address = $email_from_sys;

    // Set sender
    $sender = array(
        $mail_from_address,
        $mail_from_name
    );

    $mailer = JFactory::getMailer();
    $mailer->setSender($sender);
    $mailer->addReplyTo($from, $fromname);
    $mailer->addRecipient($recipient);
    $mailer->setSubject($subject);
    $mailer->isHTML(true);
    $mailer->Encoding = 'base64';
    $mailer->setBody($body);

    $send = $mailer->Send();
    if ( $send !== true ) {
    	JLog::add("PLUGIN emundus-attachment_public [".$key_id."]: ".JText::_("ERROR_CANNOT_SEND_EMAIL").$send->__toString(), JLog::ERROR, 'com_emundus');
        echo 'Error sending email: ' . $send->__toString(); die();
    } else {
		$sql = "INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `subject`, `message`, `date_time`)
				VALUES ('62', '".$student->id."', ".$db->quote($subject).", ".$db->quote($body).", NOW()";
        $db->setQuery($sql);
        $db->execute();
    }

    // Step one is to get the email and name of the referent.
    $query = 'SELECT *
                FROM #__emundus_references as er
                WHERE er.fnum IN (
                    SELECT fnum
                    FROM #__emundus_files_request as efr
                    WHERE efr.keyid = "'.$key_id.'"
                )';
    $db->setQuery($query);
    $reference = $db->loadObject();

    // Récupération des données du mail
	$query = 'SELECT id, subject, emailfrom, name, message
        FROM #__emundus_setup_emails
        WHERE lbl = "reference_form_received"';
    $db->setQuery($query);
    $obj = $db->loadObject();

    $patterns = array('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/','/\n/');
    $replacements = array($student->id, $reference->Last_Name_1." ".$reference->First_Name_1, $reference->Email_1, '<br />');

    // Mail au référent
	$from           = $obj->emailfrom;
	$fromname       = $obj->name;
	$recipient[]    = $reference->Email_1;
	$subject        = $obj->subject;
	$body           = preg_replace($patterns, $replacements, $obj->message).'<br/>';
	$mode           = 1;
	$replyto        = $obj->emailfrom;
    $replytoname    = $obj->name;

    // If the email sender has the same domain as the system sender address.
    if (!empty($from) && substr(strrchr($from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1))
        $mail_from_address = $from;
    else
        $mail_from_address = $email_from_sys;

    // Set sender
    $sender = array(
        $mail_from_address,
        $mail_from_name
    );

    $mailer = JFactory::getMailer();
    $mailer->setSender($sender);
    $mailer->addReplyTo($from, $fromname);
    $mailer->addRecipient($recipient);
    $mailer->setSubject($subject);
    $mailer->isHTML(true);
    $mailer->Encoding = 'base64';
    $mailer->setBody($body);

    $send = $mailer->Send();
    if ($send !== true) {
    	JLog::add("PLUGIN IMT_emundus-attachment_public [".$key_id."]: ".JText::_("ERROR_CANNOT_SEND_EMAIL").$send->__toString(), JLog::ERROR, 'com_emundus');
        echo 'Error sending email: ' . $send->__toString(); die();
    }

} catch (Exception $e) {
    // catch any database errors.
    JLog::add(JUri::getInstance().' :: USER ID : '.JFactory::getUser()->id.' -> '.$query, JLog::ERROR, 'com_emundus');
}

header('Location: '.$baseurl.'index.php?option=com_content&view=article&id=18');
exit();
?>
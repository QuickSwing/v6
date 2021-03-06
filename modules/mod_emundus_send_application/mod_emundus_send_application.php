<?php
/**
 * @package		Joomla
 * @subpackage	eMundus
 * @copyright	Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Include the latest functions only once
require_once dirname(__FILE__).'/helper.php';

$user = JFactory::getSession()->get('emundusUser');
if (!empty($user->fnum)) {

	include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');
	require_once(JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'checklist.php');

	$layout = $params->get('layout', 'default');
	$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

	$eMConfig = JComponentHelper::getParams('com_emundus');
	$id_applicants = $eMConfig->get('id_applicants', '0');
	$applicants = explode(',',$id_applicants);

	$application = modemundusSendApplicationHelper::getApplication($user->fnum);

	$m_application = new EmundusModelApplication;
	$m_checklist = new EmundusModelChecklist;

	$attachments = $m_application->getAttachmentsProgress($user->id, $user->profile, $user->fnum);
	$forms 	= $m_application->getFormsProgress($user->id, $user->profile, $user->fnum);

	// We redirect to the "send application" form, this form will redirect to payment if required.
	$confirm_form_url = $m_checklist->getConfirmUrl().'&usekey=fnum&rowid='.$user->fnum;

	$app = JFactory::getApplication();
	$offset = $app->get('offset', 'UTC');
	try {
		$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
		$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
		$now = $dateTime->format('Y-m-d H:i:s');
	} catch (Exception $e) {
		echo $e->getMessage() . '<br />';
	}

	if (!empty($user->end_date))
		$is_dead_line_passed = (strtotime(date($now)) > strtotime($user->end_date))?true:false;
	if (!empty($user->status))
		$is_app_sent = ($user->status != 0)? true : false;

	require JModuleHelper::getLayoutPath('mod_emundus_send_application', $layout);
}



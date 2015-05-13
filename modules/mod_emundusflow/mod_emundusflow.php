<?php
/**
* @version		$Id: mod_emundusflow.php 7692 2007-06-08 20:41:29Z tcp $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'menu.php');
require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'checklist.php');
require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

$user = JFactory::getUser();

$db = JFactory::getDBO();

$eMConfig = JComponentHelper::getParams('com_emundus');
$applicant_can_renew = $eMConfig->get('applicant_can_renew');
$show_programme = $params->get('show_programme', 1);
$checklist = new EmundusModelChecklist;
$application = new EmundusModelApplication;

$attachments = $application->getAttachmentsProgress($user->id, $user->profile, $user->fnum);
$forms = $application->getFormsProgress($user->id, $user->profile, $user->fnum);

$sent = $checklist->getSent();
$confirm_form_url = $checklist->getConfirmUrl(); 

require(JModuleHelper::getLayoutPath('mod_emundusflow'));
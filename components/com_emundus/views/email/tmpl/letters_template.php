<?php 
defined('_JEXEC') or die('Restricted access'); 

JHTML::_('behavior.modal'); 
JHTML::_('behavior.tooltip'); 
JHTML::stylesheet( 'emundus.css', 'media/com_emundus/css/' );
JHTML::stylesheet( 'light2.css', 'templates/rt_afterburner/css/' );
JHTML::stylesheet( 'general.css', 'templates/system/css/' );
JHTML::stylesheet( 'system.css', 'templates/system/css/' );

require_once (JPATH_COMPONENT.DS.'helpers'.DS.'list.php');
require_once (JPATH_COMPONENT.DS.'helpers'.DS.'filters.php');

$current_user = JFactory::getUser();

if (EmundusHelperAccess::asCoordinatorAccessLevel($current_user->id)) {

	$student_id = 62; 
	$itemid = JRequest::getVar('Itemid', null, 'GET', 'INT',0); 
	$letter_id = JRequest::getVar('rowid', null, 'GET', 'INT',0); 

	$user = JFactory::getUser($student_id);

	if (!empty($letter_id)) { 
		require(JPATH_LIBRARIES.DS.'emundus'.DS.'pdf.php');
		$files = letter_pdf_template($user->id, $letter_id);
	}
 } 
 ?>
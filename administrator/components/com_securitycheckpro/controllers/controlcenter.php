<?php
/**
* ControlCenter Controller para Securitycheck Pro
* @ author Jose A. Luque
* @ Copyright (c) 2011 - Jose A. Luque
* @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
*/

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Load framework base classes
jimport('joomla.application.component.controller');

/**
* Securitycheckpros  Controller
*
*/
class SecuritycheckprosControllerControlCenter extends SecuritycheckproController
{

/* Redirecciona las peticiones al componente */
function redireccion()
{
	$this->setRedirect( 'index.php?option=com_securitycheckpro' );
}


/* Guarda los cambios y redirige al cPanel */
public function save()
{
	$model = $this->getModel('cron');
	$jinput = JFactory::getApplication()->input;
	$data = $jinput->getArray($_POST);
	$model->saveConfig($data, 'controlcenter');

	$this->setRedirect('index.php?option=com_securitycheckpro&view=controlcenter&'. JSession::getFormToken() .'=1',JText::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
}

/* Guarda los cambios */
public function apply()
{
	$this->save('cron_plugin');
	$this->setRedirect('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&'. JSession::getFormToken() .'=1',JText::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
}

}
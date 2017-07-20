<?php
/**
* FirewallMode View para el Componente Securitycheckpro
* @ author Jose A. Luque
* @ Copyright (c) 2011 - Jose A. Luque
* @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
*/

// Chequeamos si el archivo est� incluido en Joomla!
defined('_JEXEC') or die();
jimport( 'joomla.application.component.view' );
jimport( 'joomla.plugin.helper' );

// Load plugin language
$lang = JFactory::getLanguage();
$lang->load('plg_system_securitycheckpro');


class SecuritycheckprosViewFirewallMode extends SecuritycheckproView
{

protected $state;

function __construct() 	{
	parent::__construct();
	
	JToolBarHelper::title( JText::_( 'Securitycheck Pro' ).' | ' .JText::_('PLG_SECURITYCHECKPRO_MODE_FIELDSET_LABEL'), 'securitycheckpro' );	
}

/**
* Securitycheckpros FirewallConfig m�todo 'display'
**/
function display($tpl = null)
{

// Filtro
$this->state= $this->get('State');
$lists = $this->state->get('filter.lists_search');

// Obtenemos el modelo
$model = $this->getModel();

//  Par�metros del plugin
$items= $model->getConfig();

// Extraemos los elementos que nos interesan...
$mode= null;
if ( !is_null($items['mode']) ) {
	$mode = $items['mode'];	
}

// ... y los ponemos en el template
$this->assignRef('mode',$mode);

parent::display($tpl);
}
}
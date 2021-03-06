<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Dsconstant
 *
 * @author      Roberto Segura <roberto@phproberto.com>
 * @copyright   (c) 2012 Digital Disseny. All Rights Reserved.
 * @license     GNU/GPL 2, http://www.gnu.org/licenses/gpl-2.0.htm
 */

defined('_JEXEC') or die;

JLoader::import('joomla.plugin.plugin');

/**
 * Main plugin class
 *
 * @version     30/09/2012
 * @package     Joomla.Plugin
 * @subpackage  System.Dsconstant
 * @since       3.0
 *
 */
class PlgSystemDsconstant extends JPlugin
{

	private $_params = null;

	// Plugin info constants
	const TYPE = 'system';

	const NAME = 'dsconstant';

	// Handy objects
	private $_app      = null;

	private $_doc      = null;

	private $_jinput   = null;

	// Paths
	private $_pathPlugin = null;

	// Urls
	private $_urlPlugin    = null;

	private $_urlJs        = null;

	private $_urlCss       = null;

	// Url parameters
	private $_option   = null;

	private $_view     = null;

	private $_id       = null;

	// CSS & JS scripts calls
	private $_cssCalls = array();

	private $_jsCalls  = array();

	// HTML positions to inject CSS & JS
	private $_htmlPositions = array(
			'headtop' => array( 'pattern' => "/(<head>)/isU",
								'replacement' => "$1\n\t##CONT##"),
			'headbottom' => array(  'pattern' => "/(<\/head>)/isU",
									'replacement' => "\n\t##CONT##\n$1"),
			'bodytop' => array( 'pattern' => "/(<body)(.*)(>)/isU",
								'replacement' => "$1$2$3\n\t##CONT##"),
			'bodybottom' => array(  'pattern' => "/(<\/body>)/isU",
									'replacement' => "\n\t##CONT##\n$1"),
			'belowtitle' => array(  'pattern' => "/(<\/title>)/isU",
									'replacement' => "$1\n\t##CONT##")
			);

	// Autogenerated with array_keys($this->_htmlPositions)
	private $_htmlPositionsAvailable = array();

	// Used to validate url
	private $_componentsEnabled    = array('*');

	private $_viewsEnabled         = array('*');

	// Configure applications where enable plugin
	private $_frontendEnabled  = true;

	private $_backendEnabled   = true;

	/**
	* Constructor
	*
	* @param   mixed  &$subject  Subject
	*/
	function __construct( &$subject )
	{

		parent::__construct($subject);

		// Required objects
		$this->_app 	= JFactory::getApplication();
		$this->_doc 	= JFactory::getDocument();
		$this->_jinput 	= $this->_app->input;

		// Get url parameters
		$this->_option 	= $this->_jinput->get('option', null);
		$this->_view 	= $this->_jinput->get('view', null);
		$this->_id 		= $this->_jinput->get('id', null);

		// Set the HTML available positions
		$this->_htmlPositionsAvailable = array_keys($this->_htmlPositions);

		// Load plugin parameters
		$this->_plugin = JPluginHelper::getPlugin(self::TYPE, self::NAME);
		$this->_params = new JRegistry($this->_plugin->params);

		$this->_frontendEnabled = $this->_params->get('frontend', 1);
		$this->_backendEnabled 	= $this->_params->get('backend', 1);

		// Init folder structure
		$this->_initFolders();

		// Load plugin language
		$this->loadLanguage('plg_' . self::TYPE . '_' . self::NAME, JPATH_ADMINISTRATOR);
	}

	/**
	 * This event is triggered after the framework has loaded and the application initialise method has been called.
	 * http://docs.joomla.org/Plugin/Events/System
	 *
	 * @return boolean
	 */
	function onAfterInitialise()
	{
		// Validate view
		if (!$this->_validateUrl())
		{
			return true;
		}

		if (!defined('DS'))
		{
			define('DS', DIRECTORY_SEPARATOR);
		}
	}

	/**
	 * Add a css file declaration
	 *
	 * @param   string  $cssUrl    url of the CSS file
	 * @param   string  $position  position where we are going to load JS
	 *
	 * @return none
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 23/04/2012
	 */
	private function _addCssCall($cssUrl, $position = null)
	{
		// If position is not available we will try to load the url through $doc->addScript
		if (is_null($position) || !in_array($position, $this->_htmlPositionsAvailable))
		{
			$position = 'addstylesheet';
			$cssCall = $jsUrl;
		}
		else
		{
			$cssCall = '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" >';
		}

		// Initialize position
		if (!isset($this->_cssCalls[$position]))
		{
			$this->_cssCalls[$position] = array();
		}

		// Insert CSS call
		$this->_cssCalls[$position][] = $cssCall;
	}

	/**
	 * Add a JS script declaration
	 *
	 * @param   string  $jsUrl     url of the JS file or script content for type != url
	 * @param   string  $position  position where we are going to load JS
	 * @param   string  $type      url || script
	 *
	 * @return none
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 27/06/2012
	 */
	private function _addJsCall($jsUrl, $position = null, $type = 'url')
	{

		// If position is not available we will try to load the url through $doc->addScript
		if (is_null($position) || !in_array($position, $this->_htmlPositionsAvailable))
		{
			$position = 'addscript';
			$jsCall = $jsUrl;
		}
		else
		{
			if ($type == 'url')
			{
				$jsCall = '<script src="' . $jsUrl . '" type="text/javascript"></script>';
			}
			else
			{
				$jsCall = '<script type="text/javascript">' . $jsUrl . '</script>';
			}
		}

		// Initialize position
		if (!isset($this->_jsCalls[$position]))
		{
			$this->_jsCalls[$position] = array();
		}

		// Insert JS call
		$this->_jsCalls[$position][] = $jsCall;
	}

	/**
	 * Initialize folder structure
	 *
	 * @return none
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _initFolders()
	{
		// Paths
		$this->_pathPlugin = JPATH_PLUGINS . '/' . self::TYPE . '/' . self::NAME;

		// URLs
		$this->_urlPlugin 	= JURI::root(true) . "/plugins/" . self::TYPE . "/" . self::NAME;
		$this->_urlJs 		= $this->_urlPlugin . "/js";
		$this->_urlCss 		= $this->_urlPlugin . "/css";
	}

	/**
	 * Load / inject CSS
	 *
	 * @return none
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 27/06/2012
	 *
	 */
	private function _loadCSS()
	{
		if (!empty($this->_cssCalls))
		{
			$body = JResponse::getBody();
			foreach ($this->_cssCalls as $position => $cssCalls)
			{
				if (!empty($cssCalls))
				{
					// If position is defined we append code (inject) to the desired position
					if (in_array($position, $this->_htmlPositionsAvailable))
					{
						// Generate the injected code
						$cssIncludes = implode("\n\t", $cssCalls);
						$pattern = $this->_htmlPositions[$position]['pattern'];
						$replacement = str_replace('##CONT##', $cssIncludes, $this->_htmlPositions[$position]['replacement']);
						$body = preg_replace($pattern, $replacement, $body);
					}
					else
					{
						$doc = JFactory::getDocument();
						foreach ($cssCalls as $cssUrl)
						{
							$doc->addStyleSheet($cssUrl);
						}
					}
				}
			}
			JResponse::setBody($body);
			return $body;
		}
	}

	/**
	 * Load / inject Javascript
	 *
	 * @return none
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 27/06/2012
	 *
	 */
	private function _loadJS()
	{
		if (!empty($this->_jsCalls))
		{
			$body = JResponse::getBody();
			foreach ($this->_jsCalls as $position => $jsCalls)
			{
				if (!empty($jsCalls))
				{
					// If position is defined we append code (inject) to the desired position
					if (in_array($position, $this->_htmlPositionsAvailable))
					{
						// Generate the injected code
						$jsIncludes = implode("\n\t", $jsCalls);
						$pattern = $this->_htmlPositions[$position]['pattern'];
						$replacement = str_replace('##CONT##', $jsIncludes, $this->_htmlPositions[$position]['replacement']);
						$body = preg_replace($pattern, $replacement, $body);
					}
					else
					{
						$doc = JFactory::getDocument();
						foreach ($jsCalls as $jsUrl)
						{
							$doc->addScript($jsUrl);
						}
					}
				}
			}
			JResponse::setBody($body);
			return $body;
		}
	}

	/**
	 * Validate if the plugin is enabled for current application (frontend / backend)
	 *
	 * @return boolean
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _validateApplication()
	{
		if ( ($this->_app->isSite() && $this->_frontendEnabled)
			|| ($this->_app->isAdmin() && $this->_backendEnabled) )
		{
			return true;
		}
		return false;
	}

	/**
	 * Validate option in url
	 *
	 * @return boolean
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _validateComponent()
	{
		if ( in_array('*', $this->_componentsEnabled) || in_array($this->_option, $this->_componentsEnabled) )
		{
			return true;
		}
		return false;
	}

	/**
	 * Custom method for extra validations
	 *
	 * @return boolean
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _validateExtra()
	{
		return $this->_validateApplication();
	}

	/**
	 * Plugin enabled for this url?
	 *
	 * @return boolean
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _validateUrl()
	{
		if ( $this->_validateComponent() && $this->_validateView())
		{
			if (method_exists($this, '_validateExtra'))
			{
				return $this->_validateExtra();
			}
			else
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate view parameter in url
	 *
	 * @return boolean
	 *
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 28/06/2012
	 *
	 */
	private function _validateView()
	{
		if ( in_array('*', $this->_viewsEnabled) || in_array($this->_view, $this->_viewsEnabled))
		{
			return true;
		}
		return false;
	}
}

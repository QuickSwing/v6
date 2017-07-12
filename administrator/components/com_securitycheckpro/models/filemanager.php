<?php
/**
* Modelo FileManager para el Componente Securitycheckpro
* @ author Jose A. Luque
* @ Copyright (c) 2011 - Jose A. Luque
* @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
*/

// Chequeamos si el archivo est� inclu�do en Joomla!
defined('_JEXEC') or die();
jimport( 'joomla.application.component.model' );
jimport( 'joomla.version' );
jimport( 'joomla.access.rule' );
jimport( 'joomla.application.component.helper' );
jimport('joomla.updater.update' );
jimport('joomla.installer.helper' );
jimport('joomla.installer.installer' );
jimport( 'joomla.application.component.controller' );
jimport('joomla.filesystem.file');

/**
* Modelo Filemanager
*/
class SecuritycheckprosModelFileManager extends JModelLegacy
{
/** @var object Pagination */
var $_pagination = null;

/** @var array The files to process */
private $Stack = array();

/** @var array The files to process */
private $Stack_Integrity = array();

/** @var int Total numbers of file/folders in this site. Permissions option */
public $files_scanned = 0;

/** @var int Total numbers of file/folders in this site. Integrity option*/
public $files_scanned_integrity = 0;

/** @var int Numbers of files/folders with  incorrect permissions */
public $files_with_incorrect_permissions = 0;

/** @var int Numbers of files/folders with  incorrect integrity */
public $files_with_incorrect_integrity = 0;

/** @var array Skip subdirectories and files of these directories. Permissions option */
private $skipDirsPermissions = array();

/** @var array Skip subdirectories and files of these directories. Integrity option */
private $skipDirsIntegrity = array();

/** @var int Percent of files processed each time */
public $last_percent = 0;

/** @var int Percent of files processed each time */
private $last_percent_permissions = 0;

/** @var int Percent of files processed each time */
private $files_processed_permissions = 0;

/** @var boolean Task completed */
private $task_completed = false;

/** @var string Path to the folder where scans will be stored */
private $folder_path = '';

/** @var string filemanager's name */
private $filemanager_name = '';

/** @var string fileintegrity's name */
private $fileintegrity_name = '';

/** @var int Numbers of files scanned looking for malware */
public $files_scanned_malwarescan = 0;

/** @var int Numbers of files suspicious of malware */
public $suspicious_files = 0;

/** @var int Percent of files processed each time */
private $files_processed_malwaresecan = 0;

/** @var int Percent of files processed each time */
private $last_percent_malwarescan = 0;

/** @var array The files to process */
private $Stack_malwarescan = array();

/** @var string malwarescan's name */
private $malwarescan_name = '';

/** @var string file content */
public $content = null;

/** @var array File extensions to analyze looking for malware */
private $fileExt = null;

/** @var array Use the exceptions stablished in File Manager option (Malware scan) */
private $use_filemanager_exceptions = 1;

/** @var array Skip subdirectories and files of these directories. Integrity option */
private $skipDirsMalwarescan = array();

/** @var int Percent of files processed each time */
private $files_processed = 0;

function __construct()
{
	parent::__construct();
	
	// Excepci�n
	$excepcion_escaneos = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'protection.php';
	
	// Establecemos la ruta donde se almacenar�n los escaneos
	$this->folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans';
	
	// Establecemos el tama�o m�ximo de memoria que el script puede consumir
	$params = JComponentHelper::getParams('com_securitycheckpro');
	$memory_limit = $params->get('memory_limit','512M');
	
	if ( preg_match('/^[0-9]*M$/',$memory_limit) ) {
		ini_set('memory_limit',$memory_limit);
	} else {
		ini_set('memory_limit','512M');
		JFactory::getApplication()->enqueueMessage(JText::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'),'error');
	}	
		
	// A�adimos los directorios 'cache', 'tmp' y 'log' a la lista de excepciones
	$this->skipDirsPermissions[] = rtrim(JPATH_CACHE,DIRECTORY_SEPARATOR);
	$this->skipDirsPermissions[] = rtrim(JPATH_ROOT. DIRECTORY_SEPARATOR . 'cache',DIRECTORY_SEPARATOR);
	$this->skipDirsIntegrity[] = rtrim(JPATH_CACHE,DIRECTORY_SEPARATOR);
	$this->skipDirsIntegrity[] = rtrim(JPATH_ROOT. DIRECTORY_SEPARATOR . 'cache',DIRECTORY_SEPARATOR);
	$this->skipDirsMalwarescan[] = rtrim(JPATH_CACHE,DIRECTORY_SEPARATOR);
	$this->skipDirsMalwarescan[] = rtrim(JPATH_ROOT. DIRECTORY_SEPARATOR . 'cache',DIRECTORY_SEPARATOR);
	$this->skipDirsMalwarescan[] = $this->folder_path;
	if(version_compare(JVERSION, '3.0', 'ge')) {
		$this->skipDirsPermissions[] = rtrim(JFactory::getConfig()->get('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsPermissions[] = rtrim(JFactory::getConfig()->get('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
		$this->skipDirsIntegrity[] = rtrim(JFactory::getConfig()->get('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsIntegrity[] = rtrim(JFactory::getConfig()->get('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
		//$this->skipDirsMalwarescan[] = rtrim(JFactory::getConfig()->get('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsMalwarescan[] = rtrim(JFactory::getConfig()->get('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
	} else {
		$this->skipDirsPermissions[] = rtrim(JFactory::getConfig()->getValue('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsPermissions[] = rtrim(JFactory::getConfig()->getValue('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
		$this->skipDirsIntegrity[] = rtrim(JFactory::getConfig()->getValue('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsIntegrity[] = rtrim(JFactory::getConfig()->getValue('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
		//$this->skipDirsMalwarescan[] = rtrim(JFactory::getConfig()->getValue('tmp_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'tmp'), DIRECTORY_SEPARATOR);
		$this->skipDirsMalwarescan[] = rtrim(JFactory::getConfig()->getValue('log_path', JPATH_ROOT. DIRECTORY_SEPARATOR . 'logs'), DIRECTORY_SEPARATOR);
	}
	
	// A�adimos el fichero de escaneos como excepci�n al escaneo de integridad
	array_push($this->skipDirsIntegrity,$this->folder_path);
	
	// A�adimos el fichero 'protection.php' como excepci�n a los escaneos de integridad y malware
	array_push($this->skipDirsIntegrity,$excepcion_escaneos);
	array_push($this->skipDirsMalwarescan,$excepcion_escaneos);
	
	// Obtenemos las excepciones extablecidas por el usuario para la opci�n 'File Manager' 
	$exceptions_permissions = $params->get('file_manager_path_exceptions',null);
	
	// Creamos un array que contendr� rutas de archivos o directorios exentos del chequeo de permisos
	$exceptions_permissions_array= null;
	if ( !is_null($exceptions_permissions) ) {
		$exceptions_permissions_array = explode(',',$exceptions_permissions);
		// A�adimos las excepciones al array de excepciones
		foreach($exceptions_permissions_array as $exception_path) {
			$this->skipDirsPermissions[] = rtrim($exception_path, DIRECTORY_SEPARATOR);
		}
	}
	
	// Obtenemos las excepciones extablecidas por el usuario para la opci�n 'File Integrity' 
	$exceptions_integrity = $params->get('file_integrity_path_exceptions',null);
	
	// Creamos un array que contendr� rutas de archivos o directorios exentos del chequeo de integridad
	$exceptions_integrity_array= null;
	if ( !is_null($exceptions_integrity) ) {
		$exceptions_integrity_array = explode(',',$exceptions_integrity);
		// A�adimos las excepciones al array de excepciones
		foreach($exceptions_integrity_array as $exception_path) {
			$this->skipDirsIntegrity[] = rtrim($exception_path, DIRECTORY_SEPARATOR);
		}
	}
	
	// Obtenemos las excepciones establecidas por el usuario para la opci�n 'File Manager' 
	$exceptions_malwarescan = $params->get('malwarescan_path_exceptions',null);
	
	// Creamos un array que contendr� rutas de archivos o directorios exentos del chequeo de permisos
	$exceptions_malwarescan_array= null;
	if ( !is_null($exceptions_malwarescan) ) {
		$exceptions_malwarescan_array = explode(',',$exceptions_malwarescan);
		// A�adimos las excepciones al array de excepciones
		foreach($exceptions_malwarescan_array as $exception_path) {
			$this->skipDirsMalwarescan[] = rtrim($exception_path, DIRECTORY_SEPARATOR);
		}
	}
	
	// Obtenemos el nombre de los escaneos anteriores
	$db = $this->getDbo();
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
	$db->setQuery($query);
	$stack = $db->loadResult();
	$stack = json_decode($stack, true);
	
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
	$db->setQuery($query);
	$stack_integrity = $db->loadResult();
	$stack_integrity = json_decode($stack_integrity, true);
	
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
	$db->setQuery($query);
	$stack_malwarescan = $db->loadResult();
	$stack_malwarescan = json_decode($stack_malwarescan, true);
	
	if( (!empty($stack)) && (isset($stack['filename'])) ) {
		$this->filemanager_name = $stack['filename'];
	}
	
	if( (!empty($stack_integrity)) && (isset($stack_integrity['filename'])) ) {
		$this->fileintegrity_name = $stack_integrity['filename'];
	}
	
	if( (!empty($stack_malwarescan)) && (isset($stack_malwarescan['filename'])) ) {
		$this->malwarescan_name = $stack_malwarescan['filename'];
	}
	
	// Obtenemos las extensiones de ficheros a analizar
	$this->fileExt = $params->get('malwarescan_file_extensions',null);
	
	// �El escaneo de malware usa las mismas excepciones que el de integridad?
	$this->use_filemanager_exceptions = $params->get('use_filemanager_exceptions',1);
}

/* Funci�n que obtiene todos los archivos del sitio */
public function getFiles($root = null, $include_exceptions, $recursive, $opcion)
{
	/* Cargamos el lenguaje del sitio */
	$lang = JFactory::getLanguage();
	$lang->load('com_securitycheckpro',JPATH_ADMINISTRATOR);
		
	if(empty($root)) $root = JPATH_ROOT;

	if(empty($root)) {
		$root = '..';
		$root = realpath($root);
	}
		
	jimport('joomla.filesystem.folder');
	
	// �Debemos escanear todos los archivos o s�lo los ejecutables?
	$params = JComponentHelper::getParams('com_securitycheckpro');
	$scan_executables_only = $params->get('scan_executables_only',0);
	
	if ( $opcion == "malwarescan_modified" ) {
		$files_name = $this->loadModifiedFiles();			
	} else {
		// Extensiones excluidas (no ser�n analizadas)
		$excludedExtensions = array('\.aif','\.iff','\.conf','\.m3u','\.m4a','\.mid','\.mp3','\.mpa','\.wav','\.wma','\.3g2','\.3gp','\.asf','\.asx','\.avi','\.flv','\.m4v','\.mov','\.mp4','\.mpg','\.rm','\.srt','\.swf','\.vob','\.wmv','\.bmp','\.dds','\.gif','\.jpg','\.png','\.psd','\.pspimage','\.tga','\.thm','\.tif','\.tiff','\.yuv','\.eps','\.svg','\.txt','\.tar','\.zip','\.jpa');		
		$excludedExtensions = array_merge($excludedExtensions, array_map('strtoupper',$excludedExtensions));
		
		/* A�adimos las excepciones de integridad para excluirlas del escaneo inicial */
		if ( $opcion == "permissions" ) {
			foreach($this->skipDirsPermissions as $file) {
				$last_part = explode(DIRECTORY_SEPARATOR,$file);
				$excludedFiles[] = end($last_part);
			}
		} else if ( $opcion == "integrity" ) {
			foreach($this->skipDirsIntegrity as $file) {
				$last_part = explode(DIRECTORY_SEPARATOR,$file);
				$excludedFiles[] = end($last_part);
			}
		}
		
		/* Comprobamos si tenemos que escanear todos los archivos o s�lo los ejecutables */
		if ( $scan_executables_only ) {
			$files_name = JFolder::files($root,'.',true,true,$excludedFiles,$excludedExtensions);			
		} else {
			$files_name = JFolder::files($root,'',true,true);
			// Buscamos si existe el archivo .htaccess en la ruta a escanear (s�lo lo buscamos en la ruta base, no en subdirectorios)
			if ( file_exists($root . DIRECTORY_SEPARATOR . ".htaccess") ) {
				$files_name[] = $root . DIRECTORY_SEPARATOR . ".htaccess";
			}
		}		
	}
	
	/* Reemplazamos los caracteres distintos del usado como DIRECTORY_SEPARATOR. Esto pasa, por ejemplo, en un servidor IIS:  */
	$files_name = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $files_name);
			
	if ( $opcion == "permissions" ) {
		
		$this->files_scanned += count($files_name);
		
		$files = array();
		if ( !empty($files_name) ) {
			foreach($files_name as $file) {
				
				// Transformamos el nombre del archivo a UTF-8
				$file = mb_convert_encoding($file,"UTF-8");
			
				$this->files_processed_permissions++;
				$percent_permissions = intval(round(($this->files_processed_permissions / $this->files_scanned) * 100));
				if ( (($percent_permissions - $this->last_percent_permissions) >= 10) && ($percent_permissions < 100) ) {
					$this->set_campo_filemanager("files_scanned",$percent_permissions);
					$this->last_percent_permissions = $percent_permissions;
				} else if ( $percent_permissions == 100 ) {
					$this->task_completed = true;
				}
				
				/* Dejamos sin efecto el tiempo m�ximo de ejecuci�n del script. Esto es necesario cuando existen miles de archivos a escanear */
				set_time_limit(0);
				$safe = 1;
				// Chequeamos si el archivo est� incluido en las excepciones
					if ( $recursive == 1 ) {  // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
						if ( !is_null($this->skipDirsPermissions) ) {
							$i = 0;
							foreach ($this->skipDirsPermissions as $excep){
								if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
									$safe = (int) 2;
								}
								$i++;
							}
						
						}
					} else {  // Comprobamos que si el archivo est� expl�citamente en la lista de excepciones
						if ( (!is_null($this->skipDirsPermissions)) && (in_array($file,$this->skipDirsPermissions)) ) {
							$safe = (int) 2;
						} else {
							 // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
							if ( !is_null($this->skipDirsPermissions) ) {
								$i = 0;
								foreach ($this->skipDirsPermissions as $excep){									
									if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
										$safe = (int) 2;
									}
									$i++;
								}
							}
						}
					}
					// Si el archivo se encuentra entre las excepciones y la opci�n 'a�adir excepciones a la bbdd' est� activada guardamos el archivo. 
					if ( ( ($safe == 2) && ($include_exceptions)) || ($safe!=2) ) {
						$permissions = $this->file_perms($file);
						// Obtenemos la extensi�n del archivo
						$last_part = explode('.',$file);
						$extension = end($last_part);
						if ( ($permissions > '0644') && ($safe!=2) ) {
							$safe = 0;
							$this->files_with_incorrect_permissions = $this->files_with_incorrect_permissions+1;
						}
						$last_part = explode(DIRECTORY_SEPARATOR,$file);
						$last_part_2 = explode('.',end($last_part));
						$files[] = array(
							'path'      => $file,							
							'kind'    => $lang->_('COM_SECURITYCHECKPRO_FILEMANAGER_FILE'),
							'permissions' => $permissions,
							'last_modified' => date('Y-m-d H:i:s',filemtime($file)),
							'safe' => $safe
						);
					}
			}
		}
		
		if( !empty($files) ) {
			$this->Stack = array_merge($this->Stack, $files);		
		} 
	} else if ( $opcion == "integrity" ) {
		
		
		// Esta variable indica si se ha cambiado el algoritmo con el que se ha calculado el valor hash de los ficheros
		$hash_alg_has_changed = false;
		// Esta variable contendr� el valor hash actual del fichero
		$hash_actual = null;
		// Contendr� si la integridad del archivo es correcta o si el fichero es nuevo. Por defecto es que est� bien, que ser� la opci�n m�s com�n
		$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK'); 
		$new_file = (int) 0; // �Es nuevo el archivo? Por defecto es NO.
		$factor_corrector = (int) 0; // �Es nuevo el archivo? Por defecto es NO.
		// Array que contendr� las rutas de los archivos de escaneos anteriores
		$array_rutas_anterior = array();
		// Array que contendr� los archivos pertenecientes a excepciones
		$array_excepciones_actual = array();
						
		//Inicializamos el porcentaje de ficheros escaneados
		$this->set_campo_filemanager("files_scanned_integrity",0);		
		
		// Cargamos los datos de la BBDD, si existen, de escaneos anteriores.
		if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name) ) {
			$stack = JFile::read($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
			// Eliminamos la parte del fichero que evita su lectura al acceder directamente
			$stack = str_replace("#<?php die('Forbidden.'); ?>",'',$stack);
		}
		
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array($db->quoteName('storage_value')))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
		$db->setQuery($query);
		$stack_resume = $db->loadResult();
		
		if(empty($stack)) {
			$this->Stack_Integrity = array();			
		} else {
			$this->Stack_Integrity = json_decode($stack, true);
			$this->stack_resume = json_decode($stack_resume, true);
			// Actualizamos el valor de los archivos 'inseguros'
			$this->files_with_incorrect_integrity = $this->stack_resume['files_with_incorrect_integrity'];
			// Cargamos los archivos que est�n almacenados en la BBDD
			$this->Stack_Integrity = $this->Stack_Integrity['files_folders'];
		}
		
		// Recorremos los datos que estaban almacenados en la BBDD y los comparamos con los obtenidos en el nuevo escaneo para eliminar los ficheros que ya no existan
		$tamanno_array = count($this->Stack_Integrity);
				
		// Actualizamos la BBDD para mostrar informaci�n del estado del chequeo
		$this->set_campo_filemanager('estado_integrity','CHECKING_DELETED_FILES');
		
		if ( !empty($stack) ) {
			set_time_limit(0);
			$array_rutas_anterior = array_map( function ($element) { return $element['path']; },$this->Stack_Integrity );
			$diff = array_diff($array_rutas_anterior,$files_name);
			$diff = array_keys($diff);
			$this->factor_corrector = 0;
			foreach($diff as $indice) {
				/* Si el archivo estaba marcado como 'inseguro', decrementamos el valor de la variable '$this->files_with_incorrect_integrity', puesto que el elemento ya no existe en el sistema de archivos */
				if ( ($this->Stack_Integrity[$indice - $this->factor_corrector]['safe_integrity'] == 0) && (($this->files_with_incorrect_integrity) > 0) ) {
					$this->files_with_incorrect_integrity--;
				}
				// Eliminamos el elemento del array
				array_splice($this->Stack_Integrity, $indice - $this->factor_corrector, 1);
				// Hemos eliminado un elemento del array, as� que decrementamos el valor de 'tamanno_array' e incrementamos el factor corrector para que no haya errores al referenciar los elementos del array
				$tamanno_array--;
				$this->factor_corrector++;
			}
			$array_rutas_anterior = array_map( function ($element) { return $element['path']; },$this->Stack_Integrity );
		}
				
		// Actualizamos la BBDD para mostrar informaci�n del estado del chequeo
		$this->set_campo_filemanager('estado_integrity','IN_PROGRESS');
				
		// Obtenemos el algoritmo con el que crearemos el valor hash de los ficheros (extraido del fichero de configuraci�n)
		$params = JComponentHelper::getParams('com_securitycheckpro');
		$hash_alg = $params->get('file_integrity_hash_alg','SHA1');
		// Obtenemos el algoritmo con el que se ha calculado el hash de los ficheros almacenados en la BBDD (extraido de la tabla '#__securitycheckpro_file_manager')
		$hash_alg_db = $this->get_campo_filemanager('hash_alg');
		/* Comparamos los dos valores anteriores para ver si se ha cambiado o no estaba establecido el algoritmo con el que se calcula el hash. En ese caso debemos volver a almacenar los valores obtenidos para cada fichero chequeado */
		if ( (is_null($hash_alg_db)) || ($hash_alg != $hash_alg_db) ){
			$hash_alg_has_changed = true;
			$this->set_campo_filemanager('hash_alg',$hash_alg);
		}
				
		$this->files_scanned_integrity += count($files_name);
		
		$files = array();
		
		$array_hashes_actual = array();
		
		if ( !empty($files_name) ) {
			foreach($files_name as $file) {
				
				// Transformamos el nombre del archivo a UTF-8
				$file = mb_convert_encoding($file,"UTF-8");
												
				$this->files_processed++;
				$percent = intval(round(($this->files_processed / $this->files_scanned_integrity) * 100));
				if ( (($percent - $this->last_percent) >= 10) && ($percent < 100) ) {
					$this->set_campo_filemanager("files_scanned_integrity",$percent);
					$this->last_percent = $percent;
				} else if ( $percent == 100 ) {
					$this->task_completed = true;
				}
											
				// Dejamos sin efecto el tiempo m�ximo de ejecuci�n del script. Esto es necesario cuando existen miles de archivos a escanear 
				set_time_limit(0);
				$safe_integrity = 1;
				// Chequeamos si el archivo est� incluido en las excepciones
					if ( $recursive == 1 ) {  // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
						if ( !is_null($this->skipDirsIntegrity) ) {
							$i = 0;
							foreach ($this->skipDirsIntegrity as $excep){
								if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
									$safe_integrity = (int) 2;
								}
								$i++;
							}
						
						}
					} else {  // Comprobamos que si el archivo est� expl�citamente en la lista de excepciones
						if ( (!is_null($this->skipDirsIntegrity)) && (in_array($file,$this->skipDirsIntegrity)) ) {
							$safe_integrity = (int) 2;
						} else {
							 // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
							if ( !is_null($this->skipDirsIntegrity) ) {
								$i = 0;
								foreach ($this->skipDirsIntegrity as $excep){
									if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
										$safe_integrity = (int) 2;
									}
									$i++;
								}
							}
						}
					}
					
					switch ($hash_alg_db) {
						case "SHA1":
							$hash_actual = sha1_file($file);
							break;
						case "MD5":
							$hash_actual = md5_file($file);
							break;
					}
					
					//Si el archivo est� en la lista de excepciones lo a�adimos al array 'array_excepciones_actual'
					if ( $safe_integrity == 2 ) {
						$indice_excepcion = array_search($file,$array_rutas_anterior);
						if ( !($indice_excepcion === FALSE) ) {
							array_push($array_excepciones_actual,$indice_excepcion);
						}
					}
					 
					if ( empty($stack) ) {
						//$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_NEW_FILE');
						$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
						$new_file = (int) 1;
						if ( $safe_integrity != 2 ) {  // El archivo es nuevo y no est� en la lista de excepciones
							// Lo marcamos con integridad correcta porque es el primer escaneo
							$safe_integrity = 1;							
						} else {
							$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
						}
						$last_part = explode(DIRECTORY_SEPARATOR,$file);
						if ( !empty($file) ) {
							$files[] = array(
								'path'      => $file,							
								'hash' => $hash_actual,
								'notes' => $texto_notes,
								'new_file' => $new_file,
								'safe_integrity' => $safe_integrity
							);
						}
					} else {
						$array_hashes_actual[]  = $hash_actual;
					}
						
									
					
			}
			
			$array_hashes_anterior = array_map( function ($element) { return $element['hash']; },$this->Stack_Integrity );
			if ( is_null($array_hashes_anterior) ) {
				$array_hashes_anterior= array();
			}
			
			$diff = array_diff($array_hashes_actual,$array_hashes_anterior);
			$diff = array_keys($diff);
						
			foreach($diff as $indice) {
				$file = $files_name[$indice];
				
				$file = utf8_encode($file);
											
				$safe_integrity = 1;
				// Chequeamos si el archivo est� incluido en las excepciones
				if ( $recursive == 1 ) {  // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
					if ( !is_null($this->skipDirsIntegrity) ) {
						$i = 0;
						foreach ($this->skipDirsIntegrity as $excep){
							if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
								$safe_integrity = (int) 2;
							}
							$i++;
						}
						
					}
				} else {  // Comprobamos que si el archivo est� expl�citamente en la lista de excepciones
					if ( (!is_null($this->skipDirsIntegrity)) && (in_array($file,$this->skipDirsIntegrity)) ) {
						$safe_integrity = (int) 2;
					} else {
						 // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
						if ( !is_null($this->skipDirsIntegrity) ) {
							$i = 0;
							foreach ($this->skipDirsIntegrity as $excep){
								if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
									$safe_integrity = (int) 2;
								}
								$i++;
							}
						}
					}
				}
					
				if ( !in_array($file,$array_rutas_anterior) ) {
					$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_NEW_FILE');
					$new_file = (int) 1;
					if ( $safe_integrity != 2 ) {  // El archivo es nuevo y no est� en la lista de excepciones
						$safe_integrity = 0;						
					} else {
						$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
					}
					
					switch ($hash_alg_db) {
						case "SHA1":
							$hash_actual = sha1_file($file);
							break;
						case "MD5":
							$hash_actual = md5_file($file);
							break;
					}
					$last_part = explode(DIRECTORY_SEPARATOR,$file);
					if ( !empty($file) ) {
						$files = array(
							'path'      => $file,							
							'hash' => $hash_actual,							
							'notes' => $texto_notes,
							'new_file' => $new_file,
							'safe_integrity' => $safe_integrity
						);													
						//array_push($this->Stack_Integrity,$files);
						$this->Stack_Integrity[] = $files;
					}
					
					
				} else {	// El archivo existe pero su valor hash ha cambiado					
					$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_HASH_CHANGED');
					$new_file = (int) 0;
					
					switch ($hash_alg_db) {
						case "SHA1":
							$hash_actual = sha1_file($file);
							break;
						case "MD5":
							$hash_actual = md5_file($file);
							break;
					}
					
					/* Si el valor del hash actual est� vac�o, no modificamos el valor del campo 'safe_integrity' */
					if ( !empty($hash_actual) ) {
						$safe_integrity = (int) 0;
					}
					
					// Buscamos el elemento en el array...
					$indice = array_search($file,$array_rutas_anterior);
										
					// ... y modificamos los valores
					$this->Stack_Integrity[$indice]['size'] = filesize($file);
					$this->Stack_Integrity[$indice]['last_modified'] = date('Y-m-d H:i:s',filemtime($file));
					$this->Stack_Integrity[$indice]['notes'] = $texto_notes;
					$this->Stack_Integrity[$indice]['new_file'] = $new_file;
					
					if ( !empty($hash_actual) ) {
						$this->Stack_Integrity[$indice]['hash'] = $hash_actual;
						$this->Stack_Integrity[$indice]['safe_integrity'] = $safe_integrity;
					}
					
				}
			}	
		} 
		
		if( empty($stack) ) {
			// Actualizamos el n�mero de archivos con integridad incorrecta
			$this->files_with_incorrect_integrity = count(array_filter($files, function ($element) { return ( ( $element['safe_integrity'] == 0 ) );} ));
			
			$this->Stack_Integrity = array_replace_recursive($this->Stack_Integrity, $files);			
		} else {
			/* Eliminamos los elementos que estaban establecidos como excepciones pero ya no. Debemos excluir la ruta donde hacemos los escaneos, puesto que el archivo de un escaneo posterior no figurar� en las excepciones actuales porque se borra y se crea con otro nombre en cada escaneo */
			$array_excepciones_anterior = array_keys(array_filter($this->Stack_Integrity, function ($element) { return ( ( ($element['safe_integrity'] == 2) && (!(strstr($element['path'],'com_securitycheckpro/scans'))) ) );} ));
			$diff = array_diff($array_excepciones_anterior,$array_excepciones_actual);
			foreach($diff as $indice_excepcion_anterior) {
				$this->Stack_Integrity[$indice_excepcion_anterior]['safe_integrity'] = 1;
				$this->Stack_Integrity[$indice_excepcion_anterior]['notes'] = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
			}
			
			// Actualizamos los elementos que pertenecen a la lista de excepciones
			foreach($array_excepciones_actual as $indice_excepcion) {
				$this->Stack_Integrity[$indice_excepcion]['safe_integrity'] = 2;
				$this->Stack_Integrity[$indice_excepcion]['notes'] = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
			}
			
			// Actualizamos el n�mero de archivos con integridad incorrecta
			$this->files_with_incorrect_integrity = count(array_filter($this->Stack_Integrity, function ($element) { return ( ( $element['safe_integrity'] == 0 ) );} ));
			
		}
	} else if ( ($opcion == "malwarescan") || ($opcion == "malwarescan_modified") ) {
	
		// Inicializamos las variables
		$exceptions = $this->skipDirsIntegrity;
		// Establecemos la ruta donde est� la cuarentena
		$quarantine_folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR.'quarantine';
		
		$this->files_scanned_malwarescan += count($files_name);
		
		// Extensiones de ficheros que ser�n analizadas
		// Eliminamos los espacios en blanco
		$this->fileExt = str_replace(' ', '', $this->fileExt);
		$ext = explode(',', $this->fileExt);
		
		// �Qu� excepciones hemos de mirar? Por defecto; las de File integrity. 
		if ( !$this->use_filemanager_exceptions ) {
			$exceptions = $this->skipDirsMalwarescan;
		} 
		
		// Consultamos la antig�edad de los archivos sobre los que buscar patrones sospechosos
		$params = JComponentHelper::getParams('com_securitycheckpro');
		$timeline = $params->get('timeline',7);
		$files = array();
		
		// Establecemos la ruta donde se almacenan los escaneos
		$this->folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
		$malwarescan_name = "";
		$stack = "";
		$filtered_array = array();
		
		// Obtenemos el nombre de los escaneos anteriores
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array($db->quoteName('storage_value')))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
		$db->setQuery($query);
		$stack_malwarescan = $db->loadResult();	
		$stack_malwarescan = json_decode($stack_malwarescan, true);
		
		if(!empty($stack_malwarescan)) {
			$malwarescan_name = $stack_malwarescan['filename'];
		}
		
		if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name) ) {
			$stack = JFile::read($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
			// Eliminamos la parte del fichero que evita su lectura al acceder directamente
			$stack = str_replace("#<?php die('Forbidden.'); ?>",'',$stack);
		}
		$stack = json_decode($stack, true);
		
		if( !empty($stack) ) {
			// Extraemos la info de los ficheros que se han movido a cuarentena para a�adirlos al nuevo fichero
			$filtered_array = array_values(array_filter($stack['files_folders'], function ($element) { return ( ($element['moved_to_quarantine'] == 1) );} ));	
		}
		
		// A�adimos los ficheros almacenados en la carpeta 'quarantine' al array de resultados
		if( !empty($filtered_array) ) {
			$this->Stack = array_merge($this->Stack, $filtered_array);
		}
		
		if ( !empty($files_name) ) {
			foreach($files_name as $file) {
				
				$file = utf8_encode($file);
				
				/* Dejamos sin efecto el tiempo mximo de ejecucin del script. Esto es necesario cuando existen miles de archivos a escanear */
				set_time_limit(0);
				$this->files_processed_malwaresecan++;
				$percent_malwarescan = intval(round(($this->files_processed_malwaresecan / $this->files_scanned_malwarescan) * 100));
				if ( (($percent_malwarescan - $this->last_percent_malwarescan) >= 10) && ($percent_malwarescan < 100) ) {
					$this->set_campo_filemanager("files_scanned_malwarescan",$percent_malwarescan);
					$this->last_percent_malwarescan = $percent_malwarescan;
				} else if ( $percent_malwarescan == 100 ) {
					$this->task_completed = true;
				}
				
				// Inicializamos las variables
				$safe_malwarescan = 1;
				$malware_type = '';
				$malware_description = '';
				$malware_code = '';
				$malware_alert_level = '';
				
				// Chequeamos si el archivo est� incluido en las excepciones
				if ( $recursive == 1 ) {  // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
					if ( !is_null($exceptions) ) {
						$i = 0;
						foreach ($exceptions as $excep){
							if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
								$safe_malwarescan = (int) 2;
							}
							$i++;
						}
						
					}
				} else {  // Comprobamos que si el archivo est� expl�citamente en la lista de excepciones
					if ( (!is_null($exceptions)) && (in_array($file,$exceptions)) ) {
						$safe_malwarescan = (int) 2;
					} else {
						 // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
						if ( !is_null($exceptions) ) {
							$i = 0;
							foreach ($exceptions as $excep){
								if ( strstr($file . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
									$safe_malwarescan = (int) 2;
								}
								$i++;
							}
						}
					}
				}
								
				// D�as desde que el fichero fue modificado
				$days_since_last_mod = intval(abs( (filemtime($file) - time())/86400 ));
				// Si el fichero no est� en la lista de excepciones, comprobamos si contiene malware
				if ( ($safe_malwarescan != 2) && ($days_since_last_mod <= $timeline) ) {
				
					// Buscamos la verdadera extensi�n del fichero (esto es, buscamos archivos tipo .php.xxx o .php.xxx.yyy)
					$explodedName = explode('.', $file);
					array_reverse($explodedName);
										
					if( (count($explodedName) > 3) && (strtolower($explodedName[1]) == 'php') ) {  // Archivo tipo .php.xxx.yyy
						/* Cargamos el lenguaje del sitio */
						$lang = JFactory::getLanguage();
						$lang->load('com_securitycheckpro',JPATH_ADMINISTRATOR);
						
						$safe_malwarescan = 0;
						$malware_type = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_MULTIPLE_EXTENSIONS');
						$malware_description = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . $explodedName[2] . "." . $explodedName[3] ;
						$malware_code =  $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
						$malware_alert_level = 0;
						$this->suspicious_files++;
					} else if ( (count($explodedName) > 2) && (strtolower($explodedName[1]) == 'php') ) {  // Archivo tipo .php.xxx
						/* Cargamos el lenguaje del sitio */
						$lang = JFactory::getLanguage();
						$lang->load('com_securitycheckpro',JPATH_ADMINISTRATOR);
						
						$safe_malwarescan = 0;
						$malware_type = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_MULTIPLE_EXTENSIONS');
						$malware_description = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . $explodedName[2];
						$malware_code =  $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
						$malware_alert_level = 0;
						$this->suspicious_files++;
					} else if (in_array(pathinfo($file, PATHINFO_EXTENSION), $ext) && filesize($file) ) {  // Archivo en la lista de extensiones a analizar
						$resultado = $this->scan_file($file);
						if ( $resultado[0][0] ) {  // Se ha encontrado contenido malicioso!
							$safe_malwarescan = 0;
							$malware_type = $resultado[0][1];
							$malware_description = $resultado[0][2];
							$malware_code = $resultado[0][3];
							$malware_alert_level = $resultado[0][4];
							$this->suspicious_files++;						
						}
					}
								
				}				
				// Si hemos encontrado algo sospechoso o si el fichero est� en la lista de excepciones, guardamos la informaci�n en el fichero
				if ( $safe_malwarescan != 1 ) {
					// Inicializamos las variables
					$quarantined_file_name = '';
					$moved = 0;
					// Indica si el fichero ha de ser movido a la carpeta de cuarentena. Ser� falso cuando ya exista un fichero movido desde una ubicaci�n y se intente mover de nuevo
					$to_move = true;
					
					// Ruta original del fichero; la necesitaremos para restaurarlo
					$original_file = $file;
					
					$move_to_quarantine = $params->get('move_to_quarantine',0);
					// Hemos de mover los archivos cat�logados con un nivel de alerta 'Alto' a la carpeta cuarentena
					if ( ($move_to_quarantine == 1) && ($malware_alert_level == '0') ) {
						// Extraemos el nombre del fichero en la ruta de cuarentena
						$last_part = explode(DIRECTORY_SEPARATOR,$file);
						$quarantined_file_name = $quarantine_folder_path . DIRECTORY_SEPARATOR . end($last_part);
						// Si el archivo existe lo renombramos a�adiendole un '1'
						if ( JFile::exists($quarantined_file_name) ) {
							$value = array_search($file,array_column($filtered_array,'path'));
							if ( is_int($value) ) {
								// La ruta del archivo ya exista en la carpeta 'quarantine'; en este caso no movemos el archivo para evitar sobreescribirlo.
								$to_move = false;
							} else {
								$quarantined_file_name .= $quarantined_file_name + "1";								
							}						
						}
						if ( $to_move ) {
							$moved = JFile::move($file,$quarantined_file_name);						
							// La informaci�n a extraer estar� en el archivo de cuarentena
							$file = $quarantined_file_name;			
							$safe_malwarescan = 3;
						}
					}
					
					$files[] = array(
						'path'      => $original_file,
						'size'      => filesize($file),
						'last_modified' => date('Y-m-d H:i:s',filemtime($file)),
						'malware_type' => $malware_type,
						'malware_description' => $malware_description,
						'malware_code' => $malware_code,
						'malware_alert_level'	=> $malware_alert_level,
						'safe_malwarescan' => $safe_malwarescan,
						'sha1_value' => sha1_file($file),
						'data_id' => '',
						'rest_ip' => '',
						'online_check' => 200,
						'moved_to_quarantine' => $moved,
						'quarantined_file_name'	=>	$quarantined_file_name
					);
				}
					
							
			}
		
		}
				
				if( !empty($files) ) {
			$this->Stack = array_merge($this->Stack, $files);
		}
	}
}

/* Funci�n que obtiene todos los directorios del sitio */
public function getDirectories($root = null, $include_exceptions, $recursive, $opcion)
{
	/* Cargamos el lenguaje del sitio */
	$lang = JFactory::getLanguage();
	$lang->load('com_securitycheckpro',JPATH_ADMINISTRATOR);
	
	if(empty($root)) $root = JPATH_ROOT;
	
	jimport('joomla.filesystem.folder');
	
	$folders_name = JFolder::folders($root,'.',true,true);
	if ( $opcion == "permissions" ) {
		$this->files_scanned += count($folders_name);
		
		//Inicializamos el porcentaje de ficheros escaneados
		$this->set_campo_filemanager("files_scanned",0);
	
		// Actualizamos la BBDD para mostrar informaci�n del estado del chequo
		$this->set_campo_filemanager('estado','IN_PROGRESS');
		
		$folders = array();
		if ( !empty($folders_name) ) {
			foreach($folders_name as $folder) {
				
				$this->files_processed_permissions++;
				$percent_permissions = intval(round(($this->files_processed_permissions / $this->files_scanned) * 100));
				if ( (($percent_permissions - $this->last_percent_permissions) >= 10) && ($percent_permissions < 100) ) {
					$this->set_campo_filemanager("files_scanned",$percent_permissions);
					$this->last_percent_permissions = $percent_permissions;
				} else if ( $percent_permissions == 100 ) {
					$this->task_completed = true;
				}
				
				$safe = 1;
				// Chequeamos si el archivo est� incluido en las excepciones
				if ( $recursive == 1 ) {  // Comprobamos si el archivo pertenece a un directorio que est� incluido en la lista de excepciones
					if ( !is_null($this->skipDirsPermissions) ) {
						$i = 0;
						foreach ($this->skipDirsPermissions as $excep){
							if ( strstr($folder . DIRECTORY_SEPARATOR,$excep . DIRECTORY_SEPARATOR) ) {  // A�adimos una barra invertida a la comparaci�n por si la excepci�n es un directorio
								$safe = (int) 2;
							}
							$i++;
						}
						
					}
				} else {  // Comprobamos que si el archivo est� expl�citamente en la lista de excepciones
					if ( (!is_null($this->skipDirsPermissions)) && (in_array($folder,$this->skipDirsPermissions)) ) {
						$safe = (int) 2;
					}
				}
				// Si el archivo se encuentra entre las excepciones y la opci�n 'a�adir excepciones a la bbdd' est� activada guardamos el archivo. 
				if ( ( ($safe == 2) && ($include_exceptions)) || ($safe!=2) ) {
					$permissions = $this->file_perms($folder);
					if ( ($permissions > '0755') && ($safe!=2) ) {
						$safe = 0;
						$this->files_with_incorrect_permissions = $this->files_with_incorrect_permissions+1;
					}
					$last_part = explode(DIRECTORY_SEPARATOR,$folder);
					$folders[] = array(
						'path'      => $folder,						
						'kind'    => $lang->_('COM_SECURITYCHECKPRO_FILEMANAGER_DIRECTORY'),
						'permissions' => $permissions,
						'last_modified' => date('Y-m-d H:i:s',filemtime($folder)),
						'safe' => $safe
					);
				}
			}
		}
		
		if( !empty($folders) ) {
			$this->Stack = array_merge($this->Stack, $folders);
		}
	} else if ( $opcion == "integrity" ) {
		// No hacemos nada porque a los directorios no se les aplican los valores hash
	}
}

/* Funci�n que guarda en la BBDD, en formato json, el contenido de un array con todos los ficheros y directorios */
private function saveStack($opcion)
{
	// Inicializamos las variables
	$result_permissions = true;
	$result_permissions_resume = true;
	$result_integrity = true;
	$result_integrity_resume = true;
	$result_malwarescan = true;
	$result_malwarescan_resume = true;
	$array_exentos = array('index.html','web.config','.htaccess',$this->filemanager_name,$this->fileintegrity_name,$this->malwarescan_name);
	
	// Creamos el nuevo objeto query
	$db = $this->getDbo();
	
	// Extraemos la informaci�n de los archivos de escaneos online, que no deber�n ser borrados...
	$query = $db->getQuery(true)
		->select('filename')
		->from($db->quoteName('#__securitycheckpro_online_checks'));
	$db->setQuery($query);
	$online_scan_filenames = $db->loadRowList();
	
	// ... y la a�adimos al array de exentos
	foreach ( $online_scan_filenames as $filename ) {
		array_push($array_exentos,$filename[0]);
	}
	
	
	// Buscamos ficheros antiguos que no hayan sido borrados...
	$old_files = JFolder::files($this->folder_path,'.',false,true,$array_exentos);
	
	// ... y los borramos
	foreach($old_files as $old_file) {
		JFile::delete($old_file);		
	}
		
	if ( $opcion == "permissions" ) {		
		// Borramos el fichero del escaneo anterior...
		if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name) ) {
			$delete_permissions_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name);
		}
		
		// ... y escribimos el contenido del array a un nuevo fichero
		$filename = $this->generateKey();
		try {
			$content_permissions = utf8_encode(json_encode(array('files_folders'	=> $this->Stack)));
			$content_permissions = "#<?php die('Forbidden.'); ?>" . PHP_EOL . $content_permissions;
			$result_permissions = JFile::write($this->folder_path.DIRECTORY_SEPARATOR.$filename, $content_permissions);		
			// Nos aseguramos que los permisos de la carpeta 'scans' son los correctos
			chmod($this->folder_path,0755);
			
		} catch (Exception $e) {	
			$this->set_campo_filemanager('estado','DATABASE_ERROR');
			$result_permissions = false;
		}
		
		// Vamos a limpiar las variables que no necesitamos. No uso unset() porque as� no necesitamos esperar al garbage collector
		$content_permissions = null;
		$this->Stack = null;
		
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
		$db->setQuery($query);
		$db->execute();
		
		$object = (object)array(
			'storage_key'	=> 'filemanager_resume',
			'storage_value'	=> utf8_encode(json_encode(array(
				'files_scanned'		=> $this->files_scanned,
				'files_with_incorrect_permissions'	=> $this->files_with_incorrect_permissions,
				'last_check'	=> $this->currentDateTime_func(),
				'filename'		=> $filename
			)))
		);
		
		try {
			$result_permissions_resume = $db->insertObject('#__securitycheckpro_storage', $object);
		} catch (Exception $e) {		
			$this->set_campo_filemanager('estado','DATABASE_ERROR');
			$result_permissions_resume = false;
		}
				
		if ( ($this->task_completed == true) && ($result_permissions) && ($result_permissions_resume) ) {
			$this->set_campo_filemanager('estado','ENDED');
		}
		$this->set_campo_filemanager("files_scanned",100);
		
	} else if ( $opcion == "integrity" ) {
		// Borramos el fichero del escaneo anterior...
		if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name) ) {
			$delete_integrity_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
		}
		
		// ... y escribimos el contenido del array a un nuevo fichero
		$filename = $this->generateKey();
		
		try {
			$content_integrity = utf8_encode(json_encode(array('files_folders'	=> $this->Stack_Integrity)));			
			$content_integrity = "#<?php die('Forbidden.'); ?>" . PHP_EOL . $content_integrity;
			$result_integrity = JFile::write($this->folder_path.DIRECTORY_SEPARATOR.$filename, $content_integrity);	
			// Nos aseguramos que los permisos de la carpeta 'scans' son los correctos
			chmod($this->folder_path,0755);
		} catch (Exception $e) {	
			$this->set_campo_filemanager('estado_integrity','DATABASE_ERROR');
			$result_integrity = 
			false;
		}
		// Vamos a limpiar las variables que no necesitamos. No uso unset() porque as� no necesitamos esperar al garbage collector
		$content_integrity = null;
		$this->Stack_Integrity = null;
		
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
		$db->setQuery($query);
		$db->execute();
		
		$object = (object)array(
			'storage_key'	=> 'fileintegrity_resume',
			'storage_value'	=> utf8_encode(json_encode(array(
				'files_scanned_integrity'		=> $this->files_scanned_integrity,
				'files_with_incorrect_integrity'	=> $this->files_with_incorrect_integrity,
				'last_check_integrity'	=> $this->currentDateTime_func(),
				'filename'		=> $filename
			)))
		);
		
		try {
			$result_integrity_resume = $db->insertObject('#__securitycheckpro_storage', $object);
		} catch (Exception $e) {	
			$this->set_campo_filemanager('estado_integrity','DATABASE_ERROR');
			$result_integrity_resume = false;
		}
		
		if ( ($this->task_completed == true) && ($result_integrity) && ($result_integrity_resume) ) {
			$this->set_campo_filemanager('estado_integrity','ENDED');
		}
		$this->set_campo_filemanager("files_scanned_integrity",100);
		
	} else if ( ($opcion == "malwarescan") || ($opcion == "malwarescan_modified") ) {
		// Borramos el fichero del escaneo anterior...
		if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name) ) {
			$delete_malwarescan_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
		}
		
		// ... y escribimos el contenido del array a un nuevo fichero
		$filename = $this->generateKey();
		try {
			$content_malwarescan = utf8_encode(json_encode(array('files_folders'	=> $this->Stack)));
			$content_malwarescan = "#<?php die('Forbidden.'); ?>" . PHP_EOL . $content_malwarescan;
			$result_malwarescan = JFile::write($this->folder_path.DIRECTORY_SEPARATOR.$filename, $content_malwarescan);
			// Nos aseguramos que los permisos de la carpeta 'scans' son los correctos
			chmod($this->folder_path,0755);
		} catch (Exception $e) {	
			$this->set_campo_filemanager('estado','DATABASE_ERROR');
			$result_malwarescan = false;
		}
		
		// Vamos a limpiar las variables que no necesitamos. No uso unset() porque as� no necesitamos esperar al garbage collector
		$content_malwarescan = null;
		$this->Stack = null;
		
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
		$db->setQuery($query);
		$db->execute();
		
		$object = (object)array(
			'storage_key'	=> 'malwarescan_resume',
			'storage_value'	=> utf8_encode(json_encode(array(
				'files_scanned_malwarescan'		=> $this->files_scanned_malwarescan,
				'suspicious_files'	=> $this->suspicious_files,
				'last_check_malwarescan'	=> $this->currentDateTime_func(),
				'filename'		=> $filename
			)))
		);
		
		try {
			$result_malwarescan_resume = $db->insertObject('#__securitycheckpro_storage', $object);
		} catch (Exception $e) {		
			$this->set_campo_filemanager('estado_malwarescan','DATABASE_ERROR');
			$result_malwarescan_resume = false;
		}
				
		if ( ($this->task_completed == true) && ($result_malwarescan) && ($result_malwarescan_resume) ) {
			$this->set_campo_filemanager('estado_malwarescan','ENDED');
		}
		$this->set_campo_filemanager("files_scanned_malwarescan",100);
				
	}

}

/* Funci�n que obtiene un array con los datos que ser�n mostrados en la opci�n 'file manager' */
function loadStack($opcion,$field)
{
	$db = $this->getDbo();
	$stack = null;
	
	// Establecemos el tama�o m�ximo de memoria que el script puede consumir
	$params = JComponentHelper::getParams('com_securitycheckpro');
	$memory_limit = $params->get('memory_limit','512M');
	if ( preg_match('/^[0-9]*M$/',$memory_limit) ) {
		ini_set('memory_limit',$memory_limit);
	} else {
		ini_set('memory_limit','512M');
		JFactory::getApplication()->enqueueMessage(JText::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'),'error');
	}
			
	switch ($opcion) {
		case "permissions":
			
			// Leemos el contenido del fichero
			$stack = JFile::read($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name);
			
			if(empty($stack)) {
				$this->Stack = array();
				return;
			}
			break;
		case "integrity":
			
			// Leemos el contenido del fichero
			$stack = JFile::read($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
			
			if(empty($stack)) {
				$this->Stack_Integrity = array();
				return;
			}
			break;
		case "filemanager_resume":
			$query = $db->getQuery(true)
				->select(array($db->quoteName('storage_value')))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
			$db->setQuery($query);
			$stack = $db->loadResult();
			
			if(empty($stack)) {
				$this->files_scanned = 0;
				$this->files_with_incorrect_permissions = 0;
				return;
			}
			break;
		case "fileintegrity_resume":
			$query = $db->getQuery(true)
				->select(array($db->quoteName('storage_value')))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
			$db->setQuery($query);
			$stack = $db->loadResult();
						
			if(empty($stack)) {
				$this->files_scanned_integrity = 0;
				$this->files_with_incorrect_integrity = 0;
				return;
			}
			break;
		case "malwarescan_resume":
			$query = $db->getQuery(true)
				->select(array($db->quoteName('storage_value')))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
			$db->setQuery($query);
			$stack = $db->loadResult();
			
			if(empty($stack)) {
				$this->files_scanned_malwarescan = 0;
				$this->suspicious_files = 0;
				return;
			}
			break;
	}
	
	$stack = json_decode($stack, true);
	
	switch ($field) {
		case "file_manager":
			$this->Stack = array_splice($stack['files_folders'], $this->getState('limitstart'), $this->getState('limit'));
			return ($this->Stack);
		case "files_scanned":
			$this->files_scanned = $stack['files_scanned'];
			return ($this->files_scanned);
		case "files_with_incorrect_permissions":
			if(empty($stack)) {
				$this->files_with_incorrect_permissions = 0;
			} else {
				$this->files_with_incorrect_permissions = $stack['files_with_incorrect_permissions'];			
			}	
			return ($this->files_with_incorrect_permissions);
		case "last_check":
			return ($stack['last_check']);
		case "files_scanned_integrity":
			$this->files_scanned_integrity = $stack['files_scanned_integrity'];
			return ($this->files_scanned_integrity);
		case "files_with_bad_integrity":
			if(empty($stack)) {
				$this->files_with_incorrect_integrity = 0;
			} else {
				$this->files_with_incorrect_integrity = $stack['files_with_incorrect_integrity'];			
			}
			return ($this->files_with_incorrect_integrity);
		case "last_check_integrity":
			return ($stack['last_check_integrity']);
		case "last_check_malwarescan":
			return ($stack['last_check_malwarescan']);
		case "files_scanned_malwarescan":
			$this->files_scanned_malwarescan = $stack['files_scanned_malwarescan'];
			return ($this->files_scanned_malwarescan);
		case "suspicious_files":
			if(empty($stack)) {
				$this->suspicious_files = 0;
			} else {
				$this->suspicious_files = $stack['suspicious_files'];			
			}	
			return ($this->suspicious_files);
	}
}

/* Funci�n que escanea el sitio para obtener los permisos o la integridad de los archivos y directorios */
function scan($opcion){

	$include_exceptions = 1;
	$folder_exceptions = 0;
	
	// Obtenemos la ruta sobre la que vamos a hacer el chequeo
	$params = JComponentHelper::getParams('com_securitycheckpro');
	$file_check_path = $params->get('file_manager_path',JPATH_ROOT);
	
	if ( ($file_check_path == "JPATH_ROOT") || ($file_check_path == JPATH_ROOT) ) {
		$file_check_path = JPATH_ROOT;
	} else {
		$file_check_path = JPATH_ROOT . DIRECTORY_SEPARATOR . $file_check_path;
	}
	
	switch ($opcion) {
		case "permissions":
			$this->files_processed_permissions = 0;
			// Obtenemos si debemos guardar las excepciones
			$include_exceptions = $params->get('file_manager_include_exceptions_in_database',1);	
			// �El escaneo ha de ser recursivo?
			$folder_exceptions = $params->get('file_manager_recursive_folder_exceptions',0);
			break;
		case "integrity":
			// Obtenemos si debemos guardar las excepciones
			$include_exceptions = $params->get('file_integrity_include_exceptions_in_database',1);	
			// �El escaneo ha de ser recursivo?
			$folder_exceptions = $params->get('file_integrity_recursive_folder_exceptions',0);
			break;
		case "malwarescan":
			// Obtenemos si debemos guardar las excepciones
			$include_exceptions = $params->get('file_integrity_include_exceptions_in_database',1);	
			// �El escaneo ha de ser recursivo?
			$folder_exceptions = $params->get('file_integrity_recursive_folder_exceptions',0);
			break;
		case "malwarescan_modified":
			// Obtenemos si debemos guardar las excepciones
			$include_exceptions = $params->get('file_integrity_include_exceptions_in_database',1);	
			// El escaneo ha de ser recursivo?
			$folder_exceptions = $params->get('file_integrity_recursive_folder_exceptions',0);
			break;
	}
	
	$this->getDirectories($file_check_path, $include_exceptions, $folder_exceptions, $opcion);
	$this->getFiles($file_check_path, $include_exceptions, $folder_exceptions, $opcion);
	$this->saveStack($opcion);
}

/* Funci�n para establecer el valor de un campo de la tabla '#_securitycheckpro_file_manager' */
function set_campo_filemanager($campo,$valor)
{
	// Creamos el nuevo objeto query
	$db = $this->getDbo();
	$query = $db->getQuery(true);
	
	$campo = filter_var($campo, FILTER_SANITIZE_STRING);
	$valor = filter_var($valor, FILTER_SANITIZE_STRING);
	
	// Sanitizamos las entradas
	$campo_sanitizado = $db->escape($campo);
	$valor_sanitizado = $db->Quote($db->escape($valor));

	// Construimos la consulta...
	$query->update('#__securitycheckpro_file_manager');
	$query->set($campo_sanitizado .'=' .$valor_sanitizado);
	$query->where('id=1');

	// ... y la lanzamos
	$db->setQuery( $query );
	$db->execute();
}

/* Funci�n para obtener el valor de un campo de la tabla '#_securitycheckpro_file_manager' */
function get_campo_filemanager($campo)
{
	// Creamos el nuevo objeto query
	$db = $this->getDbo();
	$query = $db->getQuery(true);
	
	// Sanitizamos las entradas
	$campo_sanitizado = $db->Quote($db->escape($campo));
	
	// Construimos la consulta...
	$query->select($campo);
	$query->from('#__securitycheckpro_file_manager');
	$query->where('id=1');
	
	// ... y la lanzamos
	$db->setQuery( $query );
	$result = $db->loadResult();
	
	// Devolvemos el resultado
	return $result;	
}

/* Obtiene los permisos de un archivo o directorio en formato octal */
function file_perms($file) {
	// Obtenemos el tipo de servidor web
	$mainframe = JFactory::getApplication();
	$server = $mainframe->getUserState("server",'apache');
	
	// Si el servidor es un IIS, devolvemos que los permisos son correctos.
	if ( strstr($server,"iis") ) {
		return "0644";
	}
	return substr( sprintf('%o', fileperms($file)), -4 );

}

/* Funci�n que devuelve la hora y fecha actuales */
public function currentDateTime_func() {
    return (date('Y-m-d H:i:s'));
}


/* Destruye y crea la tabla '#__securitycheckpro_file_permissions' */
function initialize_database(){
	// Creamos el nuevo objeto query
	$db = $this->getDbo();
	
	// Borramos la tabla...
	$query = 'DROP TABLE IF EXISTS #__securitycheckpro_file_permissions';
	$db->setQuery( $query );
	$db->execute();

	// Actualizamos los campos de la tabla '#__securitycheckpro_file_manager'
	$query = 'UPDATE #__securitycheckpro_file_manager SET last_check=null,last_check_integrity=null,last_check_malwarescan=null,files_scanned=0,files_scanned_integrity=0,files_with_incorrect_permissions=0,files_scanned_malwarescan=0,files_with_bad_integrity=0,suspicious_files=0,estado="ENDED",estado_integrity="ENDED",estado_malwarescan="ENDED",cron_tasks_launched=0 where id=1';
	$db->setQuery( $query );
	$db->execute();
	
	// Obtenemos el nombre de los escaneos anteriores...
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
	$db->setQuery($query);
	$stack = $db->loadResult();
	$stack = json_decode($stack, true);
	
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
	$db->setQuery($query);
	$stack_integrity = $db->loadResult();
	$stack_integrity = json_decode($stack_integrity, true);
	
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
	$db->setQuery($query);
	$stack_malwarescan = $db->loadResult();
	$stack_malwarescan = json_decode($stack_malwarescan, true);
	
	if(!empty($stack)) {
		$this->filemanager_name = $stack['filename'];
	}
	
	if(!empty($stack_integrity)) {
		$this->fileintegrity_name = $stack_integrity['filename'];
	}
	
	if(!empty($stack_malwarescan)) {
		$this->malwarescan_name = $stack_malwarescan['filename'];
	}
	
	
	// ... y borramos los ficheros
	$delete_permissions_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name);
	$delete_integrity_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
	$delete_malwarescan_file = JFile::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
	
	// Nos aseguramos que los permisos de la carpeta 'scans' son los correctos
	chmod($this->folder_path,0755);
	
	// Inicializamos la tabla  '#__securitycheckpro_storage'
	$query = $db->getQuery(true)
		->delete($db->quoteName('#__securitycheckpro_storage'))
		->where( '(' .$db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume') .') OR (' .$db->quoteName('storage_key').' = '.$db->quote('filemanager_resume') .') OR (' .$db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume') .')' );
	$db->setQuery($query);
	$db->execute();
}

/* Funci�n para grabar los logs de la propia aplicaci�n*/
function grabar_log_propio($description){

	$db = JFactory::getDBO();
	
	// Sanitizamos la entrada
	$description = filter_var($description, FILTER_SANITIZE_STRING);
	$description = $db->getEscaped($description);
		
	$sql = "INSERT INTO `#__securitycheckpro_own_logs` ( `time`, `description` ) VALUES (now(), '{$description}')";
	$db->setQuery($sql);
	$db->execute();
		
}

/* Obtiene la diferencia en horas entre dos tareas */
function get_timediff($opcion) {
	(int) $interval = 0;
	
	switch ($opcion) {
		case "integrity":
			$last_check_integrity_start_time = new DateTime(date('Y-m-d H:i:s',strtotime($this->get_campo_filemanager('last_check_integrity'))));
			$now = new DateTime($this->currentDateTime_func());
			$interval = date_diff($last_check_integrity_start_time,$now);
			// Extraemos el n�mero total de d�as entre las dos fechas. Si es cero, no ha transcurrido ning�n d�a, por lo que devolvemos la diferencia de horas. Si ha transcurrido un d�a o m�s, devolvemos un valor suficientemente alto para activar los disparadores necesarios
			if ( $interval->format('%a') == 0 ) {
				// Extraemos el n�mero total de horas que han pasado desde el �ltimo chequeo
				$interval = $interval->format('%h');
			} else {
				$interval = 20000;
			}	
		break;
		case "permissions":
			$last_check_start_time = new DateTime(date('Y-m-d H:i:s',strtotime($this->get_campo_filemanager('last_check'))));
			$now = new DateTime($this->currentDateTime_func());
			$interval = date_diff($last_check_start_time,$now);
			// Extraemos el n�mero total de d�as entre las dos fechas. Si es cero, no ha transcurrido ning�n d�a, por lo que devolvemos la diferencia de horas. Si ha transcurrido un d�a o m�s, devolvemos un valor suficientemente alto para activar los disparadores necesarios
			if ( $interval->format('%a') == 0 ) {
				// Extraemos el n�mero total de horas que han pasado desde el �ltimo chequeo
				$interval = $interval->format('%h');
			} else {
				$interval = 20000;
			}	
		break;
		case "malwarescan":
			$last_check_malwarescan_start_time = new DateTime(date('Y-m-d H:i:s',strtotime($this->get_campo_filemanager('last_check_malwarescan'))));
			$now = new DateTime($this->currentDateTime_func());
			$interval = date_diff($last_check_malwarescan_start_time,$now);
			// Extraemos el n�mero total de d�as entre las dos fechas. Si es cero, no ha transcurrido ning�n d�a, por lo que devolvemos la diferencia de horas. Si ha transcurrido un d�a o m�s, devolvemos un valor suficientemente alto para activar los disparadores necesarios
			if ( $interval->format('%a') == 0 ) {
				// Extraemos el n�mero total de horas que han pasado desde el �ltimo chequeo
				$interval = $interval->format('%h');
			} else {
				$interval = 20000;
			}	
		break;
	}
	return $interval;
}

/*Genera un nombre de fichero .php  de 20 caracteres */
function generateKey() {
	
	$chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"; //available characters
	srand( ( double ) microtime() * 1000000 ); //random seed
	$pass = '' ;
		
	for ( $i = 1; $i <= 20; $i++ ) {
		$num = rand() % 33;
		$tmp = substr( $chars, $num, 1 );
		$pass = $pass . $tmp;
	}

	return $pass.'.php';	
}

/**
* Scan given file for all malware patterns
*
* Based on the JAMSS - Joomla! Anti-Malware Scan Script
* @version 1.0.7
*
* @author Bernard Toplak [WarpMax] <bernard@orion-web.hr>
* @link http://www.orion-web.hr
*
* @global string $fileExt file extension list to be scanned
* @global array $patterns array of patterns to search for
* @param string $path path of the scanned file
*/
private function scan_file($path) {

/* Cargamos el lenguaje del sitio */
$lang = JFactory::getLanguage();
$lang->load('com_securitycheckpro',JPATH_ADMINISTRATOR);

// Aadimos los strings sospechosos a la bsqueda de malware?
$params = JComponentHelper::getParams('com_securitycheckpro');
$deep_scan = $params->get('deep_scan',0);
if ( $deep_scan ) {

	// Cargamos los strings que se buscan como malware desde el fichero de strings
	if(@file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_strings.dat')) {
		$Suspicious_Strings = JFile::read(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_strings.dat');			
	} 
} 

// Cargamos los patrones que se buscar�n como malware desde el fichero de patrones
if(@file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_patterns.dat')) {

	// Leemos el contenido del fichero
	$malware_patterns = JFile::read(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_patterns.dat');	

	// Creamos un array bidimensional con el contenido del fichero le�do
	$Suspicious_Patterns  = array_map (
		function ($_) {return explode('�', $_);},
		explode ('�', $malware_patterns)
	);	
}


$jamssFileNames = array(
    $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_OFC_UPLOAD_IMAGE')
        => 'ofc_upload_image.php',
    $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_R57')
        => 'r57.php',
    $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_PHPINFO')
        => 'phpinfo.php',
    );

/* * * * * Patterns End * * * * */

	// Extensiones de ficheros que ser�n analizadas
	// Eliminamos los espacios en blanco
	$this->fileExt = str_replace(' ', '', $this->fileExt);
	$ext = explode(',', $this->fileExt);
	
	// Patrones y strings a buscar
	if ( $deep_scan ) {
		$patterns = array_merge($Suspicious_Patterns, explode('|', $Suspicious_Strings));
	} else {
		$patterns = $Suspicious_Patterns;
	}
		
	// Inicializamos las variables
	$resultado = array(array());
	$resultado[0][0] = false;
	$count = 0;
	$total_results = 0;
	$malware_found = false;
	
	
    if ( $malic_file_descr = array_search(pathinfo($path,PATHINFO_BASENAME), $jamssFileNames) ) {
		$resultado[0][0] = true;
		$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME');
		$resultado[0][2] = $malic_file_descr;
		$resultado[0][3] = '';
		$resultado[0][4] = '0';
		      
    } else {
		$content = file_get_contents($path);
		if ( !$content ) {
            /*$error = 'Could not check '.$path;
            echo formatError($error);*/
        } else { // do a search for fingerprints
			// Look for obfuscated code
			preg_match_all("/\\\x([0-9]{2})/", $content,$found);
			$pattern[1] = "Php obfuscated";
			$pattern[2] = "29";
			$pattern[3] = "Encoded representation of source code, commonly used to hide malware";
			
			$all_results = $found[0]; // remove outer array from results
			$results_count = count($all_results); // count the number of results
			$total_results += $results_count; // total results of all fingerprints				
								
			if ( (!empty($all_results)) && ($results_count>50) ) {					
				// Update the variable to stop looking for more malware patterns
				$malware_found = true;
				// Let's see if this seems a Joomla file, which usually forbids direct access using the JEXEC feature
				$content_without_spaces = $this->clean_espaces($content);
				//if ( (!strstr($content_without_spaces,"defined('_JEXEC')ordie")) && (!strstr($content_without_spaces,"defined('JPATH_BASE')ordie")) ) {
					$count++;
					$resultado[0][0] = true;
					$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_ENCODED_CONTENT');
					$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'),$pattern[2],$pattern[1],$results_count,$pattern[3]);					
					$resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
					$resultado[0][4] = '0';					
				//}				
			}
			
			// Look for obfuscated code using conversions
			if ( !$malware_found ) {
					$info = pathinfo($path);					
					if ( (array_key_exists('extension',$info)) && ($info['extension'] == 'php') ) {
						$length = strlen($content);
						$number_of_spaces = substr_count($content,' ');
						if ( ($number_of_spaces/$length) < 0.001 ) {
							// Update the variable to stop looking for more malware patterns
							$malware_found = true;
							$pattern[1] = "Obfuscated file";
							$pattern[2] = "30";
							$pattern[3] = "Encoded representation of source code, commonly used to hide malware";
							$resultado[0][0] = true;
							$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_ENCODED_CONTENT');
							$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'),$pattern[2],$pattern[1],'Not applicable',$pattern[3]);
							$resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
							$resultado[0][4] = '0';	
						}
					}					
			}
					
			// The file is not obfuscated
			if ( !$malware_found ) {		
				foreach ( $patterns As $pattern ) {
					if ( !$malware_found ) {
						if ( is_array($pattern) ) { // it's a pattern					
							// RegEx modifiers: i=case-insensitive; s=dot matches also newlines; S=optimization
							preg_match_all('/' . $pattern[0] . '/sS', $content, $found, PREG_OFFSET_CAPTURE); 												
						} else { // it's a string
						   preg_match_all('/' . $pattern . '/isS', $content, $found, PREG_OFFSET_CAPTURE);
						}
						
						$all_results = $found[0]; // remove outer array from results
						$results_count = count($all_results); // count the number of results
						$total_results += $results_count; // total results of all fingerprints	
																									
						if ( !empty($all_results) ) {	
							// Update the variable to stop looking for more malware patterns
							$malware_found = true;
							// Let's see if this seems a Joomla file, which usually forbids direct access using the JEXEC feature
							$content_without_spaces = $this->clean_espaces($content);
							// Check the line of the ocurrence; on modified files it's usuallly the first line
							foreach ($all_results as $match) {
								$line = $this->calculate_line_number($match[1], $content);
							}
							
							if ( ((!strstr($content_without_spaces,"defined('_JEXEC')ordie")) && (!strstr($content_without_spaces,"defined('JPATH_BASE')ordie")) && ($line==1)) || ($line==1) ) {
								$count++;
								if ( is_array($pattern) ) { // then it has some additional comments
									$resultado[0][0] = true;
									$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
									$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'),$pattern[2],$pattern[1],$results_count,$pattern[3]);
									$resultado[0][4] = '0';									
								} else { // it's a string, no comments available
									$resultado[0][0] = true;
									$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
									$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO_STRING'),$results_count,$pattern);
									$resultado[0][4] = '1';							
								}
								// A�adimos el c�digo sospechoso encontrado (previamente sanitizado)
								foreach ($all_results as $match) {
									$resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
									$resultado[0][3] .= "<br />";
									$resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
								}
							} else if ( is_array($pattern) ) {
								// Found a malware pattern; it's almost sure a malware even when it's hide into a valid Joomla file.
								$count++;
								$resultado[0][0] = true;
								$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
								$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'),$pattern[2],$pattern[1],$results_count,$pattern[3]);
								$resultado[0][4] = '0';									
								// A�adimos el c�digo sospechoso encontrado (previamente sanitizado)
								foreach ($all_results as $match) {
									$resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
									$resultado[0][3] .= "<br />";
									$resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
								}
							} else if ( !is_array($pattern) ) {
								// Found a malware string; can't be sure this is not a false positive.
								$count++;
								$resultado[0][0] = true;
								$resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
								$resultado[0][2] = JText::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO_STRING'),$results_count,$pattern);
								$resultado[0][4] = '1';	
								
								// A�adimos el c�digo sospechoso encontrado (previamente sanitizado)
								foreach ($all_results as $match) {
									$resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
									$resultado[0][3] .= "<br />";
									$resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
								}
							}
						}
					}
				}
			}			
			unset($content);
        }
	}
	return $resultado;
}

/* Function to clean spaces of a given text */
function clean_espaces($text) {
	$text = str_replace(' ','',$text);
	return $text;
}

/**
*  JAMSS - Joomla! Anti-Malware Scan Script
* @version 1.0.7
*
* @author Bernard Toplak [WarpMax] <bernard@orion-web.hr>
* @link http://www.orion-web.hr
*
* Calculates the line number where pattern match was found
*
* @param int $offset The offset position of found pattern match
* @param str $content The file content in string format
* @return int Returns line number where the subject code was found
*/
function calculate_line_number($offset, $file_content) {
	if ( $file_content >= 1 ) {
		list($first_part) = str_split($file_content, $offset); // fetches all the text before the match
		$line_nr = strlen($first_part) - strlen(str_replace("\n", "", $first_part)) + 1;
		return $line_nr;
	} else {
		return 0;
	}
}

/* Funci�n que obtiene un array con los datos que seran mostrados en la opcion 'filestatus' */
function loadModifiedFiles() {
	
	// Establecemos el tamao mximo de memoria que el script puede consumir
	$params = JComponentHelper::getParams('com_securitycheckpro');
	$memory_limit = $params->get('memory_limit','512M');
	if ( preg_match('/^[0-9]*M$/',$memory_limit) ) {
		ini_set('memory_limit',$memory_limit);
	} else {
		ini_set('memory_limit','512M');
		JFactory::getApplication()->enqueueMessage(JText::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'),'error');
	}
		
	$db = $this->getDbo();
	
	// Consultamos la BBDD para extraer el nombre del fichero de escaneos de integridad.
	$query = $db->getQuery(true)
		->select(array($db->quoteName('storage_value')))
		->from($db->quoteName('#__securitycheckpro_storage'))
		->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
	$db->setQuery($query);
	$stack_integrity = $db->loadResult();
	$stack_integrity = json_decode($stack_integrity, true);
	
	if( (!empty($stack_integrity)) && (isset($stack_integrity['filename'])) ) {
		$this->fileintegrity_name = $stack_integrity['filename'];
	}
	
	if ( JFile::exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name) ) {
		$stack = JFile::read($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
		// Eliminamos la parte del fichero que evita su lectura al acceder directamente
		$stack = str_replace("#<?php die('Forbidden.'); ?>",'',$stack);
	}
			
	if(empty($stack)) {
		$this->Stack_Integrity = array();
		return;
	}
		
	// Decodificamos el array
	$stack = json_decode($stack, true);
	
	
	
	// Extraemos slo los ficheros con integridad modificada
	$this->Stack_Integrity = array_values(array_filter($stack['files_folders'], function ($element) { return ( ( $element['safe_integrity'] == 0 ) );} ));
	// Mapeamos slo los valores del campo 'path'
	$this->Stack_Integrity = array_map( function ($element) { return $element['path']; },$this->Stack_Integrity );
	
	return ($this->Stack_Integrity);
		

}

}

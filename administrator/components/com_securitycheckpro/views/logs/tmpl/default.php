﻿<?php 

/*
* @ author Jose A. Luque
* @ Copyright (c) 2011 - Jose A. Luque
* @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access'); 
$description_array = array(JHtml::_('select.option','TAGS_STRIPPED', JText::_('COM_SECURITYCHECKPRO_TAGS_STRIPPED')),
			JHtml::_('select.option','DUPLICATE_BACKSLASHES', JText::_('COM_SECURITYCHECKPRO_DUPLICATE_BACKSLASHES')),
			JHtml::_('select.option','LINE_COMMENTS', JText::_('COM_SECURITYCHECKPRO_LINE_COMMENTS')),
			JHtml::_('select.option','SQL_PATTERN', JText::_('COM_SECURITYCHECKPRO_SQL_PATTERN')),
			JHtml::_('select.option','IF_STATEMENT', JText::_('COM_SECURITYCHECKPRO_IF_STATEMENT')),
			JHtml::_('select.option','INTEGERS', JText::_('COM_SECURITYCHECKPRO_INTEGERS')),
			JHtml::_('select.option','BACKSLASHES_ADDED', JText::_('COM_SECURITYCHECKPRO_BACKSLASHES_ADDED')),
			JHtml::_('select.option','LFI', JText::_('COM_SECURITYCHECKPRO_LFI')),
			JHtml::_('select.option','IP_BLOCKED', JText::_('COM_SECURITYCHECKPRO_IP_BLOCKED')),
			JHtml::_('select.option','IP_BLOCKED_DINAMIC', JText::_('COM_SECURITYCHECKPRO_IP_BLOCKED_DINAMIC')),
			JHtml::_('select.option','IP_PERMITTED', JText::_('COM_SECURITYCHECKPRO_IP_PERMITTED')),
			JHtml::_('select.option','FORBIDDEN_WORDS', JText::_('COM_SECURITYCHECKPRO_FORBIDDEN_WORDS')),
			JHtml::_('select.option','SESSION_PROTECTION', JText::_('COM_SECURITYCHECKPRO_SESSION_PROTECTION')),
			JHtml::_('select.option','UPLOAD_SCANNER', JText::_('COM_SECURITYCHECKPRO_UPLOAD_SCANNER')),
			JHtml::_('select.option','FAILED_LOGIN_ATTEMPT_LABEL', JText::_('COM_SECURITYCHECKPRO_FAILED_LOGIN_ATTEMPT_LABEL')));
	
$type_array = array(JHtml::_('select.option','XSS', JText::_('COM_SECURITYCHECKPRO_TITLE_XSS')),
			JHtml::_('select.option','XSS_BASE64', JText::_('COM_SECURITYCHECKPRO_TITLE_XSS_BASE64')),
			JHtml::_('select.option','SQL_INJECTION', JText::_('COM_SECURITYCHECKPRO_TITLE_SQL_INJECTION')),
			JHtml::_('select.option','SQL_INJECTION_BASE64', JText::_('COM_SECURITYCHECKPRO_TITLE_SQL_INJECTION_BASE64')),
			JHtml::_('select.option','LFI', JText::_('COM_SECURITYCHECKPRO_TITLE_LFI')),
			JHtml::_('select.option','LFI_BASE64', JText::_('COM_SECURITYCHECKPRO_TITLE_LFI_BASE64')),
			JHtml::_('select.option','IP_PERMITTED', JText::_('COM_SECURITYCHECKPRO_TITLE_IP_PERMITTED')),
			JHtml::_('select.option','IP_BLOCKED', JText::_('COM_SECURITYCHECKPRO_TITLE_IP_BLOCKED')),
			JHtml::_('select.option','IP_BLOCKED_DINAMIC', JText::_('COM_SECURITYCHECKPRO_TITLE_IP_BLOCKED_DINAMIC')),
			JHtml::_('select.option','SECOND_LEVEL', JText::_('COM_SECURITYCHECKPRO_TITLE_SECOND_LEVEL')),
			JHtml::_('select.option','USER_AGENT_MODIFICATION', JText::_('COM_SECURITYCHECKPRO_TITLE_USER_AGENT_MODIFICATION')),
			JHtml::_('select.option','REFERER_MODIFICATION', JText::_('COM_SECURITYCHECKPRO_TITLE_REFERER_MODIFICATION')),
			JHtml::_('select.option','SESSION_PROTECTION', JText::_('COM_SECURITYCHECKPRO_TITLE_SESSION_PROTECTION')),
			JHtml::_('select.option','SESSION_HIJACK_ATTEMPT', JText::_('COM_SECURITYCHECKPRO_TITLE_SESSION_HIJACK_ATTEMPT')),
			JHtml::_('select.option','FORBIDDEN_EXTENSION', JText::_('COM_SECURITYCHECKPRO_TITLE_FORBIDDEN_EXTENSION')),
			JHtml::_('select.option','MULTIPLE_EXTENSIONS', JText::_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION')),
			JHtml::_('select.option','SPAM_PROTECTION', JText::_('COM_SECURITYCHECKPRO_SPAM_PROTECTION')),
			JHtml::_('select.option','URL_INSPECTOR', JText::_('COM_SECURITYCHECKPRO_CPANEL_URL_INSPECTOR_TEXT')));
			
$leido_array = array(JHtml::_('select.option',0, JText::_('COM_SECURITYCHECKPRO_LOG_NOT_READ')),
			JHtml::_('select.option',1, JText::_('COM_SECURITYCHECKPRO_LOG_READ')));

// Load plugin language
$lang2 = JFactory::getLanguage();
$lang2->load('plg_system_securitycheckpro');
			
$vulnerable_array = array(JHtml::_('select.option','Si', JText::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE')),
			JHtml::_('select.option','No', JText::_('COM_SECURITYCHECKPRO_GREEN_COLOR')));

// Cargamos el comportamiento modal para mostrar las ventanas para exportar
JHtml::_('behavior.modal');

// Eliminamos la carga de las librerías mootools
$document = JFactory::getDocument();
$rootPath = JURI::root(true);
$arrHead = $document->getHeadData();
unset($arrHead['scripts'][$rootPath.'/media/system/js/mootools-core.js']);
unset($arrHead['scripts'][$rootPath.'/media/system/js/mootools-more.js']);
$document->setHeadData($arrHead);

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$sweet = "media/com_securitycheckpro/stylesheets/sweetalert.css";
JHTML::stylesheet($sweet);

?>

  <!-- Bootstrap core JavaScript -->
<script src="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/jquery/jquery.min.js"></script>

<?php 
// Cargamos el contenido común
include JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/common.php';
?>

<script src="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/js/sweetalert.min.js"></script>

<?php 
if ( version_compare(JVERSION, '3.20', 'lt') ) {
?>
<!-- Bootstrap core CSS-->
<link href="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
<?php } else { ?>
<link href="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/bootstrap/css/bootstrap_j4.css" rel="stylesheet">
<?php } ?>
<!-- Custom fonts for this template-->
<link href="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/font-awesome/css/fontawesome.css" rel="stylesheet" type="text/css">
<link href="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/font-awesome/css/fa-solid.css" rel="stylesheet" type="text/css">
 <!-- Custom styles for this template-->
<link href="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/css/sb-admin.css" rel="stylesheet">

<form action="<?php echo JRoute::_('index.php?option=com_securitycheckpro&view=logs');?>" style="margin-top: -18px;" method="post" name="adminForm" id="adminForm">

		<?php 
		// Cargamos la navegación
		include JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
		?>

			<?php if (!($this->logs_attacks)){ ?>
			<div class="alert alert-danger text-center" style="margin-bottom: 10px;">
				<h2><?php echo JText::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED'); ?></h2>
				<div id="top"><?php echo JText::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED_TEXT'); ?></div>
			</div>
			<?php } ?>
		
		  <!-- Breadcrumb-->
		  <ol class="breadcrumb">
			<li class="breadcrumb-item">
			  <a href="<?php echo JRoute::_( 'index.php?option=com_securitycheckpro' );?>"><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_DASHBOARD'); ?></a>
			</li>			
			<li class="breadcrumb-item active"><?php echo JText::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS_TEXT'); ?></li>
		  </ol>
			
			<!-- Contenido principal -->			
			<div class="card mb-3">
				<div class="card-body">
				
					<div id="filter-bar" class="btn-toolbar" style="height: auto">
						<div class="filter-search btn-group pull-left" style="margin-bottom: 10px; margin-left: 10px;">
							<input type="text" name="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('JSEARCH_FILTER'); ?>" />
						</div>
						<div class="btn-group pull-left" style="margin-bottom: 10px;">
							<button class="btn tip" type="submit" rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
							<button class="btn tip" type="button" onclick="document.getElementById('filter_search').value=''; this.form.submit();" rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
						</div>
						<div class="filter-search btn-group pull-left hidden-phone" style="margin-left: 10px;">
							<?php echo JHTML::_('calendar', $this->getModel()->getState('datefrom',''), 'datefrom', 'datefrom', '%Y-%m-%d', array('onchange'=>'document.adminForm.submit();', 'class' => 'input-small')); ?>
						</div>
						<div class="filter-search btn-group pull-left hidden-phone" style="margin-left: 10px; margin-bottom: 10px;">
							<?php echo JHTML::_('calendar', $this->getModel()->getState('dateto',''), 'dateto', 'dateto', '%Y-%m-%d', array('onchange'=>'document.adminForm.submit();', 'class' => 'input-small')); ?>
						</div>						
						<div class="btn-group">
							<select name="filter_leido" class="custom-select" style="margin-left: 5px;" onchange="this.form.submit()">
								<option value=""><?php echo JText::_('COM_SECURITYCHECKPRO_MARKED_DESCRIPTION');?></option>
								<?php echo JHtml::_('select.options', $leido_array, 'value', 'text', $this->state->get('filter.leido'));?>
							</select>
							<select name="filter_type" class="custom-select" style="margin-left: 5px;" onchange="this.form.submit()">
								<option value=""><?php echo JText::_('COM_SECURITYCHECKPRO_TYPE_DESCRIPTION');?></option>
								<?php echo JHtml::_('select.options', $type_array, 'value', 'text', $this->state->get('filter.type'));?>
							</select>
							<select name="filter_description" class="custom-select" style="margin-left: 5px;" onchange="this.form.submit()">
								<option value=""><?php echo JText::_('COM_SECURITYCHECKPRO_SELECT_DESCRIPTION');?></option>
								<?php echo JHtml::_('select.options', $description_array, 'value', 'text', $this->state->get('filter.description'));?>
							</select>
						</div>
					</div>
					</div>				
						<div style="width: 100%; overflow-y: auto; _overflow: auto;	margin: 0 0 1em; font-size: 12px;">
							<table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
								<thead>
									<tr>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'Ip', 'ip', $listDirn, $listOrder); ?>				
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_GEOLOCATION_LABEL', 'geolocation', $listDirn, $listOrder); ?>				
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TIME', 'time', $listDirn, $listOrder); ?>				
										</th>
										<th class="logs text-center">
											<?php echo JText::_( 'COM_SECURITYCHECKPRO_USER' ); ?>
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_DESCRIPTION', 'description', $listDirn, $listOrder); ?>			
										</th>
										<th class="logs text-center" style="width: 35%;">
											<?php echo JText::_( 'COM_SECURITYCHECKPRO_LOG_URI' ); ?>
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_TYPE_COMPONENT', 'component', $listDirn, $listOrder); ?>				
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TYPE', 'type', $listDirn, $listOrder); ?>					
										</th>
										<th class="logs text-center">
											<?php echo JHtml::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_READ', 'marked', $listDirn, $listOrder); ?>				
										</th>
										<th class="logs text-center">
											<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
										</th>										
									</tr>
								</thead>
								<?php
								
								if ( !empty($this->items) ) {		
						
									$k = 0;
									foreach ($this->items as &$row) {	
									?>
									<tr>
										<td align="center">
												<?php 
													$ip_sanitized =  htmlentities(filter_var($row->ip, FILTER_SANITIZE_STRING));
												echo $ip_sanitized; ?>	
										</td>
										<td align="center">
												<?php 
													$geolocation_sanitized =  htmlentities(filter_var($row->geolocation, FILTER_SANITIZE_STRING));
												echo $geolocation_sanitized; ?>	
										</td>
										<td align="center">
												<?php echo $row->time; ?>	
										</td>
										<td align="center">
												<?php 
												$username_sanitized =  htmlentities(filter_var($row->username, FILTER_SANITIZE_STRING));
												echo $username_sanitized; ?>	
										</td>
										<td align="center">
												<?php $title = JText::_( 'COM_SECURITYCHECK_ORIGINAL_STRING' ); ?>
												<?php $decoded_string = base64_decode($row->original_string); ?>
												<?php $decoded_string = filter_var($decoded_string, FILTER_SANITIZE_STRING); ?>
												<?php $description_sanitized =  htmlentities(filter_var($row->description, FILTER_SANITIZE_STRING)); ?>
												<?php echo JText::_( 'COM_SECURITYCHECKPRO_' .$row->tag_description ); ?>
												<?php echo JText::_( ':' .$description_sanitized ); ?>
												<?php echo "<br />"; ?>
												<textarea cols="30" rows="1" readonly><?php echo $decoded_string ?></textarea>
										</td>	
										<td align="center; font-size: 0.75em;">
												<?php 
													$uri_sanitized =  htmlentities(filter_var($row->uri, FILTER_SANITIZE_STRING));
												echo substr(($uri_sanitized),0,40); ?>
												
										</td>
										<td align="center">
												<?php $component_sanitized = htmlentities(filter_var($row->component, FILTER_SANITIZE_STRING));
												echo substr(($component_sanitized),0,20);	?>	
										</td>
										<td align="center">
											<?php 
												$type_sanitized =  htmlentities(filter_var($row->type, FILTER_SANITIZE_STRING));
												$type = $type_sanitized;			
												if ( $type == 'XSS' ){
													echo ('<img src="../media/com_securitycheckpro/images/xss.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'XSS_BASE64' ){
													echo ('<img src="../media/com_securitycheckpro/images/xss_base64.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SQL_INJECTION' ){
													echo ('<img src="../media/com_securitycheckpro/images/sql_injection.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SQL_INJECTION_BASE64' ){
													echo ('<img src="../media/com_securitycheckpro/images/sql_injection_base64.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'LFI' ){
													echo ('<img src="../media/com_securitycheckpro/images/local_file_inclusion.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'LFI_BASE64' ){
													echo ('<img src="../media/com_securitycheckpro/images/local_file_inclusion_base64.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'IP_PERMITTED' ){
													echo ('<img src="../media/com_securitycheckpro/images/permitted.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'IP_BLOCKED' ){
													echo ('<img src="../media/com_securitycheckpro/images/blocked.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'IP_BLOCKED_DINAMIC' ){
													echo ('<img src="../media/com_securitycheckpro/images/dinamically_blocked.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SECOND_LEVEL' ){
													echo ('<img src="../media/com_securitycheckpro/images/second_level.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'USER_AGENT_MODIFICATION' ){
													echo ('<img src="../media/com_securitycheckpro/images/http.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'REFERER_MODIFICATION' ){
													echo ('<img src="../media/com_securitycheckpro/images/http.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SESSION_PROTECTION' ){
													echo ('<img src="../media/com_securitycheckpro/images/session_protection.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SESSION_HIJACK_ATTEMPT' ){
													echo ('<img src="../media/com_securitycheckpro/images/session_hijack.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( ($type == 'MULTIPLE_EXTENSIONS') || ($type == 'FORBIDDEN_EXTENSION') ){
													echo ('<img src="../media/com_securitycheckpro/images/upload_scanner.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'SPAM_PROTECTION' ){
													echo ('<img src="../media/com_securitycheckpro/images/spam_protection.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}else if ( $type == 'URL_INSPECTOR' ){
													echo ('<img src="../media/com_securitycheckpro/images/url_inspector.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_TITLE_' .$row->type ) .'">');
												}			
											?>
										</td>
										<td align="center">
											<?php 
												$marked = $row->marked;			
												if ( $marked == 1 ){
													echo ('<img src="../media/com_securitycheckpro/images/read.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_LOG_READ' ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_LOG_READ' ) .'">');
												} else {
													echo ('<img src="../media/com_securitycheckpro/images/no_read.png" title="' . JText::_( 'COM_SECURITYCHECKPRO_LOG_UNREAD' ) .'" alt="' . JText::_( 'COM_SECURITYCHECKPRO_LOG_UNREAD' ) .'">');
												}
											?>
										</td>
										<td align="center">
												<?php echo JHtml::_('grid.id', $k, $row->id); ?>
										</td>
									</tr>
									<?php
									$k = $k+1;
									}
								}
									?>							
							</table>						
						</div>	
						
						<?php
							if ( !empty($this->items) ) {		
						?>
						<div style="margin-left: 10px;">
							<?php echo $this->pagination->getListFooter(); echo $this->pagination->getLimitBox(); ?>							
						</div>							
						<?php }	?>						
						
						<div class="card" style="margin-top: 10px; margin-left: 10px; width: 40rem;">
							<div class="card-body card-header">
								<?php echo JText::_('COM_SECURITYCHECKPRO_COPYRIGHT'); ?><br/>
								<span class="badge badge-success"><?php echo JText::_('COM_SECURITYCHECKPRO_ICONS_ATTRIBUTION'); ?></span>
							</div>								
						</div>
					</div>		  					
				</div>
		</div>
</div>


 <!-- Bootstrap core JavaScript -->
<script src="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/popper/popper.min.js"></script>
<script src="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/vendor/bootstrap/js/bootstrap.min.js"></script>
<!-- Custom scripts for all pages -->
<script src="<?php echo JURI::root(); ?>media/com_securitycheckpro/new/js/sb-admin.js"></script> 

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="securitycheckpro" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>
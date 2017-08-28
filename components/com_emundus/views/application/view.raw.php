<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
require_once (JPATH_COMPONENT.DS.'models'.DS.'profile.php');
require_once (JPATH_COMPONENT.DS.'models'.DS.'files.php');
require_once (JPATH_COMPONENT.DS.'models'.DS.'emails.php');
require_once (JPATH_COMPONENT.DS.'models'.DS.'users.php');
require_once (JPATH_COMPONENT.DS.'models'.DS.'evaluation.php');
require_once (JPATH_COMPONENT.DS.'models'.DS.'admission.php');


class EmundusViewApplication extends JViewLegacy
{
	protected $_user = null;
	var $_db = null;

	protected $synthesis;

	function __construct($config = array()){
		// require_once (JPATH_COMPONENT.DS.'helpers'.DS.'javascript.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'filters.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'list.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'access.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'emails.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'export.php');
		require_once (JPATH_COMPONENT.DS.'helpers'.DS.'menu.php');

		$this->_user = JFactory::getSession()->get('emundusUser');
		$this->_db = JFactory::getDbo();
		parent::__construct($config);
	}
	
	function display($tpl = null) {
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
			die(JText::_('RESTRICTED_ACCESS'));


		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_emundus');

		$jinput = $app->input;
		$fnum 	= $jinput->getString('fnum', null);
        $layout = $jinput->getString('layout', 0);
        $Itemid = $jinput->get('Itemid', 0);
		
		$profiles = new EmundusModelProfile();
		$fnumInfos = $profiles->getFnumDetails($fnum);
		
		$model = $this->getModel('Application');

		$expire=time()+60*60*24*30;
		setcookie("application_itemid", $jinput->getString('id', 0), $expire);

		if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
			switch ($layout) {
				case "synthesis":
					$program = $model->getProgramSynthesis($fnumInfos['campaign_id']);
					$applicant = $model->getApplicantInfos($fnumInfos['applicant_id'], array('jos_emundus_personal_detail.last_name', 'jos_emundus_personal_detail.first_name', 'jos_emundus_personal_detail.gender', 'jos_users.username', 'jos_users.email', 'jos_users.id', '#__emundus_uploads.filename'));
					$campaignInfo = $model->getUserCampaigns($fnumInfos['applicant_id'], $fnumInfos['campaign_id']);
					$modelEmail = new EmundusModelEmails();
					$tag = array(
						'FNUM' => $fnum,
						'CAMPAIGN_NAME' => $fnum,
						'APPLICATION_STATUS' => $fnum,
						'APPLICATION_TAGS' => $fnum,
						'APPLICATION_PROGRESS' => $fnum
					);

					$tags = $modelEmail->setTags(intval($fnumInfos['applicant_id']), $tag);
					$synthesis = new stdClass();
					$synthesis->applicant = $applicant;
					$synthesis->program = $program;
					$synthesis->camp = $campaignInfo;
					@$synthesis->fnumInfos = $fnumInfos;
					$synthesis->fnum = $fnum;
					$synthesis->block = preg_replace($tags['patterns'], $tags['replacements'], $program->synthesis);
					// replace {fabrik_element_ids} in body

					$element_ids = $modelEmail->getFabrikElementIDs($synthesis->block);
					if (count(@$element_ids[0])>0) {
						$element_values = $modelEmail->getFabrikElementValues($fnum, $element_ids[1]);
						$synthesis->block = $modelEmail->setElementValues($synthesis->block, $element_values);
					}

					$this->assignRef('synthesis', $synthesis);
					break;
				
				case 'assoc_files':
					$show_related_files = $params->get('show_related_files', 0);

					if ($show_related_files || EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asManagerAccessLevel($this->_user->id))
						$campaignInfo = $model->getUserCampaigns($fnumInfos['applicant_id']);
					else
						$campaignInfo = $model->getCampaignByFnum($fnum);

					$this->synthesis = new stdClass();
					$this->synthesis->camps = $campaignInfo;
					$this->synthesis->fnumInfos = $fnumInfos;
					$this->synthesis->fnum = $fnum;
					break;
				
				case 'attachment':
					if (EmundusHelperAccess::asAccessAction(4, 'r', $this->_user->id, $fnum)) {
						$expert_document_id = $params->get('expert_document_id', '36');

						$userAttachments = $model->getUserAttachmentsByFnum($fnum);
						$profile = $profiles->getProfileByCampaign($fnumInfos['campaign_id']);
						$attachmentsProgress = $model->getAttachmentsProgress($fnumInfos['applicant_id'], $profile['profile_id'], $fnum);
						$this->assignRef('userAttachments', $userAttachments);
						$this->assignRef('student_id', $fnumInfos['applicant_id']);
						$this->assignRef('attachmentsProgress', $attachmentsProgress);
						$this->assignRef('expert_document_id', $expert_document_id);
					} else {
						echo JText::_("RESTRICTED_ACCESS");
						exit();
					}
					break;
				
				case 'assessment':
					if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
						$student = JFactory::getUser(intval($fnumInfos['applicant_id']));
						$this->assignRef('campaign_id', $fnumInfos['campaign_id']);
						$this->assignRef('student', $student);
					} else {
						echo JText::_("RESTRICTED_ACCESS");
						exit();
					}
					break;
                
				case 'evaluation':
                    if (EmundusHelperAccess::asAccessAction(5, 'r', $this->_user->id, $fnum)) {

						$params = JComponentHelper::getParams('com_emundus');
						$can_copy_evaluations = $params->get('can_copy_evaluations', 0);

                        $this->student = JFactory::getUser(intval($fnumInfos['applicant_id']));
                        $evaluation = new EmundusModelEvaluation();
                        $myEval = $evaluation->getEvaluationsFnumUser($fnum, $this->_user->id);

                        // get evaluation form ID
                        $formid = $evaluation->getEvaluationFormByProgramme($fnumInfos['training']);
                        /*$form_url_view = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&tmpl=component&iframe=1&rowid='.@$myEval[0]->id.'&student_id='.$student->id;
                        $form_url_edit = 'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&tmpl=component&iframe=1&rowid='.@$myEval[0]->id.'&student_id='.$student->id;
                        $this->assignRef('form_url_edit', $form_url_edit);
*/
                        if (!empty($formid)) {
	                        
							if(count($myEval) > 0) {
	                            
								if(EmundusHelperAccess::asAccessAction(5, 'u', $this->_user->id, $fnum))
	                                $this->url_form = JURI::base(true).'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&tmpl=component&iframe=1&rowid='.$myEval[0]->id.'&student_id='.$this->student->id;
	                            elseif (EmundusHelperAccess::asAccessAction(5, 'r', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$this->student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                        
							} else {
	                            
								if (EmundusHelperAccess::asAccessAction(5, 'c', $this->_user->id, $fnum)) {
	                                if(!empty($formid))
	                                    $this->url_form = JURI::base(true).'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&rowid=&jos_emundus_evaluations___student_id[value]='.$this->student->id.'&jos_emundus_evaluations___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_evaluations___fnum[value]='.$fnum.'&student_id='.$this->student->id.'&tmpl=component&iframe=1';
	                            } elseif (EmundusHelperAccess::asAccessAction(5, 'r', $this->_user->id, $fnum)) {
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$this->student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                            }
	                        }
	                        
							if (!empty($formid))
	                            $this->url_evaluation = JURI::base(true).'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid='.$Itemid.'&cfnum='.$fnum;
	                    } else {
                            $this->url_evaluation = '';
                            $this->url_form = '';
                        }

						// This means that a previous evaluation of this user on any other programme can be copied to this one
						if ($can_copy_evaluations == 1) {
							
							if (EmundusHelperAccess::asAccessAction(1, 'u', JFactory::getUser()->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'c', JFactory::getUser()->id, $fnum)) {
							
								$m_evaluation 	= new EmundusModelEvaluation;
								$h_files 		= new EmundusHelperFiles;
								$eval_fnums 	= array();
								$evals 			= array();

								// Gets all evaluations of this student
								$user_evaluations = $m_evaluation->getEvaluationsByStudent($this->student->id);

								foreach ($user_evaluations as $ue) {
									$eval_fnums[] = $ue->fnum;
								}

								// Evaluation fnums need to be made unique as it is possible to have multiple evals on one fnum by different people
								$eval_fnums = array_unique($eval_fnums);
								
								// Gets a title for the dropdown menu that is sorted like ['fnum']->['evaluator_id']->title
								foreach ($eval_fnums as $eval_fnum) {
									$evals[] = $h_files->getEvaluation('simple',$eval_fnum);
								}

								$this->assignRef('evaluation_select', $evals);
							
							} else {
								echo JText::_("RESTRICTED_ACCESS");
                        		exit();
							}
						}

                        $this->campaign_id = $fnumInfos['campaign_id'];
                        //$this->assignRef('student', $student);
                        $this->assignRef('fnum', $fnum);
                        //$this->assignRef('url_evaluation', $url_evaluation);
                        //$this->assignRef('url_form', $url_form);
                    } else {
                        echo JText::_("RESTRICTED_ACCESS");
                        exit();
                    }
                    break;
                
				case 'decision':
                    if(EmundusHelperAccess::asAccessAction(29, 'r', $this->_user->id, $fnum)) {
                        $student = JFactory::getUser(intval($fnumInfos['applicant_id']));
                        $evaluation = new EmundusModelEvaluation();
                        $myEval = $evaluation->getDecisionFnum($fnum);

                        // get evaluation form ID
                        $formid = $evaluation->getDecisionFormByProgramme($fnumInfos['training']);

                        $url_form = '';
 						if (!empty($formid)) {
	                        if (count($myEval) > 0) {

	                            if(EmundusHelperAccess::asAccessAction(29, 'u', $this->_user->id, $fnum))       
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1'; 
	                            elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                        
							} else {

	                            if(EmundusHelperAccess::asAccessAction(29, 'c', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&rowid=&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                            elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->_user->id, $fnum)) 
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                        
							}
	                    }

                        $this->assignRef('campaign_id', $fnumInfos['campaign_id']);
                        $this->assignRef('student', $student);
                        $this->assignRef('fnum', $fnum);
                        $this->assignRef('url_form', $url_form);
                        $this->assignRef('$formid', $formid);
                    
					} else {
                        echo JText::_("RESTRICTED_ACCESS");
                        exit();
                    }
                    break;

				case 'comment':
					if (EmundusHelperAccess::asAccessAction(10, 'r', $this->_user->id, $fnum)) {
					
						$userComments = $model->getFileComments($fnum);
						$this->assignRef('userComments', $userComments);
						$this->assignRef('fnum', $fnum);
					
					} else {
						echo JText::_("RESTRICTED_ACCESS");
						exit();
					}
					break;
				case 'tag':
					if (EmundusHelperAccess::asAccessAction(14, 'r', $this->_user->id, $fnum)) {

						$files = new EmundusModelFiles();
						$tags = $files->getTagsByFnum(array($fnum));
						$this->assignRef('tags', $tags);
						$this->assignRef('fnum', $fnum);
					
					} else {
						echo JText::_("RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'form':
						if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
							$pid = (isset($fnumInfos['profile_id_form']) && !empty($fnumInfos['profile_id_form']))?$fnumInfos['profile_id_form']:$fnumInfos['profile_id'];

							$formsProgress = $model->getFormsProgress($fnumInfos['applicant_id'], $pid, $fnum);
							$this->assignRef('formsProgress', $formsProgress);
							
							$forms = $model->getforms(intval($fnumInfos['applicant_id']), $fnum, $pid);
							$this->assignRef('forms', $forms);

						} else {
							echo JText::_("RESTRICTED_ACCESS");
							exit();
						}
					break;

				case 'share':
					if (EmundusHelperAccess::asAccessAction(11, 'r', $this->_user->id, $fnum)) {

						$access = $model->getAccessFnum($fnum);
						$defaultActions = $model->getActions();
						$canUpdateAccess = EmundusHelperAccess::asAccessAction(11, 'u', JFactory::getUser()->id, $fnum);
						$this->assignRef('access', $access);
						$this->assignRef('canUpdate', $canUpdateAccess);
						$this->assignRef('defaultActions', $defaultActions);
					
					} else {
						echo JText::_("RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'admission':
                    if (EmundusHelperAccess::asAccessAction(32, 'r', $this->_user->id, $fnum)) {
						$student = JFactory::getUser(intval($fnumInfos['applicant_id']));
                        
						$m_admission 	= new EmundusModelAdmission();
                        $m_application 	= new EmundusModelApplication();
						$m_files 		= new EmundusModelFiles();

						$myEval 		= $m_admission->getAdmissionFnum($fnum);
						$myAdmission 	= $m_files->getAdmissionFormidByFnum($fnum);

                        // get admission form ID
                        $formid = $m_admission->getAdmissionFormByProgramme($fnumInfos['training']);
						if (empty($myEval))
							$html_form = "<p>User has no admission information</p>";
						else
							$html_form = $m_application->getFormByFabrikFormID($myAdmission, $student->id, $fnum);

                        $url_form = '';
 						if (!empty($formid)) {
	                        if (count($myEval) > 0) {
	                            
								if (EmundusHelperAccess::asAccessAction(32, 'u', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                            elseif (EmundusHelperAccess::asAccessAction(32, 'r', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                        
							} else {

	                            if (EmundusHelperAccess::asAccessAction(32, 'c', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=form&formid='.$formid.'&rowid=&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                            elseif (EmundusHelperAccess::asAccessAction(32, 'r', $this->_user->id, $fnum))
	                                $url_form = 'index.php?option=com_fabrik&c=form&view=details&formid='.$formid.'&rowid='.$myEval[0]->id.'&jos_emundus_final_grade___student_id[value]='.$student->id.'&jos_emundus_final_grade___campaign_id[value]='.$fnumInfos['campaign_id'].'&jos_emundus_final_grade___fnum[value]='.$fnum.'&student_id='.$student->id.'&tmpl=component&iframe=1';
	                        
							}
	                    }

                        $this->assignRef('campaign_id', $fnumInfos['campaign_id']);
                        $this->assignRef('student', $student);
                        $this->assignRef('fnum', $fnum);
						$this->assignRef('html_form',$html_form);
                        $this->assignRef('url_form', $url_form);
                        $this->assignRef('$formid', $formid);
                    
					} else {
                        echo JText::_("RESTRICTED_ACCESS");
                        exit();
                    }
                    break;
				
			}

			$this->assignRef('_user', $this->_user);
			$this->assignRef('fnum', $fnum);
			$this->assignRef('sid', $fnumInfos['applicant_id']);
			
			parent::display($tpl);
		
		} else echo JText::_("RESTRICTED_ACCESS");
	}
}
<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access
/*
if (version_compare(PHP_VERSION, '5.3.0') >= 0) {

    use PhpOffice\PhpWord\IOFactory;
    use PhpOffice\PhpWord\PhpWord;
    use PhpOffice\PhpWord\TemplateProcessor;
}
*/
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * eMundus Component Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 */
//error_reporting(E_ALL);
/**
 * Class EmundusControllerFiles
 */
class EmundusControllerFiles extends JControllerLegacy
{
    /**
     * @var JUser|null
     */
    var $_user = null;
    /**
     * @var JDatabase|null
     */
    var $_db = null;

    /**
     * @param array $config
     */
    public function __construct($config = array())
    {
        //require_once (JPATH_COMPONENT.DS.'helpers'.DS.'javascript.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'files.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'filters.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'list.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'access.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'emails.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'export.php');
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'menu.php');


        $this->_user = JFactory::getSession()->get('emundusUser');
        $this->_db = JFactory::getDBO();

        parent::__construct($config);
    }

    /**
     * @param bool $cachable
     * @param bool $urlparams
     */
    public function display($cachable = false, $urlparams = false)
    {
        // Set a default view if none exists
        if ( ! JRequest::getCmd( 'view' ) )
        {
            $default = 'files';
            JRequest::setVar('view', $default );
        }
        parent::display();
    }

////// EMAIL APPLICANT WITH CUSTOM MESSAGE///////////////////
    /**
     *
     */
    public function applicantemail()
    {
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'emails.php');
        $h_emails = new EmundusHelperEmails;
        $h_emails->sendApplicantEmail();
    }

    /**
     *
     */
    public function groupmail()
    {
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'emails.php');
        $h_emails = new EmundusHelperEmails;
        $h_emails->sendGroupEmail();
    }

    /**
     *
     */
    public function clear()
    {
        $h_files = new EmundusHelperFiles;
        $h_files->clear();
        echo json_encode((object)(array('status' => true)));
        exit;
    }

    /**
     *
     */
     public function setfilters() {
        $jinput     = JFactory::getApplication()->input;
        $filterName = $jinput->getString('id', null);
        $elements   = $jinput->getString('elements', null);
        $multi      = $jinput->getString('multi', null);

        $h_files = new EmundusHelperFiles;
        $h_files->clearfilter();

        if ($multi == "true")
            $filterval = $jinput->get('val', array(), 'ARRAY');
        else
            $filterval = $jinput->getString('val', null);

        $session    = JFactory::getSession();
        $params     = $session->get('filt_params');

        if ($elements == 'false') {
            $params[$filterName] = $filterval;
        } else {
            $vals = (array)json_decode(stripslashes($filterval));
            if (count($vals) > 0) {
                foreach ($vals as $val) {
                    if ($val->adv_fil)
                        $params['elements'][$val->name] = $val->value;
                    else
                        $params[$val->name] = $val->value;
                }

            } else $params['elements'][$filterName] = $filterval;
        }

        $session->set('filt_params', $params);
        $session->set('limitstart', 0);
        echo json_encode((object)(array('status' => true)));
        exit();
    }

    /**
     * @throws Exception
     */
    public function loadfilters() {
        try {

            $jinput = JFactory::getApplication()->input;
            $id = $jinput->getInt('id', null);

            $session = JFactory::getSession();

            $h_files = new EmundusHelperFiles;
            $filter = $h_files->getEmundusFilters($id);
            $params = (array) json_decode($filter->constraints);
            $params['select_filter'] = $id;
            $params =  json_decode($filter->constraints, true);

            $session->set('select_filter', $id);

            if (isset($params['filter_order'])) {
                $session->set('filter_order', $params['filter_order']);
                $session->set('filter_order_Dir', $params['filter_order_Dir']);
            }

            $session->set('filt_params', $params['filter']);

            if (!empty($params['col']))
                $session->set('adv_cols', $params['col']);

            echo json_encode((object)(array('status' => true)));
            exit();

        } catch(Exception $e) {
            throw new Exception;
        }
    }

    /**
     *
     */
    public function order() {
        $jinput = JFactory::getApplication()->input;
        $order = $jinput->getString('filter_order', null);

        $session = JFactory::getSession();
        $ancientOrder = $session->get('filter_order');
        $params = $session->get('filt_params');
        $session->set('filter_order', $order);

        $params['filter_order'] = $order;

        if ($order == $ancientOrder) {

            if ($session->get('filter_order_Dir') == 'desc') {

                $session->set('filter_order_Dir', 'asc');
                $params['filter_order_Dir'] = 'asc';

            } else {

                $session->set('filter_order_Dir', 'desc');
                $params['filter_order_Dir'] = 'desc';

            }

        } else {

            $session->set('filter_order_Dir', 'asc');
            $params['filter_order_Dir'] = 'asc';

        }

        $session->set('filt_params', $params);
        echo json_encode((object)(array('status' => true)));
        exit;
    }

    /**
     *
     */
    public function setlimit() {
        $jinput = JFactory::getApplication()->input;
        $limit = $jinput->getInt('limit', null);

        $session = JFactory::getSession();
        $session->set('limit', $limit);
        $session->set('limitstart', 0);

        echo json_encode((object)(array('status' => true)));
        exit;
    }

    /**
     *
     */
    public function savefilters() {
        $name = JRequest::getVar('name', null, 'POST', 'none',0);
        $current_user = JFactory::getUser();
        $user_id = $current_user->id;
        $itemid = JRequest::getVar('Itemid', null, 'GET', 'none',0);

        $session = JFactory::getSession();
        $filt_params = $session->get('filt_params');
        $adv_params = $session->get('adv_cols');
        $constraints = array('filter'=>$filt_params, 'col'=>$adv_params);

        $constraints = json_encode($constraints);

        if (empty($itemid))
            $itemid = JRequest::getVar('Itemid', null, 'POST', 'none',0);

        $time_date = (date('Y-m-d H:i:s'));

        $query = "INSERT INTO #__emundus_filters (time_date,user,name,constraints,item_id) values('".$time_date."',".$user_id.",'".$name."',".$this->_db->quote($constraints).",".$itemid.")";
        $this->_db->setQuery( $query );

        try
        {
            $this->_db->Query();
            $query = 'select f.id, f.name from #__emundus_filters as f where f.time_date = "'.$time_date.'" and user = '.$user_id.' and name="'.$name.'" and item_id="'.$itemid.'"';
            $this->_db->setQuery($query);
            $result = $this->_db->loadObject();
            echo json_encode((object)(array('status' => true, 'filter' => $result)));
            exit;

        }
        catch (Exception $e)
        {
            echo json_encode((object)(array('status' => false)));
            exit;
        }
    }

    /**
     *
     */
    public function deletefilters()
    {
        $jinput = JFactory::getApplication()->input;
        $filter_id = $jinput->getInt('id', null);

        $query="DELETE FROM #__emundus_filters WHERE id=".$filter_id;
        $this->_db->setQuery( $query );
        $result=$this->_db->Query();

        if($result!=1)
        {
            echo json_encode((object)(array('status' => false)));
            exit;
        }
        else
        {
            echo json_encode((object)(array('status' => true)));
            exit;
        }
    }

    /**
     *
     */
    public function setlimitstart()
    {
        $jinput = JFactory::getApplication()->input;
        $limistart = $jinput->getInt('limitstart', null);
        $session = JFactory::getSession();
        $limit = intval($session->get('limit'));
        $limitstart = ($limit != 0 ? ($limistart > 1 ? (($limistart - 1) * $limit) : 0) : 0);
        $session->set('limitstart', $limitstart);

        echo json_encode((object)(array('status' => true)));
        exit;
    }

    /**
     * @throws Exception
     */
    public function getadvfilters()
    {
        try
        {
            $elements = @EmundusHelperFiles::getElements();

            echo json_encode((object)(array('status' => true, 'default' => JText::_('PLEASE_SELECT'), 'defaulttrash' => JText::_('REMOVE_SEARCH_ELEMENT'), 'options' => $elements)));

            exit;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function getbox()
    {
        try
        {
            $jinput = JFactory::getApplication()->input;
            $id     = $jinput->getInt('id', null);
            $index  = $jinput->getInt('index', null);

            $session = JFactory::getSession();
            $params = $session->get('filt_params');

            $h_files = new EmundusHelperFiles;
            $element = $h_files->getElementsName($id);

            $tab_name = (isset($element[$id]->table_join)?$element[$id]->table_join:$element[$id]->tab_name);
            $key = $tab_name . '.' . $element[$id]->element_name;
            $params['elements'][$key] = '';

            $advCols = $session->get('adv_cols');

            if (!$session->has('adv_cols') || count($advCols) == 0)
            {
                $advCols = array($index => $id);
            }
            else
            {
                $advCols = $session->get('adv_cols');
                if (isset($advCols[$index])) {
                    $lastId = @$advCols[$index];
                    if (!in_array($id, $advCols))
                    {
                        $advCols[$index] = $id;
                    }
                    if (array_key_exists($index, $advCols))
                    {
                        $lastElt = $h_files->getElementsName($lastId);
                        $tab_name = (isset($lastElt[$lastId]->table_join)?$lastElt[$lastId]->table_join:$lastElt[$lastId]->tab_name);
                        unset($params['elements'][$tab_name . '.' . $lastElt[$lastId]->element_name]);
                    }
                }
                else
                    $advCols[$index] = $id;
            }
            $session->set('filt_params', $params);
            $session->set('adv_cols', $advCols);

            $html= $h_files->setSearchBox($element[$id], '', $tab_name . '.' . $element[$id]->element_name, $index);

            echo json_encode((object)(array('status' => true, 'default' => JText::_('PLEASE_SELECT'), 'defaulttrash' => JText::_('REMOVE_SEARCH_ELEMENT'), 'html' => $html)));
            exit;
        }
        catch(Exception $e)
        {
            throw $e;
        }
    }

    /**
     *
     */
    public function deladvfilter()
    {
        $jinput = JFactory::getApplication()->input;
        $name   = $jinput->getString('elem', null);
        $id     = $jinput->getInt('id',null);

        $session    = JFactory::getSession();
        $params     = $session->get('filt_params');
        $advCols    = $session->get('adv_cols');
        unset($params['elements'][$name]);
        unset($advCols[$id]);
        $session->set('filt_params', $params);
        $session->set('adv_cols', $advCols);

        echo json_encode((object)(array('status' => true)));
        exit;
    }

    /**
     *
     */
    public function addcomment()
    {
        $jinput = JFactory::getApplication()->input;
        $user   = JFactory::getUser()->id;
        $fnums  = $jinput->getString('fnums', null);
        $title  = $jinput->getString('title', '');
        $comment = $jinput->getString('comment', null);

        $fnums = (array) json_decode(stripslashes($fnums));
        $m_application = $this->getModel('Application');

        foreach($fnums as $fnum)
        {
            if(EmundusHelperAccess::asAccessAction(10, 'c', $user, $fnum))
            {
                $aid = intval(substr($fnum, 21, 7));
                $res = $m_application->addComment((array('applicant_id' => $aid, 'user_id' => $user, 'reason' => $title, 'comment_body' => $comment, 'fnum' => $fnum)));
                if($res == 0)
                {
                    echo json_encode((object)(array('status' => false, 'msg' => JText::_('ERROR'). $res)));
                    exit;
                }
            }
        }

        echo json_encode((object)(array('status' => true, 'msg' => JText::_('COMMENT_SUCCESS'), 'id' => $res)));
        exit;
    }

    /**
     *
     */
    public function gettags()
    {
        $m_files = $this->getModel('Files');
        $tags = $m_files->getAllTags();

        echo json_encode((object)(array('status' => true,
                                        'tags' => $tags,
                                        'tag' => JText::_('TAGS'),
                                        'select_tag' => JText::_('PLEASE_SELECT_TAG'))));
        exit;
    }

    /**
     * Add a tag to an application
     */
    public function tagfile()
    {
        $jinput = JFactory::getApplication()->input;
        $fnums  = $jinput->getString('fnums', null);
        $tag    = $jinput->getInt('tag', null);

        $fnums = ($fnums=='all')?'all':(array) json_decode(stripslashes($fnums));

        $m_files = $this->getModel('Files');

        if ($fnums == "all")
            $fnums = $m_files->getAllFnums();

        $validFnums = array();

        foreach ($fnums as $fnum)
        {
            if(EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $fnum))
            {
                $validFnums[] = $fnum;
            }
        }
        unset($fnums);

        $res    = $m_files->tagFile($validFnums, $tag);
        $tagged = $m_files->getTaggedFile($tag);

        echo json_encode((object)(array('status' => true, 'msg' => JText::_('TAG_SUCCESS'), 'tagged' => $tagged)));
        exit;
    }

    /**
     *
     */
    public function share()
    {
        $jinput     = JFactory::getApplication()->input;
        $fnums      = $jinput->getString('fnums', null);
        $actions    = $jinput->getString('actions', null);
        $groups     = $jinput->getString('groups', null);
        $evals      = $jinput->getString('evals', null);

        $actions    = (array) json_decode(stripslashes($actions));
        $fnums      = (array) json_decode(stripslashes($fnums));

        $m_files = $this->getModel('Files');

        $validFnums = array();
        foreach($fnums as $fnum)
        {
            if(EmundusHelperAccess::asAccessAction(11, 'c', $this->_user->id, $fnum))
            {
                if ($fnum != 'em-check-all') {
                    $validFnums[] = $fnum;
                }
            }
        }

        unset($fnums);
        if (count($validFnums) > 0)
        {
            if (!empty($groups))
            {
                $groups = (array) json_decode(stripslashes($groups));
                $res = $m_files->shareGroups($groups, $actions, $validFnums);
            }

            if (!empty($evals))
            {
                $evals = (array) json_decode(stripslashes($evals));
                $res = $m_files->shareUsers($evals, $actions, $validFnums);
            }

            if ($res !== false)
            {
                $msg = JText::_('SHARE_SUCCESS');
            }
            else
            {
                $msg = JText::_('SHARE_ERROR');
            }
        }
        else
        {
            $fnums = $m_files->getAllFnums();
            if ($groups !== null)
            {
                $groups = (array) json_decode(stripslashes($groups));
                $res = $m_files->shareGroups($groups, $actions, $fnums);
            }

            if ($evals !== null)
            {
                $evals = (array) json_decode(stripslashes($evals));
                $res = $m_files->shareUsers($evals, $actions, $fnums);
            }

            if ($res !== false)
            {
                $msg = JText::_('SHARE_SUCCESS');
            }
            else
            {
                $msg = JText::_('SHARE_ERROR');

            }
        }
        echo json_encode((object)(array('status' => $res, 'msg' => $msg)));
        exit;
    }

    /**
     *
     */
    public function getstate()
    {
        $m_files = $this->getModel('Files');
        $states = $m_files->getAllStatus();

        echo json_encode((object)(array('status' => true,
                                        'states' => $states,
                                        'state' => JText::_('STATE'),
                                        'select_state' => JText::_('PLEASE_SELECT_STATE'))));
        exit;
    }

    /**
     *
     */
    public function getpublish()
    {
        $publish = array (
            0 =>
                array (
                    'id' =>  '1',
                    'step' =>  '1' ,
                    'value' => JText::_('PUBLISHED') ,
                    'ordering' =>  '1'
                ),
            1 =>
                array (
                    'id' =>  '0',
                    'step' =>  '0' ,
                    'value' => JText::_('ARCHIVED') ,
                    'ordering' =>  '2'
                ),
            3 =>
                array (
                    'id' =>  '3',
                    'step' =>  '-1' ,
                    'value' => JText::_('TRASHED') ,
                    'ordering' =>  '3'
                )
        );

        echo json_encode((object)(array('status' => true,
                                        'states' => $publish,
                                        'state' => JText::_('PUBLISH'),
                                        'select_publish' => JText::_('PLEASE_SELECT_PUBLISH'))));
        exit;
    }

    /**
     *
     */
    public function updatestate() {
        $app    = JFactory::getApplication();
        $jinput = $app->input;
        $fnums  = $jinput->getString('fnums', null);
        $state  = $jinput->getInt('state', null);

        $email_from_sys = $app->getCfg('mailfrom');
        $fnums = (array) json_decode(stripslashes($fnums));

        $m_files = $this->getModel('Files');
        $validFnums = array();

        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum))
                $validFnums[] = $fnum;
        }

        $fnumsInfos = $m_files->getFnumsInfos($validFnums);
        $res        = $m_files->updateState($validFnums, $state);
        $msg = '';

        if ($res !== false) {
            $m_application = $this->getModel('application');
            $status = $m_files->getStatus();
            // Get all codes from fnum
            $code = array();
            foreach ($fnumsInfos as $fnum) {
                $code[] = $fnum['training'];
                $row = array('applicant_id' => $fnum['applicant_id'],
                             'user_id' => $this->_user->id,
                             'reason' => JText::_('STATUS'),
                             'comment_body' => $fnum['value'].' ('.$fnum['step'].') '.JText::_('TO').' '.$status[$state]['value'].' ('.$state.')',
                             'fnum' => $fnum['fnum']
                );
                $m_application->addComment($row);
            }
            //*********************************************************************
            // Get triggered email
            include_once(JPATH_BASE.'/components/com_emundus/models/emails.php');
            $m_email = new EmundusModelEmails;
            $trigger_emails = $m_email->getEmailTrigger($state, $code, 1);

            if (count($trigger_emails) > 0) {
                foreach ($trigger_emails as $key => $trigger_email) {
                    // Manage with default recipient by programme
                    foreach ($trigger_email as $code => $trigger) {
                        if ($trigger['to']['to_applicant'] == 1) {
                            // Manage with selected fnum
                            foreach ($fnumsInfos as $file) {
                                $mailer = JFactory::getMailer();

                                $post = array('FNUM' => $file['fnum']);
                                $tags = $m_email->setTags($file['applicant_id'], $post);

                                $from       = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['emailfrom']);
                                $from_id    = 62;
                                $fromname   = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['name']);
                                $to         = $file['email'];
                                $subject    = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['subject']);
                                $body       = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['message']);
                                $body       = $m_email->setTagsFabrik($body, array($file['fnum']));

                                // If the email sender has the same domain as the system sender address.
                                if (!empty($from) && substr(strrchr($from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1))
                                    $mail_from_address = $from;
                                else
                                    $mail_from_address = $email_from_sys;

                                // Set sender
                                $sender = [
                                    $mail_from_address,
                                    $fromname
                                ];

                                $mailer->setSender($sender);
                                $mailer->addReplyTo($from, $fromname);
                                $mailer->addRecipient($to);
                                $mailer->setSubject($subject);
                                $mailer->isHTML(true);
                                $mailer->Encoding = 'base64';
                                $mailer->setBody($body);

                                $send = $mailer->Send();
                                if ($send !== true) {
                                    $msg .= '<div class="alert alert-dismissable alert-danger">'.JText::_('EMAIL_NOT_SENT').' : '.$to.' '.$send->__toString().'</div>';
                                    JLog::add($send->__toString(), JLog::ERROR, 'com_emundus.email');
                                } else {
                                    $message = array(
                                        'user_id_from' => $from_id,
                                        'user_id_to' => $file['applicant_id'],
                                        'subject' => $subject,
                                        'message' => '<i>'.JText::_('MESSAGE').' '.JText::_('SENT').' '.JText::_('TO').' '.$to.'</i><br>'.$body
                                    );
                                    $m_email->logEmail($message);
                                    $msg .= JText::_('EMAIL_SENT').' : '.$to.'<br>';
                                    JLog::add($to.' '.$body, JLog::INFO, 'com_emundus.email');
                                }
                            }
                        }
                        foreach ($trigger['to']['recipients'] as $key => $recipient) {
                            $mailer = JFactory::getMailer();

                            $post = array();
                            $tags = $m_email->setTags($recipient['id'], $post);

                            $from       = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['emailfrom']);
                            $from_id    = 62;
                            $fromname   = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['name']);
                            $to         = $recipient['email'];
                            $subject    = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['subject']);
                            $body       = preg_replace($tags['patterns'], $tags['replacements'], $trigger['tmpl']['message']);
                            $body       = $m_email->setTagsFabrik($body, $validFnums);

                            // If the email sender has the same domain as the system sender address.
                            if (!empty($from) && substr(strrchr($from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1))
                                $mail_from_address = $from;
                            else
                                $mail_from_address = $email_from_sys;

                            // Set sender
                            $sender = [
                                $mail_from_address,
                                $fromname
                            ];

                            $mailer->setSender($sender);
                            $mailer->addReplyTo($from, $fromname);
                            $mailer->addRecipient($to);
                            $mailer->setSubject($subject);
                            $mailer->isHTML(true);
                            $mailer->Encoding = 'base64';
                            $mailer->setBody($body);

                            $send = $mailer->Send();
                            if ($send !== true) {
                                $msg .= '<div class="alert alert-dismissable alert-danger">'.JText::_('EMAIL_NOT_SENT').' : '.$to.' '.$send->__toString().'</div>';
                                JLog::add($send->__toString(), JLog::ERROR, 'com_emundus.email');
                                //die();
                            } else {
                                $message = array(
                                    'user_id_from' => $from_id,
                                    'user_id_to' => $recipient['id'],
                                    'subject' => $subject,
                                    'message' => '<i>'.JText::_('MESSAGE').' '.JText::_('SENT').' '.JText::_('TO').' '.$to.'</i><br>'.$body
                                );
                                $m_email->logEmail($message);
                                $msg .= JText::_('EMAIL_SENT').' : '.$to.'<br>';
                                JLog::add($to.' '.$body, JLog::INFO, 'com_emundus.email');
                            }
                        }
                    }
                }
            }
            //
            //***************************************************

            $msg .= JText::_('STATE_SUCCESS');
        } else $msg .= JText::_('STATE_ERROR');

        echo json_encode((object)(array('status' => $res, 'msg' => $msg)));
        exit;
    }

    public function updatepublish() {
        $jinput     = JFactory::getApplication()->input;
        $fnums      = $jinput->getString('fnums', null);
        $publish    = $jinput->getInt('publish', null);

        $fnums = (array) json_decode(stripslashes($fnums));

        $m_files = $this->getModel('Files');

        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        $validFnums = array();

        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum))
                $validFnums[] = $fnum;
        }
        $res = $m_files->updatePublish($validFnums, $publish);

        if ($res !== false) {
            // Get all codes from fnum
            $fnumsInfos = $m_files->getFnumsInfos($validFnums);
            $code = array();
            foreach ($fnumsInfos as $fnum) {
                $code[] = $fnum['training'];
            }
            $msg = JText::_('STATE_SUCCESS');
        } else $msg = JText::_('STATE_ERROR');

        echo json_encode((object)(array('status' => $res, 'msg' => $msg)));
        exit;
    }

    /**
     *
     */
    public function unlinkevaluators() {
        $jinput = JFactory::getApplication()->input;
        $fnum   = $jinput->getString('fnum', null);
        $id     = $jinput->getint('id', null);
        $group  = $jinput->getString('group', null);

        $m_files = $this->getModel('Files');

        if ($group == "true")
            $res = $m_files->unlinkEvaluators($fnum, $id, true);
        else
            $res = $m_files->unlinkEvaluators($fnum, $id, false);

        if ($res)
            $msg = JText::_('SUCCESS_SUPPR_EVAL');
        else
            $msg = JText::_('ERROR_SUPPR_EVAL');

        echo json_encode((object)(array('status' => $res, 'msg' => $msg)));
        exit;
    }

    /**
     *
     */
    public function getfnuminfos() {
        $jinput = JFactory::getApplication()->input;
        $fnum = $jinput->getString('fnum', null);
        $res = false;
        $fnumInfos = null;

        if ($fnum != null) {
            $m_files = $this->getModel('Files');
            $fnumInfos = $m_files->getFnumInfos($fnum);
            if ($fnum !== false)
                $res = true;
        }

        JFactory::getSession()->set('application_fnum', $fnum);
        echo json_encode((object)(array('status' => $res, 'fnumInfos' => $fnumInfos)));
        exit;
    }

    /**
     *
     */
    public function deletefile()
    {
        $jinput = JFactory::getApplication()->input;
        $fnum = $jinput->getString('fnum', null);
        $m_files = $this->getModel('Files');
        if (EmundusHelperAccess::asAccessAction(1, 'd', $this->_user->id, $fnum))
            $res = $m_files->changePublished($fnum);
        else
            $res = false;

        $result = array('status' => $res);

        echo json_encode((object)$result);
        exit;
    }

    /**
     *
     */
    public function getformelem() {
        //Filters
        $m_files = $this->getModel('Files');
        $h_files = new EmundusHelperFiles;

        $defaultElements    = $m_files->getDefaultElements();
        $elements           = $h_files->getElements();

        $res = array('status' => true, 'elts' => $elements, 'defaults' => $defaultElements);
        echo json_encode((object)$res);
        exit;
    }

    /**
     *
     */
    public function send_elements()
    {
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'access.php');
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die (JText::_('RESTRICTED_ACCESS') );

        $jinput = JFactory::getApplication()->input;
        $fnums  = $jinput->getVar('fnums', null);
        $fnums  = (array) json_decode(stripcslashes($fnums));
        $m_files  = $this->getModel('Files');

        if (!is_array($fnums) || count($fnums)==0 || $fnums===null || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        $validFnums = array();
        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction(6, 'c', $this->_user->id, $fnum) && $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
                $validFnums[] = $fnum;
        }
        $elts = $jinput->getString('elts', null);
//$elts = '{"0":"224","1":"1738","2":"1974","3":"2533","4":"2535","5":"2573","6":"2577","7":"2581","8":"2617","9":"2587","10":"2546","11":"2547","12":"2549","13":"2590","14":"2594","15":"2567","16":"2621"}';
        $elts = (array) json_decode(stripcslashes($elts));

        $objs = $jinput->getString('objs', null);
        $objs = (array) json_decode(stripcslashes($objs));

        $methode = $jinput->getString('methode', 0);

        // export Excel
        $name = $this->export_xls($validFnums, $objs, $elts, $methode);

        $result = array('status' => true, 'name' => $name);
        echo json_encode((object) $result);
        exit();
    }

    /**
     *
     */
    public function zip() {
        require_once (JPATH_COMPONENT.DS.'helpers'.DS.'access.php');

        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die( JText::_('RESTRICTED_ACCESS') );

        $jinput = JFactory::getApplication()->input;
        $fnums  = $jinput->getVar('fnums', null);
        $forms      = $jinput->getInt('forms', 0);
        $attachment = $jinput->getInt('attachment', 0);
        $assessment = $jinput->getInt('assessment', 0);
        $decision   = $jinput->getInt('decision', 0);
        $admission  = $jinput->getInt('admission', 0);
        $formids    = $jinput->getVar('formids', null);
        $attachids  = $jinput->getVar('attachids', null);
        $options    = $jinput->getVar('options', null);


        $fnums  = (array) json_decode(stripslashes($fnums));
        $m_files  = $this->getModel('Files');
        
        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        $validFnums = array();
        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction(6, 'c', $this->_user->id, $fnum))
                $validFnums[] = $fnum;
        }

        
        if (extension_loaded('zip'))
            $name = $this->export_zip($validFnums, $forms, $attachment, $assessment, $decision, $admission, $formids, $attachids, $options);
        else
            $name = $this->export_zip_pcl($validFnums);

        echo json_encode((object) array('status' => true, 'name' => $name));
        exit();
    }

    /**
     * @param $val
     * @return int|string
     */
    public function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);

        switch( $last) {
            // Le modifieur 'G' est disponible depuis PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * @param $array
     * @param $orderArray
     * @return array
     */
    public function sortArrayByArray($array,$orderArray) {
        $ordered = array();

        foreach ($orderArray as $key) {
            if (array_key_exists($key,$array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }

        return $ordered + $array;
    }

    /**
     * @param $object
     * @param $orderArray
     * @return array
     */
    public function sortObjectByArray($object, $orderArray) {
        $properties = get_object_vars($object);
        return sortArrayByArray($properties,$orderArray);
    }

    /**
     * Create temp CSV file for XLS extraction
     * @return String json
     */
    public function create_file_csv() {
        $today  = date("MdYHis");
        $name   = md5($today.rand(0,10));
        $name   = $name.'.csv';
        $chemin = JPATH_BASE.DS.'tmp'.DS.$name;

        if (!$fichier_csv = fopen($chemin, 'w+')) {
            $result = array('status' => false, 'msg' => JText::_('ERROR_CANNOT_OPEN_FILE').' : '.$chemin);
            echo json_encode((object) $result);
            exit();
        }

        fprintf($fichier_csv, chr(0xEF).chr(0xBB).chr(0xBF));
        if (!fclose($fichier_csv)) {
            $result = array('status' => false, 'msg'=>JText::_('ERROR_CANNOT_CLOSE_CSV_FILE'));
            echo json_encode((object) $result);
            exit();
        }

        $result = array('status' => true, 'file' => $name);
        echo json_encode((object) $result);
        exit();
    }

    /**
     * Create temp PDF file for PDF extraction
     * @return String json
     */
    public function create_file_pdf() {
        $today  = date("MdYHis");
        $name   = md5($today.rand(0,10));
        $name   = $name.'-applications.pdf';

        $result = array('status' => true, 'file' => $name);
        echo json_encode((object) $result);
        exit();
    }

    public function getfnums_csv() {
        $jinput = JFactory::getApplication()->input;
        $fnums = $jinput->getVar('fnums', null);
        $fnums = (array) json_decode(stripslashes($fnums));
        $ids = $jinput->getVar('ids', null);
        $ids = (array) json_decode(stripslashes($ids));
        

        $m_files = $this->getModel('Files');

        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();
        
        $validFnums = array();
        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)&& $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
                $validFnums[] = $fnum;
        }
        $totalfile = count($validFnums);
        
        $session = JFactory::getSession();
        $session->set('fnums_export', $validFnums);

        $result = array('status' => true, 'totalfile'=> $totalfile, 'ids'=> $ids);
        echo json_encode((object) $result);
        exit();
    }

    public function getfnums() {
        $jinput = JFactory::getApplication()->input;
        $fnums  = $jinput->getVar('fnums', null);
        $fnums  = (array) json_decode(stripslashes($fnums));
        $ids    = $jinput->getVar('ids', null);

        $action_id  = $jinput->getVar('action_id', null);
        $crud       = $jinput->getVar('crud', null);

        $m_files = $this->getModel('Files');

        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        $validFnums = array();
        foreach ($fnums as $fnum) {
            if (EmundusHelperAccess::asAccessAction($action_id, $crud, $this->_user->id, $fnum)&& $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
                $validFnums[] = $fnum;
        }
        $totalfile = count($validFnums);

        $session = JFactory::getSession();
        $session->set('fnums_export', $validFnums);

        $result = array('status' => true, 'totalfile'=> $totalfile, 'ids'=> $ids);
        echo json_encode((object) $result);
        exit();
    }

    public function getcolumn($elts) {
        return(array) json_decode(stripcslashes($elts));
    }

    public function getcolumnSup($objs) {

       /* $menu = @JSite::getMenu();
        $current_menu  = $menu->getActive();
        $menu_params = $menu->getParams($current_menu->id);
        $columnSupl = explode(',', $menu_params->get('em_actions'));*/
        $objs = (array) json_decode(stripcslashes($objs));
        //$columnSupl = array_merge($columnSupl, $objs);
        return $objs;
    }

    /**
     * Add lines to temp CSV file
     * @return String json
     */
    public function generate_array() {
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die(JText::_('RESTRICTED_ACCESS'));

        $m_files        = $this->getModel('Files');
        $m_application  = $this->getModel('Application');
        $m_profile      = $this->getModel('Profile');
        $session = JFactory::getSession();

        $fnums = $session->get('fnums_export');
        if (count($fnums) == 0)
            $fnums = array($session->get('application_fnum'));

        $jinput     = JFactory::getApplication()->input;
        $file       = $jinput->getVar('file', null, 'STRING');
        $totalfile  = $jinput->getVar('totalfile', null);
        $start      = $jinput->getInt('start', 0);
        $limit      = $jinput->getInt('limit', 0);
        $nbcol      = $jinput->getVar('nbcol', 0);
        $elts       = $jinput->getString('elts', null);
        $objs       = $jinput->getString('objs', null);
        $opts       = $jinput->getString('opts', null);
        $methode    = $jinput->getString('methode', null);

       
        $opts = $this->getcolumn($opts);
       
        $col    = $this->getcolumn($elts);
        $colsup = $this->getcolumnSup($objs);
        $colOpt = array();

        if (!$csv = fopen(JPATH_BASE.DS.'tmp'.DS.$file, 'a')) {
            $result = array('status' => false, 'msg' => JText::_('ERROR_CANNOT_OPEN_FILE').' : '.$file);
            echo json_encode((object) $result);
            exit();
        }

        $h_files = new EmundusHelperFiles;
        $elements = $h_files->getElementsName(implode(',',$col));
       
        // re-order elements
        $ordered_elements = array();
        foreach ($col as $c) {
            $ordered_elements[$c] = $elements[$c];
        }
        
        $fnumsArray = $m_files->getFnumArray($fnums, $ordered_elements, $methode, $start, $limit, 0);
        //var_dump($fnumsArray);
        // On met a jour la liste des fnums traités
        $fnums = array();
        foreach ($fnumsArray as $fnum) {
            array_push($fnums, $fnum['fnum']);
        }
        //var_dump($ordered_elements);
        foreach ($colsup as $col) {
            $col = explode('.', $col);

            switch ($col[0]) {
                case "photo":
                   //$colOpt['PHOTO'] = @EmundusHelperFiles::getPhotos($m_files, JURI::base(true));
                    $photos = $m_files->getPhotos($fnums);
                    if (count($photos) > 0) {
                        $pictures = array();
                        foreach ($photos as $photo) {

                            $folder = $baseUrl.EMUNDUS_PATH_REL.$photo['user_id'];
                           
                            $link = '=HYPERLINK("'.JURI::base(). $folder.'/tn_'.$photo['filename'] . '","'.$photo['filename'].'")';
                            $pictures[$photo['fnum']] = $link;
                            //$pictures[$photo['fnum']] = '<a href="'.$folder.'/'.$photo['filename'].'" target="_blank"><img class="img-responsive" src="'.$folder . '/tn_'. $photo['filename'] . '" width="60" /></a>';

                        }
                        $colOpt['PHOTO'] = $pictures;
                    } else {
                        $colOpt['PHOTO'] = array();
                    }
                    break;
                case "forms":
                    foreach ($fnums as $fnum) {
                        $fnumInfos = $m_profile->getFnumDetails($fnum);
                        $pid = (isset($fnumInfos['profile_id_form']) && !empty($fnumInfos['profile_id_form']))?$fnumInfos['profile_id_form']:$fnumInfos['profile_id'];
                        $aid = $fnumInfos['applicant_id'];
                        $formsProgress[$fnum] = $m_application->getFormsProgress($aid, $pid, $fnum);
                    }
                    $colOpt['forms'] = $formsProgress;
                    break;
                case "attachment":
                    foreach ($fnums as $fnum) {
                        $fnumInfos = $m_profile->getFnumDetails($fnum);
                        $pid = (isset($fnumInfos['profile_id_form']) && !empty($fnumInfos['profile_id_form']))?$fnumInfos['profile_id_form']:$fnumInfos['profile_id'];
                        $aid = $fnumInfos['applicant_id'];
                        $attachmentProgress[$fnum] = $m_application->getAttachmentsProgress($aid, $pid, $fnum);
                    }
                    $colOpt['attachment'] = $attachmentProgress;
                    break;
                case "assessment":
                    $colOpt['assessment'] = $h_files->getEvaluation('text', $fnums);
                    break;
                case "comment":
                    $colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
                    break;
                case 'evaluators':
                    $colOpt['evaluators'] = $h_files->createEvaluatorList($col[1], $m_files);
                    break;
                case 'tags':
                    $colOpt['tags'] = $m_files->getTagsByFnum($fnums);
                    break;
            }
        }
        $status = $m_files->getStatusByFnums($fnums);
        $line = "";
        $element_csv = array();
        $i = $start;


        // On traite les en-têtes
        if ($start == 0) {
            $line = JText::_('F_NUM')."\t".JText::_('STATUS')."\t".JText::_('LAST_NAME')."\t".JText::_('FIRST_NAME')."\t".JText::_('EMAIL')."\t".JText::_('PROGRAMME')."\t";
            $nbcol = 6;
            foreach ($ordered_elements as $fKey => $fLine) {
                if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_label != 'Programme') {
                    if(count($opts) > 0 && $fLine->element_name != "date_time" && $fLine->element_name != "date_submitted"){
                        if(in_array("form-title", $opts) && in_array("form-group", $opts)){
                            $line .= JText::_($fLine->form_label)." > ".JText::_($fLine->group_label)." > ".JText::_($fLine->element_label). "\t";
                            $nbcol++;
                        }elseif(count($opts) == 1){
                            if(in_array("form-title", $opts)){
                                $line .= JText::_($fLine->form_label)." > ".JText::_($fLine->element_label). "\t";
                                $nbcol++;
                            }elseif(in_array("form-group", $opts)){
                                $line .= JText::_($fLine->group_label)." > ".JText::_($fLine->element_label). "\t";
                                $nbcol++;
                            }
                        }
                    }else{
                        $line .= JText::_($fLine->element_label). "\t";
                        $nbcol++;
                    }
                }
            }
            //var_dump($line);die;
            foreach ($colsup as $kOpt => $vOpt) {
                if ($vOpt=="forms" || $vOpt=="attachment")
                    $line .= $vOpt . "(%)\t";
                else
                    $line .= $vOpt . "\t";
                $nbcol++;
            }

            // On met les en-têtes dans le CSV
            $element_csv[] = $line;
            $line = "";
            
        }
        //var_dump($fnumsArray);die;
        // On parcours les fnums

        
        foreach ($fnumsArray as $fnum) {
            // On traite les données du fnum
            foreach ($fnum as $k => $v) {
                if ($k != 'code' && $k != 'campaign_id' && $k != 'jos_emundus_campaign_candidature___campaign_id' && $k != 'c___campaign_id') {

                    if ($k === 'fnum') {
                        $line .= $v."\t";
                        $line .= $status[$v]['value']."\t";
                        $uid = intval(substr($v, 21, 7));
                        $userProfil = JUserHelper::getProfile($uid)->emundus_profile;
                        $lastname = (!empty($userProfil['lastname']))?$userProfil['lastname']:JFactory::getUser($uid)->name;
                        $line .= $lastname."\t";
                        $line .= $userProfil['firstname']."\t";
                    } else{
                        if($v == "")
                            $line .= " "."\t";
                        elseif($v[0] == "=")
                            $line .= " ".$v."\t";
                        else
                            $line .= JText::_($v)."\t";
                    } 

                }
            }

            // On ajoute les données supplémentaires
            foreach ($colOpt as $kOpt => $vOpt) {
                switch ($kOpt) {
                    case "PHOTO":
                        //$line .= JText::_('photo') . "\t";
                        if (array_key_exists($fnum['fnum'],$vOpt)) {
                            $val = $vOpt[$fnum['fnum']];
                            $line .= $val . "\t";
                        } else {
                            $line .= "\t";
                        }
                        break;
                    case "forms":
                        if (array_key_exists($fnum['fnum'],$vOpt)) {
                            $val = $vOpt[$fnum['fnum']];
                            $line .= $val . "\t";
                        } else {
                            $line .= "\t";
                        }
                        break;
                    case "attachment":
                        if (array_key_exists($fnum['fnum'],$vOpt)) {
                            $val = $vOpt[$fnum['fnum']];
                            $line .= $val . "\t";
                        } else {
                                $line .= "\t";
                        }
                        break;
                    case "assessment":
                        $eval = '';
                        if (array_key_exists($fnum['fnum'],$vOpt)) {
                            $evaluations = $vOpt[$fnum['fnum']];
                            foreach ($evaluations as $evaluation) {
                                $eval .= $evaluation;
                                $eval .= chr(10) . '______' . chr(10);
                            }
                            $line .= $eval . "\t";
                        } else {
                                $line .= "\t";
                        }
                        break;
                    case "comment":
                        $comments = "";
                        if (array_key_exists($fnum['fnum'],$vOpt)) {
                            foreach ($colOpt['comment'] as $comment) {
                                if ($comment['fnum'] == $fnum['fnum']) {
                                    $comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
                                }
                            }
                            $line .= $comments . "\t";
                        } else {
                            $line .= "\t";
                        }
                        break;
                    case 'evaluators':
                        if (array_key_exists($fnum['fnum'],$vOpt))
                            $line .= $vOpt[$fnum['fnum']] . "\t";
                        else
                            $line .= "\t";
                        break;

                    case "tags":
                        $tags = "";

                        foreach ($colOpt['tags'] as $tag) {
                            if ($tag['fnum'] == $fnum['fnum']) {
                                $tags .= $tag['label'] . ", ";
                            }
                        }
                        $line .= $tags . "\t";

                        break;
                }
            }
            // On met les données du fnum dans le CSV
            $element_csv[] = $line;
            $line = "";
            $i++;

        }
        
        // On remplit le fichier CSV
        foreach ($element_csv as $data) {
            $res = fputcsv($csv, explode("\t",$data),"\t");
            if (!$res) {
                $result = array('status' => false, 'msg'=>JText::_('ERROR_CANNOT_WRITE_TO_FILE'.' : '.$csv));
                echo json_encode((object) $result);
                exit();
            }
        }
        if (!fclose($csv)) {
            $result = array('status' => false, 'msg'=>JText::_('ERROR_CANNOT_CLOSE_CSV_FILE'));
            echo json_encode((object) $result);
            exit();
        }

        $start = $i;

        $dataresult = array('start' => $start, 'limit'=>$limit, 'totalfile'=> $totalfile,'methode'=>$methode,'elts'=>$elts, 'objs'=> $objs, 'nbcol' => $nbcol,'file'=>$file );
        $result = array('status' => true, 'json' => $dataresult);
        echo json_encode((object) $result);
        exit();
    }

    public function getformslist(){
        require_once(JPATH_COMPONENT.DS.'models'.DS.'profile.php');
        require_once(JPATH_COMPONENT.DS.'models'.DS.'campaign.php');
        $jinput     = JFactory::getApplication()->input;
        $code    = $jinput->get('code', null);
        $year    = $jinput->get('year', null);
        
        $code = explode(',', $code);
        $year = explode(',', $year);
        $profile = EmundusModelProfile::getProfileIDByCourse($code, $year);
        $pages = EmundusHelperMenu::buildMenuQuery((int)$profile[0]);

        if($year[0] != 0)
            $campaign = EmundusModelCampaign::getCampaignsByCourseYear($code[0], $year[0]);
        else
            $campaign = EmundusModelCampaign::getCampaignsByCourse($code[0]);
        
       
        $html1 = '';
        $html2 = '';
        //var_dump(count($pages));
        for ($i = 0; $i < count($pages); $i++) {
            if($i < count($pages)/2){
                $html1 .= '<input class="em-ex-check" type="checkbox" value="'.$pages[$i]->form_id.'" name="'.$pages[$i]->label.'" id="'.$pages[$i]->form_id.'" /><label for="'.$pages[$i]->form_id.'">'.JText::_($pages[$i]->label).'</label><br/>';
            }else{
                $html2 .= '<input class="em-ex-check" type="checkbox" value="'.$pages[$i]->form_id.'" name="'.$pages[$i]->label.'" id="'.$pages[$i]->form_id.'" /><label for="'.$pages[$i]->form_id.'">'.JText::_($pages[$i]->label).'</label><br/>';
            }
        }
        
        $html = '<div class="panel panel-default pdform">
                    <div class="panel-heading">
                        <button type="button" class="btn btn-info btn-xs" title="'.JText::_('COM_EMUNDUS_SHOW_ELEMENTS').'" style="float:left;" onclick="showelts(this, '."'felts-".$code[0].$year[0]."'".')">
                        <span class="glyphicon glyphicon-plus"></span>
                        </button>&ensp;&ensp;
                        <b>'.$campaign['label'].' - '.$campaign['training'].'('.$campaign['year'].')</b>
                    </div>
                    <div class="panel-body" id="felts-'.$code[0].$year[0].'" style="display:none;">
                        <table><tr><td>'.$html1.'</td><td style="padding-left:80px;">'.$html2.'</td></tr></table>
                    </div>
                </div>';
        
        echo json_encode((object)(array('status' => true, 'html' => $html)));
        exit;
    }

    public function getdoctype(){
        require_once(JPATH_COMPONENT.DS.'models'.DS.'profile.php');
        require_once(JPATH_COMPONENT.DS.'models'.DS.'campaign.php');
        $jinput     = JFactory::getApplication()->input;
        $code    = $jinput->get('code', null);
        $year    = $jinput->get('year', null);
        $code = explode(',', $code);
        $year = explode(',', $year);

        $profile = EmundusModelProfile::getProfileIDByCourse($code, $year);
        $docs = EmundusHelperFiles::getAttachmentsTypesByProfileID((int)$profile[0]);
        $campaign = EmundusModelCampaign::getCampaignsByCourse($code[0]);
        
        if($year[0] != 0)
            $campaign = EmundusModelCampaign::getCampaignsByCourseYear($code[0], $year[0]);
        else
            $campaign = EmundusModelCampaign::getCampaignsByCourse($code[0]);

        $html1 = '';
        $html2 = '';
        //var_dump(count($pages));
        for ($i = 0; $i < count($docs); $i++) {
            if($i < count($docs)/2){
                $html1 .= '<input class="em-ex-check" type="checkbox" value="'.$docs[$i]->id.'" name="'.$docs[$i]->value.'" id="'.$docs[$i]->id.'" /><label for="'.$docs[$i]->id.'">'.JText::_($docs[$i]->value).'</label><br/>';
            }else{
                $html2 .= '<input class="em-ex-check" type="checkbox" value="'.$docs[$i]->id.'" name="'.$docs[$i]->value.'" id="'.$docs[$i]->id.'" /><label for="'.$docs[$i]->id.'">'.JText::_($docs[$i]->value).'</label><br/>';
            }
        }
        
        $html = '<div class="panel panel-default pdform">
                    <div class="panel-heading">
                        <button type="button" class="btn btn-info btn-xs" title="'.JText::_('COM_EMUNDUS_SHOW_ELEMENTS').'" style="float:left;" onclick="showelts(this, '."'aelts-".$code[0].$year[0]."'".')">
                        <span class="glyphicon glyphicon-plus"></span>
                        </button>&ensp;&ensp;
                        <b>'.$campaign['label'].' - '.$campaign['training'].'('.$campaign['year'].')</b>
                    </div>
                    <div class="panel-body" id="aelts-'.$code[0].$year[0].'" style="display:none;">
                        <table><tr><td>'.$html1.'</td><td style="padding-left:80px;">'.$html2.'</td></tr></table>
                    </div>
                </div>';
        
        echo json_encode((object)(array('status' => true, 'html' => $html)));
        exit;
    }

    /**
     * Add lines to temp PDF file
     * @return String json
     */
    public function generate_pdf() {
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die(JText::_('RESTRICTED_ACCESS'));

        $m_files = $this->getModel('Files');

        $session = JFactory::getSession();
        $fnums_post = $session->get('fnums_export');
        if (count($fnums_post) == 0)
            $fnums_post = array($session->get('application_fnum'));

        $jinput     = JFactory::getApplication()->input;
        $file       = $jinput->getVar('file', null, 'STRING');
        $totalfile  = $jinput->getVar('totalfile', null);
        $start      = $jinput->getInt('start', 0);
        $limit      = $jinput->getInt('limit', 1);
        $forms      = $jinput->getInt('forms', 0);
        $attachment = $jinput->getInt('attachment', 0);
        $assessment = $jinput->getInt('assessment', 0);
        $decision   = $jinput->getInt('decision', 0);
        $admission  = $jinput->getInt('admission', 0);
        $ids        = $jinput->getVar('ids', null);
        $formids    = $jinput->getVar('formids', null);
        $attachids  = $jinput->getVar('attachids', null);
        $options    = $jinput->getVar('options', null);
        
        $formids = explode(',', $formids);
        $attachids = explode(',', $attachids);
        $options = explode(',', $options);
        
        $validFnums = array();
        foreach ($fnums_post as $fnum) {
            if (EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum))
                $validFnums[] = $fnum;
        }

        $fnumsInfo = $m_files->getFnumsInfos($validFnums);
        if (file_exists(JPATH_BASE . DS . 'tmp' . DS . $file))
            $files_list = array(JPATH_BASE . DS . 'tmp' . DS . $file);
        else
            $files_list = array();

        
        //$formids = array("275","256");
        for ($i = $start; $i < ($start+$limit) && $i < $totalfile; $i++) {
            $fnum = $validFnums[$i];
            if (is_numeric($fnum) && !empty($fnum)) {
                if ($forms) {
                    $files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $forms, $formids, $options);
                }

                if ($attachment) {
                    $tmpArray = array();
                    $m_application = $this->getModel('application');
                    $files = $m_application->getAttachmentsByFnum($fnum, $ids, $attachids);
                    
                    EmundusHelperExport::getAttachmentPDF($files_list, $tmpArray, $files, $fnumsInfo[$fnum]['applicant_id']);
                }

                if ($assessment){
                    $files_list[] = EmundusHelperExport::getEvalPDF($fnum);
                }
                    
                if ($decision)
                    $files_list[] = EmundusHelperExport::getDecisionPDF($fnum);

                if ($admission)
                    $files_list[] = EmundusHelperExport::getAdmissionPDF($fnum);
            }
        }
        $start = $i;

        if (count($files_list) > 0) {
            
            // all PDF in one file
            require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');
            $pdf = new ConcatPdf();

            $pdf->setFiles($files_list);
            $pdf->concat();
            if (isset($tmpArray)) {
                foreach ($tmpArray as $fn) {
                    unlink($fn);
                }
            }
            //for($f=1 ; $f < count($files_list) ; $f++){
            //    unlink($files_list[$f]);
            //}

            $pdf->Output(JPATH_BASE . DS . 'tmp' . DS . $file, 'F');

            $start = $i;

            $dataresult = [
                'start' => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'forms' => $forms,
                'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
                'admission' => $admission, 'file' => $file, 'msg' => JText::_('FILES_ADDED').' : '.$fnum
            ];

            $result = array('status' => true, 'json' => $dataresult);

        } else {

            $dataresult = [
                'start' => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'forms' => $forms,
                'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
                'admission' => $admission, 'file' => $file, 'msg' => JText::_('ERROR_NO_FILE_TO_ADD').' : '.$fnum
            ];

            $result = array('status' => false, 'json' => $dataresult);
        }
        echo json_encode((object) $result);
        exit();
    }


    public function export_xls_from_csv() {
        /** PHPExcel */
        ini_set('include_path', JPATH_BASE.DS.'libraries'.DS);
        include 'PHPExcel.php';
        include 'PHPExcel/Writer/Excel5.php';
        include 'PHPExcel/IOFactory.php';

        $jinput = JFactory::getApplication()->input;
        $csv = $jinput->getVar('csv', null);
        $nbcol = $jinput->getVar('nbcol', 0);
        $nbrow = $jinput->getVar('start', 0);
        $objReader = PHPExcel_IOFactory::createReader('CSV');
        $objReader->setDelimiter("\t");
        $objPHPExcel = new PHPExcel();

        // Excel colonne
        $colonne_by_id = array();
        for ($i=ord("A");$i<=ord("Z");$i++) {
            $colonne_by_id[]=chr($i);
        }
        
        for ($i=ord("A");$i<=ord("Z");$i++) {
            for ($j=ord("A");$j<=ord("Z");$j++) {
                $colonne_by_id[]=chr($i).chr($j);
                if(count($colonne_by_id) == $nbrow) break;
            }
        }

        // Set properties
        $objPHPExcel->getProperties()->setCreator("eMundus SAS : http://www.emundus.fr/");
        $objPHPExcel->getProperties()->setLastModifiedBy("eMundus SAS");
        $objPHPExcel->getProperties()->setTitle("eMmundus Report");
        $objPHPExcel->getProperties()->setSubject("eMmundus Report");
        $objPHPExcel->getProperties()->setDescription("Report from open source eMundus plateform : http://www.emundus.fr/");
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Extraction');
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $objPHPExcel->getActiveSheet()->freezePane('A2');

        $objReader->loadIntoExisting(JPATH_BASE.DS."tmp".DS.$csv, $objPHPExcel);

        $objConditional1 = new PHPExcel_Style_Conditional();
        $objConditional1->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS)
            ->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL)
            ->addCondition('0');
        $objConditional1->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

        $objConditional2 = new PHPExcel_Style_Conditional();
        $objConditional2->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS)
            ->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL)
            ->addCondition('100');
        $objConditional2->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF00FF00');

        $objConditional3 = new PHPExcel_Style_Conditional();
        $objConditional3->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS)
            ->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL)
            ->addCondition('50');
        $objConditional3->getStyle()->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

        $i = 0;
        //FNUM
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        $objPHPExcel->getActiveSheet()->getStyle('A2:A'.($nbrow+ 1))->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_NUMBER );
        $i++;
        //STATUS
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
        $i++;
        //LASTNAME
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
        $i++;
        //FIRSTNAME
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
        $i++;
        //EMAIL
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
        $objPHPExcel->getActiveSheet()->getStyle('E2:E'.($nbrow+ 1))->getNumberFormat()->setFormatCode( PHPExcel_Style_Font::UNDERLINE_SINGLE );
        $i++;
        //CAMPAIGN
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
        $i++;

        for ($i ; $i<$nbcol ; $i++) {
            $value = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($i, 1)->getValue();

            if ($value=="forms(%)" || $value=="attachment(%)") {
                $conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i].'1')->getConditionalStyles();
                array_push($conditionalStyles, $objConditional1);
                array_push($conditionalStyles, $objConditional2);
                array_push($conditionalStyles, $objConditional3);
                $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i].'1')->setConditionalStyles($conditionalStyles);
                $objPHPExcel->getActiveSheet()->duplicateConditionalStyle($conditionalStyles,$colonne_by_id[$i].'1:'.$colonne_by_id[$i].($nbrow+ 1));
            }
            $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        }
        
       
        
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save(JPATH_BASE.DS.'tmp'.DS.JFactory::getUser()->id.'_extraction.xls');
        $link = JFactory::getUser()->id.'_extraction.xls';
        if (!unlink(JPATH_BASE.DS."tmp".DS.$csv)) {
            $result = array('status' => false, 'msg'=>'ERROR_DELETE_CSV');
            echo json_encode((object) $result);
            exit();
        }
        $session     = JFactory::getSession();
        $session->clear('fnums_export');
        $result = array('status' => true, 'link' => $link);
        echo json_encode((object) $result);
        exit();

   }


    /**
     * @param $fnums
     * @param $objs
     * @param $element_id
     * @param $methode  aggregate in one cell (0) or split one data per line
     * @return string
     * @throws Exception
     */
    public function export_xls($fnums, $objs, $element_id, $methode=0) {
        //$mainframe = JFactory::getApplication();
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die( JText::_('RESTRICTED_ACCESS') );

        @set_time_limit(10800);
        jimport( 'joomla.user.user' );
        error_reporting(0);
        /** PHPExcel */
        ini_set('include_path', JPATH_BASE.DS.'libraries'.DS);

        include 'PHPExcel.php';
        include 'PHPExcel/Writer/Excel5.php';

        //$filename = 'emundus_applicants_'.date('Y.m.d').'.xls';

        $m_files = $this->getModel('Files');
        $h_files = new EmundusHelperFiles;

        $elements   = $h_files->getElementsName(implode(',',$element_id));
        $fnumsArray = $m_files->getFnumArray($fnums, $elements, $methode);
        $status     = $m_files->getStatusByFnums($fnums);

        $menu = @JSite::getMenu();
        $current_menu  = $menu->getActive();
        $menu_params = $menu->getParams($current_menu->id);

        $columnSupl = explode(',', $menu_params->get('em_actions'));
        $columnSupl = array_merge($columnSupl, $objs);
        $colOpt = array();

        $m_application = $this->getModel('Application');

        foreach ($columnSupl as $col) {
            $col = explode('.', $col);
            switch ($col[0]) {
                case "photo":
                    $colOpt['PHOTO'] = $h_files->getPhotos();
                    break;
                case "forms":
                    $colOpt['forms'] = $m_application->getFormsProgress(null, null, $fnums);
                    break;
                case "attachment":
                    $colOpt['attachment'] = $m_application->getAttachmentsProgress(null, null, $fnums);
                    break;
                case "assessment":
                    $colOpt['assessment'] = $h_files->getEvaluation('text', $fnums);
                    break;
                case "comment":
                    $colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
                    break;
                case 'evaluators':
                    $colOpt['evaluators'] = $h_files->createEvaluatorList($col[1], $m_files);
                    break;
            }
        }

        // Excel colonne
        $colonne_by_id = array();
        for ($i=ord("A");$i<=ord("Z");$i++) {
            $colonne_by_id[]=chr($i);
        }
        for ($i=ord("A");$i<=ord("Z");$i++) {
            for ($j=ord("A");$j<=ord("Z");$j++) {
                $colonne_by_id[]=chr($i).chr($j);
                if(count($colonne_by_id) == count($fnums)) break;
            }
        }
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        // Initiate cache
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array( 'memoryCacheSize' => '32MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        // Set properties
        $objPHPExcel->getProperties()->setCreator("eMundus SAS : http://www.emundus.fr/");
        $objPHPExcel->getProperties()->setLastModifiedBy("eMundus SAS");
        $objPHPExcel->getProperties()->setTitle("eMmundus Report");
        $objPHPExcel->getProperties()->setSubject("eMmundus Report");
        $objPHPExcel->getProperties()->setDescription("Report from open source eMundus plateform : http://www.emundus.fr/");


        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Extraction');
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $objPHPExcel->getActiveSheet()->freezePane('A2');

        $i = 0;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('F_NUM'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('STATUS'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('LAST_NAME'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('FIRST_NAME'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('EMAIL'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_('CAMPAIGN'));
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
        $i++;
        /*      foreach($fnumsArray[0] as $fKey => $fLine)
                {
                    if($fKey != 'fnum')
                    {
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_(strtoupper($fKey)));
                        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');

                        $i++;
                    }
                }
        */
        foreach ($elements as $fKey => $fLine) {
            if($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_name != 'campaign_id') {

                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, $fLine->element_label);
                $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
                $i++;

            }
        }
        foreach ($colOpt as $kOpt => $vOpt) {

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, 1, JText::_(strtoupper($kOpt)));
            $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
            $i++;

        }
        $line = 2;
        foreach ($fnumsArray as $fnunLine) {
            $col = 0;
            foreach ($fnunLine as $k => $v) {
                if ($k != 'code' && $k != 'campaign_id' && $k != 'jos_emundus_campaign_candidature___campaign_id' && $k != 'c___campaign_id') {

                    if ($k === 'fnum') {
                        $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($col, $line, (string) $v, PHPExcel_Cell_DataType::TYPE_STRING);
                        $col++;
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $status[$v]['value']);
                        $col++;
                        $uid = intval(substr($v, 21, 7));
                        $userProfil = JUserHelper::getProfile($uid)->emundus_profile;
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, strtoupper($userProfil['lastname']));
                        $col++;
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $userProfil['firstname']);
                        $col++;
                    } else {
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $v);
                        $col++;
                    }
                }
            }

            foreach ($colOpt as $kOpt => $vOpt) {
                switch ($kOpt) {
                    case "photo":
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, JText::_('PHOTO'));
                        break;
                    case "forms":
                        $val = $vOpt[$fnunLine['fnum']];
                        $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$col].':'.$colonne_by_id[$col])->getAlignment()->setWrapText(true);
                        if($val == 0) {
                            $rgb='FF6600';
                        } elseif($val == 100) {
                            $rgb='66FF66';
                        } elseif($val == 50) {
                            $rgb='FFFF00';
                        } else {
                            $rgb='FFFFFF';
                        }
                        $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$col].$line)->applyFromArray(
                            array('fill'    => array('type'     => PHPExcel_Style_Fill::FILL_SOLID,
                                                     'color'        => array('argb' => 'FF'.$rgb)
                            ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $val.'%');
                        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
                        break;
                    case "attachment":
                        $val = $vOpt[$fnunLine['fnum']];
                        $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$col].':'.$colonne_by_id[$col])->getAlignment()->setWrapText(true);
                        if($val == 0) {
                            $rgb='FF6600';
                        } elseif($val == 100) {
                            $rgb='66FF66';
                        } elseif($val == 50) {
                            $rgb='FFFF00';
                        } else {
                            $rgb='FFFFFF';
                        }
                        $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$col].$line)->applyFromArray(
                            array('fill'    => array('type'     => PHPExcel_Style_Fill::FILL_SOLID,
                                                     'color'        => array('argb' => 'FF'.$rgb)
                            ),
                            )
                        );
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $val.'%');
                        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
                        //$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $vOpt[$fnunLine['fnum']]."%");
                        break;
                    case "assessment":
                        $eval = '';
                        $evaluations = $vOpt[$fnunLine['fnum']];
                        foreach ($evaluations as $evaluation) {
                            $eval .= $evaluation;
                            $eval .= chr(10).'______'.chr(10);
                        }
//                      $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $vOpt[$fnunLine['fnum']]);
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $eval);
                        break;
                    case "comment":
                        $comments="";
                        foreach($colOpt['comment'] as $comment)
                        {
                            if($comment['fnum'] == $fnunLine['fnum'])
                            {
                                $comments .= $comment['reason'] . " | " . $comment['comment_body']."\rn";
                            }
                        }
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $comments);
                        break;
                    case 'evaluators':
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $vOpt[$fnunLine['fnum']]);
                        break;
                }
                $col++;
            }
            $line++;
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

        $objWriter->save(JPATH_BASE.DS.'tmp'.DS.JFactory::getUser()->id.'_extraction.xls');
        return JFactory::getUser()->id.'_extraction.xls';
        //$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        // Echo done
    }

    /**
     * @param $filename
     * @param string $mimePath
     * @return bool
     */
    function get_mime_type($filename, $mimePath = '../etc') {
        $fileext = substr(strrchr($filename, '.'), 1);
        if (empty($fileext)) return (false);
        $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
        $lines = file("$mimePath/mime.types");
        foreach($lines as $line) {
            if (substr($line, 0, 1) == '#') continue; // skip comments
            $line = rtrim($line) . " ";
            if (!preg_match($regex, $line, $matches)) continue; // no match to the extension
            return ($matches[1]);
        }
        return (false); // no match at all
    }

    /**
     *
     */
    public function download()
    {
        $jinput = JFactory::getApplication()->input;

        $name = $jinput->getString('name', null);

        $file = JPATH_BASE.DS.'tmp'.DS.$name;

        if (file_exists($file)) {
            $mime_type = $this->get_mime_type($file);
            header('Content-type: application/'.$mime_type);
            header('Content-Disposition: inline; filename='.basename($file));
            header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: pre-check=0, post-check=0, max-age=0');
            header('Pragma: anytextexeptno-cache', true);
            header('Cache-control: private');
            header('Expires: 0');
            //header('Content-Transfer-Encoding: binary');
            //header('Content-Length: ' . filesize($file));
            //header('Accept-Ranges: bytes');

            ob_clean();
            flush();
            readfile($file);
            exit;
        } else {
            echo JText::_('FILE_NOT_FOUND').' : '.$file;
        }
    }

    /**
     *  Create a zip file containing all documents attached to application fil number
     * @param array $fnums
     * @return string
     */
    function export_zip($fnums, $form_post = 1, $attachment = 1, $assessment = 1, $decision = 1, $admission = 1, $form_ids = null, $attachids = null, $options = null) {
        $view           = JRequest::getCmd( 'view' );
        $current_user   = JFactory::getUser();

        if ((!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) && $view != 'renew_application')
            die(JText::_('RESTRICTED_ACCESS'));

        require_once(JPATH_COMPONENT.DS.'helpers'.DS.'access.php');
        require_once(JPATH_BASE.DS.'libraries'.DS.'emundus'.DS.'pdf.php');

        
        $zip = new ZipArchive();
        $nom = date("Y-m-d").'_'.rand(1000,9999).'_x'.(count($fnums)-1).'.zip';
        
        $path = JPATH_BASE.DS.'tmp'.DS.$nom;
        $m_files = $this->getModel('Files');
        
        $fnumsInfo = $m_files->getFnumsInfos($fnums);
        
        
        if (file_exists($path))
            unlink($path);
        
        $users = array();
        
        foreach ($fnums as $fnum) {
            
            
            $formfile   = $fnum.'_applications.pdf';
            
            
            $files_list = array();
            
            $sid = intval(substr($fnum, -7));
            $users[$fnum] = JFactory::getUser($sid);

            if (!is_numeric($sid) || empty($sid))
                continue;
            
            if ($zip->open($path, ZipArchive::CREATE) == TRUE) {
                $dossier = EMUNDUS_PATH_ABS . $users[$fnum]->id . DS;

               
                $files_list = array();

                
                if ($form_post) {
                    //application_form_pdf($users[$fnum]->id, $fnum, false, $form_post, $form_ids, $options);
                    $files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $users[$fnum]->id, $fnum, $form_post, $form_ids, $options);
                }
                
                if ($assessment)
                    $files_list[] = EmundusHelperExport::getEvalPDF($fnum);
                
                if ($decision)
                    $files_list[] = EmundusHelperExport::getDecisionPDF($fnum);
    
                if ($admission)
                    $files_list[] = EmundusHelperExport::getAdmissionPDF($fnum);
                
                
                if (count($files_list) > 0) {
                    // all PDF in one file
                    require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');
                    $pdf = new ConcatPdf();
                    
                    $pdf->setFiles($files_list);
                    $pdf->concat();
                    
                    $pdf->Output($dossier . $formfile, 'F');
                    
                } else {
                    die("ERROR");
                }

                $application_pdf = $fnum . '_applications.pdf';
                $filename = $fnum . '_' . $users[$fnum]->name . DS . $application_pdf;
                
                
                if (!$zip->addFile($dossier . $application_pdf, $filename))
                    continue;

                if ($attachment) {
                    $fnum = explode(',', $fnum);
                    $files = $m_files->getFilesByFnums($fnum, $attachids);
                
                    //if ($zip->open($path, ZipArchive::CREATE) == TRUE) {
                        foreach ($files as $key => $file) {
                            $filename = $file['fnum'] . '_' . $users[$file['fnum']]->name . DS . $file['filename'];
                            $dossier = EMUNDUS_PATH_ABS . $users[$file['fnum']]->id . DS;
                            if (!$zip->addFile($dossier . $file['filename'], $filename)) {
                                continue;
                            }
                        }
        
                        //$zip->close();
                    //} else die("ERROR");
                }
                $zip->close();
            } else die("ERROR");

            
        }
        

        return $nom;

        
    }

    /*
    *   Create a zip file containing all documents attached to application fil number
    */
    /**
     * @param $fnums
     * @return string
     */
    function export_zip_pcl($fnums)
    {
        $view           = JRequest::getCmd( 'view' );
        $current_user   = JFactory::getUser();

        if ((!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) && $view != 'renew_application')
            die( JText::_('RESTRICTED_ACCESS') );

        require_once(JPATH_COMPONENT.DS.'helpers'.DS.'access.php');
        require_once(JPATH_BASE.DS.'libraries'.DS.'emundus'.DS.'pdf.php');
        require_once(JPATH_BASE.DS.'libraries'.DS.'pclzip-2-8-2'.DS.'pclzip.lib.php');


        $nom = date("Y-m-d").'_'.rand(1000,9999).'_x'.(count($fnums)-1).'.zip';
        $path = JPATH_BASE.DS.'tmp'.DS.$nom;

        $zip = new PclZip($path);

        $m_files = $this->getModel('Files');
        $files = $m_files->getFilesByFnums($fnums);

        if(file_exists($path))
            unlink($path);

        $users = array();
        foreach ($fnums as $fnum) {
            $sid = intval(substr($fnum, -7));
            $users[$fnum] = JFactory::getUser($sid);

            if (!is_numeric($sid) || empty($sid))
                continue;

            $dossier = EMUNDUS_PATH_ABS.$users[$fnum]->id;
            $dir = $fnum.'_'.$users[$fnum]->name;
            application_form_pdf($users[$fnum]->id, $fnum, false);
            $application_pdf = $fnum.'_application.pdf';

            $zip->add($dossier.DS.$application_pdf, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);

        }


        foreach ($files as $key => $file) {
            $dir = $file['fnum'].'_'.$users[$file['fnum']]->name;
            $dossier = EMUNDUS_PATH_ABS.$users[$file['fnum']]->id.DS;
            $zip->add($dossier.$file['filename'], PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);
        }

        return $nom;
    }

    /*
    *   Get evaluation Fabrik formid by fnum
    */
    /**
     *
     */
    function getformid() {
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die( JText::_('RESTRICTED_ACCESS') );

        $jinput = JFactory::getApplication()->input;
        $fnum   = $jinput->getString('fnum', null);
        
        $m_files = $this->getModel('Files');
        $res    = $m_files->getFormidByFnum($fnum);
        
        $formid = ($res>0)?$res:29;

        $result = array('status' => true, 'formid' => $formid);
        echo json_encode((object) $result);
        exit();
    }


    /*
    *   Get my evaluation by fnum
    */
    /**
     *
     */
    function getevalid() {
        $current_user = JFactory::getUser();

        if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
            die( JText::_('RESTRICTED_ACCESS') );

        $jinput = JFactory::getApplication()->input;
        $fnum = $jinput->getString('fnum', null);

        $m_evalutaion = $this->getModel('Evaluation');
        $myEval = $m_evalutaion->getEvaluationsFnumUser($fnum, $current_user->id);
        $evalid = ($myEval[0]->id>0)?$myEval[0]->id:-1;

        $result = array('status' => true, 'evalid' => $evalid);
        echo json_encode((object) $result);
        exit();
    }

    public function getdocs()
    {
        $jinput = JFactory::getApplication()->input;
        $code = $jinput->getString('code', "");
        $m_files = $this->getModel('Files');

        $res = new stdClass();
        $res->status = true;
        $res->options = $m_files->getDocsByProg($code);

        echo json_encode($res);
        exit();
    }

    public function generatedoc()
    {
        $jinput = JFactory::getApplication()->input;
        $fnums  = $jinput->getString('fnums', "");
        $code   = $jinput->getString('code', "");
        $idTmpl = $jinput->getString('id_tmpl', "");

        $fnumsArray = explode(",", $fnums);

        $m_files        = $this->getModel('Files');
        $m_evalutaion   = $this->getModel('Evaluation');
        $m_emails       = $this->getModel('Emails');

        $user = JFactory::getUser();

        $fnumsArray = $m_files->checkFnumsDoc($code, $fnumsArray);
        $tmpl       = $m_evalutaion->getLettersTemplateByID($idTmpl);
        $attachInfos= $m_files->getAttachmentInfos($tmpl[0]['attachment_id']);

        $res = new stdClass();
        $res->status = true;
        $res->files = array();
        $fnumsInfos = $m_files->getFnumsTagsInfos($fnumsArray);

        switch($tmpl[0]['template_type'])
        {
            case 1:
                //Simple FILE
                $res->status = false;
                $res->msg = JText::_("ERROR_CANNOT_GENERATE_FILE");
                echo json_encode($res);
                break;
            case 2:
                //Generate PDF
                /*if (!empty($tmpl[0]['attachment_id'])) {
                    require(JPATH_LIBRARIES.DS.'emundus'.DS.'pdf.php');
                    $files = letter_pdf($user->id, $tmpl[0]['status'], $tmpl[0]['training'], 0, 0);
                }*/
                $res->status = false;
                $res->msg = JText::_("ERROR_CANNOT_GENERATE_FILE_FROM_HTML_TEMPLATE");
                echo json_encode($res);

                break;
            case 3:
                // template DOCX
                require_once JPATH_LIBRARIES.DS.'vendor'.DS.'autoload.php';
                //require_once JPATH_LIBRARIES.DS.'HTMLtoOpenXML'.DS.'HTMLtoOpenXML.php

                $const = array('user_id' => $user->id, 'user_email' => $user->email, 'user_name' => $user->name, 'current_date' => date('d/m/Y', time()));
                try
                {
                    $phpWord = new \PhpOffice\PhpWord\PhpWord();
                    $preprocess = $phpWord->loadTemplate(JPATH_BASE.$tmpl[0]['file']);
                    //$preprocess = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_BASE.$tmpl[0]['file']);
                    $tags = $preprocess->getVariables();
                    $idFabrik = array();
                    $setupTags = array();
                    foreach ($tags as $i => $val) {
                        $tag = strip_tags($val);
                        if(is_numeric($tag))
                            $idFabrik[] = $tag;
                        else
                            $setupTags[] = $tag;
                    }
                    $fabrikElts = $m_files->getValueFabrikByIds($idFabrik);
                    $fabrikValues = array();
                    foreach ($fabrikElts as $elt) {
                        $params = json_decode($elt['params']);
                        $groupParams = json_decode($elt['group_params']);
                        $isDate = ($elt['plugin'] == 'date');
                        $isDatabaseJoin = ($elt['plugin'] === 'databasejoin');

                        if (@$groupParams->repeat_group_button == 1 || $isDatabaseJoin) {
                            $fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnumsArray, $params, $groupParams->repeat_group_button == 1);
                        } else {
                            if ($isDate)
                                $fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name'], $params->date_form_format);
                            else
                                $fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
                        }

                        if ($elt['plugin'] == "checkbox" || $elt['plugin'] == "dropdown") {
                            foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
                                if($elt['plugin'] == "checkbox")
                                    $val = json_decode($val['val']);
                                else
                                    $val = explode(',', $val['val']);
                                if (count($val) > 0) {
                                    foreach ($val as $k => $v) {
                                        $index = array_search(trim($v),$params->sub_options->sub_values);
                                        $val[$k] = $params->sub_options->sub_labels[$index];
                                    }
                                    $fabrikValues[$elt['id']][$fnum]['val'] = implode(", ", $val);
                                } else {
                                    $fabrikValues[$elt['id']][$fnum]['val'] = "";
                                }
                            }
                        }
                        elseif($elt['plugin'] == "birthday") {
                            foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
                                $val = explode(',', $val['val']);
                                foreach ($val as $k => $v) {
                                    $val[$k] = date($params->details_date_format, strtotime($v));
                                }
                                $fabrikValues[$elt['id']][$fnum]['val'] = implode(",", $val);
                            }
                        } else {
                            if (@$groupParams->repeat_group_button == 1 || $isDatabaseJoin)
                                $fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnumsArray, $params, $groupParams->repeat_group_button == 1);
                            else
                                $fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
                        }

                    }
                    foreach ($fnumsArray as $fnum) {
                        $preprocess = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_BASE.$tmpl[0]['file']);
                        if (isset($fnumsInfos[$fnum])) {
                            foreach ($setupTags as $tag) {
                                $val = "";
                                $lowerTag = strtolower($tag);
                                if(array_key_exists($lowerTag, $const))
                                    $preprocess->setValue($tag, $const[$lowerTag]);
                                elseif(!empty(@$fnumsInfos[$fnum][$lowerTag]))
                                    $preprocess->setValue($tag, @$fnumsInfos[$fnum][$lowerTag]);
                                else {
                                    $tags = $m_emails->setTagsWord(@$fnumsInfos[$fnum]['applicant_id'], null, $fnum, '');
                                    $i = 0;
                                    foreach ($tags['patterns'] as $key => $value) {
                                        if ($value == $tag) {
                                            $val = $tags['replacements'][$i];
                                            break;
                                        }
                                        $i++;
                                    }
                                    // Add HTML to a tag value is not possible....
                                    /*$phpWord = new \PhpOffice\PhpWord\PhpWord();
                                    $section = $phpWord->addSection();
                                    $preprocessHtml = new \PhpOffice\PhpWord\Shared\Html;
                                    $preprocessHtml->addHtml($section, $val);*/

                                    //$val = HTMLtoOpenXML::getInstance()->fromHTML(str_replace("<br />","<br>", stripslashes($val)));
                                    $preprocess->setValue($tag, htmlspecialchars($val));
                                }
                            }
                            foreach($idFabrik as $id)
                            {
                                if(isset($fabrikValues[$id][$fnum]))
                                {
                                    $value = str_replace('\n', ', ', $fabrikValues[$id][$fnum]['val']);
                                    $preprocess->setValue($id, $value);
                                }
                                else
                                {
                                    $preprocess->setValue($id, '');
                                }
                            }

                            $rand = rand(0, 1000000);
                            if(!file_exists(EMUNDUS_PATH_ABS.$fnumsInfos[$fnum]['applicant_id']))
                            {
                                mkdir(EMUNDUS_PATH_ABS.$fnumsInfos[$fnum]['applicant_id'], 0775);
                            }

                            $filename = str_replace(' ', '', $fnumsInfos[$fnum]['applicant_name']).$attachInfos['lbl']."-".md5($rand.time()).".docx";

                            $preprocess->saveAs(EMUNDUS_PATH_ABS.$fnumsInfos[$fnum]['applicant_id'].DS.$filename);

                            $upId = $m_files->addAttachment($fnum, $filename, $fnumsInfos[$fnum]['applicant_id'], $fnumsInfos[$fnum]['campaign_id'], $tmpl[0]['attachment_id'], $attachInfos['description']);

                            $res->files[] = array('filename' => $filename, 'upload' => $upId, 'url' => EMUNDUS_PATH_REL.$fnumsInfos[$fnum]['applicant_id'].'/', );
                        }
                        unset($preprocess);
                    }
                    echo json_encode($res);
                }
                catch(Exception $e)
                {
                    $res->status = false;
                    $res->msg = JText::_("AN_ERROR_OCURRED").':'. $e->getMessage();
                    echo json_encode($res);
                    exit();
                }
                break;
        }
        exit();

    }

    public function exportzipdoc()
    {
        $jinput = JFactory::getApplication()->input;
        $idFiles = explode(",", $jinput->getStrings('ids', ""));
        $m_files = $this->getModel('Files');
        $files = $m_files->getAttachmentsById($idFiles);

        $nom = date("Y-m-d").'_'.md5(rand(1000,9999).time()).'_x'.(count($files)-1).'.zip';
        $path = JPATH_BASE.DS.'tmp'.DS.$nom;

        if (extension_loaded('zip')) {
            $zip = new ZipArchive();

            if($zip->open($path, ZipArchive::CREATE) == TRUE)
            {
                foreach($files as $key => $file)
                {
                    $filename = EMUNDUS_PATH_ABS.$file['user_id'].DS.$file['filename'];
                    if(!$zip->addFile($filename, $file['filename']))
                    {
                        continue;
                    }
                }
                $zip->close();
            } else {
                die ("ERROR");
            }

        } else {
            require_once(JPATH_BASE.DS.'libraries'.DS.'pclzip-2-8-2'.DS.'pclzip.lib.php');
            $zip = new PclZip($path);

            foreach($files as $key => $file)
            {
                $user = JFactory::getUser($file['user_id']);
                $dir = $file['fnum'].'_'.$user->name;
                $filename = EMUNDUS_PATH_ABS.$file['user_id'].DS.$file['filename'];

                $zip->add($filename, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);

                if(!$zip->addFile($filename, $file['filename']))
                {
                    continue;
                }
            }
        }

        $mime_type = $this->get_mime_type($path);
        header('Content-type: application/'.$mime_type);
        header('Content-Disposition: inline; filename='.basename($path));
        header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Pragma: anytextexeptno-cache', true);
        header('Cache-control: private');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($path);
        exit;
    }

    public function exportonedoc()
    {
        require_once JPATH_LIBRARIES.DS.'PHPWord'.DS.'src'.DS.'Autoloader.php';

        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            \PhpOffice\PhpWord\Autoloader::register();
            $rendererName = \PhpOffice\PhpWord\Settings::PDF_RENDERER_TCPDF;
            \PhpOffice\PhpWord\Settings::setPdfRenderer($rendererName, JPATH_LIBRARIES . DS . 'emundus' . DS . 'tcpdf');
        }

        $jinput = JFactory::getApplication()->input;
        $idFiles = explode(",", $jinput->getStrings('ids', ""));
        $m_files = $this->getModel('Files');
        $files = $m_files->getAttachmentsById($idFiles);
        $nom = date("Y-m-d").'_'.md5(rand(1000,9999).time()).'_x'.(count($files)-1).'.pdf';
        $path = JPATH_BASE.DS.'tmp'.DS.$nom;

        $wordPHP = new \PhpOffice\PhpWord\PhpWord();

        $docs = array();
        foreach($files as $key => $file)
        {
            $filename = EMUNDUS_PATH_ABS.$file['user_id'].DS.$file['filename'];
            $tmpName = JPATH_BASE.DS.'tmp'.DS.$file['filename'];
            $document = $wordPHP->loadTemplate($filename);
            $document->saveAs($tmpName); // Save to temp file

            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                $wordPHP = \PhpOffice\PhpWord\IOFactory::load($tmpName); // Read the temp file
                $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($wordPHP, 'PDF');

                $xmlWriter->save($tmpName.'.pdf');  // Save to PDF
            }

            $docs[] = $tmpName.'.pdf';
            unlink($tmpName); // Delete the temp file
        }
        require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'fpdi.php');
        $pdf = new ConcatPdf();
        $pdf->setFiles($docs);
        $pdf->concat();
        if(isset($docs))
        {
            foreach($docs as $fn)
            {
                unlink($fn);
            }
        }
        $pdf->Output($path, 'I');
        exit;
    }

    public function getPDFProgrammes(){
        require_once (JPATH_COMPONENT.DS.'models'.DS.'campaign.php');
        require_once (JPATH_COMPONENT.DS.'models'.DS.'files.php');
        $html = '';
        $session     = JFactory::getSession();
        $jinput = JFactory::getApplication()->input;
        $m_files = new EmundusModelFiles;

        $fnums = $jinput->getVar('checkInput', null);
        $fnums = (array) json_decode(stripslashes($fnums));
        
        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all"){
             $fnums = $m_files->getAllFnums();
        }
           
        
        
        $m_campaigns = new EmundusModelCampaign;
       
        if(!empty($fnums)){
            foreach($fnums as $fnum){
                if($fnum != "em-check-all"){
                    $campaign  = $m_campaigns->getCampaignByFnum($fnum);
                    $programme = $m_campaigns->getProgrammeByCampaignID((int)$campaign->id);
                    $option = '<option value="'.$programme['code'].'">'.$programme['label'].' - '.$programme['code'].'</option>';
                    if (strpos($html, $option) === false) {
                        $html .= $option;
                    }
                }
            }
        }
        
        echo json_encode((object)(array('status' => true, 'html' => $html)));
        exit;
    }
    public function getPDFCampaigns(){
        require_once (JPATH_COMPONENT.DS.'models'.DS.'campaign.php');
        require_once (JPATH_COMPONENT.DS.'models'.DS.'files.php');
        $html = '';
        $session     = JFactory::getSession();
        $jinput      = JFactory::getApplication()->input;
        $m_files = new EmundusModelFiles;

        $code        = $jinput->getString('code', null);

        $fnums = $jinput->getVar('checkInput', null);
        $fnums = (array) json_decode(stripslashes($fnums));

        if (!is_array($fnums) || count($fnums) == 0 || @$fnums[0] == "all")
            $fnums = $m_files->getAllFnums();

        $m_campaigns = new EmundusModelCampaign;
        $nbcamp = 0;
        if(!empty($fnums)){
            
            foreach($fnums as $fnum){
                $campaign  = $m_campaigns->getCampaignByFnum($fnum);
                if($campaign->training == $code){
                    $nbcamp += 1;
                    $option = '<option value="'.$campaign->year.'">'.$campaign->label.' - '.$campaign->training.'('.$campaign->year.')</option>';
                    if (strpos($html, $option) === false) {
                        $html .= $option;
                    }
                }
               
                
                
            }
        }
        
        echo json_encode((object)(array('status' => true, 'html' => $html, 'nbcamp' => $nbcamp)));
        exit;
    }
    

    public function getProgrammes(){
        require_once (JPATH_COMPONENT.DS.'models'.DS.'campaign.php');
        $html = '';
        $session     = JFactory::getSession();
        $filt_params = $session->get('filt_params');
        
        $h_files = new EmundusHelperFiles;
        $programmes = $h_files->getProgrammes($filt_params['programme']);
        $nbprg = count($programmes);
        if (empty($filt_params)){
            $params['programme'] = $programmes;
            $session->set('filt_params', $params);
        }
        foreach ($programmes as $p) {
            if ($nbprg == 1) {
                $html .= '<option value="'.$p->code.'" selected>'.$p->label.' - '.$p->code.'</option>';
            } else {
                $html .= '<option value="'.$p->code.'">'.$p->label.' - '.$p->code.'</option>';
            }
        }

        echo json_encode((object)(array('status' => true, 'html' => $html, 'nbprg' => $nbprg)));
        exit;
    }
    public function getProgramCampaigns(){
        $html = '';
       
        $h_files = new EmundusHelperFiles;
        $jinput = JFactory::getApplication()->input;
        $code       = $jinput->getString('code', null);
        $campaigns = $h_files->getProgramCampaigns($code);
        
        $nbcamp = count($campaigns);
        foreach ($campaigns as $c) {
            if ($nbcamp == 1) {
                $html .= '<option value="'.$c->year.'" selected>'.$c->label.' - '.$c->training.'('.$c->year.')</option>';
            } else {
                $html .= '<option value="'.$c->year.'">'.$c->label.' - '.$c->training.'('.$c->year.')</option>';
            }
        }

        echo json_encode((object)(array('status' => true, 'html' => $html, 'nbcamp' => $nbcamp)));
        exit;
    }

    public function saveExcelFilter()
    {
        $db = JFactory::getDBO();
        $jinput         = JFactory::getApplication()->input;
        $name           = $jinput->getString('filt_name', null);
        $current_user   = JFactory::getUser();
        $user_id        = $current_user->id;
        $itemid         = JRequest::getVar('Itemid', null, 'GET', 'none',0);
        $params       = $jinput->getString('params', null);
        $constraints    = array('excelfilter'=>$params);

        $constraints = json_encode($constraints);

        if (empty($itemid))
            $itemid = JRequest::getVar('Itemid', null, 'POST', 'none',0);

        $time_date = (date('Y-m-d H:i:s'));

        $query = "INSERT INTO #__emundus_filters (time_date,user,name,constraints,item_id) values('".$time_date."',".$user_id.",'".$name."',".$db->quote($constraints).",".$itemid.")";
        $db->setQuery( $query );

        try {

            $db->Query();
            $query = 'select f.id, f.name from #__emundus_filters as f where f.time_date = "'.$time_date.'" and user = '.$user_id.' and name="'.$name.'" and item_id="'.$itemid.'"';
            //echo $query;
            $db->setQuery($query);
            $result = $db->loadObject();
            echo json_encode((object)(array('status' => true, 'filter' => $result)));
            exit;

        } catch (Exception $e) {
            echo json_encode((object)(array('status' => false)));
            exit;
        }
    }
    public function getExportExcelFilter(){
        $db = JFactory::getDBO();
        $user_id   = JFactory::getUser()->id;
        $session     = JFactory::getSession();

        try {
            $query = 'SELECT * from #__emundus_filters  where user = '.$user_id.' and constraints LIKE "%excelfilter%"';
            $db->setQuery($query);
            $result = $db->loadObjectList();

            echo json_encode((object)(array('status' => true, 'filter' => $result)));
            exit;
        } catch (Exception $e) {
            echo json_encode((object)(array('status' => false)));
            exit;
        }
    }
}

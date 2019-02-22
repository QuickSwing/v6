<?php
function age($naiss) {
    @list($annee, $mois, $jour) = preg_split('[-.]', $naiss);
    $today['mois'] = date('n');
    $today['jour'] = date('j');
    $today['annee'] = date('Y');
    $annees = $today['annee'] - $annee;
    if ($today['mois'] <= $mois) {
        if ($mois == $today['mois']) {
            if ($jour > $today['jour'])
                $annees--;
        }
        else
            $annees--;
    }
    return $annees;
}

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


/** Generate a PDF letter based on the HTML it contains.
 * This is only for letter type 2, letters type 1 are any file uploaded by the user and 3 are DOC templates.
 *
 * @param Object $letter The letter to generate the pdf file from.
 * @param String $fnum The fnum of the file to generate for.
 * @param Int $user_id The ID of the user who's data we want.
 * @param String $training The training code for the fnum.
 *
 * @return Boolean False if queries fail or the letter template is not 2.
 */
function generateLetterFromHtml($letter, $fnum, $user_id, $training) {

    if ($letter->template_type != 2)
        return false;

    set_time_limit(0);
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'campaign.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $user = JFactory::getUser($user_id);
    $current_user = JFactory::getUser();
    $db = JFactory::getDBO();
    $config = JFactory::getConfig();
    $app = JFactory::getApplication();

    $files = array();

    $m_application 	= new EmundusModelApplication;
    $m_campaign 	= new EmundusModelCampaign;
    $m_emails 		= new EmundusModelEmails;

    $campaign = $m_campaign->getCampaignsByCourse($training);

    if (class_exists('MYPDF') === false || !class_exists('MYPDF')) {
        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF {

            var $logo = "";
            var $logo_footer = "";
            var $footer = "";

            //Page header
            public function Header() {
                // Logo
                if (is_file($this->logo))
                    $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                // Set font
                $this->SetFont('helvetica', 'B', 16);
                // Title
                $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // Page number
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
                // footer
                $this->writeHTMLCell($w=0, $h=0, $x='', $y=250, $this->footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
                //logo
                if (is_file($this->logo_footer))
                    $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

            }
        }
    }

    $error = 0;

    $attachment = $m_application->getAttachmentByID($letter->attachment_id);

    try {

        // Test if letter type has already been created for that user/campaign/attachment and delete before if true.
        $query = 'SELECT * FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter->attachment_id.' AND campaign_id='.$campaign['id']. ' AND fnum like '.$db->Quote($fnum);
        $db->setQuery($query);
        $file = $db->loadAssoc();

    } catch (Exception $e) {
        JLog::add('SQL Error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
        return false;
    }

    // test if directory exist
    if (!file_exists(EMUNDUS_PATH_ABS.$user_id)) {
        mkdir(EMUNDUS_PATH_ABS.$user_id, 0755, true);
        chmod(EMUNDUS_PATH_ABS.$user_id, 0755);
    }

    if (count($file) > 0 && strpos($file['filename'], 'lock') === false) {

        try {

            $query = 'DELETE FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter->attachment_id.' AND campaign_id='.$campaign['id']. ' AND fnum like '.$db->Quote($fnum).' AND filename NOT LIKE "%lock%"';
            $db->setQuery($query);
            $db->query();

        } catch (Exception $e) {
            JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
            return false;
        }

        @unlink(EMUNDUS_PATH_ABS.$user_id.DS.$file['filename']);
    }

    // Common tags to use.
    $post = [
        'TRAINING_CODE' 	=> $training,
        'TRAINING_PROGRAMME'=> $campaign['label'],
        'USER_NAME' 		=> $user->name,
        'USER_EMAIL' 		=> $user->email,
        'FNUM' 				=> $fnum
    ];

    $tags = $m_emails->setTags($user_id, $post, $fnum);
    $htmldata = "";
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($current_user->name);
    $pdf->SetTitle($letter->title);

    // set margins
    $pdf->SetMargins(5, 40, 5);

    $pdf->footer = $letter->footer;

    //get logo
    preg_match('#src="(.*?)"#i', $letter->header, $tab);
    $pdf->logo = JPATH_BASE.DS.$tab[1];

    preg_match('#src="(.*?)"#i', $letter->footer, $tab);
    $pdf->logo_footer = JPATH_BASE.DS.@$tab[1];

    unset($logo);
    unset($logo_footer);

    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->SetFont('helvetica', '', 8);

    $letter->body = $m_emails->setTagsFabrik($letter->body, array($fnum));

    $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter->body))));

    $pdf->AddPage();

    $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

    @chdir('tmp');

    $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.pdf';

    $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, 'F');

    $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;

    if ($error == 0) {

        try {

            $query = 'INSERT INTO #__emundus_uploads (user_id, attachment_id, filename, description, can_be_deleted, can_be_viewed, campaign_id, fnum) VALUES ('.$user_id.', '.$letter->attachment_id.', "'.$name.'","'.$training.' '.date('Y-m-d H:i:s').'", 0, 1, '.$campaign['id'].', '.$db->Quote($fnum).')';
            $db->setQuery($query);
            $db->query();

        } catch (Exception $e) {
            JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
        }

        return $path;
    }

}


/** Generate the letter result
 * @param int $user_id the user ID
 * @param bool Eligibility ID of the evaluation
 * @param String Code of the programme
 * @param int Campaign id
 * @param int Evaluation id
 * @param mixed output format
 * @param String File number
 * @return Array Files
 */
function letter_pdf ($user_id, $eligibility, $training, $campaign_id, $evaluation_id, $output = true, $fnum = null) {
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'evaluation.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'campaign.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $current_user 	= JFactory::getUser();
    $db 			= JFactory::getDBO();
    $config 		= JFactory::getConfig();
    $jdate 			= JFactory::getDate();
    $app			= JFactory::getApplication();

    $timezone = new DateTimeZone($config->get('offset'));
    $jdate->setTimezone($timezone);
    $now = $jdate->toSql();

    $files = array();

    $m_application 	= new EmundusModelApplication;
    $m_evaluation 	= new EmundusModelEvaluation;
    $m_campaign 	= new EmundusModelCampaign;
    $m_emails 		= new EmundusModelEmails;

    /*$query = "SELECT * FROM #__emundus_setup_letters WHERE eligibility=".$eligibility." AND training=".$db->Quote($training);
    $db->setQuery($query);
    $letters = $db->loadAssocList();*/
    $letters = $m_evaluation->getLettersTemplate($eligibility, $training);

    /*$query = "SELECT * FROM #__emundus_setup_teaching_unity WHERE id = (select training_id from #__emundus_training_174_repeat where applicant_id=".$user_id." and campaign_id=".$campaign_id.") ORDER BY date_start ASC";
    $db->setQuery($query);
    $courses = $db->loadAssocList();
    */

    try {

        $query = "SELECT * FROM #__emundus_setup_teaching_unity
					WHERE published=1 AND date_start>'".$now."' AND code IN (".$db->Quote($letters[0]['training']).")
					ORDER BY date_start ASC";
        $db->setQuery($query);
        $courses = $db->loadAssocList();

    } catch (Exception $e) {
        JLog::add('SQL Error in Emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }

    $courses_list = '';
    $courses_fee = ' ';
    foreach ($courses as $c) {
        $ds = !empty($c['date_start']) ? date(JText::_('DATE_FORMAT_LC3'), strtotime($c['date_start'])) : JText::_('NOT_DEFINED');
        $de = !empty($c['date_end']) ? date(JText::_('DATE_FORMAT_LC3'), strtotime($c['date_end'])) : JText::_('NOT_DEFINED');
        //$courses_list .= '<li>'.$ds.' - '.$de.'</li>';
        $courses_list .= '<img src="'.JPATH_BASE.DS."media".DS."com_emundus".DS."images".DS."icones".DS."checkbox-unchecked_16x16.png".'" width="8" height="8" align="left" /> ';
        $courses_list .= $ds.' - '.$de.'<br />';
        $courses_fee  .= 'Euro '.$c['price'].',-- ';
    }

    $campaign = $m_campaign->getCampaignByID($campaign_id);

    // Extend the TCPDF class to create custom Header and Footer
    class MYPDF extends TCPDF {

        var $logo = "";
        var $logo_footer = "";
        var $footer = "";

        //Page header
        public function Header() {
            // Logo
            if (is_file($this->logo))
                $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            // Set font
            $this->SetFont('helvetica', 'B', 16);
            // Title
            $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            // footer
            $this->writeHTMLCell($w=0, $h=0, $x='', $y=250, $this->footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
            //logo
            if (is_file($this->logo_footer))
                $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

        }
    }

    //
    // Evaluation result
    //
    if ($evaluation_id > 0) {
        $evaluation = $m_evaluation->getEvaluationByID($evaluation_id);
        $reason = $m_evaluation->getEvaluationReasons();
        unset($evaluation[0]["id"]);
        unset($evaluation[0]["user"]);
        unset($evaluation[0]["time_date"]);
        unset($evaluation[0]["student_id"]);
        unset($evaluation[0]["parent_id"]);
        unset($evaluation[0]["campaign_id"]);
        unset($evaluation[0]["comment"]);

        if(empty($evaluation[0]["reason"])) {
            unset($evaluation[0]["reason"]);
            unset($evaluation[0]["reason_other"]);
        } elseif(empty($evaluation[0]["reason_other"])) {
            unset($evaluation[0]["reason_other"]);
        }

        include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'list.php');
        $evaluation_details = @EmundusHelperList::getElementsDetailsByName('"'.implode('","', array_keys($evaluation[0])).'"');

        $result = "";
        foreach ($evaluation_details as $ed) {
            if ($ed->hidden==0 && $ed->published==1 && $ed->tab_name=="jos_emundus_evaluations") {
                //$result .= '<br>'.$ed->element_label.' : ';
                if ($ed->element_name=="reason") {
                    $result .= '<ul>';
                    foreach ($evaluation as $e) {
                        $result .= '<li>'.@$reason[$e[@$ed->element_name]]->reason.'</li>'; //die(print_r(@$reason[$e[@$ed->element_name]]));
                    }
                    if (@!empty($evaluation[0]["reason_other"]))
                        $result .= '<ul><li>'.@$evaluation[0]["reason_other"].'</li></ul>';
                    $result .= '</ul>';
                } /*elseif($ed->element_name=="result") {
						$result .= $eligibility[$evaluation[0][$ed->element_name]]->title;
				} else
					$result .= $evaluation[0][$ed->element_name];*/
            }
        }
    }

    //
    // Replacement
    //
    $post = array(  'TRAINING_CODE' => $training,
        'TRAINING_PROGRAMME' => $campaign['label'],
        'REASON' => @$result,
        'TRAINING_FEE' => $courses_fee,
        'TRAINING_PERIODE' => $courses_list,
        'USER_NAME' => $current_user->name,
        'USER_EMAIL' => $current_user->email,
        'FNUM' => $fnum );

//die(var_dump($tags));
    foreach ($letters as $letter) {
        $error = 0;

        $attachment = $m_application->getAttachmentByID($letter['attachment_id']);

        /*$query = "SELECT * FROM #__emundus_setup_attachments WHERE id=".$letter['attachment_id'];
        $db->setQuery($query);
        $attachment = $db->loadAssoc();*/

        try {

            // Test if letter type has already been created for that user/campaign/attachment and delete before if true.
            $query = 'SELECT * FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter['attachment_id'].' AND campaign_id='.$campaign_id. ' AND fnum like '.$db->Quote($fnum);
            $db->setQuery($query);
            $file = $db->loadAssoc();

        } catch (Exception $e) {
            JLog::add('SQL Error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
        }
        // test if directory exist
        if (!file_exists(EMUNDUS_PATH_ABS.$user_id)) {
            mkdir(EMUNDUS_PATH_ABS.$user_id, 0755, true);
            chmod(EMUNDUS_PATH_ABS.$user_id, 0755);
        }

        if (count($file) > 0 && strpos($file['filename'], 'lock') === false && $letter['template_type'] != 4) {

            try {

                $query = 'DELETE FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter['attachment_id'].' AND campaign_id='.$campaign_id. ' AND fnum like '.$db->Quote($fnum).' AND filename NOT LIKE "%lock%"';
                $db->setQuery($query);
                $db->query();

            } catch (Exception $e) {
                JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
            }

            @unlink(EMUNDUS_PATH_ABS.$user_id.DS.$file['filename']);
        }

        if ($letter['template_type'] == 1) { // Static file

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.'.$file_type[1];

            if (file_exists(JPATH_BASE.$letter['file'])) {
                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
                copy(JPATH_BASE.$letter['file'], $path);
            } else {
                $app->enqueueMessage($name.' - '.JText::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } elseif ($letter['template_type'] == 3) { // Template file .docx

            $tags = $m_emails->setTagsWord($user_id, $post, $fnum);
            require_once JPATH_LIBRARIES.DS.'PHPWord.php';

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.'.$file_type[1];

            if (file_exists(JPATH_BASE.$letter['file'])) {

                $PHPWord = new PHPWord();
                $document = $PHPWord->loadTemplate(JPATH_BASE.$letter['file']);

                for ($i = 0; $i < count($tags['patterns']); $i++) {
                    $document->setValue($tags['patterns'][$i], $tags['replacements'][$i]);
                    //echo $tags['patterns'][$i]." - ".$tags['replacements'][$i]."<br>";
                }

                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;

                $document->save($path);
                unset($document);
            } else {
                $app->enqueueMessage($name.' - '.JText::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } elseif ($letter['template_type'] == 4) { // Applicant file
            $upload_file = $m_application->getAttachmentsByFnum($fnum, $letter['attachment_id']);
            $name = $upload_file[0]->filename;
            if (file_exists(JPATH_BASE.$letter['file'])) {
                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
            } else {
                $app->enqueueMessage($name.' - '.JText::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } else { // From HTML : $letter['template_type'] == 2
            $tags = $m_emails->setTags($user_id, $post, $fnum);
            $htmldata = "";
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($current_user->name);
            $pdf->SetTitle($letter['title']);

            // set margins
            $pdf->SetMargins(5, 40, 5);
            //$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            //$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            $pdf->footer = $letter["footer"];

            //get logo
            preg_match('#src="(.*?)"#i', $letter['header'], $tab);
            $pdf->logo = JPATH_BASE.DS.$tab[1];

            preg_match('#src="(.*?)"#i', $letter['footer'], $tab);
            $pdf->logo_footer = JPATH_BASE.DS.@$tab[1];

            //get title
            //	$config =& JFactory::getConfig();
            //	$title = $config->getValue('config.sitename');
            //	$title = "";
            //	$pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $title, PDF_HEADER_STRING);

            unset($logo);
            unset($logo_footer);

            //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            //$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 8);

            //$dimensions = $pdf->getPageDimensions();

            //$htmldata .= $letter["header"];
            $letter["body"] = $m_emails->setTagsFabrik($letter["body"], array($fnum));

            $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter["body"]))));

            //$htmldata .= $letter["footer"];
            //die($htmldata);
            $pdf->AddPage();

            // Print text using writeHTMLCell()
            $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

            @chdir('tmp');

            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.pdf';
            if ($output)
                $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, $output);
            else
                $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, 'F');
            $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
            $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
        }

        if ($error == 0) {
            if ($letter['template_type'] == 4) {
                $id = $upload_file[0]->id;
            } else {

                try {

                    $query = 'INSERT INTO #__emundus_uploads (user_id, attachment_id, filename, description, can_be_deleted, can_be_viewed, campaign_id, fnum) VALUES ('.$user_id.', '.$letter['attachment_id'].', "'.$name.'","'.$training.' '.date('Y-m-d H:i:s').'", 0, 1, '.$campaign_id.', '.$db->Quote($fnum).')';
                    $db->setQuery($query);
                    $db->query();
                    $id = $db->insertid();

                } catch (Exception $e) {
                    JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
                }
            }
            $file_info['id'] = $id;
            $file_info['path'] = $path;
            $file_info['attachment_id'] = $letter['attachment_id'];
            $file_info['name'] = $attachment['value'];
            $file_info['url'] = $url;

            $files[] = $file_info;
        }
    }

    return $files;
}


// @description Generate the letter template result
// @params Applicant user ID
// @params Eligibility ID of the evaluation
// @params Code of the programme
// @params Type of output

function letter_pdf_template ($user_id, $letter_id, $fnum = null) {
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'evaluation.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $current_user 	= JFactory::getUser();
    $db 			= JFactory::getDBO();
    $config 		= JFactory::getConfig();
    $jdate 			= JFactory::getDate();

    $timezone = new DateTimeZone($config->get('offset'));
    $jdate->setTimezone($timezone);
    $now = $jdate->toSql();

    $files = array();

    $m_application 	= new EmundusModelApplication;
    $m_evaluation 	= new EmundusModelEvaluation;
    $m_emails 		= new EmundusModelEmails;


    $letters = $m_evaluation->getLettersTemplateByID($letter_id);

//print_r($letters);
    //$query = "SELECT * FROM #__emundus_setup_teaching_unity WHERE published=1 AND date_start>NOW() AND code=".$db->Quote($letters[0]['training']). " ORDER BY date_start ASC";
    try {

        $query = "SELECT * FROM #__emundus_setup_teaching_unity
					WHERE published=1 AND date_start>'".$now."' AND code IN (".$letters[0]['training'].")
					ORDER BY date_start ASC";
        $db->setQuery($query);
        $courses = $db->loadAssocList();

    } catch (Exception $e) {
        JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }

    $courses_list = '';
    $courses_fee = ' ';
    foreach ($courses as $c) {
        $ds = !empty($c['date_start']) ? date(JText::_('DATE_FORMAT_LC3'), strtotime($c['date_start'])) : JText::_('NOT_DEFINED');
        $de = !empty($c['date_end']) ? date(JText::_('DATE_FORMAT_LC3'), strtotime($c['date_end'])) : JText::_('NOT_DEFINED');
        //$courses_list .= '<li>'.$ds.' - '.$de.'</li>';
        $courses_list .= '<img src="'.JPATH_BASE.DS."media".DS."com_emundus".DS."images".DS."icones".DS."checkbox-unchecked_16x16.png".'" width="8" height="8" align="left" /> ';
        $courses_list .= $ds.' - '.$de.'<br />';
        $courses_fee  .= 'Euro '.$c['price'].'<br>';
        $programme = $c['label'];
    }

    // Extend the TCPDF class to create custom Header and Footer
    class MYPDF extends TCPDF {

        var $logo = "";
        var $logo_footer = "";
        var $footer = "";

        //Page header
        public function Header() {
            // Logo
            if (is_file($this->logo))
                $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            // Set font
            $this->SetFont('helvetica', 'B', 16);
            // Title
            $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            // footer
            $this->writeHTMLCell($w=0, $h=0, $x='', $y=250, $this->footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
            //logo
            if (is_file($this->logo_footer))
                $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

        }
    }

    //
    // Replacement
    //
    $post = array(  'TRAINING_CODE' 		=> @$letters[0]['training'],
        'TRAINING_PROGRAMME' 	=> @$programme,
        'REASON'				=> JText::_("DEPEND_OF_EVALUATION"),
        'TRAINING_FEE' 			=> @$courses_fee,
        'TRAINING_PERIODE'		=> @$courses_list
    );
    $tags = $m_emails->setTags($user_id, $post, $fnum);

    foreach ($letters as $letter) {
        $attachment = $m_application->getAttachmentByID($letter['attachment_id']);

        if ($letter['template_type'] == 1) { // Static file
            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = date('Y-m-d_H-i-s').$attachment['lbl'].'.'.$file_type[1];

            $file = JPATH_BASE.$letter['file']; //die($file);
            if (file_exists($file)) {
                $mime_type = get_mime_type($file);
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
                JError::raiseWarning( 500, JText::_( 'FILE_NOT_FOUND' ).' '.$file );
                //$this->setRedirect('index.php?option=com_emundus&view='.$view.'&Itemid='.$Itemid);
            }

        } elseif ($letter['template_type'] == 3) { // Template file .docx
            require_once JPATH_LIBRARIES.DS.'PHPWord.php';

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = date('Y-m-d_H-i-s').$attachment['lbl'].'.'.$file_type[1];

            $PHPWord = new PHPWord();

            $document = $PHPWord->loadTemplate(JPATH_BASE.$letter['file']);

            for ($i = 0; $i < count($tags['patterns']); $i++) {
                $document->setValue($tags['patterns'][$i], $tags['replacements'][$i]);
                //echo $tags['patterns'][$i]." - ".$tags['replacements'][$i]."<br>";
            }

            $document->save(JPATH_BASE.DS.'tmp'.DS.$name);

            $file = JPATH_BASE.DS.'tmp'.DS.$name; //die($file);
            if (file_exists($file)) {
                $mime_type = get_mime_type($file);
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
                JError::raiseWarning( 500, JText::_( 'FILE_NOT_FOUND' ).' '.$file );
                //$this->setRedirect('index.php?option=com_emundus&view='.$view.'&Itemid='.$Itemid);
            }

            unset($document);

        } else { // From HTML
            $htmldata = "";

            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($current_user->name);
            $pdf->SetTitle($letter['title']);

            // set margins
            $pdf->SetMargins(5, 40, 5);
            //$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            //$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            $pdf->footer = $letter["footer"];

            //get logo
            preg_match('#src="(.*?)"#i', $letter['header'], $tab);
            $pdf->logo = JPATH_BASE.DS.$tab[1];

            preg_match('#src="(.*?)"#i', $letter['footer'], $tab);
            $pdf->logo_footer = JPATH_BASE.DS.$tab[1];

            //get title
            /*	$config =& JFactory::getConfig();
                $title = $config->getValue('config.sitename');
                $title = "";
                $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $title, PDF_HEADER_STRING);*/
            unset($logo);
            unset($logo_footer);

            //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            //$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 8);

            //$dimensions = $pdf->getPageDimensions();

            //$htmldata .= $letter["header"];
            ;
            $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter["body"]))));
            //$htmldata .= $letter["footer"];
            //die($htmldata);
            $pdf->AddPage();

            // Print text using writeHTMLCell()
            $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

            @chdir('tmp');
            $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS."demo", 'I');
        }
    }
//die(print_r($files));
    exit();
}

function data_to_img($match) {
    list(, $img, $type, $base64, $end) = $match;

    $bin = base64_decode($base64);
    $md5 = md5($bin);   // generate a new temporary filename
    $fn = "tmp/$md5.$type";
    file_exists($fn) or file_put_contents($fn, $bin);

    return "$img$fn$end";  // new <img> tag
}

function getSiseCountry($country) {
    $db 			= JFactory::getDBO();

    $query = 'SELECT valeur FROM #__emundus_sise_code_pays WHERE code LIKE "'.$country.'"';
    try {
     $db->setQuery($query);
     return $db->loadResult();
    } catch (Exception $e) {
        JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }

}

function application_form_pdf($user_id, $fnum = null, $output = true, $form_post = 1, $form_ids = null, $options = null, $application_form_order = null, $profile_id = null) {
    jimport('joomla.html.parameter');
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');

    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'profile.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'files.php');

    $config = JFactory::getConfig();
    $offset = $config->get('offset');

    $m_profile 		= new EmundusModelProfile;
    $m_application 	= new EmundusModelApplication;
    $m_files		= new EmundusModelFiles;

    $db 			= JFactory::getDBO();
    $app 			= JFactory::getApplication();
    //$eMConfig 		= JComponentHelper::getParams('com_emundus');
    $current_user 	= JFactory::getUser();
    $user 			= $m_profile->getEmundusUser($user_id);
    $fnum 			= empty($fnum)?$user->fnum:$fnum;

    //$export_pdf = $eMConfig->get('export_pdf');
    //$user_profile = $m_users->getCurrentUserProfile($user_id);

    $infos = $m_profile->getFnumDetails($fnum);
    $campaign_id = $infos['campaign_id'];

    // Get form HTML
    $htmldata = '';
    $forms ='';
    if ($form_post || !empty($form_ids))
        $forms = $m_application->getFormsPDF($user_id, $fnum, $form_ids, $application_form_order, $profile_id);

    // Create PDF object
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Decision Publique');
    $pdf->SetTitle('Application Form');

    try {

        // Users informations
        $query = 'SELECT u.id AS user_id,u.email as user_email, c.civility, c.firstname, c.lastname,c.mobile_phone, a.filename AS avatar, p.label AS cb_profile, c.profile, esc.label, esc.year AS cb_schoolyear, esc.training, u.id, u.registerDate, u.email, epd.nationality, epd.birth_date, epd.street_1, epd.city_1, epd.city_other, epd.zipcode_1, epd.country_1, ed.user, ea.*
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__users AS u ON u.id=ecc.applicant_id
					LEFT JOIN #__emundus_users AS c ON u.id = c.user_id
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = '.$campaign_id.'
					LEFT JOIN #__emundus_uploads AS a ON a.user_id=u.id AND a.attachment_id = '.EMUNDUS_PHOTO_AID.' AND a.fnum like '.$db->Quote($fnum).'
					LEFT JOIN #__emundus_setup_profiles AS p ON p.id = esc.profile_id
					LEFT JOIN #__emundus_personal_detail AS epd ON epd.user = u.id AND epd.fnum like '.$db->Quote($fnum).'
					LEFT JOIN #__emundus_declaration AS ed ON ed.user = u.id AND ed.fnum like '.$db->Quote($fnum).'
					LEFT JOIN #__emundus_admission AS ea ON ed.user = u.id AND ea.fnum like '.$db->Quote($fnum).'
					WHERE ecc.fnum like '.$db->Quote($fnum).'
					ORDER BY esc.id DESC';
        $db->setQuery($query);
        $item = $db->loadObject();
    } catch (Exception $e) {
        JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }

    try {
        $query = 'SELECT valeur, code FROM #__emundus_list_profession_insee_sise';
        $db->setQuery($query);
        $profession_insee = $db->loadAssocList();
    } catch (Exception $e) {
        JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }

    $pro_list = '';
    foreach ($profession_insee as $profession) {
        $pro_list .= '<b style="color: #0081c5">'.$profession['code'].'</b> '.$profession['valeur'].'. ';
    }

    //get logo
    $template 	= $app->getTemplate(true);
    $params     = $template->params;

    if (!empty($params->get('logo')->custom->image))
        $logo   	= json_decode(str_replace("'", "\"", $params->get('logo')->custom->image), true);
    $logo 		= !empty($logo['path']) ? JPATH_ROOT.DS.$logo['path'] : "";

    // manage logo by programme
    $ext = substr($logo, -3);
    $logo_prg = substr($logo, 0, -4).'-'.$item->training.'.'.$ext;
    if (is_file($logo_prg))
        $logo = $logo_prg;

    //get title
    $title = $config->get('sitename');
    if (is_file($logo))
        $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $title, PDF_HEADER_STRING);

    unset($logo);
    unset($title);

    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, 'I', PDF_FONT_SIZE_DATA));
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_RIGHT);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    $dimensions = $pdf->getPageDimensions();
    /*** Applicant   ***/
    $htmldata .=
        '<style>
            .card { border: none; display:block; line-height:6px;}     
            .card-table-main {line-height: 4px;}       
            .blue-box { background-color: #0081c5; display: inline-block; height: 10px; width: 5%;}
            .inner-table {line-height: 5px; border: 1px solid #0081c5;}
            .title {background-color: #0081c5;}
	    </style>';


    $applicant_email = !empty($item->user_email) ? $item->user_email : $item->email;
    $applicant_city = !empty($item->city_1) ? $item->city_1 : $item->city_other;
    $tutor1_city = !empty($item->responsable_ville_1) ? $item->responsable_ville_1 : $item->responsable_ville_other_1;
    $tutor2_city = !empty($item->responsable_ville_2) ? $item->responsable_ville_2 : $item->responsable_ville_other_2;
    $financer_city = !empty($item->repondant_financier_city) ? $item->repondant_financier_city : $item->repondant_financier_city_other;

    if($campaign_id == '7') {
        $dossier_label = JText::_('DOSSIER_INSCRIPTION');
    }
    elseif ($campaign_id == '8') {
        $dossier_label = JText::_('DOSSIER_REINSCRIPTION');
    }

    if (!empty($options) && $options[0] != "" && $options[0] != "0") {
        $htmldata .= '<div class="card">
					<table width="100%"><tr>';
        if (file_exists(EMUNDUS_PATH_REL.@$item->user_id.'/tn_'.@$item->avatar) && !empty($item->avatar))
            $htmldata .= '<td width="20%"><img src="'.EMUNDUS_PATH_REL.@$item->user_id.'/tn_'.@$item->avatar.'" width="100" align="left" /></td>';
        elseif (file_exists(EMUNDUS_PATH_REL.@$item->user_id.'/'.@$item->avatar) && !empty($item->avatar))
            $htmldata .= '<td width="20%"><img src="'.EMUNDUS_PATH_REL.@$item->user_id.'/'.@$item->avatar.'" width="100" align="left" /></td>';

        $htmldata .= '
		<td width="80%">

		<div class="name"><strong>'.@$item->firstname.' '.strtoupper(@$item->lastname).'</strong>, '.@$item->label.' ('.@$item->cb_schoolyear.')</div>';

        if (isset($item->maiden_name))
            $htmldata .= '<div class="maidename">'.JText::_('MAIDEN_NAME').' : '.$item->maiden_name.'</div>';

        $date_submitted = (!empty($item->date_submitted) && !strpos($item->date_submitted, '0000'))?JHTML::_('date',$item->date_submitted):JText::_('NOT_SENT');

        // create a $dt object with the UTC timezone
        $dt = new DateTime('NOW', new DateTimeZone('UTC'));
        // change the timezone of the object without changing it's time
        $dt->setTimezone(new DateTimeZone($offset));

        if (in_array("aid", $options)) {
            $htmldata .= '<div class="nationality">'.JText::_('ID_CANDIDAT').' : '.@$item->user_id.'</div>';
        }
        if (in_array("afnum", $options)) {
            $htmldata .= '<div class="nationality">'.JText::_('FNUM').' : '.$fnum.'</div>';
        }
        if (in_array("aemail", $options)) {
            $htmldata .= '<div class="birthday">'.JText::_('EMAIL').' : '.@$item->email.'</div>';
        }
        if (in_array("aapp-sent", $options)) {
            $htmldata .= '<div class="sent">'.JText::_('APPLICATION_SENT_ON').' : '.$date_submitted.'</div>';
        }
        if (in_array("adoc-print", $options)) {
            $htmldata .= '<div class="sent">'.JText::_('DOCUMENT_PRINTED_ON').' : '.$dt->format('d/m/Y H:i').'</div>';
        }
        if (in_array("status", $options)) {
            $status = $m_files->getStatusByFnums(explode(',', $fnum));
            $htmldata .= '<div class="sent">'.JText::_('PDF_STATUS').' : '.$status[$fnum]['value'].'</div>';
        }
        if (in_array("tags", $options)) {
            $tags = $m_files->getTagsByFnum(explode(',', $fnum));
            $htmldata .='<br/><table><tr><td style="display: inline;"> ';
            foreach($tags as $tag){
                $htmldata .= '<span class="label '.$tag['class'].'" >'.$tag['label'].'</span>&nbsp;';
            }
            $htmldata .='</td></tr></table>';
        }
        $htmldata .= '</td></tr></table></div>';
    } elseif ($options[0] == "0") {

        $htmldata .= '';
    } else {
        $htmldata .= '
                    <div style="background-color: #0089d2; color: white;">
                    <div class="card" style="background-color: #0089d2; color: white;">
                    <table class="card-table-main">
                        <tr>
                            <td><img class="logo" src="/images/custom/logo-esiea.png" alt="ESIEA - Ecole d\'ingénieurs du monde numérique"></td>
                            <td style="font-size:60px;">
                                '.JText::_("FILE").'<br>   <b>'.$dossier_label.'</b><br><br>
                                <span>'.JText::_("SCHOOLYEARS").'<br>  '. str_replace('-', ' / ', $user->schoolyear).'</span>
                            </td>
                        </tr>
                    </table>
                    </div>
                    </div>
                    
                    <br>
                    
                    <table class="card-table-contact">
                        <tr>
                            <td>
                                <span><u>'.JText::_("STUDENT_CODE").'</u>: </span> <br> <b>'.$fnum.'</b><br>
                                <span><u>'.JText::_("SCHOOLYEAR").'</u>: </span><br> <b>'.@$item->label.'</b>
                            </td>
                            <td>
                                '.JText::_("ESIEA_SEND_APPLICATION_ADDRESS").'
                            </td>
                        </tr>
                    </table>    
                    <br>
                    
                    <table width="100%">
                    <tr>
                        <td  class="title" width="200px"><b style="color: white"> '.JText::_("APPLICANT_PERSONAL_DETAILS").'</b></td>
                        <td width="79px"></td>
                        <td width="30px"></td>
                        <td width="200px"><b>'.JText::_("UPDATE_INFORMATION").'</b></td>
                        <td width="109px"></td>
                    </tr>
                    <tr >
                        <td width="280px" class="inner-table">
                            <table>
                                <tr>
                                    <td><b>'. JText::_("CIVILITY") .' :</b></td>
                                    <td>'.$item->civility.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                    <td>'.$item->lastname.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                    <td>'.$item->firstname.'</td>
                                </tr>
                                 <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td>'.$item->street_1.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : '.$item->zipcode_1.'</td>
                                    <td>'. JText::_("CITY") .' : '.$applicant_city.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : '.$item->country_1.'</td>
                                    <td>'. JText::_("TELEPHONE") .' : '.$item->mobile_phone.'</td>
                                </tr>
                                <tr>'. JText::_("EMAIL") .' : '.$applicant_email.'
                                </tr>
                            </table>
                        </td>
                        <td width="30px"></td>
                        <td width="309px" class="inner-table">
                            <table>
                                <tr>
                                    <td>'. JText::_("CIVILITY") .' :</td>
                                    <td>Madame</td>
                                    <td>Monsieur</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #000;">
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : </td>
                                    <td>'. JText::_("CITY") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : </td>
                                    <td>'. JText::_("TELEPHONE") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("EMAIL") .' : </td>
                                </tr>
                            </table></td>
                    </tr>
                    
                    </table>
                    
                    <br>
                    
                    <table width="100%">
                    <tr>
                        <td class="title" width="250px"><b style="color: white"> '.JText::_("TUTOR_DETAILS").'</b></td>
                        <td width="29px"></td>
                        <td width="30px"></td>
                        <td width="200px"><b>'.JText::_("UPDATE_INFORMATION").'</b></td>
                        <td width="109px"></td>
                    </tr>
                    <tr>
                        <td width="280px" class="inner-table">
                            <table >
                                <tr>
                                    <td><b>'. JText::_("CIVILITY") .' :</b></td>
                                    <td>'.$item->responsable_civility_1.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                    <td>'.$item->responsable_nom_1.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                    <td>'.$item->responsable_prenom_1.'</td>
                                </tr>
                                 <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td>'.$item->responsable_adresse_1.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : '.$item->responsable_cp_1.'</td>
                                    <td>'. JText::_("CITY") .' : '.$tutor1_city.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : '.getSiseCountry($item->responsable_pays_1).'</td>
                                    <td>'. JText::_("TELEPHONE") .' : '.$item->responsable_telephone_1.'</td>
                                </tr>
                                <tr>'. JText::_("EMAIL") .' : '.$item->responsable_email_1.'
                                </tr><br>
                                <tr>  '. JText::_("PROFESSION") .' : '.$item->responsable_profession_1.'
                                </tr>
                            </table>
                        </td>
                        <td width="30px"></td>
                        <td width="309px" class="inner-table">
                            <table>
                                <tr>
                                    <td>'. JText::_("CIVILITY") .' :</td>
                                    <td>Madame</td>
                                    <td>Monsieur</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #000;">
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : </td>
                                    <td>'. JText::_("CITY") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : </td>
                                    <td>'. JText::_("TELEPHONE") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("EMAIL") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("PROFESSION") .' : </td>
                                </tr>
                                
                            </table></td>
                    </tr>
                    
                    </table>
                    
                     <br>
                    
                    <table width="100%">
                    <tr>
                        <td class="title" width="250px"><b style="color: white"> '.JText::_("TUTOR_DETAILS").'</b></td>
                        <td width="29px"></td>
                        <td width="30px"></td>
                        <td width="200px"><b>'.JText::_("UPDATE_INFORMATION").'</b></td>
                        <td width="109px"></td>
                    </tr>
                    <tr>
                        <td width="280px" class="inner-table">
                            <table>
                                <tr>
                                    <td><b>'. JText::_("CIVILITY") .' :</b></td>
                                    <td>'.$item->responsable_civility_2.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                    <td>'.$item->responsable_nom_2.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                    <td>'.$item->responsable_prenom_2.'</td>
                                </tr>
                                 <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td>'.$item->responsable_adresse_2.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : '.$item->responsable_cp_2.'</td>
                                    <td>'. JText::_("CITY") .' : '.$tutor2_city.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : '.getSiseCountry($item->responsable_pays_2).'</td>
                                    <td>'. JText::_("TELEPHONE") .' : '.$item->responsable_telephone_2.'</td>
                                </tr>
                                <tr>'. JText::_("EMAIL") .' : '.$item->responsable_email_2.'
                                </tr><br> 
                                <tr>  '. JText::_("PROFESSION") .' : '.$item->responsable_profession_2.'
                                </tr>
                            </table>
                        </td>
                        <td width="30px"></td>
                        <td width="309px" class="inner-table">
                            <table>
                                <tr>
                                    <td>'. JText::_("CIVILITY") .' :</td>
                                    <td>Madame</td>
                                    <td>Monsieur</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #000;">
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : </td>
                                    <td>'. JText::_("CITY") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : </td>
                                    <td>'. JText::_("TELEPHONE") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("EMAIL") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("PROFESSION") .' : </td>
                                </tr>
                                
                            </table></td>
                    </tr>
                    
                    </table>
                    <br><b style="font-size:20px;">*Se référer à la nomenclature INSEE en page 2</b> 
                    <p style="page-break-after: always;"></p>
                    
                    
                    <b style="color: #0081c5;">NOMENCLATURE INSEE</b><br>'.$pro_list.'
                    <br>
                    <br>
                    <table width="100%">
                    <tr>
                        <td class="title" width="250px"><b style="color: white"> '.JText::_("FINACER_DETAILS").'</b></td>
                        <td width="29px"></td>
                        <td width="30px"></td>
                        <td width="200px"><b>'.JText::_("UPDATE_INFORMATION").'</b></td>
                        <td width="109px"></td>
                    </tr>
                    <tr>
                        <td width="280px" class="inner-table">
                            <table>
                                <tr>
                                    <td><b>'. JText::_("CIVILITY") .' :</b></td>
                                    <td>'.$item->repondant_financier_civility.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                    <td>'.$item->repondant_financier_nom_1.'</td>
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                    <td>'.$item->repondant_financier_prenom_1.'</td>
                                </tr>
                                 <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td>'.$item->repondant_address.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : '.$item->repondant_financier_zipcode.'</td>
                                    <td>'. JText::_("CITY") .' : '.$financer_city.'</td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : '.getSiseCountry($item->repondant_financier_country).'</td>
                                    <td>'. JText::_("TELEPHONE") .' : '.$item->repondant_financier_telephone.'</td>
                                </tr>
                                <tr>'. JText::_("EMAIL") .' : '.$item->repondant_financier_email.'
                                </tr>
                            </table>
                        </td>
                        <td width="30px"></td>
                        <td width="309px" class="inner-table">
                            <table>
                                <tr>
                                    <td>'. JText::_("CIVILITY") .' :</td>
                                    <td>Madame</td>
                                    <td>Monsieur</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #000;">
                                    <td><b>'. JText::_("LASTNAME") .' :</b></td>
                                
                                </tr>
                                <tr>
                                    <td><b>'. JText::_("FIRSTNAME") .' :</b></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ADDRESS") .' :</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("ZIPCODE") .' : </td>
                                    <td>'. JText::_("CITY") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("COUNTRY") .' : </td>
                                    <td>'. JText::_("TELEPHONE") .' : </td>
                                </tr>
                                <tr>
                                    <td>'. JText::_("EMAIL") .' : </td>
                                </tr>
                            </table></td>
                    </tr>
                    
                    </table>
                    
                    <br>
                    
                    <b style="color:#0081c5; ">FRAIS DE SCOLARITÉ '. $user->schoolyear .' () </b> <br>
                    
                    
                    <table>
                        <tr><td class="title"><b style="color: white"> '.JText::_("FRAIS_INSCRIPTION_TABLE_TITLE").'</b></td><td></td></tr>
                        <tr><td width="100%">__ Je procède au virement de 1 450.00 € sur le compte :</td></tr><br>
                        <tr><td width="100%"><span style="font-size: 25px;">Banque : SOCIETE GENERALE - Titulaire : ESIEA Comptabilite - IBAN : FR76 3000 3033 5000 0372 8557 046 - BIC : SOGEFRPP</span></td></tr><br>
                        <tr><td width="100%">En indiquant la référence : <b>102065-FLEURY</b></td></tr><br> 
                        <tr><td width="100%"> __ Je joins un chèque de 1 450.00 € à l\'ordre de l\'ESIEA en indiquant la référence 102065-FLEURY au dos</td></tr><br>
                    </table>
                    <br>
                    
                    <table>
                        <tr>
                            <td class="title"><b style="color: white"> '.JText::_("SOLDE_INSCRIPTION").'</b></td><td></td></tr>
                    </table>
                    <div>
                        <ul><li><b>'.JText::_("PAY_IN_ONE_GO").'</b></li>
                            <ul>
                                <li>
                                    Par virement sur le compte :<br>
                                    <span style="font-size: 25px;">Banque : SOCIETE GENERALE - Titulaire : ESIEA Comptabilite - IBAN : FR76 3000 3033 5000 0372 8557 046 - BIC : SOGEFRPP</span><br>
                                    En indiquant la référence : 102065-FLEURY
                                </li>
                                <li>
                                    Par <b>chèque</b> à l\'ordre de l\'ESIEA en indiquant la référence <b>102065-FLEURY</b> au dos
                                </li>
                            </ul>
                            <br>
                            <li><b>En 10 fois* 700.00 € le 5 de chaque mois à compter du 5 septembre 2018 par prélèvement :</b></li>
                            <ul>
                                <li>
                                    En utilisant mon compte bancaire / mandat SEPA de l\'année passée
                                </li>
                                <li>
                                    En joignant le mandat SEPA ESIEA complété ainsi qu\'un IBAN / BIC de ma banque
                                </li>
                            </ul>
                        </ul>
                    </div>
					';
    }
    /**  END APPLICANT   ****/

    // Listes des fichiers chargés
    if (!empty($options)) {
        if (in_array("upload", $options)) {
            $uploads = $m_application->getUserAttachmentsByFnum($fnum);
            $nbuploads = 0;
            foreach ($uploads as $upload) {
                if (strrpos($upload->filename, "application_form") === false) {
                    $nbuploads++;
                }
            }
            $titleupload = $nbuploads>0?JText::_('FILES_UPLOADED'):JText::_('FILE_UPLOADED');

            $htmldata .='
			<h2>'.$titleupload.' : '.$nbuploads.'</h2>';

            $htmldata .='<div class="file_upload">';
            $htmldata .= '<ol>';
            foreach ($uploads as $upload) {
                if (strrpos($upload->filename,"application_form") === false) {
                    $path_href = JURI::base() . EMUNDUS_PATH_REL . $user_id . '/' . $upload->filename;
                    $htmldata .= '<li><b>' . $upload->value . '</b>';
                    $htmldata .= '<ul>';
                    $htmldata .= '<li><a href="' . $path_href . '" dir="ltr" target="_blank">' . $upload->filename . '</a> (' . strftime("%d/%m/%Y %H:%M", strtotime($upload->timedate)) . ')<br/><b>' . JText::_('DESCRIPTION') . '</b> : ' . $upload->description . '</li>';
                    $htmldata .= '</ul>';
                    $htmldata .= '</li>';
                }
            }
            $htmldata .='</ol></div>';
        }
    }

    $htmldata = preg_replace_callback('#(<img\s(?>(?!src=)[^>])*?src=")data:image/(gif|png|jpeg);base64,([\w=+/]++)("[^>]*>)#', "data_to_img", $htmldata);

    if (!empty($htmldata)) {
        $pdf->startTransaction();
        $start_y = $pdf->GetY();
        $start_page = $pdf->getPage();
        $pdf->writeHTMLCell(0,'','',$start_y,$htmldata,'B', 1);
        $htmldata = '';
    }

    if (!file_exists(EMUNDUS_PATH_ABS.@$item->user_id)) {
        mkdir(EMUNDUS_PATH_ABS.$item->user_id, 0777, true);
        chmod(EMUNDUS_PATH_ABS.$item->user_id, 0777);
    }

    @chdir('tmp');
    if ($output) {
        if (!isset($current_user->applicant) && @$current_user->applicant != 1) {
            $name = 'application_form_'.date('Y-m-d_H-i-s').'.pdf';
            $pdf->Output(EMUNDUS_PATH_ABS.$item->user_id.DS.$name, 'FI');
            $attachment = $m_application->getAttachmentByLbl("_application_form");
            $keys 	= array('user_id', 'attachment_id', 'filename', 'description', 'can_be_deleted', 'can_be_viewed', 'campaign_id', 'fnum' );
            $values = array($item->user_id, $attachment['id'], $name, $item->training.' '.date('Y-m-d H:i:s'), 0, 0, $campaign_id, $fnum);
            $data 	= array('key' => $keys, 'value' => $values);
            $m_application->uploadAttachment($data);
        } else
            $pdf->Output(EMUNDUS_PATH_ABS.@$item->user_id.DS.$fnum.'_application.pdf', 'FI');
    } else
        $pdf->Output(EMUNDUS_PATH_ABS.@$item->user_id.DS.$fnum.'_application.pdf', 'F');
}

function application_header_pdf($user_id, $fnum = null, $output = true, $options = null) {
    jimport( 'joomla.html.parameter' );
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');

    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'profile.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'files.php');

    $config = JFactory::getConfig();
    $offset = $config->get('offset');

    $m_profile 		= new EmundusModelProfile;
    $m_application 	= new EmundusModelApplication;
    $m_files		= new EmundusModelFiles;

    $db 			= JFactory::getDBO();
    $app 			= JFactory::getApplication();
    //$eMConfig 		= JComponentHelper::getParams('com_emundus');
    $current_user 	= JFactory::getUser();
    $user 			= $m_profile->getEmundusUser($user_id);
    $fnum 			= empty($fnum)?$user->fnum:$fnum;

    //$export_pdf = $eMConfig->get('export_pdf');
    //$user_profile = $m_users->getCurrentUserProfile($user_id);

    $infos = $m_profile->getFnumDetails($fnum);
    $campaign_id = $infos['campaign_id'];

    // Get form HTML
    $htmldata = '';

    // Create PDF object
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Decision Publique');
    $pdf->SetTitle('Application Form');

    try {

        // Users informations
        $query = 'SELECT u.id AS user_id, c.firstname, c.lastname, a.filename AS avatar, p.label AS cb_profile, c.profile, esc.label, esc.year AS cb_schoolyear, esc.training, u.id, u.registerDate, u.email, epd.gender, epd.nationality, epd.birth_date, ed.user, ecc.date_submitted
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__users AS u ON u.id=ecc.applicant_id
					LEFT JOIN #__emundus_users AS c ON u.id = c.user_id
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = '.$campaign_id.'
					LEFT JOIN #__emundus_uploads AS a ON a.user_id=u.id AND a.attachment_id = '.EMUNDUS_PHOTO_AID.' AND a.fnum like '.$db->Quote($fnum).'
					LEFT JOIN #__emundus_setup_profiles AS p ON p.id = esc.profile_id
					LEFT JOIN #__emundus_personal_detail AS epd ON epd.user = u.id AND epd.fnum like '.$db->Quote($fnum).'
					LEFT JOIN #__emundus_declaration AS ed ON ed.user = u.id AND ed.fnum like '.$db->Quote($fnum).'
					WHERE ecc.fnum like '.$db->Quote($fnum).'
					ORDER BY esc.id DESC';
        $db->setQuery($query);
        $item = $db->loadObject();

    } catch (Exception $e) {
        JLog::add('SQL error in emundus pdf library at query : '.$query, JLog::ERROR, 'com_emundus');
    }
//die(str_replace("#_", "jos", $query));


    //get logo
    $template 	= $app->getTemplate(true);
    $params     = $template->params;

    $logo   	= json_decode(str_replace("'", "\"", $params->get('logo')->custom->image), true);
    $logo 		= !empty($logo['path']) ? JPATH_ROOT.DS.$logo['path'] : "";

    // manage logo by programme
    $ext = substr($logo, -3);
    $logo_prg = substr($logo, 0, -4).'-'.$item->training.'.'.$ext;
    if (is_file($logo_prg))
        $logo = $logo_prg;

    //get title
    $title = $config->get('sitename');
    if (is_file($logo))
        $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $title, PDF_HEADER_STRING);



    unset($logo);
    unset($title);

    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, 'I', PDF_FONT_SIZE_DATA));
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();
    $dimensions = $pdf->getPageDimensions();


    /*** Applicant   ***/
    $htmldata .=
        '<style>
	.card  { border: none; display:block; line-height:80%;}
	.name  { display: block; font-size: 12pt; margin: 0 0 0 20px; padding:0; display:block; line-height:110%;}
	.maidename  { display: block; font-size: 20pt; margin: 0 0 0 20px; padding:0; }
	.nationality { display: block; margin: 0 0 0 20px;  padding:0;}
	.sent { display: block; font-family: monospace; margin: 0 0 0 10px; padding:0; text-align:right;}
	.birthday { display: block; margin: 0 0 0 20px; padding:0;}

	.label		   {white-space:nowrap; color:black; border-radius: 2px; padding:2px 2px 2px 2px; font-size: 90%; font-weight:bold; }
	.label-default {background-color:#999999;}
	.label-primary {background-color:#337ab7;}
	.label-success {background-color:#5cb85c;}
	.label-info    {background-color:#033c73;}
	.label-warning {background-color:#dd5600;}
	.label-danger  {background-color:#c71c22;}
	.label-lightpurple { background-color: #DCC6E0 }
	.label-purple { background-color: #947CB0 }
	.label-darkpurple {background-color: #663399 }
	.label-lightblue { background-color: #6bb9F0 }
	.label-blue { background-color: #19B5FE }
	.label-darkblue { background-color: #013243 }
	.label-lightgreen { background-color: #00E640 }
	.label-green { background-color: #3FC380 }
	.label-darkgreen { background-color: #1E824C }
	.label-lightyellow { background-color: #FFFD7E }
	.label-yellow { background-color: #FFFD54 }
	.label-darkyellow { background-color: #F7CA18 }
	.label-lightorange { background-color: #FABE58 }
	.label-orange { background-color: #E87E04 }
	.label-darkorange {background-color: #D35400 }
	.label-lightred { background-color: #EC644B }
	.label-red { background-color: #CF000F }
	.label-darkred { background-color: #96281B }

	</style>';
    //var_dump($options[0]);die;
    if(!empty($options) && $options[0] != "" && $options[0] != "0"){
        $htmldata .= '<div class="card">
					<table width="100%"><tr>';
        if (file_exists(EMUNDUS_PATH_REL.@$item->user_id.'/tn_'.@$item->avatar) && !empty($item->avatar))
            $htmldata .= '<td width="20%"><img src="'.EMUNDUS_PATH_REL.@$item->user_id.'/tn_'.@$item->avatar.'" width="100" align="left" /></td>';
        elseif (file_exists(EMUNDUS_PATH_REL.@$item->user_id.'/'.@$item->avatar) && !empty($item->avatar))
            $htmldata .= '<td width="20%"><img src="'.EMUNDUS_PATH_REL.@$item->user_id.'/'.@$item->avatar.'" width="100" align="left" /></td>';

        $htmldata .= '
		<td width="80%">

		<div class="name"><strong>'.@$item->firstname.' '.strtoupper(@$item->lastname).'</strong>, '.@$item->label.' ('.@$item->cb_schoolyear.')</div>';

        if (isset($item->maiden_name))
            $htmldata .= '<div class="maidename">'.JText::_('MAIDEN_NAME').' : '.$item->maiden_name.'</div>';

        $date_submitted = (!empty($item->date_submitted) && !strpos($item->date_submitted, '0000'))?JHTML::_('date',$item->date_submitted):JText::_('NOT_SENT');

        // create a $dt object with the UTC timezone
        $dt = new DateTime('NOW', new DateTimeZone('UTC'));
        // change the timezone of the object without changing it's time
        $dt->setTimezone(new DateTimeZone($offset));

        //var_dump($options);

        if(in_array("aid", $options)){
            $htmldata .= '<div class="nationality">'.JText::_('ID_CANDIDAT').' : '.@$item->user_id.'</div>';
        }
        if(in_array("afnum", $options)){
            $htmldata .= '<div class="nationality">'.JText::_('FNUM').' : '.$fnum.'</div>';
        }
        if(in_array("aemail", $options)){
            $htmldata .= '<div class="birthday">'.JText::_('EMAIL').' : '.@$item->email.'</div>';
        }
        if(in_array("aapp-sent", $options)){
            $htmldata .= '<div class="sent">'.JText::_('APPLICATION_SENT_ON').' : '.$date_submitted.'</div>';
        }
        if(in_array("adoc-print", $options)){
            $htmldata .= '<div class="sent">'.JText::_('DOCUMENT_PRINTED_ON').' : '.$dt->format('d/m/Y H:i').'</div>';
        }
        if(in_array("status", $options)){
            $status = $m_files->getStatusByFnums(explode(',', $fnum));
            $htmldata .= '<div class="sent">'.JText::_('PDF_STATUS').' : '.$status[$fnum]['value'].'</div>';
        }
        if(in_array("tags", $options)){
            $tags = $m_files->getTagsByFnum(explode(',', $fnum));
            $htmldata .='<br/><table><tr><td style="display: inline;"> ';
            foreach($tags as $tag){
                $htmldata .= '<span class="label '.$tag['class'].'" >'.$tag['label'].'</span>&nbsp;';
            }
            $htmldata .='</td></tr></table>';
        }
        $htmldata .= '</td></tr></table></div>';
    }elseif($options[0] == "0"){
        $htmldata .= '';
    }

    /**  END APPLICANT   ****/

    // Listes des fichiers chargés
    if(!empty($options)){
        if(in_array("upload", $options)){
            $uploads = $m_application->getUserAttachmentsByFnum($fnum);
            $nbuploads=0;
            foreach ($uploads as $upload) {
                if (strrpos($upload->filename, "application_form") === false) {
                    $nbuploads++;
                }
            }
            $titleupload = $nbuploads>0?JText::_('FILES_UPLOADED'):JText::_('FILE_UPLOADED');

            $htmldata .='
			<h2>'.$titleupload.' : '.$nbuploads.'</h2>';

            $htmldata .='<div class="file_upload">';
            $htmldata .= '<ol>';
            foreach ($uploads as $upload){
                if (strrpos($upload->filename,"application_form")=== false) {
                    $path_href = JURI::base() . EMUNDUS_PATH_REL . $user_id . '/' . $upload->filename;
                    $htmldata .= '<li><b>' . $upload->value . '</b>';
                    $htmldata .= '<ul>';
                    $htmldata .= '<li><a href="' . $path_href . '" dir="ltr" target="_blank">' . $upload->filename . '</a> (' . strftime("%d/%m/%Y %H:%M", strtotime($upload->timedate)) . ')<br/><b>' . JText::_('DESCRIPTION') . '</b> : ' . $upload->description . '</li>';
                    $htmldata .= '</ul>';
                    $htmldata .= '</li>';

                }

            }
            $htmldata .='</ol></div>';
        }
    }

    $htmldata = preg_replace_callback('#(<img\s(?>(?!src=)[^>])*?src=")data:image/(gif|png|jpeg);base64,([\w=+/]++)("[^>]*>)#', "data_to_img", $htmldata);


    if (!empty($htmldata)) {
        $pdf->startTransaction();
        $start_y = $pdf->GetY();
        $start_page = $pdf->getPage();
        $pdf->writeHTMLCell(0,'','',$start_y,$htmldata,'B', 1);
        $htmldata = '';
    }

    if (!file_exists(EMUNDUS_PATH_ABS.@$item->user_id)) {
        mkdir(EMUNDUS_PATH_ABS.$item->user_id, 0777, true);
        chmod(EMUNDUS_PATH_ABS.$item->user_id, 0777);
    }


    @chdir('tmp');
    if ($output) {
        if (!isset($current_user->applicant) && @$current_user->applicant != 1) {
            //$output?'FI':'F'
            $name = 'application_header_'.date('Y-m-d_H-i-s').'.pdf';
            $pdf->Output(EMUNDUS_PATH_ABS.$item->user_id.DS.$name, 'FI');
            $attachment = $m_application->getAttachmentByLbl("_application_form");
            $keys 	= array('user_id', 'attachment_id', 'filename', 'description', 'can_be_deleted', 'can_be_viewed', 'campaign_id', 'fnum' );
            $values = array($item->user_id, $attachment['id'], $name, $item->training.' '.date('Y-m-d H:i:s'), 0, 0, $campaign_id, $fnum);
            $data 	= array('key' => $keys, 'value' => $values);
            $m_application->uploadAttachment($data);

        } else
            $pdf->Output(EMUNDUS_PATH_ABS.@$item->user_id.DS.$fnum.'_header.pdf', 'FI');
    } else
        $pdf->Output(EMUNDUS_PATH_ABS.@$item->user_id.DS.$fnum.'_header.pdf', 'F');
}



/** Generate a PDF file from HTML.
 * This is a general function which takes an HTML string and builds a PDF from it.
 *
 * @param String $html The HTML to generate the pdf file from.
 * @param String $path The path to export the file to, if none is supplied a path will be generated.
 * @param String $footer HTML for the footer of the PDF.
 * @return String The path to the generated PDF or false if export fails.
 */
function generatePDFfromHTML($html, $path = null, $footer = '') {

    set_time_limit(0);
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');

    $db = JFactory::getDBO();
    $config = JFactory::getConfig();
    $app = JFactory::getApplication();

    $files = array();


    if (class_exists('MYPDF') === false || !class_exists('MYPDF')) {
        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF {

            var $logo = "";
            var $logo_footer = "";
            var $footer = "";

            //Page header
            public function Header() {
                // Logo
                if (is_file($this->logo))
                    $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                // Set font
                $this->SetFont('helvetica', 'B', 16);
                // Title
                $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // footer
                $this->writeHTMLCell($w=0, $h=0, $x='', $y=260, $this->footer.' Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</p>', $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
                //logo
                if (is_file($this->logo_footer))
                    $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

            }
        }
    }

    $error = 0;

    // Generate a random file name in case one isn't supplied.
    if (empty($path))
        $path = DS.'images'.DS.'emundus'.DS.'pdf'.substr(md5(microtime()),rand(0,26),5).'.pdf';

    if (!file_exists(dirname(JPATH_BASE.$path))) {
        mkdir(dirname(JPATH_BASE.$path), 0755, true);
        chmod(dirname(JPATH_BASE.$path), 0755);
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle(basename(JPATH_BASE.$path));
    $pdf->footer = $footer;

    // set margins
    $pdf->SetMargins(15, 40, 15);

    $pdf->SetAutoPageBreak(true, 50);
    $pdf->SetFont('helvetica', '', 8);

    $pdf->AddPage();

    $pdf->writeHTMLCell($w=0, $h=30, $x='', $y=10, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

    @chdir('tmp');

    $pdf->Output(JPATH_BASE.$path, 'F');

    if ($error == 0)
        return $path;
    else
        return false;

}

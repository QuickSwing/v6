<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @copyright  eMundus
 * @author     Hugo Moracchini
 * @since      3.8.8
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * eMundus Component Controller
 *
 * @package    Joomla
 * @subpackage Components
 */

class EmundusControllerCifre extends JControllerLegacy {

	// Initialize class variables
	var $user = null;
	var $m_cifre = null;
	var $c_messages = null;
	var $m_files = null;

	public function __construct(array $config = array()) {

		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'cifre.php');
		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'logs.php');
		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'messages.php');
		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'controllers'.DS.'messages.php');
		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'files.php');


		// Load class variables
		$this->user = JFactory::getSession()->get('emundusUser');
		$this->m_cifre = new EmundusModelCifre();
		$this->c_messages = new EmundusControllerMessages();
		$this->m_files = new EmundusModelFiles();

		parent::__construct($config);
	}


	/**
	 * Gets the type of action button to be put on the page.
	 * @param $fnum
	 *
	 * @return bool|string
	 */
	public function getActionButton($fnum) {

		// If the user is looking at his own cifre offer, no button.
		if (empty($fnum) || $this->user->id == (int)substr($fnum, -7))
			return false;

		// The contact status is the 'level' of link they have together.
		// // -1 = user has already been contacted by the other.
		// // 1 = user has contacted but not been answered.
		// // 2 = users are in contact.
		$contact_status = $this->m_cifre->getContactStatus($this->user->id, $fnum);

		// The actions of the button are dependent on the different conditions.
		if ($contact_status == -1)
			return 'reply';
		elseif ($contact_status == 1)
			return 'retry';
		elseif ($contact_status == 2)
			return 'breakup';
		else
			return 'contact';

	}

	/**
	 * Get all offers made by the current user.
	 *
	 * @param $fnum String do not get offers linked to this fnum.
	 * @return Mixed
	 */
	public function getOwnOffers($fnum = null) {
		return $this->m_cifre->getOffersByUser($this->user->id, $fnum);
	}


	/**
	 * Contact someone for their offer.
	 */
	public function contact() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

        $toAttach = [];
		$jinput = $application->input;
		$fnum = $jinput->post->get('fnum', null);
		$linkedOffer = $jinput->post->get('linkedOffer', null);
		$message = $jinput->post->getString('message', '');
		$cv = $jinput->post->getPath('CV', null);
		$ml = $jinput->post->getPath('ML', null);
		$doc = $jinput->post->getPath('DOC', null);
		$bcc = $jinput->post->getString('bcc', 'false') === 'true';

		// check if the files are on the server
        if (!empty($cv) && file_exists(JPATH_BASE.DS.$cv)) {
	        $toAttach[] = JPATH_BASE.DS.$cv;
        }

        if (!empty($ml) && file_exists(JPATH_BASE.DS.$ml)) {
            $toAttach[] = JPATH_BASE.DS.$ml;
        }

		if (!empty($doc) && file_exists(JPATH_BASE.DS.$doc)) {
			$toAttach[] = JPATH_BASE.DS.$doc;
		}
		
		if (!empty($message)) {
			$message = "Message de l'utilisateur : ".$message;
		}

		// If there is an entry in the contact table then that means we already have a link with this person
		if (!empty($this->m_cifre->getContactStatus($this->user->id, $fnum))) {
			echo json_encode((object)['status' => false, 'msg' => 'Vous avez déja contacté cette personne pour cette offre.']);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, (int)substr($fnum, -7), $fnum, 32, 'c', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST');

		// Add the contact request into the DB.
		if ($this->m_cifre->createContactRequest((int)substr($fnum, -7), $this->user->id, $fnum, $linkedOffer)) {

			// Get additional info for the fnums such as the user email.
			$fnum = $this->m_files->getFnumInfos($fnum);

			// This gets additional information about the offer, for example the title.
			$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

			// Link created: Send email.
			if (!empty($linkedOffer)) {
				
				$linkedOffer = $this->m_cifre->getOffer($linkedOffer);
				$url = JRoute::_(JURI::base()."/les-offres/consultez-les-offres/details/299/".$linkedOffer->search_engine_page);
				$post = [
					'USER_NAME' => $this->user->name,
					'LINKED_OFFER_FNUM' => $linkedOffer->fnum,
					'LINKED_OFFER_NAME' => $linkedOffer->titre,
					'OFFER_USER_NAME' => $fnum['name'],
					'OFFER_NAME' => $offerInformation->titre,
					'LINKED_OFFER_ID' => "<a href ='" . $url . "'>Voir offre</a>",
					'OFFER_MESSAGE' => $message
				];

				$email_to_send = 72;

			} else {

				$post = [
					'USER_NAME' => $this->user->name,
					'OFFER_USER_NAME' => $fnum['name'],
					'OFFER_NAME' => $offerInformation->titre,
					'OFFER_MESSAGE' => $message
				];

				$email_to_send = 71;
			}
			
			echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post, $toAttach, $bcc)]);
			exit;

		} else {
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}
	}

	/**
	 * Retry contacting someone for their offer.
	 */
	public function retry() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$fnum = $jinput->post->get('fnum', null);

		$fnum = $this->m_files->getFnumInfos($fnum);

		// If we have a link type that isn't 1 then we have not contacted them.
		if ($this->m_cifre->getContactStatus($this->user->id, $fnum['fnum']) != 1) {
			echo json_encode((object)['status' => false, 'msg' => "Vous n'avez pas contacté la personne pour cette offre ou vous etes déja en lien avec cette personne."]);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, $fnum['applicant_id'], $fnum['fnum'], 32, 'u', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_RETRY');

		// This gets additional information about the offer, for example the title.
		$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

		$post = [
			'USER_NAME' => $this->user->name,
			'OFFER_USER_NAME' => $fnum['name'],
			'OFFER_NAME' => $offerInformation->titre,
		];

		$email_to_send = 73;

		echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
		exit;
	}

	/**
	 * Retry contacting someone for their offer by link id.
	 */
	public function retrybyid() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$id = $jinput->post->get('id', null);

		if (empty($id)) {
			echo json_encode((object) ['status' => false, 'msg' => "Internal Server Error"]);
			exit;
		}

		// Get the link info from the DB.
		$link = $this->m_cifre->getLinkByID($id);

		// If we have a link type that isnt 1 then we have not contacted them.
		if ($link->state != 1) {
			echo json_encode((object) ['status' => false, 'msg' => "L'offre a laquelle vous répondez ne vous a jamais contacté."]);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, $link->user_to, $link->fnum_to, 34, 'u', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_RETRY');

		$fnum = $this->m_files->getFnumInfos($link->fnum_to);

		// This gets additional information about the offer, for example the title.
		$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

		$post = [
			'USER_NAME' => $this->user->name,
			'OFFER_USER_NAME' => $fnum['name'],
			'OFFER_NAME' => $offerInformation->titre,
		];

		$email_to_send = 73;

		echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
		exit;

	}

	/**
	 * Reply to an offer that someone has contacted you with.
	 */
	public function reply() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$fnum = $jinput->post->get('fnum', null);

		// If we have a link type that isnt -1 then we are not replying.
		if ($this->m_cifre->getContactStatus($this->user->id, $fnum) != -1) {
			echo json_encode((object)['status' => false, 'msg' => "L'offre a laquelle vous répondez ne vous a jamais contacté."]);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, (int)substr($fnum, -7), $fnum, 34, 'c', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_ACCEPTED');

		// Add the contact request into the DB.
		if ($this->m_cifre->acceptContactRequest((int)substr($fnum, -7), $this->user->id, $fnum)) {

			// Send a chat message to the user in order to start a conversation thread.
			$m_messages = new EmundusModelMessages();
			$m_messages->sendMessage((int)substr($fnum, -7), JText::_('COM_EMUNDUS_CIFRE_CONTACT_MESSAGE'), $this->user->id);

			$fnum = $this->m_files->getFnumInfos($fnum);
			
			// This gets additional information about the offer, for example the title.
			$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

			$post = [
				'USER_NAME' => $this->user->name,
				'OFFER_USER_NAME' => $fnum['name'],
				'OFFER_NAME' => $offerInformation->titre,
			];

			$email_to_send = 74;

			echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
			exit;

		} else {
			echo json_encode((object)['status' => false, 'msg' => 'Internal server error']);
			exit;
		}
	}

	/**
	 * Reply to an offer by ID.
	 */
	public function replybyid() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object) ['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$id = $jinput->post->get('id', null);

		if (empty($id)) {
			echo json_encode((object) ['status' => false, 'msg' => "Internal Server Error"]);
			exit;
		}

		// Get the link info from the DB.
		$link = $this->m_cifre->getLinkByID($id);

		// If we have a link type that isnt 1 then we are not replying.
		if ($link->state != 1) {
			echo json_encode((object) ['status' => false, 'msg' => "L'offre a laquelle vous répondez ne vous a jamais contacté."]);
			exit;
		}

		// Update the CIFRE links to the correct state.
		if (!$this->m_cifre->setLinkState($id, 2)) {
			echo json_encode((object) ['status' => false, 'msg' => "Internal Server Error"]);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, $link->user_from, $link->fnum_from, 34, 'c', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_ACCEPTED');

		// Send a chat message to the user in order to start a conversation thread.
		$m_messages = new EmundusModelMessages();
		$m_messages->sendMessage($link->user_from, JText::_('COM_EMUNDUS_CIFRE_CONTACT_MESSAGE'), $this->user->id);

		if (!empty($link->fnum_from)) {

			$fnum = $this->m_files->getFnumInfos($link->fnum_from);

			// This gets additional information about the offer, for example the title.
			$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

			$post = [
				'USER_NAME' => $this->user->name,
				'OFFER_USER_NAME' => $fnum['name'],
				'OFFER_NAME' => $offerInformation->titre,
			];

			$email_to_send = 74;

			echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
			exit;

		} else {

			$email_to_send = 76;

			$user_from = JFactory::getUser($link->user_from);

			// We cannot use the usual mailing function in c_messages because the recipient is not an fnum.
			require_once(JPATH_COMPONENT . DS . 'models' . DS . 'files.php');
			require_once(JPATH_COMPONENT . DS . 'models' . DS . 'emails.php');

			$m_messages = new EmundusModelMessages();
			$m_emails   = new EmundusModelEmails();

			$template = $m_messages->getEmail($email_to_send);

			// Get default mail sender info
			$config             = JFactory::getConfig();
			$mail_from_sys      = $config->get('mailfrom');
			$mail_from_sys_name = $config->get('fromname');

			// If no mail sender info is provided, we use the system global config.
			$mail_from_name = $this->user->name;
			$mail_from      = $template->emailfrom;

			// If the email sender has the same domain as the system sender address.
			if (substr(strrchr($mail_from, "@"), 1) === substr(strrchr($mail_from_sys, "@"), 1))
				$mail_from_address = $mail_from;
			else {
				$mail_from_address = $mail_from_sys;
				$mail_from_name    = $mail_from_sys_name;
			}

			// Set sender
			$sender = [
				$mail_from_address,
				$mail_from_name
			];

			$post = [
				'USER_NAME'       => $this->user->name,
				'OFFER_USER_NAME' => $user_from->name
			];

			// Tags are replaced with their corresponding values using the PHP preg_replace function.
			$tags    = $m_emails->setTags($user_from->id, $post);
			$subject = preg_replace($tags['patterns'], $tags['replacements'], $template->subject);
			$body    = preg_replace($tags['patterns'], $tags['replacements'], $template->message);
			if ($template != false)
				$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);

			// Configure email sender
			$mailer = JFactory::getMailer();
			$mailer->setSender($sender);
			$mailer->addReplyTo($mail_from, $mail_from_name);
			$mailer->addRecipient($user_from->email);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);


			// Send and log the email.
			$send = $mailer->Send();

			if ($send !== true) {

				JLog::add($send->__toString(), JLog::ERROR, 'com_emundus');
				echo json_encode((object) ['status' => false, 'msg' => 'Error sending email.']);
				exit;

			} else {

				$sent[] = $user_from->email;
				$log    = [
					'user_id_from' => $this->user->id,
					'user_id_to'   => $user_from->id,
					'subject'      => $subject,
					'message'      => '<i>' . JText::_('MESSAGE') . ' ' . JText::_('SENT') . ' ' . JText::_('TO') . ' ' . $user_from->email . '</i><br>' . $body,
					'type'         => $template->type
				];
				$m_emails->logEmail($log);

				// Log the email in the eMundus logging system.
				EmundusModelLogs::log($this->user->id, $user_from->id, '', 9, 'c', 'COM_EMUNDUS_LOGS_SEND_EMAIL');

				echo json_encode((object) ['status' => true]);
				exit;
			}
		}
	}

	/**
	 * Break contact with someone for their offer.
	 */
	public function breakup() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object) ['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$action = $jinput->get->get('action', 'breakup');
		$fnum   = $jinput->post->get('fnum', null);

		if (empty($this->m_cifre->getContactStatus($this->user->id, $fnum))) {
			echo json_encode((object) ['status' => false, 'msg' => "Vous n'etes pas en contact avec cette personne pour cette offre."]);
			exit;
		}

		// Log the act of having contacted the person.
		EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 34, 'd', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_DELETED');

		// Add the contact request into the DB.
		if ($this->m_cifre->deleteContactRequest((int) substr($fnum, -7), $this->user->id, $fnum)) {

			$fnum = $this->m_files->getFnumInfos($fnum);

			// This gets additional information about the offer, for example the title.
			$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

			$post = [
				'USER_NAME' => $this->user->name,
				'OFFER_USER_NAME' => $fnum['name'],
				'OFFER_NAME' => $offerInformation->titre,
			];

			// Send a different email based on the context of cancellation.
			if ($action == 'cancel')
				$email_to_send = 77;
			elseif ($action == 'ignore')
				$email_to_send = 78;
			else
				$email_to_send = 75;

			echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
			exit;

		} else {
			echo json_encode((object) ['status' => false, 'msg' => 'Internal server error']);
			exit;
		}
	}


	/**
	 * Break contact with someone for their offer.
	 */
	public function breakupbyid() {

		try {
			$application = JFactory::getApplication();
		} catch (Exception $e) {
			JLog::add('Unable to start application in c/cifre', JLog::ERROR, 'com_emundus');
			echo json_encode((object) ['status' => false, 'msg' => 'Internal server error']);
			exit;
		}

		$jinput = $application->input;
		$action = $jinput->get->get('action', 'breakup');
		$id = $jinput->post->get('id', null);

		if (empty($id)) {
			echo json_encode((object) ['status' => false, 'msg' => "Internal Server Error"]);
			exit;
		}

		// Get the link info from the DB.
		$link = $this->m_cifre->getLinkByID($id);

		if (empty($link)) {
			echo json_encode((object) ['status' => false, 'msg' => "Vous n'etes pas en contact avec cette personne pour cette offre."]);
			exit;
		}

		// Log the act of having deleted the link.
		EmundusModelLogs::log($this->user->id, $this->user->id==$link->user_from?$link->user_to:$link->user_from, $link->fnum_to, 34, 'd', 'COM_EMUNDUS_LOGS_CONTACT_REQUEST_DELETED');

		// Remove the contact request from the DB.
		if ($this->m_cifre->deleteContactRequest($link->user_to, $link->user_from, $link->fnum_to)) {

			// Either we are user_from: send to fnum_to about fnum_to
			// Or we are user_to and fnum_from exists: send to fnum_from about fnum_from
			if ($this->user->id == $link->user_from)
				$fnum = $link->fnum_to;
			else
				$fnum = $link->fnum_from;

			// Send a different email based on the context of cancellation.
			if ($action == 'cancel')
				$email_to_send = 77;
			elseif ($action == 'ignore')
				$email_to_send = 78;
			else
				$email_to_send = 75;

			// If no fnum: We are user_to and fnum_from does not exist: send to user_from about fnum_to
			if (empty($fnum)) {
				
				$fnum = $this->m_files->getFnumInfos($link->fnum_to);
				$user_from = JFactory::getUser($link->user_from);

				// This gets additional information about the offer, for example the title.
				$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

				// We cannot use the usual mailing function in c_messages because the recipient is not an fnum.
				require_once(JPATH_COMPONENT . DS . 'models' . DS . 'files.php');
				require_once(JPATH_COMPONENT . DS . 'models' . DS . 'emails.php');

				$m_messages = new EmundusModelMessages();
				$m_emails   = new EmundusModelEmails();

				$template = $m_messages->getEmail($email_to_send);

				// Get default mail sender info
				$config             = JFactory::getConfig();
				$mail_from_sys      = $config->get('mailfrom');
				$mail_from_sys_name = $config->get('fromname');

				// If no mail sender info is provided, we use the system global config.
				$mail_from_name = $this->user->name;
				$mail_from      = $template->emailfrom;

				// If the email sender has the same domain as the system sender address.
				if (substr(strrchr($mail_from, "@"), 1) === substr(strrchr($mail_from_sys, "@"), 1))
					$mail_from_address = $mail_from;
				else {
					$mail_from_address = $mail_from_sys;
					$mail_from_name    = $mail_from_sys_name;
				}

				// Set sender
				$sender = [
					$mail_from_address,
					$mail_from_name
				];

				$post = [
					'USER_NAME'       => $fnum['name'],
					'OFFER_USER_NAME' => $user_from->name,
					'OFFER_NAME'      => $offerInformation->titre,
				];

				// Tags are replaced with their corresponding values using the PHP preg_replace function.
				$tags    = $m_emails->setTags($user_from->id, $post);
				$subject = preg_replace($tags['patterns'], $tags['replacements'], $template->subject);
				$body    = preg_replace($tags['patterns'], $tags['replacements'], $template->message);
				if ($template != false)
					$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);

				// Configure email sender
				$mailer = JFactory::getMailer();
				$mailer->setSender($sender);
				$mailer->addReplyTo($mail_from, $mail_from_name);
				$mailer->addRecipient($user_from->email);
				$mailer->setSubject($subject);
				$mailer->isHTML(true);
				$mailer->Encoding = 'base64';
				$mailer->setBody($body);


				// Send and log the email.
				$send = $mailer->Send();

				if ($send !== true) {

					JLog::add($send->__toString(), JLog::ERROR, 'com_emundus');
					echo json_encode((object) ['status' => false, 'msg' => 'Error sending email.']);
					exit;

				} else {

					$sent[] = $user_from->email;
					$log = [
						'user_id_from' => $this->user->id,
						'user_id_to'   => $user_from->id,
						'subject'      => $subject,
						'message'      => '<i>'.JText::_('MESSAGE').' '.JText::_('SENT').' '.JText::_('TO').' '.$user_from->email.'</i><br>'.$body,
						'type'         => $template->type
					];
					$m_emails->logEmail($log);

					// Log the email in the eMundus logging system.
					EmundusModelLogs::log($this->user->id, $user_from->id, '', 9, 'c', 'COM_EMUNDUS_LOGS_SEND_EMAIL');

					echo json_encode((object) ['status' => true]);
					exit;
				}

				echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
				exit;

			} else {

				$fnum = $this->m_files->getFnumInfos($fnum);

				// This gets additional information about the offer, for example the title.
				$offerInformation = $this->m_cifre->getOffer($fnum['fnum']);

				$post = [
					'USER_NAME'       => $this->user->name,
					'OFFER_USER_NAME' => $fnum['name'],
					'OFFER_NAME'      => $offerInformation->titre,
				];

				echo json_encode((object)['status' => $this->c_messages->sendEmail($fnum['fnum'], $email_to_send, $post)]);
				exit;

			}

		} else {
			echo json_encode((object) ['status' => false, 'msg' => 'Internal server error']);
			exit;
		}
	}
}
<?php
/* Copyright (C) 2013-2016  Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023		Benjamin Falière		<benjamin.faliere@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/public/ticket/view.php
 *       \ingroup    ticket
 *       \brief      Public file to show one ticket
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $gestimag_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
// If this page is public (can be called outside logged session)

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$track_id = GETPOST('track_id', 'alpha');
$email    = GETPOST('email', 'email');
$suffix = "";

if (GETPOST('btn_view_ticket')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new ActionsTicket($db);

if (!isModEnabled('ticket')) {
	httponly_accessforbidden('Module Ticket not enabled');
}


/*
 * Actions
 */

if ($cancel) {
	$backtopage = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = 'view_ticket';
}

if ($action == "view_ticket" || $action == "presend" || $action == "close" || $action == "confirm_public_close" || $action == "add_message" || $action == "add_contact") {
	$error = 0;
	$display_ticket = false;
	if (!strlen($track_id)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("TicketTrackId")));
		$action = '';
	}
	if (!strlen($email)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
		$action = '';
	} else {
		if (!isValidEmail($email)) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorEmailInvalid"));
			$action = '';
		}
	}

	if (!$error) {
		$ret = $object->fetch('', '', $track_id);
		if ($ret && $object->dao->id > 0) {
			// Check if emails provided is the one of author
			$emailofticket = CMailFile::getValidAddress($object->dao->origin_email, 2);
			if (strtolower($emailofticket) == strtolower($email)) {
				$display_ticket = true;
				$_SESSION['email_customer'] = $email;
			} else {
				// Check if emails provided is inside list of contacts
				$contacts = $object->dao->liste_contact(-1, 'external');
				foreach ($contacts as $contact) {
					if (strtolower($contact['email']) == strtolower($email)) {
						$display_ticket = true;
						$_SESSION['email_customer'] = $email;
						break;
					} else {
						$display_ticket = false;
					}
				}
			}
			// Check email of thirdparty of ticket
			if ($object->dao->fk_soc > 0 || $object->dao->socid > 0) {
				$object->dao->fetch_thirdparty();
				if ($email == $object->dao->thirdparty->email) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
			// Check if email is email of creator
			if ($object->dao->fk_user_create > 0) {
				$tmpuser = new User($db);
				$tmpuser->fetch($object->dao->fk_user_create);
				if (strtolower($email) == strtolower($tmpuser->email)) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
			// Check if email is email of creator
			if ($object->dao->fk_user_assign > 0 && $object->dao->fk_user_assign != $object->dao->fk_user_create) {
				$tmpuser = new User($db);
				$tmpuser->fetch($object->dao->fk_user_assign);
				if (strtolower($email) == strtolower($tmpuser->email)) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
		} else {
			$error++;
			array_push($object->errors, $langs->trans("ErrorTicketNotFound", $track_id));
			$action = '';
		}
	}

	if (!$error && $action == 'confirm_public_close' && $display_ticket) {
		if ($object->dao->close($user)) {
			setEventMessages($langs->trans('TicketMarkedAsClosed'), null, 'mesgs');

			$url = 'view.php?action=view_ticket&track_id='.GETPOST('track_id', 'alpha').(!empty($entity) && isModEnabled('multicompany') ? '&entity='.$entity : '').'&token='.newToken();
			header("Location: ".$url);
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error && $action == "add_message" && $display_ticket && GETPOSTISSET('btn_add_message')) {
		$ret = $object->dao->newMessage($user, $action, 0, 1);

		if (!$error) {
			$action = 'view_ticket';
		}
	}

	// Add a new external contributor to a ticket
	if (!$error && $action == "add_contact" && $display_ticket && GETPOSTISSET('btn_add_contact')) {
		$ret = $object->dao->add_contact(GETPOSTINT('contactid'), 'CONTRIBUTOR');

		if (!$error) {
			$action = 'view_ticket';
		}
	}

	if ($error || !empty($object->errors)) {
		setEventMessages($object->error, $object->errors, 'errors');
		if ($action == "add_message") {
			$action = 'presend';
		} else {
			$action = '';
		}
	}
}

// Actions to send emails (for ticket, we need to manage the addfile and removefile only)
$triggersendname = 'TICKET_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_TICKET_TO'; // used to know the automatic BCC to add
if (!empty($object->dao->id)) {
	$trackid = 'tic'.$object->dao->id;
}
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';



/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('ticketpublicview', 'globalcard'));

if (!getDolGlobalString('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	echo '<div class="error">'.$langs->trans('TicketPublicInterfaceForbidden').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array(getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', '/ticket/').'css/styles.css.php');

llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);


if ($action == "view_ticket" || $action == "presend" || $action == "close" || $action == "confirm_public_close") {
	if ($display_ticket) {
		echo '<!-- public view ticket -->';
		echo '<div class="ticketpublicarea ticketlargemargin centpercent">';

		// Confirmation close
		if ($action == 'close') {
			echo $form->formconfirm($_SERVER["PHP_SELF"]."?track_id=".$track_id.(!empty($entity) && isModEnabled('multicompany') ? '&entity='.$entity : ''), $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_public_close", '', '', 1);
		}

		echo '<div id="form_view_ticket" class="margintoponly">';

		echo '<table class="ticketpublictable centpercent tableforfield">';

		// Ref
		echo '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
		echo img_picto('', 'ticket', 'class="pictofixedwidth"');
		echo dol_escape_htmltag($object->dao->ref);
		echo '</td></tr>';

		// Tracking ID
		echo '<tr><td>'.$langs->trans("TicketTrackId").'</td><td>';
		echo dol_escape_htmltag($object->dao->track_id);
		echo '</td></tr>';

		// Subject
		echo '<tr><td>'.$langs->trans("Subject").'</td><td>';
		echo '<span class="bold">';
		echo dol_escape_htmltag($object->dao->subject);
		echo '</span>';
		echo '</td></tr>';

		// Statut
		echo '<tr><td>'.$langs->trans("Status").'</td><td>';
		echo $object->dao->getLibStatut(2);
		echo '</td></tr>';

		// Type
		echo '<tr><td>'.$langs->trans("Type").'</td><td>';
		echo dol_escape_htmltag($object->dao->type_label);
		echo '</td></tr>';

		// Category
		echo '<tr><td>'.$langs->trans("Category").'</td><td>';
		if ($object->dao->category_label) {
			echo img_picto('', 'category', 'class="pictofixedwidth"');
			echo dol_escape_htmltag($object->dao->category_label);
		}
		echo '</td></tr>';

		// Severity
		echo '<tr><td>'.$langs->trans("Severity").'</td><td>';
		echo dol_escape_htmltag($object->dao->severity_label);
		echo '</td></tr>';

		// Creation date
		echo '<tr><td>'.$langs->trans("DateCreation").'</td><td>';
		echo dol_print_date($object->dao->datec, 'dayhour');
		echo '</td></tr>';

		// Author
		echo '<tr><td>'.$langs->trans("Author").'</td><td>';
		if ($object->dao->fk_user_create > 0) {
			$langs->load("users");
			$fuser = new User($db);
			$fuser->fetch($object->dao->fk_user_create);
			echo img_picto('', 'user', 'class="pictofixedwidth"');
			echo $fuser->getFullName($langs);
		} else {
			echo img_picto('', 'email', 'class="pictofixedwidth"');
			echo dol_escape_htmltag($object->dao->origin_email);
		}

		echo '</td></tr>';

		// Read date
		if (!empty($object->dao->date_read)) {
			echo '<tr><td>'.$langs->trans("TicketReadOn").'</td><td>';
			echo dol_print_date($object->dao->date_read, 'dayhour');
			echo '</td></tr>';
		}

		// Close date
		if (!empty($object->dao->date_close)) {
			echo '<tr><td>'.$langs->trans("TicketCloseOn").'</td><td>';
			echo dol_print_date($object->dao->date_close, 'dayhour');
			echo '</td></tr>';
		}

		// User assigned
		echo '<tr><td>'.$langs->trans("AssignedTo").'</td><td>';
		if ($object->dao->fk_user_assign > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->dao->fk_user_assign);
			echo img_picto('', 'user', 'class="pictofixedwidth"');
			echo $fuser->getFullName($langs, 0);
		}
		echo '</td></tr>';

		// External contributors
		if (getDolGlobalInt('TICKET_PUBLIC_DISPLAY_EXTERNAL_CONTRIBUTORS')) {
			echo '<tr><td>'.$langs->trans("ExternalContributors").'</td><td>';
			if ($object->dao->id > 0) {
				$contactlist = $object->dao->liste_contact(-1, 'external');
				foreach ($contactlist as $externalContributor) {
					echo img_picto('', 'contact', 'class="pictofixedwidth"');
					echo $externalContributor["lastname"]." ".$externalContributor["firstname"]."<br>";
				}
			}
			echo '</td></tr>';
		}

		// Add new external contributor
		if (getDolGlobalInt('TICKET_PUBLIC_SELECT_EXTERNAL_CONTRIBUTORS') && !empty($object->dao->fk_soc)) {
			echo '<form method="post" id="form_view_add_contact" name="form_view_add_contact" action="'.$_SERVER['PHP_SELF'].'?track_id='.$object->dao->track_id.'">';
			echo '<input type="hidden" name="token" value="'.newToken().'">';
			echo '<input type="hidden" name="action" value="add_contact">';
			echo '<input type="hidden" name="email" value="'.$_SESSION['email_customer'].'">';
			echo '<tr><td>'.$langs->trans("AddContributor").'</td><td>';
			//echo $form->selectcontacts($object->dao->fk_soc, '', 'contactid', 3, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400');
			echo $form->select_contact($object->dao->fk_soc, '', 'contactid', 3, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth400', true);
			echo '<input type="submit" class="button smallpaddingimp reposition" name="btn_add_contact" value="'.$langs->trans('Add').'" />';
			echo '</td></tr></form>';
		}

		// Progression
		if (getDolGlobalString('TICKET_SHOW_PROGRESSION')) {
			echo '<tr><td>'.$langs->trans("Progression").'</td><td>';
			print($object->dao->progress > 0 ? dol_escape_htmltag($object->dao->progress) : '0').'%';
			echo '</td></tr>';
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		echo '</table>';

		echo '</div>';

		echo '<div style="clear: both; margin-top: 1.5em;"></div>';

		if ($action == 'presend') {
			echo '<br>';
			echo load_fiche_titre($langs->trans('TicketAddMessage'), '', 'conversation');

			$formticket = new FormTicket($db);

			$formticket->action = "add_message";
			$formticket->track_id = $object->dao->track_id;
			$formticket->trackid = 'tic'.$object->dao->id;

			$baseurl = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

			$formticket->param = array('track_id' => $object->dao->track_id, 'fk_user_create' => '-1',
									   'returnurl' => $baseurl.'view.php'.(!empty($entity) && isModEnabled('multicompany')?'?entity='.$entity:''));

			$formticket->withfile = 2;
			$formticket->withcancel = 1;

			$formticket->showMessageForm('100%');
		}

		if ($action != 'presend') {
			$baseurl = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

			echo '<form method="POST" id="form_view_ticket_list" name="form_view_ticket_list" action="'.$baseurl.'list.php'.(!empty($entity) && isModEnabled('multicompany')?'?entity='.$entity:'').'">';
			echo '<input type="hidden" name="token" value="'.newToken().'">';
			echo '<input type="hidden" name="action" value="view_ticketlist">';
			echo '<input type="hidden" name="track_id" value="'.$object->dao->track_id.'">';
			echo '<input type="hidden" name="email" value="'.$_SESSION['email_customer'].'">';
			//echo '<input type="hidden" name="search_fk_status" value="non_closed">';
			echo "</form>\n";

			echo '<div class="tabsAction">';

			// List ticket
			echo '<div class="inline-block divButAction"><a class="left" style="padding-right: 50px" href="javascript:$(\'#form_view_ticket_list\').submit();">'.$langs->trans('ViewMyTicketList').'</a></div>';

			if ($object->dao->fk_statut < Ticket::STATUS_CLOSED) {
				// New message
				echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=presend&mode=init&track_id='.$object->dao->track_id.(!empty($entity) && isModEnabled('multicompany') ? '&entity='.$entity : '').'&token='.newToken().'">'.$langs->trans('TicketAddMessage').'</a></div>';

				// Close ticket
				if ($object->dao->fk_statut >= Ticket::STATUS_NOT_READ && $object->dao->fk_statut < Ticket::STATUS_CLOSED) {
					echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=close&track_id='.$object->dao->track_id.(!empty($entity) && isModEnabled('multicompany') ? '&entity='.$entity : '').'&token='.newToken().'">'.$langs->trans('CloseTicket').'</a></div>';
				}
			}

			echo '</div>';
		}

		echo '</div>';

		// Message list
		echo '<div class="ticketpublicarea ticketlargemargin centpercent">';
		echo load_fiche_titre($langs->trans('TicketMessagesList'), '', 'conversation');
		echo '</div>';

		$object->viewTicketMessages(false, true, $object->dao);
	} else {
		echo '<!-- public view ticket -->';
		echo '<div class="ticketpublicarea ticketlargemargin centpercent">';

		echo '<div class="error">Not Allowed<br><a href="'.$_SERVER['PHP_SELF'].'?track_id='.$object->dao->track_id.(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'" rel="nofollow noopener">'.$langs->trans('Back').'</a></div>';

		echo '</div>';
	}
} else {
	echo '<!-- public view ticket -->';
	echo '<div class="ticketpublicarea ticketlargemargin centpercent">';

	echo '<div class="center opacitymedium margintoponly marginbottomonly ticketlargemargin">'.$langs->trans("TicketPublicMsgViewLogIn").'</div>';

	echo '<div id="form_view_ticket">';
	echo '<form method="POST" class="maxwidth1000 center" name="form_view_ticket" action="'.$_SERVER['PHP_SELF'].(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'">';

	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="view_ticket">';

	echo '<p><label for="track_id" style="display: inline-block;" class="titlefieldcreate left"><span class="fieldrequired">';
	echo img_picto($langs->trans("TicketTrackId"), 'generic', 'class="pictofixedwidth"');
	echo $langs->trans("TicketTrackId").'</span></label>';
	echo '<br class="showonsmartphone hidden">';
	echo '<input class="minwidth100" id="track_id" name="track_id" value="'.(GETPOST('track_id', 'alpha') ? GETPOST('track_id', 'alpha') : '').'" />';
	echo '</p>';

	echo '<p><label for="email" style="display: inline-block;" class="titlefieldcreate left"><span class="fieldrequired">';
	echo img_picto($langs->trans("Email"), 'email', 'class="pictofixedwidth"');
	echo $langs->trans('Email').'</span></label>';
	echo '<br class="showonsmartphone hidden">';
	echo '<input class="minwidth100" id="email" name="email" value="'.(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : (!empty($_SESSION['customer_email']) ? $_SESSION['customer_email'] : "")).'" />';
	echo '</p>';

	echo '<p style="text-align: center; margin-top: 1.5em;">';
	echo '<input type="submit" class="button" name="btn_view_ticket" value="'.$langs->trans('ViewTicket').'" />';
	echo ' &nbsp; ';
	echo '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	echo "</p>\n";

	echo "</form>\n";
	echo "</div>\n";

	echo '</div>';
}

if (getDolGlobalInt('TICKET_SHOW_COMPANY_FOOTER')) {
	// End of page
	htmlPrintOnlineFooter($mysoc, $langs, 0, $suffix, $object);
}

llxFooter('', 'public');

$db->close();

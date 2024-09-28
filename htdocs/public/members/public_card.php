<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/public/members/public_card.php
 *	\ingroup    member
 * 	\brief      File to show a public card of a member
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $gestimag_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Security check
if (!isModEnabled('member')) {
	httponly_accessforbidden('Module Membership not enabled');
}


$langs->loadLangs(array("main", "members", "companies", "other"));

$id = GETPOSTINT('id');
$object = new Adherent($db);
$extrafields = new ExtraFields($db);



/*
 * Actions
 */

// None



/*
 * View
 */

$morehead = '';
if (getDolGlobalString('MEMBER_PUBLIC_CSS')) {
	$morehead = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MEMBER_PUBLIC_CSS').'">';
} else {
	$morehead = '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/eldy/style.css.php">';
}

llxHeaderVierge($langs->trans("MemberCard"), $morehead);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0) {
	$res = $object->fetch($id);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}
	$res = $object->fetch_optionals();

	echo load_fiche_titre($langs->trans("MemberCard"), '', '');

	if (empty($object->public)) {
		echo $langs->trans("ErrorThisMemberIsNotPublic");
	} else {
		echo '<table class="public_border" width="100%" cellpadding="3">';

		echo '<tr><td width="15%">'.$langs->trans("Type").'</td><td class="valeur">'.$object->type."</td></tr>\n";
		echo '<tr><td>'.$langs->trans("Person").'</td><td class="valeur">'.$object->morphy.'</td></tr>';
		echo '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" width="35%">'.$object->firstname.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$object->lastname.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Gender").'</td><td class="valeur">'.$object->gender.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->company.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($object->address).'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td class="valeur">'.$object->zip.' '.$object->town.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">'.$object->country.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.$object->email.'&nbsp;</td></tr>';
		echo '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.dol_print_date($object->birth, 'day').'</td></tr>';

		if (isset($object->photo) && $object->photo != '') {
			$form = new Form($db);
			echo '<tr><td>URL Photo</td><td class="valeur">';
			echo $form->showphoto('memberphoto', $object, 64);
			echo '</td></tr>'."\n";
		}
		//  foreach($extrafields->attributes[$object->table_element]['label'] as $key=>$value){
		//    echo "<tr><td>$value</td><td>".$object->array_options["options_$key"]."&nbsp;</td></tr>\n";
		//  }

		echo '<tr><td class="tdtop">'.$langs->trans("Comments").'</td><td class="valeur sensiblehtmlcontent">'.dol_string_onlythesehtmltags(dol_htmlcleanlastbr($object->note_public)).'</td></tr>';

		echo '</table>';
	}
}


llxFooterVierge();

$db->close();



/**
 * Show header for card member
 *
 * @param 	string		$title		Title
 * @param 	string		$head		More info into header
 * @return	void
 */
function llxHeaderVierge($title, $head = "")
{
	top_htmlhead($head, $title);

	echo '<body class="public_body">'."\n";
}

/**
* Show footer for card member
*
* @return	void
*/
function llxFooterVierge()
{
	printCommonFooter('public');

	echo "</body>\n";
	echo "</html>\n";
}

<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2017 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/adherents/type_ldap.php
 *      \ingroup    ldap
 *      \brief      Page fiche LDAP members types
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members", "ldap"));

$id = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');

// Security check
$result = restrictedArea($user, 'adherent', $id, 'adherent_type');

$object = new AdherentType($db);
$object->fetch($id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypeldapcard', 'globalcard'));

/*
 * Actions
 */


$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'gestimag2ldap') {
		$ldap = new Ldap();
		$result = $ldap->connectBind();

		if ($result > 0) {
			$object->listMembersForMemberType('', 1);

			$info = $object->_load_ldap_info();
			$dn = $object->_load_ldap_dn($info);
			$olddn = $dn; // We can say that old dn = dn as we force synchro

			$result = $ldap->update($dn, $info, $user, $olddn);
		}

		if ($result >= 0) {
			setEventMessages($langs->trans("MemberTypeSynchronized"), null, 'mesgs');
		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$head = member_type_prepare_head($object);

echo dol_get_fiche_head($head, 'ldap', $langs->trans("MemberType"), -1, 'group');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

echo '<div class="fichecenter">';
echo '<div class="underbanner clearboth"></div>';

echo '<table class="border centpercent">';

// LDAP DN
echo '<tr><td>LDAP '.$langs->trans("LDAPMemberTypeDn").'</td><td class="valeur">' . getDolGlobalString('LDAP_MEMBER_TYPE_DN')."</td></tr>\n";

// LDAP Cle
echo '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">' . getDolGlobalString('LDAP_KEY_MEMBERS_TYPES')."</td></tr>\n";

// LDAP Server
echo '<tr><td>LDAP '.$langs->trans("Type").'</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_TYPE')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("Version").'</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_HOST')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_HOST_SLAVE')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_PORT')."</td></tr>\n";

echo '</table>';

echo '</div>';

echo dol_get_fiche_end();

/*
 * Action bar
 */

echo '<div class="tabsAction">';

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	echo '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=gestimag2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

echo "</div>\n";

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
	echo "<br>\n";
}



// Display LDAP attributes
echo load_fiche_titre($langs->trans("LDAPInformationsForThisMemberType"));

echo '<table width="100%" class="noborder">';

echo '<tr class="liste_titre">';
echo '<td>'.$langs->trans("LDAPAttributes").'</td>';
echo '<td>'.$langs->trans("Value").'</td>';
echo '</tr>';

// LDAP reading
$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result > 0) {
	$info = $object->_load_ldap_info();
	$dn = $object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	$records = $ldap->getAttribute($dn, $search);

	//print_r($records);

	// Show tree
	if (((!is_numeric($records)) || $records != 0) && (!isset($records['count']) || $records['count'] > 0)) {
		if (!is_array($records)) {
			echo '<tr class="oddeven"><td colspan="2"><span class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</span></td></tr>';
		} else {
			$result = show_ldap_content($records, 0, $records['count'], true);
		}
	} else {
		echo '<tr class="oddeven"><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.dol_escape_htmltag($dn).' - search='.dol_escape_htmltag($search).')</td></tr>';
	}

	$ldap->unbind();
} else {
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}

echo '</table>';

// End of page
llxFooter();
$db->close();

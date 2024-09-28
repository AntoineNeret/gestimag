<?php
/* Copyright (C) 2020-2022	Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/public/users/view.php
 *       \ingroup    user
 *       \brief      Public file to user profile
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

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$mode     = GETPOST('mode', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$backtopage = '';

$id = GETPOSTINT('id');
$securekey = GETPOST('securekey', 'alpha');
$suffix = GETPOST('suffix');

$object = new User($db);
$object->fetch($id, '', '', 1);

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($gestimag_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

// Security check
global $conf;
$encodedsecurekey = dol_hash($conf->file->instance_unique_id.'uservirtualcard'.$object->id.'-'.$object->login, 'md5');
if ($encodedsecurekey != $securekey) {
	httponly_accessforbidden('Bad value for securitykey or public profile not enabled');
}

if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	httponly_accessforbidden('Bad value for securitykey or public profile not enabled');
}


/*
 * Actions
 */

if ($cancel) {
	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	$action = 'view';
}


/*
 * View
 */

$form = new Form($db);
$v = new vCard();

$company = $mysoc;

$modulepart = 'userphotopublic';
$dir = $conf->user->dir_output;

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logo = '';
$logosmall = '';
if (!empty($object->photo)) {
	if (dolIsAllowedForPreview($object->photo)) {
		$logosmall = get_exdir(0, 0, 0, 0, $object, 'user').'photos/'.getImageFileNameForSize($object->photo, '_small');
		$logo = get_exdir(0, 0, 0, 0, $object, 'user').'photos/'.$object->photo;
		//$originalfile = get_exdir(0, 0, 0, 0, $object, 'user').'photos/'.$object->photo;
	}
}
//echo '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($dir.'/'.$logosmall)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).($conf->entity > 1 ? '&amp;entity='.$conf->entity : '').'&amp;securekey='.urlencode($securekey).'&amp;file='.urlencode($logosmall);
	$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart='.$modulepart.($conf->entity > 1 ? '&entity='.$conf->entity : '').'&securekey='.urlencode($securekey).'&file='.urlencode($logosmall);
} elseif (!empty($logo) && is_readable($dir.'/'.$logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).($conf->entity > 1 ? '&amp;entity='.$conf->entity : '').'&amp;securekey='.urlencode($securekey).'&amp;file='.urlencode($logo);
	$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart='.$modulepart.($conf->entity > 1 ? '&entity='.$conf->entity : '').'&securekey='.urlencode($securekey).'&file='.urlencode($logo);
}

// Clean data we don't want on public page
if (getDolUserInt('USER_PUBLIC_HIDE_PHOTO', 0, $object)) {
	$logo = '';
	$logosmall = '';
	$urllogo = '';
	$urllogofull = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object)) {
	$object->job = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object)) {
	$object->email = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object)) {
	$object->job = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object)) {
	$object->office_phone = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object)) {
	$object->office_fax = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object)) {
	$object->user_mobile = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object)) {
	$object->socialnetworks = [];
}
// By default, personal address not visible
if (!getDolUserInt('USER_PUBLIC_SHOW_BIRTH', 0, $object)) {
	$object->birth = null;
}
if (!getDolUserInt('USER_PUBLIC_SHOW_ADDRESS', 0, $object)) {
	$object->address = '';
	$object->town = '';
	$object->zip = '';
	$object->state = '';
	$object->country = '';
}
if (getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object)) {
	$company = null;
}


// Output vcard
if ($mode == 'vcard') {
	// We create VCard
	$output = $v->buildVCardString($object, $company, $langs, $urllogofull);

	$filename = trim(urldecode($v->getFileName())); // "Nom prenom.vcf"
	$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
	//$filename = dol_sanitizeFileName($filename);

	top_httphead('text/vcard; name="'.$filename.'"');

	header("Content-Disposition: attachment; filename=\"".$filename."\"");
	header("Content-Length: ".dol_strlen($output));
	header("Connection: close");

	echo $output;

	$db->close();

	exit;
}

$head = '';
if (getDolGlobalString('MAIN_USER_PROFILE_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_USER_PROFILE_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	$langs->load("errors");
	echo '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $object->getFullName($langs).' - '.$langs->trans("PublicVirtualCard"), '', '', 0, 0, '', '', '', 'onlinepaymentbody'.(GETPOST('mode')=='preview' ? ' scalepreview cursorpointer virtualcardpreview' : ''), $replacemainarea, 1, 1);

echo '
<style>
@media (prefers-color-scheme: dark) {
	form {
		background-color: #CCC !important;
	}
}
</style>
';

echo '<span id="dolpaymentspan"></span>'."\n";
echo '<div class="center">'."\n";

echo '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
echo '<input type="hidden" name="token" value="'.newToken().'">'."\n";
echo '<input type="hidden" name="action" value="dosubmit">'."\n";
echo '<input type="hidden" name="securekey" value="'.$securekey.'">'."\n";
echo '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
echo "\n";

// Output html code for logo
echo '<div class="backgreypublicpayment">';
echo '<div class="logopublicpayment">';

// Name
echo '<div class="double colortext">'.$object->getFullName($langs).'</div>';
// User position
if ($object->job && !getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object)) {
	echo '<div class="">';
	echo dol_escape_htmltag($object->job);
	echo '</div>';
}
if (!getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object)) {
	echo '<div class="bold">';
	echo dol_escape_htmltag($mysoc->name);
	echo '</div>';
}



echo '</div>';
/*if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
	echo '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.gestimag.org?utm_medium=website&utm_source=poweredby" target="gestimag" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/gestimag_logo.svg" width="80px"></a></div>';
}*/
echo '</div>';


if (getDolGlobalString('USER_IMAGE_PUBLIC_INTERFACE')) {
	echo '<div class="backimagepublicrecruitment">';
	echo '<img id="idUSER_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('USER_IMAGE_PUBLIC_INTERFACE').'">';
	echo '</div>';
}

$urlforqrcode = $object->getOnlineVirtualCardUrl('vcard');

$socialnetworksdict = getArrayOfSocialNetworks();



// Show barcode
$showbarcode = GETPOST('nobarcode') ? 0 : 1;
if ($showbarcode) {
	$outdir = '/tmp';
	if ($object instanceof User) {
		$outdir = $conf->user->dir_temp;
	} elseif ($object instanceof Contact) {
		$outdir = $conf->contact->dir_temp;
	}

	$filename = $v->buildVCardString($object, $company, $langs, '', $outdir);

	echo '<br>';
	echo '<div class="floatleft inline-block valignmiddle paddingleft paddingright">';
	echo '<img style="max-width: 100%" src="'.$gestimag_main_url_root.'/viewimage.php?modulepart=barcode&entity='.((int) $conf->entity).'&generator=tcpdfbarcode&encoding=QRCODE&code='.urlencode(basename($filename)).'">';
	echo '</div>';
	echo '<br>';
}


// Me section

$usersection = '';

// User email
if ($object->email && !getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= dol_print_email($object->email, 0, 0, 1, 0, 1, 1);
	$usersection .= '</div>';
}

// User url
if ($object->url && !getDolUserInt('USER_PUBLIC_HIDE_URL', 0, $object)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'globe', 'class="pictofixedwidth"');
	$usersection .= dol_print_url($object->url, '_blank', 0, 0, '');
	$usersection .= '</div>';
}

// User phone
if ($object->office_phone && !getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'phone', 'class="pictofixedwidth"');
	$usersection .= dol_print_phone($object->office_phone, $object->country_code, 0, $mysoc->id, 'tel', ' ', 0, '');
	$usersection .= '</div>';
}
if ($object->office_fax && !getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'phoning_fax', 'class="pictofixedwidth"');
	$usersection .= dol_print_phone($object->office_fax, $object->country_code, 0, $mysoc->id, 'fax', ' ', 0, '');
	$usersection .= '</div>';
}
if ($object->user_mobile && !getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'phone', 'class="pictofixedwidth"');
	$usersection .= dol_print_phone($object->user_mobile, $object->country_code, 0, $mysoc->id, 'tel', ' ', 0, '');
	$usersection .= '</div>';
}
if (getDolUserInt('USER_PUBLIC_SHOW_BIRTH', 0, $object) && !is_null($object->birth)) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'calendar', 'class="pictofixedwidth"');
	$usersection .= dol_print_date($object->birth);
	$usersection .= '</div>';
}
if (getDolUserInt('USER_PUBLIC_SHOW_ADDRESS', 0, $object) && $object->address) {
	$usersection .= '<div class="flexitemsmall">';
	$usersection .= img_picto('', 'state', 'class="pictofixedwidth"');
	$usersection .= dol_print_address(dol_format_address($object, 0, "\n", $langs), 'map', 'user', $object->id, 1);
	$usersection .= '</div>';
}

// Social networks
if (!empty($object->socialnetworks) && is_array($object->socialnetworks) && count($object->socialnetworks) > 0) {
	if (!getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object)) {
		foreach ($object->socialnetworks as $key => $value) {
			if ($value) {
				$usersection .= '<div class="flexitemsmall">'.dol_print_socialnetworks($value, 0, $mysoc->id, $key, $socialnetworksdict).'</div>';
			}
		}
	}
}

if ($usersection) {
	// Show photo
	if ($urllogo) {
		echo '<img class="userphotopublicvcard" id="dolpaymentlogo" src="'.$urllogofull.'">';
	}

	echo '<table id="dolpaymenttable" summary="Job position offer" class="center">'."\n";

	// Output payment summary form
	echo '<tr><td class="left">';

	echo '<div class="nowidthimp nopaddingtoponsmartphone" id="tablepublicpayment">';

	echo $usersection;

	echo '</div>'."\n";
	echo "\n";

	echo '</td></tr>'."\n";

	echo '</table>'."\n";
} else {
	// Show photo
	if ($urllogo) {
		echo '<br><center><img class="userphotopublicvcard" style="position: unset !important;" id="dolpaymentlogo" src="'.$urllogofull.'"></center>';
	}
}


if (!getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object)) {
	$companysection = '';

	if ($mysoc->email) {
		$companysection .= '<div class="flexitemsmall">';
		$companysection .= img_picto('', 'email', 'class="pictofixedwidth"');
		$companysection .= dol_print_email($mysoc->email, 0, 0, 1);
		$companysection .= '</div>';
	}

	if ($mysoc->url) {
		$companysection .= '<div class="flexitemsmall">';
		$companysection .= img_picto('', 'globe', 'class="pictofixedwidth"');
		$companysection .= dol_print_url($mysoc->url, '_blank', 0, 0, '');
		$companysection .= '</div>';
	}

	if ($mysoc->phone) {
		$companysection .= '<div class="flexitemsmall">';
		$companysection .= img_picto('', 'phone', 'class="pictofixedwidth"');
		$companysection .= dol_print_phone($mysoc->phone, $mysoc->country_code, 0, $mysoc->id, 'tel', ' ', 0, '');
		$companysection .= '</div>';
	}
	if ($mysoc->fax) {
		$companysection .= '<div class="flexitemsmall">';
		$companysection .= img_picto('', 'phoning_fax', 'class="pictofixedwidth"');
		$companysection .= dol_print_phone($mysoc->fax, $mysoc->country_code, 0, $mysoc->id, 'fax', ' ', 0, '');
		$companysection .= '</div>';
	}

	// Social networks
	if (!empty($mysoc->socialnetworks) && is_array($mysoc->socialnetworks) && count($mysoc->socialnetworks) > 0) {
		foreach ($mysoc->socialnetworks as $key => $value) {
			if ($value) {
				$companysection .= '<div class="flexitemsmall wordbreak">'.dol_print_socialnetworks($value, 0, $mysoc->id, $key, $socialnetworksdict).'</div>';
			}
		}
	}

	// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
	// Define logo and logosmall
	$logosmall = $mysoc->logo_squarred_small ? $mysoc->logo_squarred_small : $mysoc->logo_small;
	$logo = $mysoc->logo_squarred ? $mysoc->logo_squarred : $mysoc->logo;
	$paramlogo = 'ONLINE_USER_LOGO_'.$suffix;
	if (getDolGlobalString($paramlogo)) {
		$logosmall = getDolGlobalString($paramlogo);
	} elseif (getDolGlobalString('ONLINE_USER_LOGO')) {
		$logosmall = getDolGlobalString('ONLINE_USER_LOGO');
	}
	//echo '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
	// Define urllogo
	$urllogo = '';
	$urllogofull = '';
	if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany'.($conf->entity > 1 ? '&amp;entity='.$conf->entity : '').'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
		$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart=mycompany'.($conf->entity > 1 ? '&entity='.$conf->entity : '').'&file='.urlencode('logos/thumbs/'.$logosmall);
	} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany'.($conf->entity > 1 ? '&amp;entity='.$conf->entity : '').'&amp;file='.urlencode('logos/'.$logo);
		$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart=mycompany'.($conf->entity > 1 ? '&entity='.$conf->entity : '').'&file='.urlencode('logos/'.$logo);
	}
	// Output html code for logo
	if ($urllogo) {
		echo '<div class="logopublicpayment center">';
		if (!empty($mysoc->url)) {
			echo '<a href="'.$mysoc->url.'" target="_blank" rel="noopener">';
		}
		echo '<img class="userphotopublicvcard" id="dolpaymentlogo" src="'.$urllogofull.'">';
		if (!empty($mysoc->url)) {
			echo '</a>';
		}
		echo '</div>';
	}
	echo '<table id="dolpaymenttable" summary="Job position offer" class="center">'."\n";

	// Output payment summary form
	echo '<tr><td class="left">';

	echo '<div class="nowidthimp nopaddingtoponsmartphone" id="tablepublicpayment">';

	// Add company info
	if ($mysoc->name) {
		echo '<div class="center bold">';
		echo dol_escape_htmltag($mysoc->name);
		echo '</div>';
		echo '<br>';
	}

	echo $companysection;

	echo '</div>'."\n";
	echo "\n";

	echo '</td></tr>'."\n";

	echo '</table>'."\n";
}


// Description
$text = getDolUserString('USER_PUBLIC_MORE', '', $object);
echo $text;


echo '</form>'."\n";
echo '</div>'."\n";
echo '<br>';


echo '<div class="backgreypublicpayment">';
echo '<div class="center">';
echo '<a href="'.$urlforqrcode.'">';
// Download / AddToContacts
echo img_picto($langs->trans("Download").' VCF', 'add').' ';
echo $langs->trans("Download").' VCF';
echo '</a>';
echo '</div>';
//echo '<div>';
//echo '</div>';
echo '</div>';

$fullexternaleurltovirtualcard = $object->getOnlineVirtualCardUrl('', 'external');
$fullinternalurltovirtualcard = $object->getOnlineVirtualCardUrl('', 'internal');

echo '<script>';
echo 'jQuery(document).ready(function() {
 	jQuery(".virtualcardpreview").click(function(event) {
 		event.preventDefault();
		console.log("We click on the card");
		window.open("'.$fullexternaleurltovirtualcard.'");
 	});
});';
echo '</script>';

llxFooter('', 'public');

$db->close();

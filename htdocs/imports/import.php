<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2022      Charlene Benke		<charlene@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       htdocs/imports/import.php
 *      \ingroup    import
 *      \brief      Pages of import Wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/import.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('exports', 'compta', 'errors', 'projects', 'admin'));

// Security check
$result = restrictedArea($user, 'import');

// Map icons, array duplicated in export.php, was not synchronized, TODO put it somewhere only once
$entitytoicon = array(
	'invoice'      => 'bill',
	'invoice_line' => 'bill',
	'order'        => 'order',
	'order_line'   => 'order',
	'propal'       => 'propal',
	'propal_line'  => 'propal',
	'intervention' => 'intervention',
	'inter_line'   => 'intervention',
	'member'       => 'user',
	'member_type'  => 'group',
	'subscription' => 'payment',
	'payment'      => 'payment',
	'tax'          => 'bill',
	'tax_type'     => 'generic',
	'other'        => 'generic',
	'account'      => 'account',
	'product'      => 'product',
	'virtualproduct' => 'product',
	'subproduct'   => 'product',
	'product_supplier_ref'      => 'product',
	'stock'        => 'stock',
	'warehouse'    => 'stock',
	'batch'        => 'stock',
	'stockbatch'   => 'stock',
	'category'     => 'category',
	'shipment'     => 'sending',
	'shipment_line' => 'sending',
	'reception' => 'sending',
	'reception_line' => 'sending',
	'expensereport' => 'trip',
	'expensereport_line' => 'trip',
	'holiday'      => 'holiday',
	'contract_line' => 'contract',
	'translation'  => 'generic',
	'bomm'         => 'bom',
	'bomline'      => 'bom'
);

// Translation code, array duplicated in export.php, was not synchronized, TODO put it somewhere only once
$entitytolang = array(
	'user'         => 'User',
	'company'      => 'Company',
	'contact'      => 'Contact',
	'invoice'      => 'Bill',
	'invoice_line' => 'InvoiceLine',
	'order'        => 'Order',
	'order_line'   => 'OrderLine',
	'propal'       => 'Proposal',
	'propal_line'  => 'ProposalLine',
	'intervention' => 'Intervention',
	'inter_line'   => 'InterLine',
	'member'       => 'Member',
	'member_type'  => 'MemberType',
	'subscription' => 'Subscription',
	'tax'          => 'SocialContribution',
	'tax_type'     => 'DictionarySocialContributions',
	'account'      => 'BankTransactions',
	'payment'      => 'Payment',
	'product'      => 'Product',
	'virtualproduct'  => 'AssociatedProducts',
	'subproduct'      => 'SubProduct',
	'product_supplier_ref'      => 'SupplierPrices',
	'service'      => 'Service',
	'stock'        => 'Stock',
	'movement'	   => 'StockMovement',
	'batch'        => 'Batch',
	'stockbatch'   => 'StockDetailPerBatch',
	'warehouse'    => 'Warehouse',
	'category'     => 'Category',
	'other'        => 'Other',
	'trip'         => 'TripsAndExpenses',
	'shipment'     => 'Shipments',
	'shipment_line' => 'ShipmentLine',
	'project'      => 'Projects',
	'projecttask'  => 'Tasks',
	'task_time'    => 'TaskTimeSpent',
	'action'       => 'Event',
	'expensereport' => 'ExpenseReport',
	'expensereport_line' => 'ExpenseReportLine',
	'holiday'      => 'TitreRequestCP',
	'contract'     => 'Contract',
	'contract_line' => 'ContractLine',
	'translation'  => 'Translation',
	'bom'          => 'BOM',
	'bomline'      => 'BOMLine'
);

$datatoimport		= GETPOST('datatoimport');
$format				= GETPOST('format');
$filetoimport		= GETPOST('filetoimport');
$action				= GETPOST('action', 'alpha');
$confirm			= GETPOST('confirm', 'alpha');
$step				= (GETPOST('step') ? GETPOST('step') : 1);
$import_name = GETPOST('import_name');
$hexa				= GETPOST('hexa');
$importmodelid = GETPOSTINT('importmodelid');
$excludefirstline = (GETPOST('excludefirstline') ? GETPOST('excludefirstline') : 2);
$endatlinenb		= (GETPOST('endatlinenb') ? GETPOST('endatlinenb') : '');
$updatekeys			= (GETPOST('updatekeys', 'array') ? GETPOST('updatekeys', 'array') : array());
$separator			= (GETPOST('separator', 'nohtml') ? GETPOST('separator', 'nohtml', 3) : '');
$enclosure			= (GETPOST('enclosure', 'nohtml') ? GETPOST('enclosure', 'nohtml') : '"');	// We must use 'nohtml' and not 'alphanohtml' because we must accept "
$charset            = GETPOST('charset', 'aZ09');
$separator_used     = str_replace('\t', "\t", $separator);


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('imports'));


$objimport = new Import($db);
$objimport->load_arrays($user, ($step == 1 ? '' : $datatoimport));

if (empty($updatekeys) && !empty($objimport->array_import_preselected_updatekeys[0])) {
	$updatekeys = $objimport->array_import_preselected_updatekeys[0];
}

$objmodelimport = new ModeleImports();

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);

// Init $array_match_file_to_database from _SESSION
if (empty($array_match_file_to_database)) {
	$serialized_array_match_file_to_database = isset($_SESSION["dol_array_match_file_to_database_select"]) ? $_SESSION["dol_array_match_file_to_database_select"] : '';
	$array_match_file_to_database = array();
	$fieldsarray = explode(',', $serialized_array_match_file_to_database);
	foreach ($fieldsarray as $elem) {
		$tabelem = explode('=', $elem, 2);
		$key = $tabelem[0];
		$val = (isset($tabelem[1]) ? $tabelem[1] : '');
		if ($key && $val) {
			$array_match_file_to_database[$key] = $val;
		}
	}
}


/*
 * Actions
 */

if ($action == 'deleteprof') {
	if (GETPOSTINT("id")) {
		$objimport->fetch(GETPOSTINT("id"));
		$result = $objimport->delete($user);
	}
}

// Save import config to database
if ($action == 'add_import_model') {
	if ($import_name) {
		// Set save string
		$hexa = '';
		foreach ($array_match_file_to_database as $key => $val) {
			if ($hexa) {
				$hexa .= ',';
			}
			$hexa .= $key.'='.$val;
		}

		$objimport->model_name = $import_name;
		$objimport->datatoimport = $datatoimport;
		$objimport->hexa = $hexa;
		$objimport->fk_user = (GETPOST('visibility', 'aZ09') == 'all' ? 0 : $user->id);

		$result = $objimport->create($user);
		if ($result >= 0) {
			setEventMessages($langs->trans("ImportModelSaved", $objimport->model_name), null, 'mesgs');
			$import_name = '';
		} else {
			$langs->load("errors");
			if ($objimport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->trans("ErrorImportDuplicateProfil"), null, 'errors');
			} else {
				setEventMessages($objimport->error, null, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ImportModelName")), null, 'errors');
	}
}

if ($step == 3 && $datatoimport) {
	if (GETPOST('sendit') && getDolGlobalString('MAIN_UPLOAD_DOC')) {
		dol_mkdir($conf->import->dir_temp);
		$nowyearmonth = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		$fullpath = $conf->import->dir_temp."/".$nowyearmonth.'-'.$_FILES['userfile']['name'];
		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath, 1) > 0) {
			dol_syslog("File ".$fullpath." was added for import");
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
		}
	}

	// Delete file
	if ($action == 'confirm_deletefile' && $confirm == 'yes') {
		$langs->load("other");

		$param = '&datatoimport='.urlencode($datatoimport).'&format='.urlencode($format);
		if ($excludefirstline) {
			$param .= '&excludefirstline='.urlencode($excludefirstline);
		}
		if ($endatlinenb) {
			$param .= '&endatlinenb='.urlencode($endatlinenb);
		}

		$file = $conf->import->dir_temp.'/'.GETPOST('urlfile');
		$ret = dol_delete_file($file);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?step='.$step.$param);
		exit;
	}
}

if ($step == 4 && $action == 'select_model') {
	// Reinit match arrays
	$_SESSION["dol_array_match_file_to_database"] = '';
	$serialized_array_match_file_to_database = '';
	$array_match_file_to_database = array();

	// Load model from $importmodelid and set $array_match_file_to_database
	// and $_SESSION["dol_array_match_file_to_database"]
	$result = $objimport->fetch($importmodelid);
	if ($result > 0) {
		$serialized_array_match_file_to_database = $objimport->hexa;
		$fieldsarray = explode(',', $serialized_array_match_file_to_database);
		foreach ($fieldsarray as $elem) {
			$tabelem = explode('=', $elem);
			$key = $tabelem[0];
			$val = $tabelem[1];
			if ($key && $val) {
				$array_match_file_to_database[$key] = $val;
			}
		}
		$_SESSION["dol_array_match_file_to_database"] = $serialized_array_match_file_to_database;
		$_SESSION['dol_array_match_file_to_database_select'] = $_SESSION["dol_array_match_file_to_database"];
	}
}
if ($action == 'saveselectorder') {
	// Enregistrement de la position des champs
	$serialized_array_match_file_to_database = '';
	dol_syslog("selectorder=".GETPOST('selectorder'), LOG_DEBUG);
	$selectorder = explode(",", GETPOST('selectorder'));
	$fieldtarget = $fieldstarget = $objimport->array_import_fields[0];
	foreach ($selectorder as $key => $code) {
		$serialized_array_match_file_to_database .= $key.'='.$code;
		$serialized_array_match_file_to_database .= ',';
	}
	$serialized_array_match_file_to_database = substr($serialized_array_match_file_to_database, 0, -1);
	dol_syslog('dol_array_match_file_to_database_select='.$serialized_array_match_file_to_database);
	$_SESSION["dol_array_match_file_to_database_select"] = $serialized_array_match_file_to_database;
	echo  "{}";
	exit(0);
}



/*
 * View
 */


$help_url = 'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones';


// STEP 1: Page to select dataset to import
if ($step == 1 || !$datatoimport) {
	// Clean saved file-database matching
	$serialized_array_match_file_to_database = '';
	$array_match_file_to_database = array();
	$_SESSION["dol_array_match_file_to_database"] = '';
	$_SESSION["dol_array_match_file_to_database_select"] = '';

	$param = '';
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if ($separator) {
		$param .= '&separator='.urlencode($separator);
	}
	if ($enclosure) {
		$param .= '&enclosure='.urlencode($enclosure);
	}

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 1);

	echo dol_get_fiche_head($head, 'step1', '', -1);

	echo '<div class="opacitymedium">'.$langs->trans("SelectImportDataSet").'</div><br>';

	// Affiche les modules d'imports
	echo '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre">';
	echo '<td>'.$langs->trans("Module").'</td>';
	echo '<td>'.$langs->trans("ImportableDatas").'</td>';
	echo '<td>&nbsp;</td>';
	echo '</tr>';

	if (count($objimport->array_import_module)) {
		$sortedarrayofmodules = dol_sort_array($objimport->array_import_module, 'position_of_profile', 'asc', 0, 0, 1);
		foreach ($sortedarrayofmodules as $key => $value) {
			//var_dump($key.' '.$value['position_of_profile'].' '.$value['import_code'].' '.$objimport->array_import_module[$key]['module']->getName().' '.$objimport->array_import_code[$key]);
			echo '<tr class="oddeven"><td>';
			$titleofmodule = $objimport->array_import_module[$key]['module']->getName();
			// Special cas for import common to module/services
			if (in_array($objimport->array_import_code[$key], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
				$titleofmodule = $langs->trans("ProductOrService");
			}
			echo $titleofmodule;
			echo '</td><td>';
			$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[$key]);
			$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
			echo img_object($objimport->array_import_module[$key]['module']->getName(), $entityicon).' ';
			echo $objimport->array_import_label[$key];
			echo '</td><td style="text-align: right">';
			if ($objimport->array_import_perms[$key]) {
				echo '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$objimport->array_import_code[$key].$param.'">'.img_picto($langs->trans("NewImport"), 'next', 'class="fa-15"').'</a>';
			} else {
				echo $langs->trans("NotEnoughPermissions");
			}
			echo '</td></tr>';
		}
	} else {
		echo '<tr><td class="oddeven" colspan="3">'.$langs->trans("NoImportableData").'</td></tr>';
	}
	echo '</table>';
	echo '</div>';

	echo dol_get_fiche_end();
}


// STEP 2: Page to select input format file
if ($step == 2 && $datatoimport) {
	$param = '&datatoimport='.urlencode($datatoimport);
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if ($separator) {
		$param .= '&separator='.urlencode($separator);
	}
	if ($enclosure) {
		$param .= '&enclosure='.urlencode($enclosure);
	}

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 2);

	echo dol_get_fiche_head($head, 'step2', '', -2);

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table class="border tableforfield centpercent">';

	// Module
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Module").'</td>';
	echo '<td>';
	$titleofmodule = $objimport->array_import_module[0]['module']->getName();
	// Special cas for import common to module/services
	if (in_array($objimport->array_import_code[0], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
		$titleofmodule = $langs->trans("ProductOrService");
	}
	echo $titleofmodule;
	echo '</td></tr>';

	// Dataset to import
	echo '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	echo '<td>';
	$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	echo img_object($objimport->array_import_module[0]['module']->getName(), $entityicon).' ';
	echo $objimport->array_import_label[0];
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo dol_get_fiche_end();

	echo '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" METHOD="POST">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';

	echo '<br>';

	echo '<span class="opacitymedium">';
	$s = $langs->trans("ChooseFormatOfFileToImport", '{s1}');
	$s = str_replace('{s1}', img_picto('', 'next'), $s);
	echo $s;
	echo '</span><br><br>';

	echo '<br>';

	echo '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	echo '<table class="noborder centpercent" cellpadding="4">';

	$filetoimport = '';

	// Add format information and link to download example
	echo '<tr class="liste_titre"><td colspan="5">';
	echo $langs->trans("FileMustHaveOneOfFollowingFormat");
	echo '</td></tr>';
	$list = $objmodelimport->listOfAvailableImportFormat($db);
	foreach ($list as $key) {
		echo '<tr class="oddeven">';
		echo '<td width="16">'.img_picto_common($key, $objmodelimport->getPictoForKey($key)).'</td>';
		$htmltext = $objmodelimport->getDriverDescForKey($key);
		echo '<td>'.$form->textwithpicto($objmodelimport->getDriverLabelForKey($key), $htmltext).'</td>';
		echo '<td style="text-align:center">';
		if (empty($objmodelimport->drivererror[$key])) {
			$filename = $langs->transnoentitiesnoconv("ExampleOfImportFile").'_'.$datatoimport.'.'.$key;
			echo '<a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$key.$param.'&output=file&file='.urlencode($filename).'" target="_blank" rel="noopener noreferrer">';
			echo img_picto('', 'download', 'class="paddingright opacitymedium"');
			echo $langs->trans("DownloadEmptyExampleShort");
			echo '</a>';
			echo $form->textwithpicto('', $langs->trans("DownloadEmptyExample").'.<br>'.$langs->trans("StarAreMandatory"));
		} else {
			echo dolPrintHTML($objmodelimport->drivererror[$key]);
		}
		echo '</td>';
		// Action button
		echo '<td style="text-align:right">';
		if (empty($objmodelimport->drivererror[$key])) {
			echo '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=3&format='.$key.$param.'">'.img_picto($langs->trans("SelectFormat"), 'next', 'class="fa-15"').'</a>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '</div>';

	echo '</form>';
}


// STEP 3: Page to select file
if ($step == 3 && $datatoimport) {
	$param = '&datatoimport='.urlencode($datatoimport).'&format='.urlencode($format);
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if ($separator) {
		$param .= '&separator='.urlencode($separator);
	}
	if ($enclosure) {
		$param .= '&enclosure='.urlencode($enclosure);
	}

	$list = $objmodelimport->listOfAvailableImportFormat($db);

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 3);

	echo dol_get_fiche_head($head, 'step3', '', -2);

	/*
	 * Confirm delete file
	 */
	if ($action == 'delete') {
		echo $form->formconfirm($_SERVER["PHP_SELF"].'?urlfile='.urlencode(GETPOST('urlfile')).'&step=3'.$param, $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table class="border tableforfield centpercent">';

	// Module
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Module").'</td>';
	echo '<td>';
	$titleofmodule = $objimport->array_import_module[0]['module']->getName();
	// Special cas for import common to module/services
	if (in_array($objimport->array_import_code[0], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
		$titleofmodule = $langs->trans("ProductOrService");
	}
	echo $titleofmodule;
	echo '</td></tr>';

	// Lot de donnees a importer
	echo '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	echo '<td>';
	$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	echo img_object($objimport->array_import_module[0]['module']->getName(), $entityicon).' ';
	echo $objimport->array_import_label[0];
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo load_fiche_titre($langs->trans("InformationOnSourceFile"), '', 'file-export');

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';
	echo '<table width="100%" class="border tableforfield">';

	// Source file format
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("SourceFileFormat").'</td>';
	echo '<td class="nowraponall">';
	$text = $objmodelimport->getDriverDescForKey($format);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	echo $form->textwithpicto($objmodelimport->getDriverLabelForKey($format), $text);
	echo '</td><td style="text-align:right" class="nowrap">';
	$filename = $langs->transnoentitiesnoconv("ExampleOfImportFile").'_'.$datatoimport.'.'.$format;
	echo '<a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$format.$param.'&output=file&file='.urlencode($filename).'" target="_blank" rel="noopener noreferrer">';
	echo img_picto('', 'download', 'class="paddingright opacitymedium"');
	echo $langs->trans("DownloadEmptyExampleShort");
	echo '</a>';
	echo $form->textwithpicto('', $langs->trans("DownloadEmptyExample").'.<br>'.$langs->trans("StarAreMandatory"));
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo dol_get_fiche_end();


	if ($format == 'xlsx' && !class_exists('XMLWriter')) {
		$langs->load("install");
		echo info_admin($langs->trans("ErrorPHPDoesNotSupport", 'php-xml'), 0, 0, 1, 'error');
	}


	echo '<br><br>';

	echo '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" method="POST">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" value="'.$step.'" name="step">';
	echo '<input type="hidden" value="'.dol_escape_htmltag($format).'" name="format">';
	echo '<input type="hidden" value="'.$excludefirstline.'" name="excludefirstline">';
	echo '<input type="hidden" value="'.$endatlinenb.'" name="endatlinenb">';
	echo '<input type="hidden" value="'.dol_escape_htmltag($separator).'" name="separator">';
	echo '<input type="hidden" value="'.dol_escape_htmltag($enclosure).'" name="enclosure">';
	echo '<input type="hidden" value="'.dol_escape_htmltag($datatoimport).'" name="datatoimport">';

	echo '<span class="opacitymedium">';
	$s = $langs->trans("ChooseFileToImport", '{s1}');
	$s = str_replace('{s1}', img_picto('', 'next'), $s);
	echo $s;
	echo '</span><br><br>';

	$filetoimport = '';

	// Input file name box
	echo '<div class="marginbottomonly">';
	$maxfilesizearray = getMaxFileSizeArray();
	$maxmin = $maxfilesizearray['maxmin'];
	if ($maxmin > 0) {
		echo '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	echo '<input type="file" name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
	$out = (!getDolGlobalString('MAIN_UPLOAD_DOC') ? ' disabled' : '');
	echo '<input type="submit" class="button small" value="'.$langs->trans("AddFile").'"'.$out.' name="sendit">';
	$out = '';
	if (getDolGlobalString('MAIN_UPLOAD_DOC')) {
		$max = getDolGlobalString('MAIN_UPLOAD_DOC'); // In Kb
		$maxphp = @ini_get('upload_max_filesize'); // In unknown
		if (preg_match('/k$/i', $maxphp)) {
			$maxphp = (int) substr($maxphp, 0, -1);
		}
		if (preg_match('/m$/i', $maxphp)) {
			$maxphp = (int) substr($maxphp, 0, -1) * 1024;
		}
		if (preg_match('/g$/i', $maxphp)) {
			$maxphp = (int) substr($maxphp, 0, -1) * 1024 * 1024;
		}
		if (preg_match('/t$/i', $maxphp)) {
			$maxphp = (int) substr($maxphp, 0, -1) * 1024 * 1024 * 1024;
		}
		$maxphp2 = @ini_get('post_max_size'); // In unknown
		if (preg_match('/k$/i', $maxphp2)) {
			$maxphp2 = (int) substr($maxphp2, 0, -1);
		}
		if (preg_match('/m$/i', $maxphp2)) {
			$maxphp2 = (int) substr($maxphp2, 0, -1) * 1024;
		}
		if (preg_match('/g$/i', $maxphp2)) {
			$maxphp2 = (int) substr($maxphp2, 0, -1) * 1024 * 1024;
		}
		if (preg_match('/t$/i', $maxphp2)) {
			$maxphp2 = (int) substr($maxphp2, 0, -1) * 1024 * 1024 * 1024;
		}
		// Now $max and $maxphp and $maxphp2 are in Kb
		$maxmin = $max;
		$maxphptoshow = $maxphptoshowparam = '';
		if ($maxphp > 0) {
			$maxmin = min($max, $maxphp);
			$maxphptoshow = $maxphp;
			$maxphptoshowparam = 'upload_max_filesize';
		}
		if ($maxphp2 > 0) {
			$maxmin = min($max, $maxphp2);
			if ($maxphp2 < $maxphp) {
				$maxphptoshow = $maxphp2;
				$maxphptoshowparam = 'post_max_size';
			}
		}

		$langs->load('other');
		$out .= ' ';
		$out .= info_admin($langs->trans("ThisLimitIsDefinedInSetup", $max, $maxphptoshow), 1);
	} else {
		$out .= ' ('.$langs->trans("UploadDisabled").')';
	}
	echo $out;
	echo '</div>';

	// Search available imports
	$filearray = dol_dir_list($conf->import->dir_temp, 'files', 0, '', '', 'name', SORT_DESC);
	if (count($filearray) > 0) {
		echo '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		echo '<table class="noborder centpercent" width="100%" cellpadding="4">';

		$dir = $conf->import->dir_temp;

		// Search available files to import
		$i = 0;
		foreach ($filearray as $key => $val) {
			$file = $val['name'];

			// readdir return value in ISO and we want UTF8 in memory
			if (!utf8_check($file)) {
				$file = mb_convert_encoding($file, 'UTF-8', 'ISO-8859-1');
			}

			if (preg_match('/^\./', $file)) {
				continue;
			}

			$modulepart = 'import';
			$urlsource = $_SERVER["PHP_SELF"].'?step='.$step.$param.'&filetoimport='.urlencode($filetoimport);
			$relativepath = $file;

			echo '<tr class="oddeven">';
			echo '<td>';
			echo img_mime($file, '', 'pictofixedwidth');
			echo '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=3'.$param.'" target="_blank" rel="noopener noreferrer">';
			echo $file;
			echo '</a>';
			echo '</td>';
			// Affiche taille fichier
			echo '<td style="text-align:right">'.dol_print_size(dol_filesize($dir.'/'.$file)).'</td>';
			// Affiche date fichier
			echo '<td style="text-align:right">'.dol_print_date(dol_filemtime($dir.'/'.$file), 'dayhour').'</td>';
			// Del button
			echo '<td style="text-align:right"><a href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&step=3'.$param.'&urlfile='.urlencode($relativepath);
			echo '">'.img_delete().'</a></td>';
			// Action button
			echo '<td style="text-align:right">';
			echo '<a href="'.$_SERVER['PHP_SELF'].'?step=4'.$param.'&filetoimport='.urlencode($relativepath).'">'.img_picto($langs->trans("NewImport"), 'next', 'class="fa-15"').'</a>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
	}

	echo '</form>';
}


// STEP 4: Page to make matching between source file and database fields
if ($step == 4 && $datatoimport) {
	//var_dump($_SESSION["dol_array_match_file_to_database_select"]);
	$serialized_array_match_file_to_database = isset($_SESSION["dol_array_match_file_to_database_select"]) ? $_SESSION["dol_array_match_file_to_database_select"] : '';
	$fieldsarray = explode(',', $serialized_array_match_file_to_database);
	$array_match_file_to_database = array();		// Same than $fieldsarray but with mapped value only  (col1 => 's.fielda', col2 => 's.fieldb'...)
	foreach ($fieldsarray as $elem) {
		$tabelem = explode('=', $elem, 2);
		$key = $tabelem[0];
		$val = (isset($tabelem[1]) ? $tabelem[1] : '');
		if ($key && $val) {
			$array_match_file_to_database[$key] = $val;
		}
	}

	//var_dump($serialized_array_match_file_to_database);
	//var_dump($fieldsarray);
	//var_dump($array_match_file_to_database);

	$model = $format;
	$list = $objmodelimport->listOfAvailableImportFormat($db);

	if (empty($separator)) {
		$separator = (!getDolGlobalString('IMPORT_CSV_SEPARATOR_TO_USE') ? ',' : $conf->global->IMPORT_CSV_SEPARATOR_TO_USE);
	}

	// The separator has been defined, if it is a unique char, we check it is valid by reading the source file
	if ($model == 'csv' && strlen($separator) == 1 && !GETPOSTISSET('separator')) {
		// Count the char in first line of file.
		$fh = fopen($conf->import->dir_temp.'/'.$filetoimport, 'r');
		if ($fh) {
			$sline = fgets($fh, 1000000);
			fclose($fh);
			$nboccurence = substr_count($sline, $separator);
			$nboccurencea = substr_count($sline, ',');
			$nboccurenceb = substr_count($sline, ';');
			//var_dump($nboccurence." ".$nboccurencea." ".$nboccurenceb);exit;
			if ($nboccurence == 0) {
				if ($nboccurencea > 2) {
					$separator = ',';
				} elseif ($nboccurenceb > 2) {
					$separator = ';';
				}
			}
		}
	}

	// The value to use
	$separator_used = str_replace('\t', "\t", $separator);

	// Create class to use for import
	$dir = DOL_DOCUMENT_ROOT."/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once $dir.$file;
	$obj = new $classname($db, $datatoimport);
	if ($model == 'csv') {
		$obj->separator = $separator_used;
		$obj->enclosure = $enclosure;
		$obj->charset = '';
	}
	if ($model == 'xlsx') {
		if (!preg_match('/\.xlsx$/i', $filetoimport)) {
			$langs->load("errors");
			$param = '&datatoimport='.$datatoimport.'&format='.$format;
			setEventMessages($langs->trans("ErrorFileMustHaveFormat", $model), null, 'errors');
			header("Location: ".$_SERVER["PHP_SELF"].'?step=3'.$param.'&filetoimport='.urlencode($relativepath));
			exit;
		}
	}

	if (GETPOST('update')) {
		$array_match_file_to_database = array();
	}

	// Load the source fields from input file into variable $arrayrecord
	$fieldssource = array();
	$result = $obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport, $langs);
	if ($result >= 0) {
		// Read first line
		$arrayrecord = $obj->import_read_record();

		// Create array $fieldssource starting with 1 with values found of first line.
		$i = 1;
		foreach ($arrayrecord as $key => $val) {
			if ($val["type"] != -1) {
				$fieldssource[$i]['example1'] = dol_trunc($val['val'], 128);
				$i++;
			} else {
				$fieldssource[$i]['example1'] = $langs->trans('Empty');
				$i++;
			}
		}
		$obj->import_close_file();
	}

	// Load targets fields in database
	$fieldstarget = $objimport->array_import_fields[0];
	$minpos = min(count($fieldssource), count($fieldstarget));
	//var_dump($array_match_file_to_database);


	$initialloadofstep4 = false;
	if (empty($_SESSION['dol_array_match_file_to_database_select'])) {
		$initialloadofstep4 = true;
	}

	// Is it a first time in page (if yes, we must initialize array_match_file_to_database)
	if (count($array_match_file_to_database) == 0) {
		// This is first input in screen, we need to define
		// $array_match_file_to_database
		// $serialized_array_match_file_to_database
		// $_SESSION["dol_array_match_file_to_database"]
		$pos = 1;
		$num = count($fieldssource);
		while ($pos <= $num) {
			if ($num >= 1 && $pos <= $num) {
				$posbis = 1;
				foreach ($fieldstarget as $key => $val) {
					if ($posbis < $pos) {
						$posbis++;
						continue;
					}
					// We found the key of targets that is at position pos
					$array_match_file_to_database[$pos] = $key;
					break;
				}
			}
			$pos++;
		}
	}
	$array_match_database_to_file = array_flip($array_match_file_to_database);
	//var_dump($array_match_database_to_file);
	//var_dump($_SESSION["dol_array_match_file_to_database_select"]);

	$fieldstarget_tmp = array();
	$arraykeysfieldtarget = array_keys($fieldstarget);
	$position = 0;
	foreach ($fieldstarget as $key => $label) {
		$isrequired = preg_match('/\*$/', $label);
		if (!empty($isrequired)) {
			$newlabel = substr($label, 0, -1);
			$fieldstarget_tmp[$key] = array("label" => $newlabel, "required" => true);
		} else {
			$fieldstarget_tmp[$key] = array("label" => $label, "required" => false);
		}
		if (!empty($array_match_database_to_file[$key])) {
			$fieldstarget_tmp[$key]["imported"] = true;
			$fieldstarget_tmp[$key]["position"] = $array_match_database_to_file[$key] - 1;
			$keytoswap = $key;
			while (!empty($array_match_database_to_file[$keytoswap])) {
				if ($position + 1 > $array_match_database_to_file[$keytoswap]) {
					$keytoswapwith = $array_match_database_to_file[$keytoswap] - 1;
					$tmp = [$keytoswap => $fieldstarget_tmp[$keytoswap]];
					unset($fieldstarget_tmp[$keytoswap]);
					$fieldstarget_tmp = arrayInsert($fieldstarget_tmp, $keytoswapwith, $tmp);
					$keytoswapwith = $arraykeysfieldtarget[$array_match_database_to_file[$keytoswap] - 1];
					$tmp = $fieldstarget_tmp[$keytoswapwith];
					unset($fieldstarget_tmp[$keytoswapwith]);
					$fieldstarget_tmp[$keytoswapwith] = $tmp;
					$keytoswap = $keytoswapwith;
				} else {
					break;
				}
			}
		} else {
			$fieldstarget_tmp[$key]["imported"] = false;
		}
		$position++;
	}
	$fieldstarget = $fieldstarget_tmp;

	//echo $serialized_array_match_file_to_database;
	//echo $_SESSION["dol_array_match_file_to_database"];
	//echo $_SESSION["dol_array_match_file_to_database_select"];
	//var_dump($array_match_file_to_database);exit;

	// Now $array_match_file_to_database contains  fieldnb(1,2,3...)=>fielddatabase(key in $array_match_file_to_database)

	$param = '&format='.$format.'&datatoimport='.urlencode($datatoimport).'&filetoimport='.urlencode($filetoimport);
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if ($separator) {
		$param .= '&separator='.urlencode($separator);
	}
	if ($enclosure) {
		$param .= '&enclosure='.urlencode($enclosure);
	}

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 4);

	echo dol_get_fiche_head($head, 'step4', '', -2);

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table class="centpercent border tableforfield">';

	// Module
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Module").'</td>';
	echo '<td>';
	$titleofmodule = $objimport->array_import_module[0]['module']->getName();
	// Special cas for import common to module/services
	if (in_array($objimport->array_import_code[0], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
		$titleofmodule = $langs->trans("ProductOrService");
	}
	echo $titleofmodule;
	echo '</td></tr>';

	// Lot de donnees a importer
	echo '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	echo '<td>';
	$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	echo img_object($objimport->array_import_module[0]['module']->getName(), $entityicon).' ';
	echo $objimport->array_import_label[0];
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo load_fiche_titre($langs->trans("InformationOnSourceFile"), '', 'file-export');

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';
	echo '<table width="100%" class="border tableforfield">';

	// Source file format
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("SourceFileFormat").'</td>';
	echo '<td>';
	$text = $objmodelimport->getDriverDescForKey($format);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	echo $form->textwithpicto($objmodelimport->getDriverLabelForKey($format), $text);
	echo '</td></tr>';

	// Separator and enclosure
	if ($model == 'csv') {
		echo '<tr><td>'.$langs->trans("CsvOptions").'</td>';
		echo '<td>';
		echo '<form method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<input type="hidden" value="'.$step.'" name="step">';
		echo '<input type="hidden" value="'.$format.'" name="format">';
		echo '<input type="hidden" value="'.$excludefirstline.'" name="excludefirstline">';
		echo '<input type="hidden" value="'.$endatlinenb.'" name="endatlinenb">';
		echo '<input type="hidden" value="'.$datatoimport.'" name="datatoimport">';
		echo '<input type="hidden" value="'.$filetoimport.'" name="filetoimport">';
		echo $langs->trans("Separator").' : ';
		echo '<input type="text" class="width25 center" name="separator" value="'.dol_escape_htmltag($separator).'"/>';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("Enclosure").' : ';
		echo '<input type="text" class="width25 center" name="enclosure" value="'.dol_escape_htmltag($enclosure).'"/> ';
		echo '<input name="update" type="submit" value="'.$langs->trans('Update').'" class="button smallpaddingimp" />';
		echo '</form>';
		echo '</td></tr>';
	}

	// File to import
	echo '<tr><td>'.$langs->trans("FileToImport").'</td>';
	echo '<td>';
	$modulepart = 'import';
	$relativepath = GETPOST('filetoimport');
	echo '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank" rel="noopener noreferrer">';
	echo img_mime($file, '', 'pictofixedwidth');
	echo $filetoimport;
	echo img_picto($langs->trans("Download"), 'download', 'class="paddingleft opacitymedium"');
	echo '</a>';
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo dol_get_fiche_end();

	echo '<br>'."\n";


	// List of source fields
	echo '<!-- List of source fields -->'."\n";
	echo '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="select_model">';
	echo '<input type="hidden" name="step" value="4">';
	echo '<input type="hidden" name="format" value="'.$format.'">';
	echo '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
	echo '<input type="hidden" name="filetoimport" value="'.$filetoimport.'">';
	echo '<input type="hidden" name="excludefirstline" value="'.$excludefirstline.'">';
	echo '<input type="hidden" name="endatlinenb" value="'.$endatlinenb.'">';
	echo '<input type="hidden" name="separator" value="'.dol_escape_htmltag($separator).'">';
	echo '<input type="hidden" name="enclosure" value="'.dol_escape_htmltag($enclosure).'">';

	// Import profile to use/load
	echo '<div class="marginbottomonly">';
	echo '<span class="opacitymedium">';
	$s = $langs->trans("SelectImportFieldsSource", '{s1}');
	$s = str_replace('{s1}', img_picto('', 'grip_title', '', false, 0, 0, '', '', 0), $s);
	echo $s;
	echo '</span> ';
	$htmlother->select_import_model($importmodelid, 'importmodelid', $datatoimport, 1, $user->id);
	echo '<input type="submit" class="button small reposition" value="'.$langs->trans("Select").'">';
	echo '</div>';
	echo '</form>';

	// Title of array with fields
	echo '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre">';
	echo '<td>'.$langs->trans("FieldsInSourceFile").'</td>';
	echo '<td>'.$langs->trans("FieldsInTargetDatabase").'</td>';
	echo '</tr>';

	//var_dump($array_match_file_to_database);

	echo '<tr valign="top"><td width="50%" class="nopaddingleftimp">';

	$fieldsplaced = array();
	$valforsourcefieldnb = array();
	$listofkeys = array();
	foreach ($array_match_file_to_database as $key => $val) {
		$listofkeys[$key] = 1;
	}

	echo "\n<!-- Box left container -->\n";
	echo '<div id="left" class="connectedSortable">'."\n";

	// List of source fields

	$lefti = 1;
	foreach ($fieldssource as $key => $val) {
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		show_elem($fieldssource, $key, $val); // key is field number in source file
		$listofkeys[$key] = 1;
		$fieldsplaced[$key] = 1;
		$valforsourcefieldnb[$lefti] = $key;
		$lefti++;

		/*if ($lefti > count($fieldstarget)) {
			break; // Other fields are in the not imported area
		}*/
	}
	//var_dump($valforsourcefieldnb);

	echo "</div>\n";
	echo "<!-- End box left container -->\n";


	echo '</td><td width="50%" class="nopaddingrightimp">';

	// Set the list of all possible target fields in Gestimag.

	$optionsall = array();
	foreach ($fieldstarget as $code => $line) {
		//var_dump($line);

		$tmparray = explode('|', $line["label"]);	// If label of field is several translation keys separated with |
		$labeltoshow = '';
		foreach ($tmparray as $tmpkey => $tmpval) {
			$labeltoshow .= ($labeltoshow ? ' '.$langs->trans('or').' ' : '').$langs->transnoentities($tmpval);
		}
		$optionsall[$code] = array('labelkey' => $line['label'], 'labelkeyarray' => $tmparray, 'label' => $labeltoshow, 'required' => (empty($line["required"]) ? 0 : 1), 'position' => !empty($line['position']) ? $line['position'] : 0);
		// TODO Get type from a new array into module descriptor.
		//$picto = 'email';
		$picto = '';
		if ($picto) {
			$optionsall[$code]['picto'] = $picto;
		}
	}
	// $optionsall is an array of all possible target fields. key=>array('label'=>..., 'xxx')

	$height = '32px'; //needs px for css height attribute below
	$i = 0;
	$mandatoryfieldshavesource = true;

	//var_dump($fieldstarget);
	//var_dump($optionsall);
	//exit;

	//var_dump($_SESSION['dol_array_match_file_to_database']);
	//var_dump($_SESSION['dol_array_match_file_to_database_select']);
	//exit;
	//var_dump($optionsall);
	//var_dump($fieldssource);
	//var_dump($fieldstarget);

	$modetoautofillmapping = 'session';		// Use setup in session
	if ($initialloadofstep4) {
		$modetoautofillmapping = 'guess';
	}
	//var_dump($modetoautofillmapping);

	echo '<table class="nobordernopadding centpercent tableimport">';
	foreach ($fieldssource as $code => $line) {	// $fieldssource is an array code=column num,  line=content on first line for column in source file.
		/*if ($i == $minpos) {
			break;
		}*/
		echo '<tr style="height:'.$height.'" class="trimport oddevenimport">';
		$entity = (!empty($objimport->array_import_entities[0][$code]) ? $objimport->array_import_entities[0][$code] : $objimport->array_import_icon[0]);

		$entityicon = !empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity; // $entityicon must string name of picto of the field like 'project', 'company', 'contact', 'modulename', ...
		$entitylang = !empty($entitytolang[$entity]) ? $entitytolang[$entity] : $objimport->array_import_label[0]; // $entitylang must be a translation key to describe object the field is related to, like 'Company', 'Contact', 'MyModyle', ...

		echo '<td class="nowraponall hideonsmartphone" style="font-weight: normal">=> </td>';
		echo '<td class="nowraponall" style="font-weight: normal">';

		//var_dump($_SESSION['dol_array_match_file_to_database_select']);
		//var_dump($_SESSION['dol_array_match_file_to_database']);

		$selectforline = '';
		$selectforline .= '<select id="selectorderimport_'.($i + 1).'" class="targetselectchange minwidth300" name="select_'.($i + 1).'">';
		if (!empty($line["imported"])) {
			$selectforline .= '<option value="-1">&nbsp;</option>';
		} else {
			$selectforline .= '<option selected="" value="-1">&nbsp;</option>';
		}

		$j = 0;
		$codeselectedarray = array();
		foreach ($optionsall as $tmpcode => $tmpval) {	// Loop on each entry to add into each combo list.
			$label = '';
			if (!empty($tmpval['picto'])) {
				$label .= img_picto('', $tmpval['picto'], 'class="pictofixedwidth"');
			}
			$label .= $tmpval['required'] ? '<strong>' : '';
			$label .= $tmpval['label'];
			$label .= $tmpval['required'] ? '*</strong>' : '';

			$tablealias = preg_replace('/(\..*)$/i', '', $tmpcode);
			$tablename = !empty($objimport->array_import_tables[0][$tablealias]) ? $objimport->array_import_tables[0][$tablealias] : "";

			$htmltext = '';

			$filecolumn = ($i + 1);
			// Source field info
			if (empty($objimport->array_import_convertvalue[0][$tmpcode])) {	// If source file does not need conversion
				$filecolumntoshow = num2Alpha($i);
			} else {
				if ($objimport->array_import_convertvalue[0][$tmpcode]['rule'] == 'fetchidfromref') {
					$htmltext .= $langs->trans("DataComeFromIdFoundFromRef", $langs->transnoentitiesnoconv($entitylang)).'<br>';
				}
				if ($objimport->array_import_convertvalue[0][$tmpcode]['rule'] == 'fetchidfromcodeid') {
					$htmltext .= $langs->trans("DataComeFromIdFoundFromCodeId", $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$tmpcode]['dict'])).'<br>';
				}
			}
			// Source required
			$example = !empty($objimport->array_import_examplevalues[0][$tmpcode]) ? $objimport->array_import_examplevalues[0][$tmpcode] : "";
			// Example
			if (empty($objimport->array_import_convertvalue[0][$tmpcode])) {	// If source file does not need conversion
				if ($example) {
					$htmltext .= $langs->trans("SourceExample").': <b>'.str_replace('"', '', $example).'</b><br>';
				}
			} else {
				if ($objimport->array_import_convertvalue[0][$tmpcode]['rule'] == 'fetchidfromref') {
					$htmltext .= $langs->trans("SourceExample").': <b>'.$langs->transnoentitiesnoconv("ExampleAnyRefFoundIntoElement", $entitylang).($example ? ' ('.$langs->transnoentitiesnoconv("Example").': '.str_replace('"', '', $example).')' : '').'</b><br>';
				} elseif ($objimport->array_import_convertvalue[0][$tmpcode]['rule'] == 'fetchidfromcodeid') {
					$htmltext .= $langs->trans("SourceExample").': <b>'.$langs->trans("ExampleAnyCodeOrIdFoundIntoDictionary", $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$tmpcode]['dict'])).($example ? ' ('.$langs->transnoentitiesnoconv("Example").': '.str_replace('"', '', $example).')' : '').'</b><br>';
				} elseif ($example) {
					$htmltext .= $langs->trans("SourceExample").': <b>'.str_replace('"', '', $example).'</b><br>';
				}
			}
			// Format control rule
			if (!empty($objimport->array_import_regex[0][$tmpcode])) {
				$htmltext .= $langs->trans("FormatControlRule").': <b>'.str_replace('"', '', $objimport->array_import_regex[0][$tmpcode]).'</b><br>';
			}

			//var_dump($htmltext);
			$htmltext .= $langs->trans("InformationOnTargetTables").': &nbsp; <b>'.$tablename."->".preg_replace('/^.*\./', '', $tmpcode)."</b>";

			$labelhtml = $label.' '.$form->textwithpicto('', $htmltext, 1, 'help', '', 1);

			$selectforline .= '<option value="'.$tmpcode.'"';
			if ($modetoautofillmapping == 'orderoftargets') {
				// The mode where we fill the preselected value of combo one by one in order of available targets fields in the declaration in descriptor file.
				if ($j == $i) {
					$selectforline .= ' selected';
				}
			} elseif ($modetoautofillmapping == 'guess') {
				// The mode where we try to guess which value to preselect from the name in first column of source file.
				// $line['example1'] is the label of the column found on first line
				$regs = array();
				if (preg_match('/^(.+)\((.+\..+)\)$/', $line['example1'], $regs)) {	// If text is "Label (x.abc)"
					$tmpstring1 = $regs[1];
					$tmpstring2 = $regs[2];
				} else {
					$tmpstring1 = $line['example1'];
					$tmpstring2 = '';
				}
				$tmpstring1 = strtolower(dol_string_unaccent(str_replace('*', '', trim($tmpstring1))));
				$tmpstring2 = strtolower(dol_string_unaccent(str_replace('*', '', trim($tmpstring2))));

				// $tmpstring1 and $tmpstring2 are string from the input file title of column "Label (fieldname)".
				// $tmpval is array of target fields read from the module import profile.
				foreach ($tmpval['labelkeyarray'] as $tmpval2) {
					$labeltarget = $langs->transnoentities($tmpval2);
					//var_dump($tmpstring1.' - '.$tmpstring2.' - '.$tmpval['labelkey'].' - '.$tmpval['label'].' - '.$tmpval2.' - '.$labeltarget);
					if ($tmpstring1 && ($tmpstring1 == $tmpcode || $tmpstring1 == strtolower($labeltarget)
						|| $tmpstring1 == strtolower(dol_string_unaccent($labeltarget)) || $tmpstring1 == strtolower($tmpval2))) {
						if (empty($codeselectedarray[$code])) {
							$selectforline .= ' selected';
							$codeselectedarray[$code] = 1;
							break;
						}
					} elseif ($tmpstring2 && ($tmpstring2 == $tmpcode || $tmpstring2 == strtolower($labeltarget)
						|| $tmpstring2 == strtolower(dol_string_unaccent($labeltarget)) || $tmpstring2 == strtolower($tmpval2))) {
						if (empty($codeselectedarray[$code])) {
							$selectforline .= ' selected';
							$codeselectedarray[$code] = 1;
							break;
						}
					}
				}
			} elseif ($modetoautofillmapping == 'session' && !empty($_SESSION['dol_array_match_file_to_database_select'])) {
				$tmpselectioninsession = dolExplodeIntoArray($_SESSION['dol_array_match_file_to_database_select'], ',', '=');
				//var_dump($code);
				if (!empty($tmpselectioninsession[(string) ($i + 1)]) && $tmpselectioninsession[(string) ($i + 1)] == $tmpcode) {
					$selectforline .= ' selected';
				}
				$selectforline .= ' data-debug="'.$tmpcode.'-'.$code.'-'.$j.'-'.(!empty($tmpselectioninsession[(string) ($i + 1)]) ? $tmpselectioninsession[(string) ($i + 1)] : "").'"';
			}
			$selectforline .= ' data-html="'.dol_escape_htmltag($labelhtml).'"';
			$selectforline .= '>';
			$selectforline .= $label;
			$selectforline .= '</options>';
			$j++;
		}
		$selectforline .= '</select>';
		$selectforline .= ajax_combobox('selectorderimport_'.($i + 1));

		echo $selectforline;

		echo '</td>';

		// Tooltip at end of line
		echo '<td class="nowraponall" style="font-weight:normal; text-align:right">';

		// Source field info
		$htmltext = '<b><u>'.$langs->trans("FieldSource").'</u></b><br>';
		$filecolumntoshow = num2Alpha($i);
		$htmltext .= $langs->trans("DataComeFromFileFieldNb", $filecolumntoshow).'<br>';

		echo $form->textwithpicto('', $htmltext);

		echo '</td>';
		echo '</tr>';
		$i++;
	}
	echo '</table>';

	echo '</td></tr>';

	// Lines for remark
	echo '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Note").'</td></tr>';
	echo '<tr><td colspan="2"><div id="div-mandatory-target-fields-not-mapped"></div></td></tr>';

	echo '</table>';
	echo '</div>';


	if (!empty($conf->use_javascript_ajax)) {
		echo '<script type="text/javascript">'."\n";
		echo 'var previousselectedvalueimport = "0";'."\n";
		echo 'var previousselectedlabelimport = "0";'."\n";
		echo 'var arrayofselectedvalues = [];'."\n";
		echo 'var arrayoftargetfields = [];'."\n";
		echo 'var arrayoftargetmandatoryfields = [];'."\n";

		// Loop on $fieldstarget (seems sorted by 'position') to store php array into javascript array
		$tmpi = 0;
		foreach ($fieldstarget as $key => $val) {
			echo "arrayoftargetfields[".$tmpi."] = '".dol_escape_js($langs->trans($val['label']))."'; ";
			if ($val['required']) {
				echo "arrayoftargetmandatoryfields[".$tmpi."] = '".dol_escape_js($key)."'; ";
			}
			$tmpi++;
		}
		echo "\n";

		echo '$(document).ready(function () {'."\n";

		echo 'setOptionsToDisabled();'."\n";
		echo 'saveSelection();'."\n";

		echo '$(".targetselectchange").focus(function(){'."\n";
		echo '		previousselectedvalueimport = $(this).val();'."\n";
		echo '		previousselectedlabelimport = $(this).children("option:selected").text();'."\n";
		echo '		console.log("previousselectedvalueimport="+previousselectedvalueimport)'."\n";
		echo '})'."\n";

		// Function to set the disabled flag
		// - We set all option to "enabled"
		// - Then we scan all combo to get the value currently selected and save them into the array arrayofselectedvalues
		// - Then we set to disabled all fields that are selected
		echo 'function setOptionsToDisabled() {'."\n";
		echo '		console.log("Remove the disabled flag everywhere");'."\n";
		echo '		$("select.targetselectchange").not($( this )).find(\'option\').prop("disabled", false);'."\n";	// Enable all options
		echo '		arrayofselectedvalues = [];'."\n";

		echo '		$("select.targetselectchange").each(function(){'."\n";
		echo '			id = $(this).attr(\'id\')'."\n";
		echo '			value = $(this).val()'."\n";
		echo '         console.log("a selected value has been found for component "+id+" = "+value);'."\n";
		echo '			arrayofselectedvalues.push(value);'."\n";
		echo '		});'."\n";

		echo '		console.log("List of all selected values arrayofselectedvalues");'."\n";
		echo '		console.log(arrayofselectedvalues);'."\n";
		echo '     console.log("Set the option to disabled for every entry that is currently selected somewhere else (so into arrayofselectedvalues)");'."\n";

		echo '     $.each(arrayofselectedvalues, function(key, value) {'."\n";	// Loop on each selected value
		echo '         if (value != -1) {'."\n";
		echo '     		console.log("Process key="+key+" value="+value+" to disable.");'."\n";
		echo '				$("select.targetselectchange").find(\'option[value="\'+value+\'"]:not(:selected)\').prop("disabled", true);'."\n";	// Set to disabled except if currently selected
		echo '         }'."\n";
		echo '     });'."\n";
		echo '}'."\n";

		// Function to save the selection in database
		echo 'function saveSelection() {'."\n";
		//echo '		console.log(arrayofselectedvalues);'."\n";
		echo '		arrayselectedfields = [];'."\n";
		echo '		arrayselectedfields.push(0);'."\n";

		echo '     $.each( arrayofselectedvalues, function( key, value ) {'."\n";
		echo '         if (value != -1) {'."\n";
		echo '				arrayselectedfields.push(value);'."\n";
		echo '			} else {'."\n";
		echo '				arrayselectedfields.push(0);'."\n";
		echo '			}'."\n";
		echo '		});'."\n";

		echo "		$.ajax({\n";
		echo "			type: 'POST',\n";
		echo "			dataType: 'json',\n";
		echo "			url: '".dol_escape_js($_SERVER["PHP_SELF"])."?action=saveselectorder&token=".newToken()."',\n";
		echo "			data: 'selectorder='+arrayselectedfields.toString(),\n";
		echo "			success: function(){\n";
		echo "				console.log('The selected fields have been saved into '+arrayselectedfields.toString());\n";
		echo "			},\n";
		echo '		});'."\n";

		// Now we loop on all target fields that are mandatory to show if they are not mapped yet.
		echo '     console.log("arrayselectedfields");';
		echo '     console.log(arrayselectedfields);';
		echo '     console.log("arrayoftargetmandatoryfields");';
		echo '     console.log(arrayoftargetmandatoryfields);';
		echo "     listtoshow = '';";
		echo "     nbelement = arrayoftargetmandatoryfields.length
					for (let i = 0; i < nbelement; i++) {
						if (arrayoftargetmandatoryfields[i] && ! arrayselectedfields.includes(arrayoftargetmandatoryfields[i])) {
							console.log(arrayoftargetmandatoryfields[i]+' not mapped');
							listtoshow = listtoshow + (listtoshow ? ', ' : '') + '<b>' + arrayoftargetfields[i] + '*</b>';
						}
                    }
					console.log(listtoshow);
					if (listtoshow) {
						listtoshow = '".dol_escape_js(img_warning($langs->trans("MandatoryTargetFieldsNotMapped")).' '.$langs->trans("MandatoryTargetFieldsNotMapped")).": ' + listtoshow;
						$('#div-mandatory-target-fields-not-mapped').html(listtoshow);
					} else {
						$('#div-mandatory-target-fields-not-mapped').html('<span class=\"opacitymedium\">".dol_escape_js($langs->trans("AllTargetMandatoryFieldsAreMapped"))."</span>');
					}
		";

		echo '}'."\n";

		// If we make a change on a selectbox
		echo '$(".targetselectchange").change(function(){'."\n";
		echo '     setOptionsToDisabled();'."\n";

		echo '		if(previousselectedlabelimport != "" && previousselectedvalueimport != -1) {'."\n";
		echo '			let valuetochange = $(this).val(); '."\n";
		echo '			$(".boxtdunused").each(function(){'."\n";
		echo '				if ($(this).text().includes(valuetochange)){'."\n";
		echo '					arraychild = $(this)[0].childNodes'."\n";
		echo '					arraytexttomodify = arraychild[0].textContent.split(" ")'."\n";
		echo '					arraytexttomodify[1] = previousselectedvalueimport '."\n";
		echo '					textmodified = arraytexttomodify.join(" ") '."\n";
		echo '					arraychild[0].textContent = textmodified'."\n";
		echo '					arraychild[1].innerHTML = previousselectedlabelimport'."\n";
		echo '				}'."\n";
		echo '			})'."\n";
		echo '		}'."\n";
		echo '		$(this).blur()'."\n";

		echo '		saveSelection()'."\n";
		echo '});'."\n";

		echo '})'."\n";
		echo '</script>'."\n";
	}

	/*
	 * Action bar
	 */
	echo '<div class="tabsAction">';

	if (count($array_match_file_to_database)) {
		if ($mandatoryfieldshavesource) {
			echo '<a class="butAction saveorderselect" href="import.php?step=5'.$param.'&filetoimport='.urlencode($filetoimport).'">'.$langs->trans("NextStep").'</a>';
		} else {
			echo '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("SomeMandatoryFieldHaveNoSource")).'">'.$langs->trans("NextStep").'</a>';
		}
	}

	echo '</div>';


	// Area for profils import
	if (count($array_match_file_to_database)) {
		echo '<br>'."\n";
		echo '<!-- Area to add new import profile -->'."\n";
		echo '<div class="marginbottomonly"><span class="opacitymedium">'.$langs->trans("SaveImportModel").'</span></div>';

		echo '<form class="nocellnopadd" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<input type="hidden" name="action" value="add_import_model">';
		echo '<input type="hidden" name="step" value="'.$step.'">';
		echo '<input type="hidden" name="format" value="'.$format.'">';
		echo '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
		echo '<input type="hidden" name="filetoimport" value="'.$filetoimport.'">';
		echo '<input type="hidden" name="hexa" value="'.$hexa.'">';
		echo '<input type="hidden" name="excludefirstline" value="'.$excludefirstline.'">';
		echo '<input type="hidden" name="endatlinenb" value="'.$endatlinenb.'">';
		echo '<input type="hidden" name="page_y" value="">';
		echo '<input type="hidden" value="'.dol_escape_htmltag($separator).'" name="separator">';
		echo '<input type="hidden" value="'.dol_escape_htmltag($enclosure).'" name="enclosure">';

		echo '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		echo '<table summary="selectofimportprofil" class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<td>'.$langs->trans("ImportModelName").'</td>';
		echo '<td>'.$langs->trans("Visibility").'</td>';
		echo '<td></td>';
		echo '</tr>';

		$nameofimportprofile = str_replace(' ', '-', $langs->trans("ImportProfile").' '.$titleofmodule.' '.dol_print_date(dol_now('gmt'), 'dayxcard'));
		if (GETPOST('import_name')) {	// If we have submitted a form, we take value used for the update try
			$nameofimportprofile = $import_name;
		}

		echo '<tr class="oddeven">';
		echo '<td><input name="import_name" class="minwidth300" value="'.$nameofimportprofile.'"></td>';
		echo '<td>';
		$arrayvisibility = array('private' => $langs->trans("Private"), 'all' => $langs->trans("Everybody"));
		echo $form->selectarray('visibility', $arrayvisibility, 'private');
		echo '</td>';
		echo '<td class="right">';
		echo '<input type="submit" class="button smallpaddingimp reposition" value="'.$langs->trans("SaveImportProfile").'">';
		echo '</td></tr>';

		// List of existing import profils
		$sql = "SELECT rowid, label, fk_user, entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."import_model";
		$sql .= " WHERE type = '".$db->escape($datatoimport)."'";
		if (!getDolGlobalString('EXPORTS_SHARE_MODELS')) {	// EXPORTS_SHARE_MODELS means all templates are visible, whatever is owner.
			$sql .= " AND fk_user IN (0, ".((int) $user->id).")";
		}
		$sql .= " ORDER BY rowid";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			$tmpuser = new User($db);

			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				echo '<tr class="oddeven"><td>';
				echo $obj->label;
				echo '</td>';
				echo '<td class="tdoverflowmax150">';
				if (empty($obj->fk_user)) {
					echo $langs->trans("Everybody");
				} else {
					$tmpuser->fetch($obj->fk_user);
					echo $tmpuser->getNomUrl(-1);
				}
				echo '</td>';
				echo '<td class="right">';
				echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step='.$step.$param.'&action=deleteprof&token='.newToken().'&id='.$obj->rowid.'&filetoimport='.urlencode($filetoimport).'">';
				echo img_delete();
				echo '</a>';
				echo '</tr>';
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		echo '</table>';
		echo '</div>';

		echo '</form>';
	}
}

// STEP 5: Summary of choices and launch simulation
if ($step == 5 && $datatoimport) {
	$max_execution_time_for_importexport = getDolGlobalInt('IMPORT_MAX_EXECUTION_TIME', 300); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	$model = $format;
	$list = $objmodelimport->listOfAvailableImportFormat($db);

	// Create class to use for import
	$dir = DOL_DOCUMENT_ROOT."/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once $dir.$file;
	$obj = new $classname($db, $datatoimport);
	if ($model == 'csv') {
		$obj->separator = $separator_used;
		$obj->enclosure = $enclosure;
	}

	// Load source fields in input file
	$fieldssource = array();
	$result = $obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport, $langs);

	if ($result >= 0) {
		// Read first line
		$arrayrecord = $obj->import_read_record();
		// Put into array fieldssource starting with 1.
		$i = 1;
		foreach ($arrayrecord as $key => $val) {
			$fieldssource[$i]['example1'] = dol_trunc($val['val'], 24);
			$i++;
		}
		$obj->import_close_file();
	}

	$nboflines = $obj->import_get_nb_of_lines($conf->import->dir_temp.'/'.$filetoimport);

	$param = '&leftmenu=import&format='.urlencode($format).'&datatoimport='.urlencode($datatoimport).'&filetoimport='.urlencode($filetoimport).'&nboflines='.((int) $nboflines).'&separator='.urlencode($separator).'&enclosure='.urlencode($enclosure);
	$param2 = $param; // $param2 = $param without excludefirstline and endatlinenb
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if (!empty($updatekeys)) {
		$param .= '&updatekeys[]='.implode('&updatekeys[]=', $updatekeys);
	}

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 5);


	echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$param2.'" method="POST">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="step" value="5">'; // step 5
	echo '<input type="hidden" name="action" value="launchsimu">'; // step 5

	echo dol_get_fiche_head($head, 'step5', '', -2);

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table width="100%" class="border tableforfield">';

	// Module
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Module").'</td>';
	echo '<td>';
	$titleofmodule = $objimport->array_import_module[0]['module']->getName();
	// Special cas for import common to module/services
	if (in_array($objimport->array_import_code[0], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
		$titleofmodule = $langs->trans("ProductOrService");
	}
	echo $titleofmodule;
	echo '</td></tr>';

	// Lot de donnees a importer
	echo '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	echo '<td>';
	$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	echo img_object($objimport->array_import_module[0]['module']->getName(), $entityicon).' ';
	echo $objimport->array_import_label[0];
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo load_fiche_titre($langs->trans("InformationOnSourceFile"), '', 'file-export');

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';
	echo '<table width="100%" class="border tableforfield">';

	// Source file format
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("SourceFileFormat").'</td>';
	echo '<td>';
	$text = $objmodelimport->getDriverDescForKey($format);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	echo $form->textwithpicto($objmodelimport->getDriverLabelForKey($format), $text);
	echo '</td></tr>';

	// Separator and enclosure
	if ($model == 'csv') {
		echo '<tr><td>'.$langs->trans("CsvOptions").'</td>';
		echo '<td>';
		echo $langs->trans("Separator").' : '.dol_escape_htmltag($separator);
		echo '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("Enclosure").' : '.dol_escape_htmltag($enclosure);
		echo '</td></tr>';
	}

	// File to import
	echo '<tr><td>'.$langs->trans("FileToImport").'</td>';
	echo '<td>';
	$modulepart = 'import';
	$relativepath = GETPOST('filetoimport');
	echo '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank" rel="noopener noreferrer">';
	echo img_mime($file, '', 'pictofixedwidth');
	echo $filetoimport;
	echo img_picto($langs->trans("Download"), 'download', 'class="paddingleft opacitymedium"');
	echo '</a>';
	echo '</td></tr>';

	// Total lines in source file
	echo '<tr><td>';
	echo $langs->trans("NbOfSourceLines");
	echo '</td><td>';
	echo $nboflines;
	echo '</td></tr>';

	// Range of lines to import
	echo '<tr><td>';
	echo $langs->trans("ImportFromToLine");
	echo '</td><td>';
	if ($action == 'launchsimu') {
		echo '<input type="number" class="maxwidth50 right" name="excludefirstlinebis" disabled="disabled" value="'.$excludefirstline.'">';
		echo '<input type="hidden" name="excludefirstline" value="'.$excludefirstline.'">';
	} else {
		echo '<input type="number" class="maxwidth50 right" name="excludefirstline" value="'.$excludefirstline.'">';
		echo $form->textwithpicto("", $langs->trans("SetThisValueTo2ToExcludeFirstLine"));
	}
	echo ' - ';
	if ($action == 'launchsimu') {
		echo '<input type="text" class="maxwidth50" name="endatlinenbbis" disabled="disabled" value="'.$endatlinenb.'">';
		echo '<input type="hidden" name="endatlinenb" value="'.$endatlinenb.'">';
	} else {
		echo '<input type="text" class="maxwidth50" name="endatlinenb" value="'.$endatlinenb.'">';
		echo $form->textwithpicto("", $langs->trans("KeepEmptyToGoToEndOfFile"));
	}
	if ($action == 'launchsimu') {
		echo ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'?step=5'.$param.'">'.$langs->trans("Modify").'</a>';
	}
	if ($excludefirstline == 2) {
		echo $form->textwithpicto("", $langs->trans("WarningFirstImportedLine", $excludefirstline), 1, 'warning', "warningexcludefirstline");
		echo '<script>
			$( document ).ready(function() {
				$("input[name=\'excludefirstline\']").on("change",function(){
					if($(this).val() <= 1){
						$(".warningexcludefirstline").hide();
					}else{
						$(".warningexcludefirstline").show();
					}
				})
			});
		</script>';
	}
	echo '</td></tr>';

	// Keys for data UPDATE (not INSERT of new data)
	echo '<tr><td>';
	echo $form->textwithpicto($langs->trans("KeysToUseForUpdates"), $langs->trans("SelectPrimaryColumnsForUpdateAttempt"));
	echo '</td><td>';
	if ($action == 'launchsimu') {
		if (count($updatekeys)) {
			echo $form->multiselectarray('updatekeysbis', $objimport->array_import_updatekeys[0], $updatekeys, 0, 0, '', 1, '80%', 'disabled');
		} else {
			echo '<span class="opacitymedium">'.$langs->trans("NoUpdateAttempt").'</span> &nbsp; -';
		}
		foreach ($updatekeys as $val) {
			echo '<input type="hidden" name="updatekeys[]" value="'.$val.'">';
		}
		echo ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'?step=5'.$param.'">'.$langs->trans("Modify").'</a>';
	} else {
		if (is_array($objimport->array_import_updatekeys[0]) && count($objimport->array_import_updatekeys[0])) {   //TODO dropdown UL is created inside nested SPANS
			echo $form->multiselectarray('updatekeys', $objimport->array_import_updatekeys[0], $updatekeys, 0, 0, '', 1, '80%');
			//echo $form->textwithpicto("", $langs->trans("SelectPrimaryColumnsForUpdateAttempt"));
		} else {
			echo '<span class="opacitymedium">'.$langs->trans("UpdateNotYetSupportedForThisImport").'</span>';
		}
	}
	/*echo  '<pre>';
	print_r($objimport->array_import_updatekeys);
	echo  '</pre>';*/
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';


	echo load_fiche_titre($langs->trans("InformationOnTargetTables"), '', 'file-import');

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table width="100%" class="border tableforfield">';

	// Tables imported
	echo '<tr><td class="titlefieldcreate">';
	echo $langs->trans("TablesTarget");
	echo '</td><td>';
	$listtables = array();
	$sort_array_match_file_to_database = $array_match_file_to_database;
	foreach ($array_match_file_to_database as $code => $label) {
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) {
			continue;
		}
		//echo $code.'-'.$label;
		$alias = preg_replace('/(\..*)$/i', '', $label);
		$listtables[$alias] = $objimport->array_import_tables[0][$alias];
	}
	if (count($listtables)) {
		$newval = '';
		//ksort($listtables);
		foreach ($listtables as $val) {
			if ($newval) {
				echo ', ';
			}
			$newval = $val;
			// Link to Gestimag wiki pages
			/*$helppagename='EN:Table_'.$newval;
			if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
			{
				// Get helpbaseurl, helppage and mode from helppagename and langs
				$arrayres=getHelpParamFor($helppagename,$langs);
				$helpbaseurl=$arrayres['helpbaseurl'];
				$helppage=$arrayres['helppage'];
				$mode=$arrayres['mode'];
				$newval.=' <a href="'.sprintf($helpbaseurl,$helppage).'">'.img_picto($langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage'),DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).'</a>';
			}*/
			echo $newval;
		}
	} else {
		echo $langs->trans("Error");
	}
	echo '</td></tr>';

	// Fields imported
	echo '<tr><td>';
	echo $langs->trans("FieldsTarget").'</td><td>';
	$listfields = array();
	$i = 0;
	//echo 'fieldsource='.$fieldssource;
	$sort_array_match_file_to_database = $array_match_file_to_database;
	ksort($sort_array_match_file_to_database);
	//var_dump($sort_array_match_file_to_database);
	foreach ($sort_array_match_file_to_database as $code => $label) {
		$i++;
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) {
			continue;
		}
		//echo $code.'-'.$label;
		$alias = preg_replace('/(\..*)$/i', '', $label);
		$listfields[$i] = '<span class="nowrap">'.$langs->trans("Column").' '.num2Alpha($code - 1).' -> '.$label.'</span>';
	}
	echo count($listfields) ? (implode(', ', $listfields)) : $langs->trans("Error");
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo dol_get_fiche_end();


	if ($action != 'launchsimu') {
		// Show import id
		echo '<br><span class="opacitymedium">';
		echo $langs->trans("NowClickToTestTheImport", $langs->transnoentitiesnoconv("RunSimulateImportFile")).'</span><br>';
		echo '<br>';

		// Actions
		echo '<div class="center">';
		if ($user->hasRight('import', 'run')) {
			echo '<input type="submit" class="butAction" value="'.$langs->trans("RunSimulateImportFile").'">';
		} else {
			echo '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("RunSimulateImportFile").'</a>';
		}
		echo '</div>';
	} else {
		// Launch import
		$arrayoferrors = array();
		$arrayofwarnings = array();
		$maxnboferrors = !getDolGlobalString('IMPORT_MAX_NB_OF_ERRORS') ? 50 : $conf->global->IMPORT_MAX_NB_OF_ERRORS;
		$maxnbofwarnings = !getDolGlobalString('IMPORT_MAX_NB_OF_WARNINGS') ? 50 : $conf->global->IMPORT_MAX_NB_OF_WARNINGS;
		$nboferrors = 0;
		$nbofwarnings = 0;

		$importid = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		//var_dump($array_match_file_to_database);

		$db->begin();

		// Open input file
		$nbok = 0;
		$pathfile = $conf->import->dir_temp.'/'.$filetoimport;
		$result = $obj->import_open_file($pathfile, $langs);
		if ($result > 0) {
			global $tablewithentity_cache;
			$tablewithentity_cache = array();
			$sourcelinenb = 0;
			$endoffile = 0;

			// Loop on each input file record
			while (($sourcelinenb < $nboflines) && !$endoffile) {
				$sourcelinenb++;
				// Read line and store it into $arrayrecord
				//dol_syslog("line ".$sourcelinenb.' - '.$nboflines.' - '.$excludefirstline.' - '.$endatlinenb);
				$arrayrecord = $obj->import_read_record();
				if ($arrayrecord === false) {
					$arrayofwarnings[$sourcelinenb][0] = array('lib' => 'File has '.$nboflines.' lines. However we reach the end of file or an empty line at record '.$sourcelinenb.'. This may occurs when some records are split onto several lines and not correctly delimited by the "Char delimiter", or if there is line with no data on all fields.', 'type' => 'EOF_RECORD_ON_SEVERAL_LINES');
					$endoffile++;
					continue;
				}
				if ($excludefirstline && ($sourcelinenb < $excludefirstline)) {
					continue;
				}
				if ($endatlinenb && ($sourcelinenb > $endatlinenb)) {
					break;
				}

				$parameters = array(
					'step'                         => $step,
					'datatoimport'                 => $datatoimport,
					'obj'                          => &$obj,
					'arrayrecord'                  => $arrayrecord,
					'array_match_file_to_database' => $array_match_file_to_database,
					'objimport'                    => $objimport,
					'fieldssource'                 => $fieldssource,
					'importid'                     => $importid,
					'updatekeys'                   => $updatekeys,
					'arrayoferrors'                => &$arrayoferrors,
					'arrayofwarnings'              => &$arrayofwarnings,
					'nbok'                         => &$nbok,
				);

				$reshook = $hookmanager->executeHooks('ImportInsert', $parameters);
				if ($reshook < 0) {
					$arrayoferrors[$sourcelinenb][] = [
						'lib' => implode("<br>", array_merge([$hookmanager->error], $hookmanager->errors))
					];
				}

				if (empty($reshook)) {
					// Run import
					$result = $obj->import_insert($arrayrecord, $array_match_file_to_database, $objimport, count($fieldssource), $importid, $updatekeys);

					if (count($obj->errors)) {
						$arrayoferrors[$sourcelinenb] = $obj->errors;
					}
					if (count($obj->warnings)) {
						$arrayofwarnings[$sourcelinenb] = $obj->warnings;
					}
					if (!count($obj->errors) && !count($obj->warnings)) {
						$nbok++;
					}
				}
			}
			// Close file
			$obj->import_close_file();
		} else {
			echo $langs->trans("ErrorFailedToOpenFile", $pathfile);
		}

		$error = 0;

		// Run the sql after import if defined
		//var_dump($objimport->array_import_run_sql_after[0]);
		if (!empty($objimport->array_import_run_sql_after[0]) && is_array($objimport->array_import_run_sql_after[0])) {
			$i = 0;
			foreach ($objimport->array_import_run_sql_after[0] as $sqlafterimport) {
				$i++;
				$resqlafterimport = $db->query($sqlafterimport);
				if (!$resqlafterimport) {
					$arrayoferrors['none'][] = array('lib' => $langs->trans("Error running final request: ".$sqlafterimport));
					$error++;
				}
			}
		}

		$db->rollback(); // We force rollback because this was just a simulation.

		// Show OK
		if (!count($arrayoferrors) && !count($arrayofwarnings)) {
			echo '<br>';
			echo '<div class="info">';
			echo '<div class=""><b>'.$langs->trans("ResultOfSimulationNoError").'</b></div>';
			echo $langs->trans("NbInsertSim", empty($obj->nbinsert) ? 0 : $obj->nbinsert).'<br>';
			echo $langs->trans("NbUpdateSim", empty($obj->nbupdate) ? 0 : $obj->nbupdate).'<br>';
			echo '</div>';
			echo '<br>';
		} else {
			echo '<br>';
			echo '<div class="warning">';
			echo $langs->trans("NbOfLinesOK", $nbok).'...<br>';
			echo '</div>';
			echo '<br>';
		}

		// Show Errors
		//var_dump($arrayoferrors);
		if (count($arrayoferrors)) {
			echo img_error().' <b>'.$langs->trans("ErrorsOnXLines", count($arrayoferrors)).'</b><br>';
			echo '<table width="100%" class="border"><tr><td>';
			foreach ($arrayoferrors as $key => $val) {
				$nboferrors++;
				if ($nboferrors > $maxnboferrors) {
					echo $langs->trans("TooMuchErrors", (count($arrayoferrors) - $nboferrors))."<br>";
					break;
				}
				echo '* '.$langs->trans("Line").' '.dol_escape_htmltag($key).'<br>';
				foreach ($val as $i => $err) {
					echo ' &nbsp; &nbsp; > '.dol_escape_htmltag($err['lib']).'<br>';
				}
			}
			echo '</td></tr></table>';
			echo '<br>';
		}

		// Show Warnings
		//var_dump($arrayoferrors);
		if (count($arrayofwarnings)) {
			echo img_warning().' <b>'.$langs->trans("WarningsOnXLines", count($arrayofwarnings)).'</b><br>';
			echo '<table width="100%" class="border"><tr><td>';
			foreach ($arrayofwarnings as $key => $val) {
				$nbofwarnings++;
				if ($nbofwarnings > $maxnbofwarnings) {
					echo $langs->trans("TooMuchWarnings", (count($arrayofwarnings) - $nbofwarnings))."<br>";
					break;
				}
				echo ' * '.$langs->trans("Line").' '.dol_escape_htmltag($key).'<br>';
				foreach ($val as $i => $err) {
					echo ' &nbsp; &nbsp; > '.dol_escape_htmltag($err['lib']).'<br>';
				}
			}
			echo '</td></tr></table>';
			echo '<br>';
		}

		// Show import id
		$importid = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		echo '<div class="center">';
		echo '<span class="opacitymedium">'.$langs->trans("NowClickToRunTheImport", $langs->transnoentitiesnoconv("RunImportFile")).'</span><br>';
		/*if (empty($nboferrors)) {
			echo $langs->trans("DataLoadedWithId", $importid).'<br>';
		}*/
		echo '</div>';

		echo '<br>';

		// Actions
		echo '<div class="center">';
		if ($user->hasRight('import', 'run')) {
			if (empty($nboferrors)) {
				echo '<a class="butAction" href="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import&step=6&importid='.$importid.$param.'">'.$langs->trans("RunImportFile").'</a>';
			} else {
				//echo '<input type="submit" class="butAction" value="'.dol_escape_htmltag($langs->trans("RunSimulateImportFile")).'">';

				echo '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("CorrectErrorBeforeRunningImport")).'">'.$langs->trans("RunImportFile").'</a>';
			}
		} else {
			echo '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("RunSimulateImportFile").'</a>';

			echo '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("RunImportFile").'</a>';
		}
		echo '</div>';
	}

	echo '</form>';
}


// STEP 6: Real import
if ($step == 6 && $datatoimport) {
	$max_execution_time_for_importexport = getDolGlobalInt('IMPORT_MAX_EXECUTION_TIME', 300); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	$model = $format;
	$list = $objmodelimport->listOfAvailableImportFormat($db);
	$importid = GETPOST("importid", 'alphanohtml');


	// Create class to use for import
	$dir = DOL_DOCUMENT_ROOT."/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once $dir.$file;
	$obj = new $classname($db, $datatoimport);
	if ($model == 'csv') {
		$obj->separator = $separator_used;
		$obj->enclosure = $enclosure;
	}

	// Load source fields in input file
	$fieldssource = array();
	$result = $obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport, $langs);
	if ($result >= 0) {
		// Read first line
		$arrayrecord = $obj->import_read_record();
		// Put into array fieldssource starting with 1.
		$i = 1;
		foreach ($arrayrecord as $key => $val) {
			$fieldssource[$i]['example1'] = dol_trunc($val['val'], 24);
			$i++;
		}
		$obj->import_close_file();
	}

	$nboflines = (GETPOSTISSET("nboflines") ? GETPOSTINT("nboflines") : dol_count_nb_of_line($conf->import->dir_temp.'/'.$filetoimport));

	$param = '&format='.$format.'&datatoimport='.urlencode($datatoimport).'&filetoimport='.urlencode($filetoimport).'&nboflines='.((int) $nboflines);
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}
	if ($separator) {
		$param .= '&separator='.urlencode($separator);
	}
	if ($enclosure) {
		$param .= '&enclosure='.urlencode($enclosure);
	}

	llxHeader('', $langs->trans("NewImport"), $help_url);

	$head = import_prepare_head($param, 6);

	echo dol_get_fiche_head($head, 'step6', '', -1);

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';

	echo '<table width="100%" class="border">';

	// Module
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Module").'</td>';
	echo '<td>';
	$titleofmodule = $objimport->array_import_module[0]['module']->getName();
	// Special cas for import common to module/services
	if (in_array($objimport->array_import_code[0], array('produit_supplierprices', 'produit_multiprice', 'produit_languages'))) {
		$titleofmodule = $langs->trans("ProductOrService");
	}
	echo $titleofmodule;
	echo '</td></tr>';

	// Lot de donnees a importer
	echo '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	echo '<td>';
	$entity = preg_replace('/:.*$/', '', $objimport->array_import_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	echo img_object($objimport->array_import_module[0]['module']->getName(), $entityicon).' ';
	echo $objimport->array_import_label[0];
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo load_fiche_titre($langs->trans("InformationOnSourceFile"), '', 'file-export');

	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';
	echo '<table width="100%" class="border">';

	// Source file format
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("SourceFileFormat").'</td>';
	echo '<td>';
	$text = $objmodelimport->getDriverDescForKey($format);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	echo $form->textwithpicto($objmodelimport->getDriverLabelForKey($format), $text);
	echo '</td></tr>';

	// Separator and enclosure
	if ($model == 'csv') {
		echo '<tr><td>'.$langs->trans("CsvOptions").'</td>';
		echo '<td>';
		echo $langs->trans("Separator").' : ';
		echo htmlentities($separator);
		echo '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans("Enclosure").' : ';
		echo htmlentities($enclosure);
		echo '</td></tr>';
	}

	// File to import
	echo '<tr><td>'.$langs->trans("FileToImport").'</td>';
	echo '<td>';
	$modulepart = 'import';
	$relativepath = GETPOST('filetoimport');
	echo '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank" rel="noopener noreferrer">';
	echo img_mime($file, '', 'pictofixedwidth');
	echo $filetoimport;
	echo '</a>';
	echo '</td></tr>';

	// Nb of fields
	echo '<tr><td>';
	echo $langs->trans("NbOfSourceLines");
	echo '</td><td>';
	echo $nboflines;
	echo '</td></tr>';

	// Do not import first lines
	echo '<tr><td>';
	echo $langs->trans("ImportFromLine");
	echo '</td><td>';
	echo '<input type="text" size="4" name="excludefirstline" disabled="disabled" value="'.$excludefirstline.'">';
	echo '</td></tr>';

	// Do not import end lines
	echo '<tr><td>';
	echo $langs->trans("EndAtLineNb");
	echo '</td><td>';
	echo '<input type="text" size="4" name="endatlinenb" disabled="disabled" value="'.$endatlinenb.'">';
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	echo '<br>';

	echo '<b>'.$langs->trans("InformationOnTargetTables").'</b>';
	echo '<div class="underbanner clearboth"></div>';
	echo '<div class="fichecenter">';
	echo '<table class="border centpercent">';

	// Tables imported
	echo '<tr><td width="25%">';
	echo $langs->trans("TablesTarget");
	echo '</td><td>';
	$listtables = array();
	foreach ($array_match_file_to_database as $code => $label) {
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) {
			continue;
		}
		//echo $code.'-'.$label;
		$alias = preg_replace('/(\..*)$/i', '', $label);
		$listtables[$alias] = $objimport->array_import_tables[0][$alias];
	}
	if (count($listtables)) {
		$newval = '';
		foreach ($listtables as $val) {
			if ($newval) {
				echo ', ';
			}
			$newval = $val;
			// Link to Gestimag wiki pages
			/*$helppagename='EN:Table_'.$newval;
			if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
			{
				// Get helpbaseurl, helppage and mode from helppagename and langs
				$arrayres=getHelpParamFor($helppagename,$langs);
				$helpbaseurl=$arrayres['helpbaseurl'];
				$helppage=$arrayres['helppage'];
				$mode=$arrayres['mode'];
				$newval.=' <a href="'.sprintf($helpbaseurl,$helppage).'">'.img_picto($langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage'),DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).'</a>';
			}*/
			echo $newval;
		}
	} else {
		echo $langs->trans("Error");
	}
	echo '</td></tr>';

	// Fields imported
	echo '<tr><td>';
	echo $langs->trans("FieldsTarget").'</td><td>';
	$listfields = array();
	$i = 0;
	$sort_array_match_file_to_database = $array_match_file_to_database;
	ksort($sort_array_match_file_to_database);
	//var_dump($sort_array_match_file_to_database);
	foreach ($sort_array_match_file_to_database as $code => $label) {
		$i++;
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) {
			continue;
		}
		//echo $code.'-'.$label;
		$alias = preg_replace('/(\..*)$/i', '', $label);
		$listfields[$i] = $langs->trans("Field").' '.$code.'->'.$label;
	}
	echo count($listfields) ? (implode(', ', $listfields)) : $langs->trans("Error");
	echo '</td></tr>';

	echo '</table>';
	echo '</div>';

	// Launch import
	$arrayoferrors = array();
	$arrayofwarnings = array();
	$maxnboferrors = !getDolGlobalString('IMPORT_MAX_NB_OF_ERRORS') ? 50 : $conf->global->IMPORT_MAX_NB_OF_ERRORS;
	$maxnbofwarnings = !getDolGlobalString('IMPORT_MAX_NB_OF_WARNINGS') ? 50 : $conf->global->IMPORT_MAX_NB_OF_WARNINGS;
	$nboferrors = 0;
	$nbofwarnings = 0;

	$importid = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

	//var_dump($array_match_file_to_database);

	$db->begin();

	// Open input file
	$nbok = 0;
	$pathfile = $conf->import->dir_temp.'/'.$filetoimport;
	$result = $obj->import_open_file($pathfile, $langs);
	if ($result > 0) {
		global $tablewithentity_cache;
		$tablewithentity_cache = array();
		$sourcelinenb = 0;
		$endoffile = 0;

		while ($sourcelinenb < $nboflines && !$endoffile) {
			$sourcelinenb++;
			$arrayrecord = $obj->import_read_record();
			if ($arrayrecord === false) {
				$arrayofwarnings[$sourcelinenb][0] = array('lib' => 'File has '.$nboflines.' lines. However we reach the end of file or an empty line at record '.$sourcelinenb.'. This may occurs when some records are split onto several lines and not correctly delimited by the "Char delimiter", or if there is line with no data on all fields.', 'type' => 'EOF_RECORD_ON_SEVERAL_LINES');
				$endoffile++;
				continue;
			}
			if ($excludefirstline && ($sourcelinenb < $excludefirstline)) {
				continue;
			}
			if ($endatlinenb && ($sourcelinenb > $endatlinenb)) {
				break;
			}

			$parameters = array(
				'step'                         => $step,
				'datatoimport'                 => $datatoimport,
				'obj'                          => &$obj,
				'arrayrecord'                  => $arrayrecord,
				'array_match_file_to_database' => $array_match_file_to_database,
				'objimport'                    => $objimport,
				'fieldssource'                 => $fieldssource,
				'importid'                     => $importid,
				'updatekeys'                   => $updatekeys,
				'arrayoferrors'                => &$arrayoferrors,
				'arrayofwarnings'              => &$arrayofwarnings,
				'nbok'                         => &$nbok,
			);

			$reshook = $hookmanager->executeHooks('ImportInsert', $parameters);
			if ($reshook < 0) {
				$arrayoferrors[$sourcelinenb][] = [
					'lib' => implode("<br>", array_merge([$hookmanager->error], $hookmanager->errors))
				];
			}

			if (empty($reshook)) {
				// Run import
				$result = $obj->import_insert($arrayrecord, $array_match_file_to_database, $objimport, count($fieldssource), $importid, $updatekeys);

				if (count($obj->errors)) {
					$arrayoferrors[$sourcelinenb] = $obj->errors;
				}
				if (count($obj->warnings)) {
					$arrayofwarnings[$sourcelinenb] = $obj->warnings;
				}

				if (!count($obj->errors) && !count($obj->warnings)) {
					$nbok++;
				}
			}

			$reshook = $hookmanager->executeHooks('AfterImportInsert', $parameters);
			if ($reshook < 0) {
				$arrayoferrors[$sourcelinenb][] = [
					'lib' => implode("<br>", array_merge([$hookmanager->error], $hookmanager->errors))
				];
			}
		}
		// Close file
		$obj->import_close_file();
	} else {
		echo $langs->trans("ErrorFailedToOpenFile", $pathfile);
	}

	if (count($arrayoferrors) > 0) {
		$db->rollback(); // We force rollback because this was errors.
	} else {
		$error = 0;

		// Run the sql after import if defined
		//var_dump($objimport->array_import_run_sql_after[0]);
		if (!empty($objimport->array_import_run_sql_after[0]) && is_array($objimport->array_import_run_sql_after[0])) {
			$i = 0;
			foreach ($objimport->array_import_run_sql_after[0] as $sqlafterimport) {
				$i++;
				$resqlafterimport = $db->query($sqlafterimport);
				if (!$resqlafterimport) {
					$arrayoferrors['none'][] = array('lib' => $langs->trans("Error running final request: ".$sqlafterimport));
					$error++;
				}
			}
		}

		if (!$error) {
			$db->commit(); // We can commit if no errors.
		} else {
			$db->rollback();
		}
	}

	echo dol_get_fiche_end();


	// Show result
	echo '<br>';
	echo '<div class="info">';
	echo $langs->trans("NbOfLinesImported", $nbok).'</b><br>';
	echo $langs->trans("NbInsert", empty($obj->nbinsert) ? 0 : $obj->nbinsert).'<br>';
	echo $langs->trans("NbUpdate", empty($obj->nbupdate) ? 0 : $obj->nbupdate).'<br>';
	echo '</div>';
	echo '<div class="center">';
	echo $langs->trans("FileWasImported", $importid).'<br>';
	echo '<span class="opacitymedium">'.$langs->trans("YouCanUseImportIdToFindRecord", $importid).'</span><br>';
	echo '</div>';
}



echo '<br>';

// End of page
llxFooter();
$db->close();


/**
 * Function to put the movable box of a source field
 *
 * @param	array	$fieldssource	List of source fields
 * @param	int		$pos			Pos
 * @param	string	$key			Key
 * @return	void
 */
function show_elem($fieldssource, $pos, $key)
{
	global $conf, $langs;

	$height = '32px';

	if ($key == 'none') {
		//stop multiple duplicate ids with no number
		echo "\n\n<!-- Box_no-key start-->\n";
		echo '<div class="box boximport" style="padding:0;">'."\n";
		echo '<table summary="boxtable_no-key" class="centpercent nobordernopadding">'."\n";
	} else {
		echo "\n\n<!-- Box ".$pos." start -->\n";
		echo '<div class="box boximport" style="padding: 0;" id="boxto_'.$pos.'">'."\n";

		echo '<table summary="boxtable'.$pos.'" class="nobordernopadding centpercent tableimport">'."\n";
	}

	if (($pos && $pos > count($fieldssource)) && (!isset($fieldssource[$pos]["imported"]))) {	// No fields
		/*
		echo '<tr style="height:'.$height.'" class="trimport oddevenimport">';
		echo '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		echo '</td>';
		echo '<td style="font-weight: normal">';
		echo $langs->trans("NoFields");
		echo '</td>';
		echo '</tr>';
		*/
	} elseif ($key == 'none') {	// Empty line
		echo '<tr style="height:'.$height.'" class="trimport oddevenimport">';
		echo '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		echo '&nbsp;';
		echo '</td>';
		echo '<td style="font-weight: normal">';
		echo '&nbsp;';
		echo '</td>';
		echo '</tr>';
	} else {
		// Print field of source file
		echo '<tr style="height:'.$height.'" class="trimport oddevenimport">';
		echo '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		// The image must have the class 'boxhandle' because it's value used in DOM draggable objects to define the area used to catch the full object
		//echo img_picto($langs->trans("MoveField", $pos), 'grip_title', 'class="boxhandle" style="cursor:move;"');
		echo img_picto($langs->trans("Column").' '.num2Alpha($pos - 1), 'file', 'class="pictofixedwith"');
		echo '</td>';
		if (isset($fieldssource[$pos]['imported']) && $fieldssource[$pos]['imported'] == false) {
			echo '<td class="nowraponall boxtdunused" style="font-weight: normal">';
		} else {
			echo '<td class="nowraponall tdoverflowmax500" style="font-weight: normal">';
		}
		echo $langs->trans("Column").' '.num2Alpha($pos - 1).' (#'.$pos.')';
		if (empty($fieldssource[$pos]['example1'])) {
			$example = $fieldssource[$pos]['label'];
		} else {
			$example = $fieldssource[$pos]['example1'];
		}
		if ($example) {
			if (!utf8_check($example)) {
				$example = mb_convert_encoding($example, 'UTF-8', 'ISO-8859-1');
			}
			// if (!empty($conf->dol_optimize_smallscreen)) { //echo '<br>'; }
			echo ' - ';
			echo '<i class="opacitymedium">'.dol_escape_htmltag($example).'</i>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo "</table>\n";

	echo "</div>\n";
	echo "<!-- Box end -->\n\n";
}


/**
 * Return not used field number
 *
 * @param 	array	$fieldssource	Array of field source
 * @param	array	$listofkey		Array of keys
 * @return	integer
 */
function getnewkey(&$fieldssource, &$listofkey)
{
	$i = count($fieldssource) + 1;
	// Max number of key
	$maxkey = 0;
	foreach ($listofkey as $key => $val) {
		$maxkey = max($maxkey, $key);
	}
	// Found next empty key
	while ($i <= $maxkey) {
		if (empty($listofkey[$i])) {
			break;
		} else {
			$i++;
		}
	}

	$listofkey[$i] = 1;
	return $i;
}
/**
 * Return array with element inserted in it at position $position
 *
 * @param 	array	$array			Array of field source
 * @param	mixed	$position		key of position to insert to
 * @param	array	$insertArray	Array to insert
 * @return	array
 */
function arrayInsert($array, $position, $insertArray)
{
	$ret = [];

	if ($position == count($array)) {
		$ret = $array + $insertArray;
	} else {
		$i = 0;
		foreach ($array as $key => $value) {
			if ($position == $i++) {
				$ret += $insertArray;
			}

			$ret[$key] = $value;
		}
	}

	return $ret;
}

<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *      \file       htdocs/supplier_proposal/info.php
 *      \ingroup    propal
 *      \brief      Page d'affichage des infos d'une proposition commerciale
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('supplier_proposal', 'compta'));

$id = GETPOSTINT('id');
$socid = GETPOSTINT('socid');

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'supplier_proposal', $id);


/*
 *	View
 */
$form = new Form($db);
$object = new SupplierProposal($db);
$object->fetch($id);
$object->fetch_thirdparty();
$object->info($object->id);

$title = $object->ref." - ".$langs->trans('Info');
$help_url = 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';
llxHeader('', $title, $help_url);


$head = supplier_proposal_prepare_head($object);
echo dol_get_fiche_head($head, 'info', $langs->trans('CommRequest'), -1, 'supplier_proposal');

// Supplier proposal card
$linkback = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


$morehtmlref = '<div class="refidno">';
// Ref supplier
//$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', 0, 1);
//$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref .= $object->thirdparty->getNomUrl(1);
// Project
if (isModEnabled('project')) {
	$langs->load("projects");
	$morehtmlref .= '<br>';
	if (0) {
		$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
		if ($action != 'classify') {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
		}
		$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= $proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= ' - '.$proj->title;
			}
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';


dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


echo '<div class="fichecenter">';
echo '<div class="underbanner clearboth"></div>';

echo '<br>';

echo '<table width="100%"><tr><td>';
dol_print_object_info($object);
echo '</td></tr></table>';

echo '</div>';

echo dol_get_fiche_end();

// End of page
llxFooter();
$db->close();

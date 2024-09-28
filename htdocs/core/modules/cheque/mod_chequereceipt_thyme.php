<?php
/* Copyright (C) 2015       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/modules/cheque/mod_chequereceipt_thyme.php
 * \ingroup    cheque
 * \brief      File containing class for numbering module Thyme
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';


/**
 *	Class to manage cheque receipts numbering rules Thyme
 */
class mod_chequereceipt_thyme extends ModeleNumRefChequeReceipts
{
	/**
	 * Gestimag version of the loaded document
	 * @var string
	 */
	public $version = 'gestimag'; // 'development', 'experimental', 'gestimag'

	/**
	 * @var string Error message
	 */
	public $error = '';

	public $name = 'Thyme';


	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $conf, $langs, $db;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstchequereceipts" value="CHEQUERECEIPTS_THYME_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("CheckReceiptShort"), $langs->transnoentities("CheckReceiptShort"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("CheckReceiptShort"), $langs->transnoentities("CheckReceiptShort"));
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskchequereceipts" value="' . getDolGlobalString('CHEQUERECEIPTS_THYME_MASK').'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp;<input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $langs, $mysoc;

		$old_code_client = $mysoc->code_client;
		$mysoc->code_client = 'CCCCCCCCCC';
		$numExample = $this->getNextValue($mysoc, '');
		$mysoc->code_client = $old_code_client;

		if (!$numExample) {
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe			$objsoc     Object thirdparty
	 *  @param  RemiseCheque	$object		Object we need next value for
	 *  @return string|int  				Next value if OK, 0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// We get cursor rule
		$mask = getDolGlobalString('CHEQUERECEIPTS_THYME_MASK');

		if (!$mask) {
			$this->error = 'NotConfigured';
			return 0;
		}

		$numFinal = get_next_value($db, $mask, 'bordereau_cheque', 'ref', '', $objsoc, empty($object) ? dol_now() : $object->date_bordereau);

		return  $numFinal;
	}
}

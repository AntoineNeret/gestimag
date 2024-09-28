<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 * $form
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}

?>
<!-- BEGIN PHP TEMPLATE commonfields_add.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	// Discard if field is a hidden field on form
	// Ensure $val['visible'] is treated as an integer
	$visible = (int) $val['visible'];
	if (abs($visible) != 1 && abs($visible) != 3) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}

	echo '<tr class="field_'.$key.'">';
	echo '<td';
	echo ' class="titlefieldcreate';
	if (isset($val['notnull']) && $val['notnull'] > 0) {
		echo ' fieldrequired';
	}
	if ($val['type'] == 'text' || $val['type'] == 'html') {
		echo ' tdtop';
	}
	echo '"';
	echo '>';
	if (!empty($val['help'])) {
		echo $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	} else {
		echo $langs->trans($val['label']);
	}
	echo '</td>';
	echo '<td class="valuefieldcreate">';
	if (!empty($val['picto'])) {
		echo img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
	}
	if (in_array($val['type'], array('int', 'integer'))) {
		$value = GETPOSTINT($key);
	} elseif ($val['type'] == 'double') {
		$value = price2num(GETPOST($key, 'alphanohtml'));
	} elseif (preg_match('/^text/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'nohtml';
		}
		$value = GETPOST($key, $check);
	} elseif (preg_match('/^html/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'restricthtml';
		}
		$value = GETPOST($key, $check);
	} elseif ($val['type'] == 'date') {
		$value = dol_mktime(12, 0, 0, GETPOSTINT($key.'month'), GETPOSTINT($key.'day'), GETPOSTINT($key.'year'));
	} elseif ($val['type'] == 'datetime') {
		$value = dol_mktime(GETPOSTINT($key.'hour'), GETPOSTINT($key.'min'), 0, GETPOSTINT($key.'month'), GETPOSTINT($key.'day'), GETPOSTINT($key.'year'));
	} elseif ($val['type'] == 'boolean') {
		$value = (GETPOST($key) == 'on' ? 1 : 0);
	} elseif ($val['type'] == 'price') {
		$value = price2num(GETPOST($key));
	} elseif ($key == 'lang') {
		$value = GETPOST($key, 'aZ09');
	} else {
		$value = GETPOST($key, 'alphanohtml');
	}
	if (!empty($val['noteditable'])) {
		echo $object->showOutputField($val, $key, $value, '', '', '', 0);
	} else {
		if ($key == 'lang') {
			echo img_picto('', 'language', 'class="pictofixedwidth"');
			echo $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
		} else {
			echo $object->showInputField($val, $key, $value, '', '', '', 0);
		}
	}
	echo '</td>';
	echo '</tr>';
}

?>
<!-- END PHP TEMPLATE commonfields_add.tpl.php -->

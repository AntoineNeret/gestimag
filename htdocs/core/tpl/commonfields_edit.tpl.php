<?php
/* Copyright (C) 2017-2019  Laurent Destailleur  <eldy@users.sourceforge.net>
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
if (!is_object($form)) {
	$form = new Form($db);
}

?>
<!-- BEGIN PHP TEMPLATE commonfields_edit.tpl.php -->
<?php

$object->fields = dol_sort_array($object->fields, 'position');

foreach ($object->fields as $key => $val) {
	// Discard if extrafield is a hidden field on form
	if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
		continue;
	}

	if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
		continue; // We don't want this field
	}

	echo '<tr class="field_'.$key.'"><td';
	echo ' class="titlefieldcreate';
	if (isset($val['notnull']) && $val['notnull'] > 0) {
		echo ' fieldrequired';
	}
	if (preg_match('/^(text|html)/', $val['type'])) {
		echo ' tdtop';
	}
	echo '">';
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
		$value = GETPOSTISSET($key) ? GETPOSTINT($key) : $object->$key;
	} elseif ($val['type'] == 'double') {
		$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
	} elseif (preg_match('/^text/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'nohtml';
		}
		$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
	} elseif (preg_match('/^html/', $val['type'])) {
		$tmparray = explode(':', $val['type']);
		if (!empty($tmparray[1])) {
			$check = $tmparray[1];
		} else {
			$check = 'restricthtml';
		}
		$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
	} elseif (in_array($val['type'], array('date', 'datetime'))) {
		$value = GETPOSTISSET($key) ? dol_mktime(GETPOSTINT($key.'hour'), GETPOSTINT($key.'min'), GETPOSTINT($key.'sec'), GETPOSTINT($key.'month'), GETPOSTINT($key.'day'), GETPOSTINT($key.'year')) : $object->$key;
	} elseif ($val['type'] == 'price') {
		$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
	} elseif ($key == 'lang') {
		$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
	} else {
		$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
	}
	//var_dump($val.' '.$key.' '.$value);
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
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->

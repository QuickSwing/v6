<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Component\Grid;
use CCL\Content\Element\Component\Grid\Column;
use CCL\Content\Element\Component\Grid\Row;
use CCL\Content\Element\Component\Tab;
use CCL\Content\Element\Component\TabContainer;

/** @var Container * */
$root = $displayData['root'];

/** @var JForm $jform * */
$jform = $displayData['jform'];

/** @var array $fieldsToHide * */
$fieldsToHide = isset($displayData['fieldsToHide']) ? $displayData['fieldsToHide'] : array();

/** @var array $fieldsToShow * */
$fieldsToShow = isset($displayData['fieldsToShow']) ? $displayData['fieldsToShow'] : array();

/** @var array $fieldSetsToHide * */
$fieldsetsToHide = isset($displayData['fieldsetsToHide']) ? $displayData['fieldsetsToHide'] : array();

/** @var boolean $flat * */
$flat = isset($displayData['flat']) ? $displayData['flat'] : false;

/** @var boolean $columns * */
$columns = isset($displayData['columns']) ? $displayData['columns'] : 1;

/** @var \Joomla\Registry\Registry $params * */
$params = isset($displayData['params']) ? $displayData['params'] : JComponentHelper::getParams('com_dpcalendar');

/** @var array $fieldSets * */
$fieldSets = isset($displayData['fieldSets']) ? $displayData['fieldSets'] : array_merge($jform->getFieldsets(), $jform->getFieldsets('params'));

// Load some javascript we may use
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

// Add the special case captcha, which will be rendered at the end of the form
$fieldsToHide[] = 'captcha';

// The fields container
$c = new Container('container');
if (!$flat) {
	// Use the tab container
	$c = new TabContainer('container');

	// Load the safe tab script
	JHtml::_('behavior.tabstate');
}
$root->addChild($c);

// Default grid when flat mode
if ($flat) {
	// Make one fieldset
	$fieldSets = array('' => new stdClass());
}

// Loop trough the field sets
foreach ($fieldSets as $name => $fieldSet) {
	// Check if the fieldset should be ignored
	if (in_array($name, $fieldsetsToHide)) {
		// Ignore the field
		continue;
	}

	$fields = $jform->getFieldset($name);

	// Sort the fields
	\DPCalendar\Helper\DPCalendarHelper::sortFields(
		$fields,
		$params->get(str_replace('com_dpcalendar.', '', $jform->getName()) . '_form_fields_order_' . $name, new stdClass())
	);

	// The grid of columns
	$grid = new Grid('fields-' . ($name ?: 'default'));

	$hiddenFields = array();

	// Loop trough the fields
	$counter = 0;
	$row     = null;
	foreach ($fields as $field) {
		// Check if the field should be ignored
		if (in_array($field->fieldname, $fieldsToHide)) {
			// Should not be rendered
			continue;
		}

		// Check if the field should be shown
		if ($fieldsToShow && !in_array($field->fieldname, $fieldsToShow)) {
			// Ignore the field
			continue;
		}

		if (strtolower($field->type) == 'hidden') {
			$hiddenFields[] = $field;
			continue;
		}

		if ($counter % $columns == 0) {
			$row = $grid->addRow(new Row(count($grid->getChildren()) + 1));
		}

		// Render the field
		$col = $row->addColumn(new Column($field->fieldname, 100 / $columns));
		$col->setContent($field->renderField(array('class' => $c->getPrefix() . 'field-' . $field->fieldname)));
		$counter++;
	}

	// The container of the form fields
	if ($flat) {
		$c->addChild($grid);
	} else {
		$c->addTab(new Tab('tab-' . $name, $name, JText::_($fieldSet->label)))->addChild($grid);
	}
}

// Add captcha when available
if ($captcha = $jform->renderField('captcha')) {
	$root->addChild(new Element('captcha'))->setContent($captcha);
}

foreach ($hiddenFields as $hiddenField) {
	$root->addChild(new Input($hiddenField->name, 'hidden', $hiddenField->name, $jform->getValue($hiddenField->name)));
}

if (!empty($displayData['return'])) {
	// The return hidden field
	$root->addChild(new Input('return', 'hidden', 'return', $displayData['return']));
}
// Add the task hidden field
$root->addChild(new Input('task', 'hidden', 'task'));

// Add the security token
$root->setContent(JHtml::_('form.token'));

// The description field
$editorSaveCode = '';
$d              = $jform->getField('description');
if ($d && method_exists($d, 'save')) {
	$editorSaveCode = $d->save();
}

// Load the needed javascript submit code
JFactory::getDocument()->addScriptDeclaration(
	"Joomla.submitbutton = function(task) {
	var form = document.getElementsByName('adminForm')[0];
	if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form)))
	{
		" . $editorSaveCode . "
		Joomla.submitform(task, form);
	}
	else
	{
		alert('" . $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')) . "');
	}
};
");

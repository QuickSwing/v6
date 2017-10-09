<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

// Load the events
DPCalendarHelper::renderLayout(
	'events.list',
	array('root' => $this->root, 'events' => $this->items, 'params' => $this->params)
);

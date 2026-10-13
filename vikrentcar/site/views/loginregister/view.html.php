<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');

class VikrentcarViewLoginregister extends JViewVikRentCar
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();

		$ppriceid = VikRequest::getInt('priceid', 0, 'request');
		$pplace = VikRequest::getInt('place', 0, 'request');
		$preturnplace = VikRequest::getInt('returnplace', 0, 'request');
		$pcarid = VikRequest::getInt('carid', 0, 'request');
		$pdays = VikRequest::getInt('days', 0, 'request');
		$ppickup = VikRequest::getInt('pickup', 0, 'request');
		$prelease = VikRequest::getInt('release', 0, 'request');

		$copts = [];
		$q = "SELECT * FROM `#__vikrentcar_optionals`;";
		$dbo->setQuery($q);
		$optionals = $dbo->loadAssocList();

		foreach ($optionals as $opt) {
			$tmpvar = VikRequest::getInt('optid' . $opt['id'], 0, 'request');
			if (!empty($tmpvar)) {
				$copts[$opt['id']] = $tmpvar;
			}
		}

		$this->priceid = $ppriceid;
		$this->place = $pplace;
		$this->returnplace = $preturnplace;
		$this->carid = $pcarid;
		$this->days = $pdays;
		$this->pickup = $ppickup;
		$this->release = $prelease;
		$this->copts = $copts;
		//theme
		$theme = VikRentCar::getTheme();
		if ($theme != 'default') {
			$thdir = VRC_SITE_PATH.DS.'themes'.DS.$theme.DS.'loginregister';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}

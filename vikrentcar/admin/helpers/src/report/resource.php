<?php
/**
 * @package     VikRentCar
 * @subpackage  core
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Declares the methods that a specific report resource should provide.
 * 
 * @since   1.15.9 (J) - 1.4.6 (WP)
 */
interface VRCReportResource
{
    /**
     * Returns the resource URL.
     * 
     * @return  string
     */
    public function getUrl();

    /**
     * Returns the resource absolute path.
     * 
     * @return  string
     */
    public function getPath();

    /**
     * Returns the resource summary.
     * 
     * @return  string
     */
    public function getSummary();
}

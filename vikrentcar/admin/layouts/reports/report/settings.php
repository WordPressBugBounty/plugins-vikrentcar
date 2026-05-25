<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This layout file should be called once at most per page.
 * 
 * @var string  $report     The report file name (identifier).
 * @var array   $fields     List of setting fields to render.
 * @var array   $settings   List of current report settings.
 * @var object  $instance   The report object instance.
 */
extract($displayData);

?>
<form action="#save-report-settings" method="post" name="vrc-report-custom-settings" id="vrc-report-custom-settings-form">

    <input type="hidden" name="report" value="<?php echo $report; ?>" />

    <div class="vrc-admin-container vrc-admin-container-full vrc-admin-container-compact">
        <div class="vrc-params-wrap">
            <div class="vrc-params-container">

            <?php
            /**
             * Handle multiple report profile settings.
             * 
             * @since   1.17.7 (J) - 1.7.7 (WP)
             */
            if ($instance->allowsProfileSettings()) {
                // load all the existing profiles
                $report_profiles = $instance->getSettingProfiles();

                // load the active profile
                $active_profile = $instance->getActiveProfile();

                // get the active profile name
                $profile_name = $active_profile ? ($report_profiles[$active_profile] ?? JText::translate('VRC_USE_DEFAULT')) : JText::translate('VRC_USE_DEFAULT');

                ?>
                <div class="vrc-params-block">

                    <div class="vrc-param-container">
                        <div class="vrc-param-label"><?php echo JText::translate('VRC_PROFILE_SETTINGS'); ?></div>
                        <div class="vrc-param-setting">
                            <select name="_profile" onchange="VRCCore.emitEvent('vrc-report-settings-profile-changed', {value: this.value});">
                                <option value="<?php echo JHtml::fetch('esc_attr', $active_profile); ?>"><?php echo $profile_name; ?></option>
                                <option value="_new"><?php echo JText::translate('VRC_PROFILE_NEW'); ?></option>
                            </select>
                            <span class="vrc-param-setting-comment"><?php echo JText::translate('VRC_PROFILE_SETTINGS_HELP'); ?></span>
                        </div>
                    </div>

                    <div class="vrc-param-container" data-profile="_new" style="display: none;">
                        <div class="vrc-param-label"><?php echo JText::translate('VRC_PROFILE_NAME'); ?></div>
                        <div class="vrc-param-setting">
                            <input type="text" name="_newprofile" value="" maxlength="64" />
                        </div>
                    </div>

                </div>
                <?php
            }
            ?>

                <div class="vrc-params-block">

                    <?php
                    // render the report custom settings
                    echo VRCParamsRendering::getInstance($fields, (array) $settings)->setInputName('data')->getHtml();
                    ?>

                </div>

            </div>
        </div>
    </div>

</form>

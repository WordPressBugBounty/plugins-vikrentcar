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

/**
 * Obtain vars from arguments received in the layout file.
 * This layout file should be called once at most per page.
 * 
 * @var 	string 	$vrc_page 	the name of the page invoking the layout file (i.e. dashboard).
 * @var 	array 	$room_ids 	optional array of specific room IDs to filter.
 */
extract($displayData);

// optional vars that only some pages may define
if (!isset($room_ids)) {
	$room_ids = array();
}

$report_name = 'occupancy_ranking';

$cookie = JFactory::getApplication()->input->cookie;
$cookie_step = $cookie->get("vrc_reportwidget_{$report_name}_step", 'weekend', 'string');

$vrc_app = VikRentCar::getVRCApplication();
$vrc_app->loadDatePicker();

// lang vars for JS
JText::script('VRCSEVLOWOCC');
JText::script('VRCSEVMEDOCC');
JText::script('VRCSEVHIGHOCC');
JText::script('VRCSEVLOWINDAYS');
JText::script('VRCSEVMEDINDAYS');
JText::script('VRCSEVHIGHINDAYS');

// get occupancy_ranking report instance
$report = VikRentCar::getReportInstance($report_name)->loadChartsAssets();

// website date format
$df = $report->getDateFormat();

// get the next festivities
$fests = VikRentCar::getFestivitiesInstance();
if ($fests->shouldCheckFestivities()) {
	$fests->storeNextFestivities();
}
$next_fests = $fests->loadFestDates();

// calculate the next from/to date
$is_fest = null;

// find next interval by default when page loads, unless there's a festivity
if ($cookie_step == 'month') {
	// this month
	$nowinfo = getdate();
	$ts_from = mktime(0, 0, 0, $nowinfo['mon'], 1, $nowinfo['year']);
	$ts_to = mktime(23, 59, 59, $nowinfo['mon'], date('t', $nowinfo[0]), $nowinfo['year']);
	$period_name = JText::translate('VRPVIEWRESTRICTIONSTWO');
} elseif ($cookie_step == 'week') {
	// this week
	$ts_from = time();
	$ts_to = strtotime("+1 week", $ts_from);
	$period_name = JText::translate('VRCWEEK');
} else {
	// next weekend by default
	$ts_from = strtotime("next friday");
	$ts_to = strtotime("next saturday");
	if ($ts_to < $ts_from) {
		$ts_to = strtotime("+1 week", $ts_to);
	}
	$period_name = JText::translate('VRCNEXTWEEKND');
}

if (count($next_fests)) {
	// we start from the next fest only if within 15 days
	$festlim = strtotime("+15 days", time());
	foreach ($next_fests as $next_fest) {
		if ($festlim >= $next_fest['festinfo'][0]->next_ts) {
			$is_fest = $next_fest['festinfo'][0]->next_ts;
			$ts_from = $next_fest['festinfo'][0]->from_ts;
			$ts_to = $next_fest['festinfo'][0]->to_ts;
			$period_name = $next_fest['festinfo'][0]->trans_name;
		}
		// we check only the very next festivity
		break;
	}
}

// obtain data from the report
$rparams = array(
	'fromdate' => date($df, $ts_from),
	'todate'   => date($df, $ts_to),
	'period'   => 'full',
	'krsort'   => 'occupancy',
	'krorder'  => 'DESC',
	'idroom'   => $room_ids,
);
$report->injectParams($rparams);
$report_cols = $report->getColumnsValues();
$report_values = $report->getReportValues(1);
$report_chart = null;
$report_chart_metas = array();
$chart_meta_data = array(
	'keys' => array(
		'occupancy',
		'tot_bookings',
		'nights_booked',
	),
);
$error = '';
$in_days = $report->countDaysTo($ts_from);
$in_days_to = $report->countDaysTo($ts_to);
$in_days_avg = $report->countAverageDays($in_days, $in_days_to);

if (!count($report_values)) {
	$error = strlen($report->getError()) ? $report->getError() : JText::translate('VRCNOTRACKINGS');
} else {
	// get doughnut chart for the occupancy
	$report_chart = $report->getChart(array(
		'type' => 'doughnut',
		'depth' => 1,
		'keys' => array('occupancy'),
	));
	$all_chart_metas = $report->getChartMetaData(null, $chart_meta_data);
	if (count($all_chart_metas)) {
		// merge all positions into one array
		foreach ($all_chart_metas as $pos_metas) {
			$report_chart_metas = array_merge($report_chart_metas, $pos_metas);
		}
	}
}

$promo_factors = VikRentCar::getPromotionFactors();
$promo_factors = $promo_factors === false ? array() : $promo_factors;
$current_occupancy = count($report_values) ? $report_values['occupancy']['value'] : 0;
$current_in_days = $in_days > 0 ? $in_days : $in_days_avg;

?>
<div class="vrc-reportwidget-outer vrc-reportwidget-occupancy vrc-reportwidget-<?php echo $vrc_page; ?>">
	<div class="vrc-reportwidget-commands">
		<div class="vrc-reportwidget-commands-main">
			<div class="vrc-reportwidget-command-dates">
				<div class="vrc-reportwidget-period-name"><?php echo $period_name; ?></div>
				<div class="vrc-reportwidget-period-date"><?php echo count($report_values) ? $report_values['day']['display_value'] : $rparams['fromdate'] . ' - ' . $rparams['todate']; ?></div>
			</div>
			<div class="vrc-reportwidget-command-chevron vrc-reportwidget-command-prev">
				<span class="vrc-reportwidget-prev"><?php VikRentCarIcons::e('chevron-left'); ?></span>
			</div>
			<div class="vrc-reportwidget-command-chevron vrc-reportwidget-command-next">
				<span class="vrc-reportwidget-next"><?php VikRentCarIcons::e('chevron-right'); ?></span>
			</div>
		</div>
		<div class="vrc-reportwidget-command-dots">
			<span class="vrc-widget-command-togglefilters vrc-reportwidget-togglefilters"><?php VikRentCarIcons::e('ellipsis-v'); ?></span>
		</div>
	</div>
	<div class="vrc-reportwidget-filters">
		<div class="vrc-reportwidget-filter">
			<span class="vrc-reportwidget-datepicker">
				<?php VikRentCarIcons::e('calendar', 'vrc-widget-caltrigger'); ?>
				<input type="text" id="rp_fromdate" class="vrc-report-datepicker-from" value="<?php echo $rparams['fromdate']; ?>" />
			</span>
		</div>
		<div class="vrc-reportwidget-filter">
			<span class="vrc-reportwidget-datepicker">
				<?php VikRentCarIcons::e('calendar', 'vrc-widget-caltrigger'); ?>
				<input type="text" id="rp_todate" class="vrc-report-datepicker-to" value="<?php echo $rparams['todate']; ?>" />
			</span>
			<input type="hidden" id="rp_is_fest" value="<?php echo !empty($is_fest) ? $is_fest : ''; ?>" />
		</div>
		<div class="vrc-reportwidget-filter">
			<select id="rp_step">
				<option value="weekend"<?php echo $cookie_step == 'weekend' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCWEEKND'); ?></option>
				<option value="week"<?php echo $cookie_step == 'week' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRCWEEK'); ?></option>
				<option value="month"<?php echo $cookie_step == 'month' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VRPVIEWRESTRICTIONSTWO'); ?></option>
			</select>
		</div>
		<div class="vrc-reportwidget-filter vrc-reportwidget-filter-confirm">
			<button type="button" class="btn vrc-config-btn" id="vrc-reportwidget-update"><?php echo JText::translate('VRADMINNOTESUPD'); ?></button>
		</div>
	</div>
	<div class="vrc-reportwidget-body">
		<div class="vrc-reportwidget-chart">
		<?php
		if (empty($report_chart)) {
			?>
			<p class="err"><?php echo $error; ?></p>
			<?php
		} else {
			// parse the Chart meta data (if any)
			if (count($report_chart_metas)) {
				?>
			<div class="vrc-reportwidget-chart-metas">
				<?php
				foreach ($report_chart_metas as $chart_meta) {
					?>
				<div class="vrc-reportwidget-chart-meta<?php echo isset($chart_meta['class']) ? ' ' . $chart_meta['class'] : ''; ?>">
					<div class="vrc-reportwidget-chart-meta-inner">
						<div class="vrc-reportwidget-chart-meta-lbl"><?php echo $chart_meta['label']; ?></div>
						<div class="vrc-reportwidget-chart-meta-val">
							<span class="vrc-reportwidget-chart-meta-val-main"><?php echo $chart_meta['value']; ?></span>
						<?php
						if (!empty($chart_meta['descr'])) {
							?>
							<span class="vrc-reportwidget-chart-meta-val-descr"><?php echo $chart_meta['descr']; ?></span>
							<?php
						}
						?>
						</div>
					</div>
				</div>
					<?php
				}
				?>
			</div>
				<?php
			}

			// display the canvas element
			echo $report_chart;
		}
		?>
		</div>
	</div>
</div>

<a class="vrc-reportwidget-promo-baseuri" style="display: none;" href="index.php?option=com_vikrentcar&task=newseason"></a>

<script type="text/javascript">
var vrcReportWidgetPromoFactors = <?php echo json_encode($promo_factors); ?>;
var vrcReportWidgetOccupancy = <?php echo $current_occupancy; ?>;
var vrcReportWidgetIndays = <?php echo $current_in_days; ?>;
var vrcReportWidgetDfrom = "<?php echo $rparams['fromdate']; ?>";
var vrcReportWidgetDto = "<?php echo $rparams['todate']; ?>";
var vrcReportWidgetPname = "<?php echo addslashes((count($report_values) ? $report_values['day']['display_value'] : $period_name)); ?>";
var vrcReportWidgetRoomids = <?php echo json_encode((is_array($room_ids) ? $room_ids : array())); ?>;
jQuery(function() {
	// suggest promotion when the page loads for the data we have obtained via PHP
	vrcReportWidgetSuggestPromotion(null);
	//
	jQuery('.vrc-report-datepicker-from, .vrc-report-datepicker-to').datepicker({
		minDate: "-1y",
		maxDate: "+2y",
		yearRange: "<?php echo (date('Y') - 1); ?>:<?php echo (date('Y') + 2); ?>",
		changeMonth: true,
		changeYear: true,
		dateFormat: "<?php echo $report->getDateFormat('jui'); ?>",
		onSelect: vrcReportWidgetCheckDates
	});
	jQuery('i.vrc-widget-caltrigger').click(function() {
		var jdp = jQuery(this).parent().find('input.hasDatepicker');
		if (jdp.length) {
			jdp.focus();
		}
	});
	jQuery('#rp_step').change(function() {
		// update cookie for the step selected
		var nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vrc_reportwidget_<?php echo $report_name; ?>_step=" + jQuery(this).val() + "; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
	});
	jQuery('.vrc-reportwidget-prev, .vrc-reportwidget-next, #vrc-reportwidget-update').click(function() {
		if (typeof vrcJModalShowCallback === 'function') {
			// simulate STOP click for Dashboard automatic reload
			vrcJModalShowCallback();
		}
		// make the AJAX request to obtain the necessary report data for the next/prev/selected dates
		var direction = 'load';
		if (jQuery(this).hasClass('vrc-reportwidget-prev')) {
			direction = 'prev';
		} else if (jQuery(this).hasClass('vrc-reportwidget-next')) {
			direction = 'next';
		}
		// always hide the filters when making a new request
		jQuery('.vrc-reportwidget-filters').hide();
		// always hide the promotion suggestion when making a new request
		vrcReportWidgetDestroyPromotion();
		//
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikRentCar::ajaxUrl('index.php?option=com_vikrentcar&task=get_report_data'); ?>",
			data: {
				tmpl: "component",
				report_name: "<?php echo $report_name; ?>",
				current_fest: jQuery('#rp_is_fest').val(),
				current_fromdate: jQuery('#rp_fromdate').val(),
				current_todate: jQuery('#rp_todate').val(),
				step: jQuery('#rp_step').val(),
				direction: direction,
				chart_meta_data: JSON.stringify(<?php echo json_encode($chart_meta_data); ?>),
				idroom: <?php echo json_encode($room_ids); ?>,
			}
		}).done(function(res) {
			var obj = null;
			try {
				obj = JSON.parse(res);
			} catch(e) {
				obj = null;
				console.log(res, e);
				alert('Invalid response received');
			}
			if (obj === null) {
				return;
			}
			// update datepicker dates and display values for the next request
			jQuery('#rp_fromdate').val(obj.fromdate).datepicker('setDate', obj.fromdate);
			jQuery('#rp_todate').val(obj.todate).datepicker('setDate', obj.todate);
			jQuery('.vrc-reportwidget-period-name').text(obj.period_name);
			if (obj.period_date && obj.period_date.length) {
				jQuery('.vrc-reportwidget-period-date').text(obj.period_date);
			} else {
				jQuery('.vrc-reportwidget-period-date').text(obj.fromdate + ' - ' + obj.todate);
			}
			if (obj.is_fest) {
				jQuery('#rp_is_fest').val(obj.is_fest);
			} else {
				jQuery('#rp_is_fest').val("");
			}
			//
			if (obj.error) {
				// an error occurred, remove the current Chart (if any), display error, and show filters
				jQuery('.vrc-reportwidget-chart').first().html('<p class="err">' + obj.error + '</p>');
				jQuery('.vrc-reportwidget-filters').show();
				return;
			}
			// unset current meta data
			if (jQuery('.vrc-reportwidget-chart-metas').length) {
				jQuery('.vrc-reportwidget-chart-metas').remove();
			}
			//
			if (!jQuery('.vrc-reportwidget-chart').find('canvas').length) {
				// canvas is missing, add it from zero as no update can be made
				jQuery('.vrc-reportwidget-chart').first().html(obj.report_chart + "<script type=\"text/javascript\">" + obj.report_script + "<\/script>");
				vrcReportWidgetBuildMetas(obj.report_chart_metas);
				vrcReportWidgetSuggestPromotion(obj);
				return;
			}
			if (typeof vrcReportPieData === 'undefined') {
				alert('Could not update chart');
				return;
			}
			// update Chart report properties
			vrcReportPieData.labels = obj.chart_labels;
			vrcReportPieData.datasets[0].data = obj.chart_data;
			try {
				vrcReportPieData.datasets[0].backgroundColor = JSON.parse(obj.chart_colors.pieBackgroundColor);
				vrcReportPieData.datasets[0].hoverBorderColor = JSON.parse(obj.chart_colors.pieHoverBorderColor);
			} catch(e) {
				// do nothing
			}
			// trigger Chart update
			jQuery('.vrc-reportwidget-chart').find('canvas').trigger('vrc_update_report_chart');
			// re-build meta data
			vrcReportWidgetBuildMetas(obj.report_chart_metas);
			// suggest promotion
			vrcReportWidgetSuggestPromotion(obj);
		}).fail(function() {
			alert('Request failed');
		});
	});
	jQuery('.vrc-reportwidget-togglefilters').click(function() {
		jQuery('.vrc-reportwidget-filters').toggle();
	});
});
function vrcReportWidgetCheckDates(selectedDate, inst) {
	if (selectedDate === null || inst === null) {
		return;
	}
	var cur_from_date = jQuery(this).val();
	if (jQuery(this).hasClass("vrc-report-datepicker-from") && cur_from_date.length) {
		var nowstart = jQuery(this).datepicker("getDate");
		var nowstartdate = new Date(nowstart.getTime());
		jQuery(".vrc-report-datepicker-to").datepicker("option", {minDate: nowstartdate});
	}
}
function vrcReportWidgetBuildMetas(chart_metas_arr) {
	if (chart_metas_arr.length) {
		var chart_metas = '<div class="vrc-reportwidget-chart-metas">';
		for (var i in chart_metas_arr) {
			if (!chart_metas_arr.hasOwnProperty(i)) {
				continue;
			}
			chart_metas += '<div class="vrc-reportwidget-chart-meta ' + (chart_metas_arr[i].hasOwnProperty('class') ? chart_metas_arr[i]['class'] : '') + '">';
			chart_metas += '<div class="vrc-reportwidget-chart-meta-inner">';
			chart_metas += '<div class="vrc-reportwidget-chart-meta-lbl">' + chart_metas_arr[i]['label'] + '</div>';
			chart_metas += '<div class="vrc-reportwidget-chart-meta-val">';
			chart_metas += '<span class="vrc-reportwidget-chart-meta-val-main">' + chart_metas_arr[i]['value'] + '</span>';
			if (chart_metas_arr[i].hasOwnProperty('descr') && chart_metas_arr[i]['descr'].length) {
				chart_metas += '<span class="vrc-reportwidget-chart-meta-val-descr">' + chart_metas_arr[i]['descr'] + '</span>';
			}
			chart_metas += '</div>';
			chart_metas += '</div>';
			chart_metas += '</div>';
		}
		chart_metas += '</div>';
		// prepend composed metas
		jQuery('.vrc-reportwidget-chart').prepend(chart_metas);
	}
}
function vrcReportWidgetSuggestPromotion(response) {
	var promo_baseuri = jQuery('.vrc-reportwidget-promo-baseuri').attr('href');
	if (response !== null) {
		// AJAX response
		if (response.report_values.hasOwnProperty('occupancy')) {
			// update global vars
			vrcReportWidgetOccupancy = response.report_values['occupancy']['value'];
			vrcReportWidgetIndays = response.in_days > 0 ? response.in_days : response.in_days_avg;
			vrcReportWidgetDfrom = response.fromdate;
			vrcReportWidgetDto = response.todate;
			vrcReportWidgetPname = response.period_name;
		} else {
			// should not occur
			vrcReportWidgetDestroyPromotion();
			return;
		}
	}
	if (vrcReportWidgetIndays <= 0 || vrcReportWidgetOccupancy >= 100 || !vrcReportWidgetPromoFactors.hasOwnProperty('compare')) {
		// no promotions for dates in the past, full occupancy or no VCM
		vrcReportWidgetDestroyPromotion();
		return;
	}
	// parse factors depending on the occupancy and in_days
	var occupancy_severity 	= null;
	var in_days_severity 	= null;
	var occupancy_compare  	= vrcReportWidgetPromoFactors['compare']['occupancy'];
	var in_days_compare  	= vrcReportWidgetPromoFactors['compare']['in_days'];
	for (var severity in vrcReportWidgetPromoFactors['occupancy']) {
		if (!vrcReportWidgetPromoFactors['occupancy'].hasOwnProperty(severity)) {
			continue;
		}
		if (vrcReportWidgetOccupancy >= vrcReportWidgetPromoFactors['occupancy'][severity] && occupancy_compare.indexOf('gt') >= 0) {
			// severity found
			occupancy_severity = severity;
			break;
		} else if (vrcReportWidgetOccupancy <= vrcReportWidgetPromoFactors['occupancy'][severity] && occupancy_compare.indexOf('lt') >= 0) {
			// severity found
			occupancy_severity = severity;
			break;
		}
	}
	for (var severity in vrcReportWidgetPromoFactors['in_days']) {
		if (!vrcReportWidgetPromoFactors['in_days'].hasOwnProperty(severity)) {
			continue;
		}
		if (vrcReportWidgetIndays >= vrcReportWidgetPromoFactors['in_days'][severity] && in_days_compare.indexOf('gt') >= 0) {
			// severity found
			in_days_severity = severity;
			break;
		} else if (vrcReportWidgetIndays <= vrcReportWidgetPromoFactors['in_days'][severity] && in_days_compare.indexOf('lt') >= 0) {
			// severity found
			in_days_severity = severity;
			break;
		}
	}
	if (occupancy_severity === null || in_days_severity === null) {
		// could not find appropriate severity for occupancy or in_days
		console.error('could not find appropriate severity for occupancy or in_days', occupancy_severity, in_days_severity);
		vrcReportWidgetDestroyPromotion();
		return;
	}
	var occupancy_key = 'occupancy_' + occupancy_severity;
	var in_days_key   = 'in_days_' + in_days_severity;
	if (!vrcReportWidgetPromoFactors['discount'].hasOwnProperty(in_days_key) || !vrcReportWidgetPromoFactors['discount'][in_days_key].hasOwnProperty(occupancy_key)) {
		// could not find appropriate discount value for the severity obtained
		console.error('could not find appropriate discount value for the severity obtained', occupancy_key, in_days_key);
		vrcReportWidgetDestroyPromotion();
		return;
	}
	var suggested_disc = vrcReportWidgetPromoFactors['discount'][in_days_key][occupancy_key];
	// suggest promotion
	var promo_uri = promo_baseuri + '&promo=1&diffcost=' + suggested_disc + '&from=' + vrcReportWidgetDfrom + '&to=' + vrcReportWidgetDto + '&promoname=' + vrcReportWidgetPname;
	// consider type of promotion (last minute or early bird)
	if (in_days_severity == 'low') {
		// suggest last minute days
		promo_uri += '&promolastmind=' + vrcReportWidgetPromoFactors['in_days'][in_days_severity];
	} else if (in_days_severity == 'high') {
		// suggest early bird days advance (28d)
		promo_uri += '&promodaysadv=28';
	}
	if (vrcReportWidgetRoomids.length) {
		for (var idr in vrcReportWidgetRoomids) {
			if (!vrcReportWidgetRoomids.hasOwnProperty(idr)) {
				continue;
			}
			promo_uri += '&rooms[]=' + vrcReportWidgetRoomids[idr];
		}
	}
	//
	// insight texts
	var vrcPromoInsightTitle = '';
	var vrcPromoInsightHelp = '';
	if (occupancy_severity == 'low') {
		vrcPromoInsightTitle = Joomla.JText._('VRCSEVLOWOCC');
	} else if (occupancy_severity == 'med') {
		vrcPromoInsightTitle = Joomla.JText._('VRCSEVMEDOCC');
	} else if (occupancy_severity == 'high') {
		vrcPromoInsightTitle = Joomla.JText._('VRCSEVHIGHOCC');
	}
	if (in_days_severity == 'low') {
		vrcPromoInsightHelp = Joomla.JText._('VRCSEVLOWINDAYS');
	} else if (in_days_severity == 'med') {
		vrcPromoInsightHelp = Joomla.JText._('VRCSEVMEDINDAYS');
	} else if (in_days_severity == 'high') {
		vrcPromoInsightHelp = Joomla.JText._('VRCSEVHIGHINDAYS');
	}
	// cookie val
	var hidepromoinsights = false;
	if (response == null && jQuery('.vrc-reportwidget-ratesoverv').length) {
		// hide by default the insights when the page loads and we're on the Rates Overview
		hidepromoinsights = true;
	}
	var buiscuits = document.cookie;
	if (buiscuits.length) {
		var hidepromoinsightsck = "vrcHidePromoInsights=1";
		if (buiscuits.indexOf(hidepromoinsightsck) >= 0) {
			hidepromoinsights = true;
		}
	}
	// build HTML content
	var promo_content = '';
	promo_content += '<div class="vrc-reportwidget-promo-wrap ' + (hidepromoinsights ? 'vrc-reportwidget-promo-wrap-hidden' : 'vrc-reportwidget-promo-wrap-visible') + '" style="display: none;">';
	promo_content += '<div class="vrc-reportwidget-promo-inner">';
	promo_content += '<div class="vrc-reportwidget-promo-icon"><span onclick="vrcReportWidgetShowPromotion();" title="<?php echo addslashes(JText::translate('VRCINSIGHT')); ?>"><?php VikRentCarIcons::e('lightbulb'); ?></span></div>';
	promo_content += '<div class="vrc-reportwidget-promo-help" style="' + (hidepromoinsights ? 'display: none;' : '') + '">';
	promo_content += '<div class="vrc-reportwidget-promo-close"><span onclick="vrcReportWidgetHidePromotion();"><?php VikRentCarIcons::e('times'); ?></span></div>';
	promo_content += '<h5><?php echo addslashes(JText::translate('VRCINSIGHT')); ?></h5><p class="vrc-promo-tip-title">' + vrcPromoInsightTitle + '</p><p class="vrc-promo-tip-help">' + vrcPromoInsightHelp + '</p>';
	promo_content += '<div class="vrc-reportwidget-promo-link">';
	promo_content += '<a href="' + promo_uri + '" class="btn vrc-config-btn" target="_blank"><?php VikRentCarIcons::e('rocket'); ?> <?php echo addslashes(JText::translate('VRCSUGGCREATEPROMOOCC')); ?></a>';
	promo_content += '</div>';
	promo_content += '</div>';
	promo_content += '</div>';
	promo_content += '</div>';
	if (!jQuery('.vrc-reportwidget-chart').find('.vrc-reportwidget-promo-wrap').length) {
		jQuery('.vrc-reportwidget-chart').first().append(promo_content);
	}
	setTimeout(function() {
		jQuery('.vrc-reportwidget-promo-wrap').fadeIn();
	}, 1000);
}
function vrcReportWidgetDestroyPromotion() {
	jQuery('.vrc-reportwidget-promo-wrap').remove();
}
function vrcReportWidgetHidePromotion() {
	jQuery('.vrc-reportwidget-promo-help').hide();
	jQuery('.vrc-reportwidget-promo-wrap').removeClass('vrc-reportwidget-promo-wrap-visible').addClass('vrc-reportwidget-promo-wrap-hidden');
	// set cookie
	var nd = new Date();
	nd.setTime(nd.getTime() + (7*24*60*60*1000));
	document.cookie = "vrcHidePromoInsights=1; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
}
function vrcReportWidgetShowPromotion() {
	jQuery('.vrc-reportwidget-promo-wrap').addClass('vrc-reportwidget-promo-wrap-visible').removeClass('vrc-reportwidget-promo-wrap-hidden');
	jQuery('.vrc-reportwidget-promo-help').show();
	// unset cookie
	var nd = new Date();
	nd.setTime(nd.getTime() - (7*24*60*60*1000));
	document.cookie = "vrcHidePromoInsights=; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
}
<?php echo !empty($report_chart) && strlen($report->getScript()) ? $report->getScript() : ''; ?>
</script>

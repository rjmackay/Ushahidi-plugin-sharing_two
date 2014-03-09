<?php
/**
 * Sharing js file.
 *
 * Handles javascript stuff related to sharing controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Sharing JS View
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
?>
// Sharing JS
function fillFields(site)
{
	$("#site_id").attr("value", site.id);
	$("#site_name").attr("value", site.site_name);
	$("#site_url").attr("value", site.site_url);
	$("#site_color").attr("value", site.site_color);
	$("#share_reports").attr("checked", site.share_reports == 1);
	$("#share_categories").attr("checked", site.share_categories == 1);
	$("#site_username").attr("value", site.site_username);
	$("#site_password").attr("value", site.site_password);
}

// Ajax Submission
function sharingAction ( action, confirmAction, id )
{
	var statusMessage;
	var answer = confirm('<?php echo Kohana::lang('ui_admin.are_you_sure_you_want_to'); ?> ' + confirmAction + '?')
	if (answer){
		// Set Category ID
		$("#site_id_action").attr("value", id);
		// Set Submit Type
		$("#action").attr("value", action);
		// Submit Form
		$("#sharingListing").submit();
	}
}
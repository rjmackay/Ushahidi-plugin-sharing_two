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
function fillFields(id, site_url, site_name, site_color, share_reports, share_categories)
{
	$("#site_id").attr("value", decodeURIComponent(id));
	$("#site_name").attr("value", decodeURIComponent(site_name));
	$("#site_url").attr("value", decodeURIComponent(site_url));
	$("#site_color").attr("value", decodeURIComponent(site_color));
	$("#share_reports").attr("checked", decodeURIComponent(share_reports) == 1);
	$("#share_categories").attr("checked", decodeURIComponent(share_categories) == 1);
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
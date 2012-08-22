<?php
/**
 * Sharing_bar js file.
 * 
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Sharing Module
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
?>

<script type="text/javascript">
$(document).ready(function() {
	
	// Sharing Layer[s] Switch Action
	$("#sharing_switch a").click(function() {
		var shareID = this.id.substring(6);
	
		if ( ! $(this).hasClass("active") ) {
			$("#sharing_switch a").removeClass("active");
			$(this).addClass("active");
			
			// Update report filters
			map.updateReportFilters({sharing: shareID});
		}
		
		return false;
	});
});
</script>
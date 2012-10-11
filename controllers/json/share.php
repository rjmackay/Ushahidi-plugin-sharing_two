<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Json Controller
 * Generates Map GeoJSON File
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com>
 * @package	   Ushahidi - http://source.ushahididev.com
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class Share_Controller extends Json_Controller {

	/**
	 * Retrieve Single Marker (and its neighbours)
	 * 
	 * @param int $incident_id
	 */
	public function single($incident_id = 0)
	{
		$json_features = array();

		$incident_id = intval($incident_id);

		// Check if incident valid/approved
		if ( ! Sharing_Incident_Model::is_valid_incident($incident_id, TRUE) )
		{
			throw new Kohana_404_Exception();
		}

		// Load the incident
		// @todo avoid the double load here
		$marker = ORM::factory('sharing_incident')->where('sharing_incident.incident_active', 1)->with('location')->find($incident_id);
		if ( ! $marker->loaded )
		{
			throw new Kohana_404_Exception();
		}
		
		// Get geojson features for main incident (including geometry) 
		$json_features = $this->markers_geojson(array($marker), 0, null, null, TRUE);

		// Get the neigbouring incidents & their json (without geometries)
		$neighbours = Sharing_Incident_Model::get_neighbouring_incidents($incident_id, FALSE, 20, 100);
		if ($neighbours)
		{
			$json_features = array_merge($json_features, $this->markers_geojson($neighbours, 0, null, null, FALSE));
		}

		Event::run('ushahidi_filter.json_single_features', $json_features);

		$this->render_geojson($json_features);
	}

}

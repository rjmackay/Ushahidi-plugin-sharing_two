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
	 * Read in new layer JSON from shared connection
	 * @param int $sharing_id - ID of the new Share Layer
	 */
	public function index( $sharing_id = FALSE )
	{
		$sharing_id = $_GET['sharing'];
		
		if (!$sharing_id)
		{
			return $this->render_geojson(array());
		}
		
		// Get This Sharing ID Color
		$sharing = ORM::factory('sharing')
			->find($sharing_id);

		if( ! $sharing->loaded)
			throw new Kohana_404_Exception();

		$sharing_url = sharing_helper::clean_url($sharing->sharing_url);
		$sharing_color = $sharing->sharing_color;
		
		// Retrieve all markers
		$markers = ORM::factory('sharing_incident')
								->where('sharing_id', $sharing_id)
								->find_all();

		$json_features = $this->markers_geojson($markers, 0, $sharing_color, null);
		
		return $this->render_geojson($json_features);
	}

	/**
	 * Read in new layer JSON from shared connection
	 * @param int $sharing_id - ID of the new Share Layer
	 */
	public function cluster()
	{
		$sharing_id = $_GET['sharing'];
		
		if (!$sharing_id)
		{
			return $this->render_geojson(array());
		}
		
		// Get This Sharing ID Color
		$sharing = ORM::factory('sharing')->find($sharing_id);

		if( ! $sharing->loaded)
			throw new Kohana_404_Exception();

		$sharing_url = sharing_helper::clean_url($sharing->sharing_url);
		$sharing_color = $sharing->sharing_color;
		
		$markers = ORM::factory('sharing_incident')
								->where('sharing_id', $sharing_id)
								->find_all();
		
		$json_features = $this->clusters_geojson($markers, 0, $sharing_color, null);
		
		return $this->render_geojson($json_features);
	}

}

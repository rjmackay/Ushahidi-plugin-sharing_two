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
	 * Generate geojson
	 * 
	 * @param string $type type of geojson to generate. Valid options are: 'clusters' and 'markers'
	 **/
	protected function geojson($type)
	{
		$color = Kohana::config('settings.default_map_all');
		$icon = "";
		$markers = FALSE;
		$json_features = array();
		
		if (Kohana::config('settings.default_map_all_icon_id'))
		{
			$icon_object = ORM::factory('media')->find(Kohana::config('settings.default_map_all_icon_id'));
			$icon = url::convert_uploaded_to_abs($icon_object->media_medium);
		}

		// Category ID
		$category_id = (isset($_GET['c']) AND intval($_GET['c']) > 0) ? intval($_GET['c']) : 0;
		// Get the category colour
		$cat = FALSE;
		if (Category_Model::is_valid_category($category_id))
		{
			// Get the color & icon
			$cat = ORM::factory('category', $category_id);
			$color = $cat->category_color;
			if ($cat->category_image)
			{
				$icon = url::convert_uploaded_to_abs($cat->category_image);
			}
		}
		
		// Get sharing site info
		// Quick hack to set default sharing value
		! isset($_GET['sharing']) ? $_GET['sharing'] = Kohana::config('sharing_two.default_sharing_filter') : null;
		// Check we're filtered to a single country site
		if ($_GET['sharing'] != 'all' AND $_GET['sharing'] != 'main')
		{
			if ($site_id = intval($_GET['sharing']))
			{
				// Check the sharing site is active
				$site = ORM::factory('sharing_site')->where('site_active', 1)->find($site_id);
				if ($site->loaded)
				{
					$site_url = sharing_helper::clean_url($site->site_url);
					// Only set color if all categories, category color overrides site color
					if (!$cat)
					{
						$color = $site->site_color;
					}
					$icon = "";
					
					$marker_query = ORM::factory('sharing_incident')->with('location');
					if ($cat)
					{
						$marker_query
							->join('sharing_incident_category', 'sharing_incident_id', 'sharing_incident.id', 'INNER')
							->where('category_id', $cat->id);
					}
					// Retrieve all markers
					$markers = $marker_query->where('sharing_site_id', $site->id)->find_all();
				}
			}
		}

		// Run event ushahidi_filter.json_replace_markers
		// This allows a plugin to completely replace $markers
		// If markers are added at this point we don't bother fetching incidents at all
		Event::run('ushahidi_filter.json_replace_markers', $markers);

		// Fetch the incidents
		if (! $markers)
		{
			$markers = (isset($_GET['page']) AND intval($_GET['page']) > 0)
			    ? reports::fetch_incidents(TRUE)
			    : reports::fetch_incidents();
		}
		
		// Run event ushahidi_filter.json_alter_markers
		// This allows a plugin to alter $markers
		// Plugins can add or remove markers as needed
		Event::run('ushahidi_filter.json_alter_markers', $markers);
		
		if ($_GET['sharing'] == 'all')
		{
			$marker_query = ORM::factory('sharing_incident')->with('location');
			if ($cat)
			{
				$marker_query
					->join('sharing_incident_category', 'sharing_incident_id', 'sharing_incident.id', 'INNER')
					->where('category_id', $cat->id);
			}
			$sharing_markers = $marker_query->find_all();
			$markers = array_merge($markers->as_array(), $sharing_markers->as_array());
		}
		
		// Get geojson features array
		$function = "{$type}_geojson";
		$json_features = array_merge($json_features, $this->$function($markers, $category_id, $color, $icon));
		
		$this->render_geojson($json_features);
	}

}

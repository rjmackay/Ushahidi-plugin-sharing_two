<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sharing Library
 * Support functions for sharing plugin
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Robbie Mackay <rm@robbiemackay.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class Sharing {
	
	public static function routing()
	{
		if (stripos(Router::$current_uri, 'json') !== FALSE)
		{
			Router::$current_uri = str_replace('json','json/share', Router::$current_uri);
		}
	}
	
	public static function process_get_param()
	{
		// Quick hack to set default sharing value
		! isset($_GET['sharing']) ? $_GET['sharing'] = Kohana::config('sharing_two.default_sharing_filter') : null;
	}
	

	public static function sharing_admin_nav()
	{
		$this_sub_page = Event::$data;
		echo ($this_sub_page == "sharing") ? "Sharing" : "<a href=\"".url::site()."admin/manage/sharing\">Sharing</a>";
	}

	/**
	 * Loads the sharing bar on the side bar on the main page
	 */
	public static function sharing_bar()
	{
		// Get all active Shares
		$sites = array();
		foreach (ORM::factory('sharing_site')
					->where('site_active', 1)
					->find_all() as $site)
		{
			$sites[$site->id] = array($site->site_name, $site->site_color);
		}

		$sharing_bar = View::factory('sharing/sharing_bar');

		$sharing_bar->sites = $sites;
		$sharing_bar->render(TRUE);
	}
	
	/**
	 * Loads the JavaScript for the sharing sidebar
	 */
	public function sharing_bar_js()
	{
		$js = View::factory('js/sharing_bar_js');
		$js->render(TRUE);
	}
	
	
	/**
	 * Add sharing markers to json when showing all sites
	 **/
	public function json_alter_markers()
	{
		// If filter set to all sites: load extra incidents
		if ($_GET['sharing'] == 'all')
		{
			// load sharing site incidents
			$markers = Event::$data;
			
			// Get markers array
			if ($markers instanceof ORM_Iterator)
			{
				$markers = $markers->as_array();
			}
			elseif ($markers instanceof Database_Result)
			{
				$markers = $markers->result_array();
			}
			
			$sharing_markers = ORM::factory('sharing_incident')
									->find_all();
			
			Event::$data = array_merge($markers, $sharing_markers->as_array());
		}
		// if filter set to main site only, do nothing.
		elseif ($_GET['sharing'] == 'main')
		{
			// Do nothing: all incidents loaded already
		}
	}

	/**
	 * Replace json markers with current sharing site
	 */
	public function json_replace_markers()
	{
		// Check we're filtered to a single country site
		if ($_GET['sharing'] != 'all' AND $_GET['sharing'] != 'main')
		{
			$sharing_id = intval($_GET['sharing']);
			
			if (!$sharing_id) return;
			
			// Get This Sharing ID Color
			$sharing = ORM::factory('sharing')->find($sharing_id);

			// Invalid sharing id: do nothing.
			// This should possibly set an empty markers array
			if(!$sharing->loaded) return;
	
			$sharing_url = sharing_helper::clean_url($sharing->sharing_url);
			$sharing_color = $sharing->sharing_color;
			
			// Retrieve all markers
			$markers = ORM::factory('sharing_incident')
									->where('sharing_id', $sharing_id)
									->find_all();
			
			Event::$data = $markers;
		}
	}
	
	/**
	 * Callback for ushahidi_filter.fetch_incidents_set_params
	 * Add filter for source to incidents query
	 */
	public function fetch_incidents_set_params()
	{
		$params = Event::$data;
		
		if ($_GET['sharing'] == 'main')
		{
			$params[] = "i.source = 'main'";
		}
		elseif ($_GET['sharing'] != 'all' AND $_GET['sharing'] != array('all'))
		{
			$sharing = $_GET['sharing'];
			// Convert to array
			if (! is_array($sharing))
			{
				$sharing = array($sharing);
			}
			
			// escape and implode values
			$sharing = '('.implode(', ', array_map(array(Database::instance(), 'escape'), $_GET['sharing'])).')';
			$params[] = "i.source IN $sharing";
		}
		
		if (isset($_GET['m']))
		{
			$media_types = $_GET['m'];
			if (!is_array($media_types))
			{
				$media_types = explode(',',$media_types);
			}
			
			// An array of media filters has been specified
			// Validate the media types
			$media_types = array_map('intval', $media_types);
			
			if (count($media_types) > 0)
			{
				$media_types = implode(",", $media_types);
				$media_filter_key = array_search('i.id IN (SELECT DISTINCT incident_id FROM '.Kohana::config('database.default.table_prefix').'media WHERE media_type IN ('.$media_types.'))', $params);
				$params[$media_filter_key] = "
				(
					(i.id IN (SELECT DISTINCT incident_id FROM ".Kohana::config('database.default.table_prefix')."media
					WHERE media_type IN (".$media_types.")) AND i.source = 'main')
				OR
					(i.id IN (SELECT DISTINCT sharing_incident_id FROM ".Kohana::config('database.default.table_prefix')."sharing_incident_media sim
					LEFT JOIN ".Kohana::config('database.default.table_prefix')."media ON (sim.media_id = media.id)
					WHERE media_type IN (".$media_types.")) AND i.source != 'main')
				)";
			}
		}
		
		Event::$data = $params;
	}
	
	/*
	 * Callback for ushahidi_filter.get_incidents_sql
	 * Swap incidents table for combined incident view
	 */
	public function get_incidents_sql()
	{
		$sql = Event::$data;
		
		$sql = str_replace(Kohana::config('database.default.table_prefix').'incident i ', Kohana::config('database.default.table_prefix').'sharing_combined_incident i ', $sql);
		
		$sql = str_replace('i.id incident_id', 'i.id incident_id, i.source ', $sql);
		
		$sql = str_replace(Kohana::config('database.default.table_prefix')."incident_category ic ON (ic.incident_id = i.id) ",
			Kohana::config('database.default.table_prefix')."sharing_combined_incident_category ic ON ((ic.incident_id = i.id AND i.source = 'main') OR (ic.sharing_incident_id = i.id AND i.source != 'main')) ",
			$sql
		);
		
		Event::$data = $sql;
	}
	
	/**
	 * Callback for ushahidi_action.report_filters_ui
	 * Render sharing site filter
	 */
	public function report_filters_ui()
	{
		$filter = View::factory('reports/sharing_filter');
		$filter->sites = ORM::factory('sharing_site')
					->where('site_active', 1)
					->find_all();
		$filter->render(TRUE);
	}
	
	/**
	 * Callback for ushahidi_action.report_js_filterReportsAction
	 * Render js for handling sharing site filter
	 */
	public function report_js_filterReportsAction()
	{
		View::factory('reports/sharing_filter_js')->render(TRUE);
	}
}

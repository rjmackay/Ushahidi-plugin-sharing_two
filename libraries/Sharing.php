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
		if (Router::$current_uri == 'json/index' OR Router::$current_uri == 'json/cluster' OR Router::$current_uri == 'json')
		{
			Router::$current_uri = str_replace('json','json/share', Router::$current_uri);
		}
	}
	
	public static function process_get_param()
	{
		// Quick hack to set default sharing value
		! isset($_GET['sharing']) ? $_GET['sharing'] = Kohana::config('sharing_two.default_sharing_filter') : null;
		
		// Ensure sharing is an array
		if (! is_array($_GET['sharing']))
		{
			$_GET['sharing'] = array($_GET['sharing']);
		}
		
		if (($key = array_search('all', $_GET['sharing'])) !== FALSE)
		{
			unset($_GET['sharing'][$key]);
		}
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
		$sites = ORM::factory('sharing_site')
			->where('site_active', 1)
			->where('share_reports', 1)
			->find_all();
		
		if (count($sites) == 0) return;

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
	 * Callback for ushahidi_filter.fetch_incidents_set_params
	 * Add filter for source to incidents query
	 */
	public function fetch_incidents_set_params()
	{
		$params = Event::$data;
		
		if (! empty($_GET['sharing']))
		{
			$sharing = $_GET['sharing'];
			
			// escape and implode values
			$sharing = '('.implode(', ', array_map(array(Database::instance(), 'escape'), $sharing)).')';
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
		
		$sql = str_replace(
			Kohana::config('database.default.table_prefix').'incident i ',
			Kohana::config('database.default.table_prefix').'sharing_combined_incident i ',
			$sql
		);
		
		$sql = str_replace('i.id incident_id', 'i.id incident_id, i.source ', $sql);
		
		$sql = str_replace(Kohana::config('database.default.table_prefix')."incident_category ic ON (ic.incident_id = i.id) ",
			Kohana::config('database.default.table_prefix')."sharing_combined_incident_category ic ON ((ic.incident_id = i.id AND i.source = 'main') OR (ic.sharing_incident_id = i.id AND i.source != 'main')) ",
			$sql
		);
		
		Event::$data = $sql;
	}
	
	/*
	 * Callback for ushahidi_filter.get_neighbouring_incidents_sql
	 * Swap incidents table for combined incident view
	 */
	public function get_neighbouring_incidents_sql()
	{
		$sql = Event::$data;
		
		$sql = str_replace(
			'`'.Kohana::config('database.default.table_prefix').'incident` AS i ',
			'`'.Kohana::config('database.default.table_prefix').'sharing_combined_incident` AS i ',
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
	
	/**
	 * Alter color if we're showing chared markers on main page
	 */
	public function json_alter_params()
	{
		$params = Event::$data;
		
		// Category ID
		$category_id = (isset($_GET['c']) AND intval($_GET['c']) > 0) ? intval($_GET['c']) : 0;
		// We're going to assume the category id is always valid.
		
		// Get sharing site info
		// Check we're filtered to a single country site
		if (! empty($_GET['sharing']))
		{
			if (count($_GET['sharing']) == 1 AND $site_id = intval(current($_GET['sharing'])))
			{
				// Check the sharing site is active
				$site = ORM::factory('sharing_site')->where('site_active', 1)->find($site_id);
				if ($site->loaded)
				{
					$site_url = sharing_helper::clean_url($site->site_url);
					// Only set color if all categories, category color overrides site color
					if (!$category_id)
					{
						$params['color'] = $site->site_color;
					}
					$params['icon'] = "";
				}
			}
		}
		
		Event::$data = $params;
	}
	
	/**
	 * Alter markers before conversion to geojson
	 * add URL to shared markers
	 */
	public function json_alter_markers()
	{
		$markers = Event::$data;
		$markers = $markers->as_array();
		
		foreach ($markers as $key => $marker)
		{
			if (isset($marker->source) AND $marker->source != 'main')
			{
				$markers[$key]->url = Sharing_Incident_Model::get_url($marker);
			}
		}
		
		Event::$data = $markers;
	}
}

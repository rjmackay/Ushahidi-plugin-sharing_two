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
	
	/*
	 * Filter incidents for main map based on actionable status
	 */
	public static function fetch_incidents_set_params()
	{
		$params = Event::$data;
		
		if ($_GET['sharing'] == 'all')
		{
			// Do nothing
		}
		elseif ($_GET['sharing'] == 'main')
		{
			// Filter out incidents from other sites
			$params[] = 'i.id NOT IN (SELECT si.incident_id FROM sharing_incident si JOIN sharing_site ss ON (ss.id = si.sharing_site_id) WHERE ss.site_active = 1)';
		}
		else
		{
			$site_id = intval($_GET['sharing']);
			
			if (!$site_id) return;
			
			// Get This Sharing ID Color
			$site = ORM::factory('sharing_site')->where('site_active', 1)->find($site_id);

			// Invalid sharing id: do nothing.
			// This should possibly set an empty markers array
			if(!$site->loaded) return;
			
			$params[] = 'i.id IN (SELECT si.incident_id FROM sharing_incident si WHERE si.sharing_site_id = '.Database::instance()->escape($site->id).')';
		}

		Event::$data = $params;
	}
}

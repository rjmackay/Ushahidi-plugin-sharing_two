<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sharing Hook - Load All Events
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class sharing {
	
	/**
	 * Registers the main event add method
	 */

	protected $user;

	public function __construct()
	{
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));
	}
	
	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
		// Only add the events if we are on that controller
		if (strripos(Router::$current_uri, "admin/manage") !== false)
		{
			Event::add('ushahidi_action.nav_admin_manage', array($this,'sharing_admin_nav'));
		}
		elseif (Router::$controller == "main")
		{
			Event::add('ushahidi_action.header_scripts', array($this, 'sharing_bar_js'));
			Event::add('ushahidi_action.main_sidebar_post_filters', array($this, 'sharing_bar'));
		}
		elseif (Router::$controller == 'json')
		{
			// Quick hack to set default sharing value
			! isset($_GET['sharing']) ? $_GET['sharing'] = 'all' : null;
			
			Event::add('ushahidi_filter.json_alter_markers', array($this, 'json_alter_markers'));
			Event::add('ushahidi_filter.json_replace_markers', array($this, 'json_replace_markers'));

			// Override json controller with our custom version
			// Might not be needed
			/*
			Router::$controller = 'json/sharing';
			*/
		}
	}

	public function sharing_admin_nav()
	{
		$this_sub_page = Event::$data;
		echo ($this_sub_page == "sharing") ? "Sharing" : "<a href=\"".url::site()."admin/manage/sharing\">Sharing</a>";
	}

	/**
	 * Loads the sharing bar on the side bar on the main page
	 */
	public function sharing_bar()
	{
		// Get all active Shares
		$shares = array();
		foreach (ORM::factory('sharing')
					->where('sharing_active', 1)
					->find_all() as $share)
		{
			$shares[$share->id] = array($share->sharing_name, $share->sharing_color);
		}

		$sharing_bar = View::factory('sharing/sharing_bar');

		$sharing_bar->shares = $shares;
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
		if ($_GET['sharing'] != 'all' && $_GET['sharing'] != 'main')
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
}
new sharing;

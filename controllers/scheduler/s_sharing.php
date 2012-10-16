<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sharing Scheduler Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Scheduler
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
*/

class S_Sharing_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		// Get all currently active shares
		$sites = ORM::factory('sharing_site')
			->where('site_active', 1)
			->find_all();

		foreach ($sites as $site)
		{
			$this->_process_site($site);
		}

		return TRUE;
	}

	/**
	 * Process site: grabbing reports and/or categories
	 */
	private function _process_site($site)
	{
		if ( ! $site instanceOf Sharing_site_Model)
		{
			$sites = ORM::factory('sharing_site')->find($site);
		}
		if ( ! $site->loaded) return false;

		
		if (isset($_GET['debug']) AND $_GET['debug'] == 1)
		{
			echo "<strong>Processing site:</strong> ". $site->site_name . "<br/><br/>";
		}
		
		if ($site->share_reports)
		{
			$this->_process_site_reports($site);
		}
		
		if ($site->share_categories)
		{
			$this->_process_site_categories($site);
		}
	}

	/**
	 * Use remote Ushahidi deployments API to get Incident Data
	 * Limit to 20 not to kill remote server
	 */
	private function _process_site_reports($site)
	{
		$limit = 20;
		$since_id = 0;
		$count = 0;
		$modified_ids = array(); // this is an array of our primary keys
		$more_reports_to_pull = TRUE;
		// @todo grab new reports first
		while($more_reports_to_pull == TRUE)
		{
			$UshApiLib_Site_Info = new UshApiLib_Site_Info(sharing_helper::clean_url($site->site_url)."/api");
		
			$params = new UshApiLib_Incidents_Task_Parameter();
			$params->setBy(UshApiLib_Incidents_Bys::INCIDENTS_SINCE_ID);
			$params->setLimit($limit);
			$params->setId($since_id);
			$params->setOrderField(UshApiLib_Task_Base::INCIDENT_ID_INDEX);
			$params->setSort(0);
			
			if (isset($_GET['debug']) AND $_GET['debug'] == 1)
			{
				echo "<strong>Query String:</strong> ". Kohana::debug($params->get_query_string()) . "<br/><br/>";
			}
			
			$task = new UshApiLib_Incidents_Task($params, $UshApiLib_Site_Info);
			$response = $task->execute();
			
			if ($response->getError_code())
			{
				if (isset($_GET['debug']) AND $_GET['debug'] == 1)
				{
					echo "Error Code: ". $response->getError_code() . " Message: ". $response->getError_message() . "<BR /><BR />";
				}
				return;
			}
			
			// Grab existing items
			$existing_items = ORM::factory('sharing_incident')
							->where('sharing_site_id', $site->id)
							->find_all();
			
			// Build array of existing items, key'd by remote id
			$array = array();
			foreach ($existing_items as $item)
			{
				$array[$item->remote_incident_id] = $item;
			}
			$existing_items = $array;
			
			// Parse Incidents Into Database
			$count = 0;
			foreach($response->getIncidents() as $remote_incident_id => $incident_json)
			{
				if (isset($_GET['debug']) AND $_GET['debug'] == 1)
				{
					echo "Importing report $remote_incident_id : ". $incident_json["incident"]->incident_title. "<br/>";
				}
				$orm_incident = $incident_json['incident'];
				
				// Check if we've saved this before.
				if (isset($existing_items[$remote_incident_id]))
				{
					$sharing_incident = $existing_items[$remote_incident_id];
				} else {
					$sharing_incident = ORM::factory('sharing_incident');
				}
				
				// Find and save categories
				$category_titles = array(null);
				foreach( $incident_json['categories'] as $category)
				{
					$category_titles[] = $category->category_title;
				}
				$categories = ORM::factory('category')->in('category_title', $category_titles)->find_all();

				// If matching categories, finish process and save report
				// Otherwise just bump the counters and coninute
				if ($categories->count() != 0)
				{
					// Handle location
					$existing_location = $sharing_incident->location;
					if ($existing_location->loaded) $existing_location->delete();
					$incident_json['location']->save();
					$sharing_incident->location_id = $incident_json['location']->id;
					
					$sharing_incident->incident_title = $orm_incident->incident_title;
					$sharing_incident->incident_description = $orm_incident->incident_description;
					$sharing_incident->incident_date = $orm_incident->incident_date;
					$sharing_incident->incident_mode = $orm_incident->incident_mode;
					$sharing_incident->incident_active = $orm_incident->incident_active;
					$sharing_incident->incident_verified = $orm_incident->incident_verified;
					$sharing_incident->sharing_site_id = $site->id;
					$sharing_incident->remote_incident_id = $remote_incident_id;
					$sharing_incident->updated = date("Y-m-d H:i:s",time());
					$sharing_incident->save();
					
					// Save media
					ORM::factory('sharing_incident_media')
						->where('sharing_incident_id', $sharing_incident->id)
						->delete_all();
					foreach($incident_json['media'] as $media)
					{
						$media->save();
						$new_sharing_incident_media = ORM::factory('sharing_incident_media');
						$new_sharing_incident_media->media_id = $media->id;
						$new_sharing_incident_media->sharing_incident_id = $sharing_incident->id;
						$new_sharing_incident_media->save();
					}
					
					// Save categories
					ORM::factory('sharing_incident_category')
						->where('sharing_incident_id', $sharing_incident->id)
						->delete_all();
					foreach ($categories as $category)
					{
						$new_sharing_incident_category = ORM::factory('sharing_incident_category');
						$new_sharing_incident_category->category_id = $category->id;
						$new_sharing_incident_category->sharing_incident_id = $sharing_incident->id;
						$new_sharing_incident_category->save();
					}

					// Save the primary key of the row we touched. We will be deleting ones that weren't touched.
					$modified_ids[] = $sharing_incident->id;
				}

				// Save the highest pulled incident id so we can grab the next set from that id on
				$since_id = $remote_incident_id;

				// Save count so we know if we need to pull any more reports or not
				$count++;
			}

			if($count < $limit)
			{
				$more_reports_to_pull = FALSE;
			}
		}

		// Delete the reports that are no longer being displayed on the shared site
		if (count($modified_ids) > 0)
		{
			$sharing_incidents = ORM::factory('sharing_incident')
				->notin('id', $modified_ids)
				->where('sharing_site_id', $site->id)
				->find_all();
			
			if ($sharing_incidents->count() > 0)
			{
				ORM::factory('sharing_incident_category')
					->in('sharing_incident_id', $sharing_incidents->primary_key_array())
					->delete_all();
					
				ORM::factory('sharing_incident_media')
						->in('sharing_incident_id', $sharing_incidents->primary_key_array())
						->delete_all();
					
				ORM::factory('sharing_incident_comment')
						->in('sharing_incident_id', $sharing_incidents->primary_key_array())
						->delete_all();
				
				// @todo delete associated categories/location/media
				$sharing_incidents = ORM::factory('sharing_incident')
					->notin('id', $modified_ids)
					->where('sharing_site_id', $site->id)
					->delete_all();
			}
		}
	}

	/**
	 * Use remote Ushahidi deployments API to get Category Data
	 */
	private function _process_site_categories($site)
	{
		$modified_ids = array(); // this is an array of our primary keys

		$UshApiLib_Site_Info = new UshApiLib_Site_Info(sharing_helper::clean_url($site->site_url)."/api");
	
		$params = new UshApiLib_Categories_Task_Parameter();
		
		if (isset($_GET['debug']) AND $_GET['debug'] == 1)
		{
			echo "<strong>Query String:</strong> ". Kohana::debug($params->get_query_string()) . "<br/><br/>";
		}
		
		$task = new UshApiLib_Categories_Task($params, $UshApiLib_Site_Info);
		$response = $task->execute();
		
		if ($response->getError_code())
		{
			if (isset($_GET['debug']) AND $_GET['debug'] == 1)
			{
				echo "Error Code: ". $response->getError_code() . " Message: ". $response->getError_message() . "<BR /><BR />";
			}
			return;
		}
		
		// Grab existing items
		$existing_items = ORM::factory('sharing_category')
						->with('category')
						->where('sharing_site_id', $site->id)
						->find_all();
		
		// Build array of existing items, key'd by remote id
		$array = array();
		foreach ($existing_items as $item)
		{
			$array[$item->remote_category_id] = $item;
		}
		$existing_items = $array;
		
		// First pass - parent categories
		foreach($response->getCategories() as $remote_category_id => $category_info)
		{
			$remote_category = $category_info['category'];
			
			// skip child categories
			if ($remote_category->parent_id != 0) continue;
			
			$sharing_category = $this->_save_category($remote_category_id, $category_info, $site->id, $existing_items);
			if (! $sharing_category) continue;

			$existing_items[$remote_category_id] = $sharing_category;

			// Save the primary key of the row we touched. We will be deleting ones that weren't touched.
			$modified_ids[] = $sharing_category->id;
		}
		
		// 2nd pass: child categories
		foreach($response->getCategories() as $remote_category_id => $category_info)
		{
			$remote_category = $category_info['category'];
			
			// skip top level categories
			if ($remote_category->parent_id == 0) continue;
			
			// Find the sharing_category that matches the parent
			if (! isset($existing_items[$remote_category->parent_id])) continue; // Skip if parent missing
			$parent = $existing_items[$remote_category->parent_id];
			
			$sharing_category = $this->_save_category($remote_category_id, $category_info, $site->id, $existing_items, $parent);
			if (! $sharing_category) continue;
			
			$existing_items[$remote_category_id] = $sharing_category;

			// Save the primary key of the row we touched. We will be deleting ones that weren't touched.
			$modified_ids[] = $sharing_category->id;
		}

		// Delete the reports that are no longer being displayed on the shared site
		if (count($modified_ids) > 0)
		{
			$modified_ids = array_map('intval', $modified_ids);
			
			// Hide categories when deleted/hidden on main site
			// We can't tell the difference between deleted/hidden from the API
			$result = Database::instance()->query('UPDATE category SET category_visible = 0
				WHERE id IN (SELECT category_id FROM sharing_category WHERE id NOT IN ('.implode(',', $modified_ids).') AND sharing_site_id = ?)',
				$site->id);

			// @todo save hidden state so we only hide a category once, and it can be made visible by admin
			
		}
	}

	private function _save_category($remote_category_id, $category_info, $site_id, &$existing_items, $parent = FALSE)
	{
		$remote_category = $category_info['category'];
		
		// Skip special categories
		if ($remote_category->category_title == 'Trusted Reports' OR $remote_category->category_title == 'NONE') return FALSE;
		
		if (isset($_GET['debug']) AND $_GET['debug'] == 1)
		{
			echo "Importing category $remote_category_id : ". $remote_category->category_title. "<br/>";
		}
		
		// Check if we've saved this before.
		if (isset($existing_items[$remote_category_id]))
		{
			$sharing_category = $existing_items[$remote_category_id];
			$category = $sharing_category->category;
		} else {
			$sharing_category = ORM::factory('sharing_category');
			$category = ORM::factory('category');
		}
		
		$category->category_title = $remote_category->category_title;
		$category->category_description = $remote_category->category_title;
		$category->category_color = $remote_category->category_color;
		$category->parent_id = $parent ? $parent->category_id : 0;
		$category->category_position = $remote_category->category_position;
		$category->category_visible = 1; // Always make category visible
		$category->save();
		
		// Delete old category translations and save new ones
		ORM::factory('category_lang')->where('category_id', $category->id)->delete_all();
		foreach ($category_info['translations'] as $translation)
		{
			if (isset($_GET['debug']) AND $_GET['debug'] == 1)
			{
				echo "Saving translation : ". $translation->locale. "<br/>";
			}
			$translation->category_id = $category->id;
			$translation->save();
		}
	
		$sharing_category->category_id = $category->id;
		$sharing_category->remote_category_id = $remote_category_id;
		$sharing_category->sharing_site_id = $site_id;
		$sharing_category->updated = date("Y-m-d H:i:s",time());
		$sharing_category->save();
		
		return $sharing_category;
	}

}

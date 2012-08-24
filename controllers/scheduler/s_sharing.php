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
	 * Use remote Ushahidi deployments API to get Incident Data
	 * Limit to 20 not to kill remote server
	 */
	private function _process_site($site)
	{
		if ( ! $site instanceOf Sharing_site_Model)
		{
			$sites = ORM::factory('sharing_site')->find($site);
		}
		if ( ! $site->loaded) return false;

		$limit = 20;
		$since_id = 0;
		$modified_ids = array(0); // this is an array of our primary keys
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
				
				if (isset($existing_items[$remote_incident_id]))
				{
					$sharing_incident = $existing_items[$remote_incident_id];
					// Load existing incident and update values
					$existing_incident = $sharing_incident->incident;
					$existing_incident->incident_title = $orm_incident->incident_title;
					$existing_incident->incident_description = $orm_incident->incident_description;
					$existing_incident->incident_date = $orm_incident->incident_date;
					$existing_incident->incident_mode = $orm_incident->incident_mode;
					$existing_incident->incident_active = $orm_incident->incident_active;
					$existing_incident->incident_verified = $orm_incident->incident_verified;
					$orm_incident = $existing_incident;
				} else {
					$sharing_incident = ORM::factory('sharing_incident');
				}
				
				// Save everything
				$incident_json['location']->save();
				$orm_incident->location_id = $incident_json['location']->id;
				
				// Find and save categories
				$category_titles = array(0);
				foreach( $incident_json['categories'] as $category)
				{
					$category_titles[] = $category->category_title;
				}
				$categories = ORM::factory('category')->in('category_title', $category_titles)->find_all();
				$incident->categories = $categories->primary_key_array();
				
				// Save incident
				$orm_incident->save();
				
				// Delete any existing media
				$existing_media = $orm_incident->media;
				if ($existing_media->count())
				{
					ORM::factory('media')->in('id', $orm_incident->media->primary_key_array())->delete_all();
				}
				// Save media
				foreach($orm_incident->media as $media)
				{
					$media->incident_id = $orm_incident->id;
					$media->save();
				}
				
				$sharing_incident->sharing_site_id = $site->id;
				$sharing_incident->remote_incident_id = $remote_incident_id;
				$sharing_incident->incident_id = $orm_incident->id;
				$sharing_incident->save();

				// Save the primary key of the row we touched. We will be deleting ones that weren't touched.
				$modified_ids[] = $sharing_incident->id;

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
		ORM::factory('sharing_incident')
			->notin('id', $modified_ids)
			->where('sharing_site_id', $site->id)
			->delete_all();
	}
}

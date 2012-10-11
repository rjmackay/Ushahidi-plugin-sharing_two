<?php defined('SYSPATH') or die('No direct script access.');

/**
* Model for Sharing_Incident
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class Sharing_Incident_Model extends ORM
{
	protected $belongs_to = array('sharing_site');
	protected $has_one = array('location');
	protected $has_many = array(
		'category' => 'sharing_incident_category',
		'media' => 'sharing_incident_media'
	);
	
	// Database table name
	protected $table_name = 'sharing_incident';
	
	/**
	 * Get url of this incident
	 * @return string
	 **/
	public function url()
	{
		return self::get_url($this);
	}
	
	/**
	 * Get url for the incident object passed
	 * @param object
	 * @return string
	 **/
	public static function get_url($incident)
	{
		if (is_object($incident))
		{
			$id = isset($incident->incident_id) ? $incident->incident_id : $incident->id;
			//$site = isset($incident->sharing_site) ? $incident->sharing_site : ORM::factory('sharing_site', $incident->sharing_site_id);
		}
		else
		{
			return false;
		}
		
		return url::site("/reports/sharing/view/$id");
	}

	/**
	 * Checks if a specified incident id is numeric and exists in the database
	 *
	 * @param int $incident_id ID of the incident to be looked up
	 * @param bool $approved Whether to include un-approved reports
	 * @return bool
	 */
	public static function is_valid_incident($incident_id, $approved = TRUE)
	{
		$where = ($approved == TRUE) ? array("incident_active" => "1") : array("id >" => 0);
		return (intval($incident_id) > 0)
			? ORM::factory('sharing_incident')->where($where)->find(intval($incident_id))->loaded
			: FALSE;
	}

	/**
	 * Gets the comments for an incident
	 * @param int $incident_id Database ID of the incident
	 * @return mixed FALSE if the incident id is non-existent, ORM_Iterator if it exists
	 */
	public static function get_comments($incident_id)
	{
		if (self::is_valid_incident($incident_id))
		{
			$where = array(
				'sharing_incident_id' => $incident_id,
				'comment_active' => '1',
				'comment_spam' => '0'
			);

			// Fetch the comments
			return ORM::factory('comment')
					->join('sharing_incident_comment','comment.id','comment_id','INNER')
					->where($where)
					->orderby('comment_date', 'asc')
					->find_all();
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Given an incident, gets the list of incidents within a specified radius
	 *
	 * @param int $incident_id Database ID of the incident to be used to fetch the neighbours
	 * @param int $distance Radius within which to fetch the neighbouring incidents
	 * @param int $num_neigbours Number of neigbouring incidents to fetch
	 * @return mixed FALSE is the parameters are invalid, Result otherwise
	 */
	public static function get_neighbouring_incidents($incident_id, $order_by_distance = FALSE, $distance = 0, $num_neighbours)
	{
		if (self::is_valid_incident($incident_id))
		{
			// Get the table prefix
			$table_prefix = Kohana::config('database.default.table_prefix');

			$incident_id = (intval($incident_id));

			// Get the location object and extract the latitude and longitude
			$location = self::factory('sharing_incident', $incident_id)->location;
			$latitude = $location->latitude;
			$longitude = $location->longitude;

			// Garbage collection
			unset ($location);

			// Query to fetch the neighbour
			$sql = "SELECT DISTINCT i.*, l.`latitude`, l.`longitude`, l.location_name, "
				. "((ACOS(SIN( ? * PI() / 180) * SIN(l.`latitude` * PI() / 180) + COS( ? * PI() / 180) * "
				. "	COS(l.`latitude` * PI() / 180) * COS(( ? - l.`longitude`) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance "
				. "FROM `".$table_prefix."sharing_combined_incident` AS i "
				. "INNER JOIN `".$table_prefix."location` AS l ON (l.`id` = i.`location_id`) "
				. "WHERE i.incident_active = 1 "
				. "AND i.id <> ? ";

			// Check if the distance has been specified
			if (intval($distance) > 0)
			{
				$sql .= "HAVING distance <= ".intval($distance)." ";
			}

			// If the order by distance parameter is TRUE
			if ($order_by_distance)
			{
				$sql .= "ORDER BY distance ASC ";
			}
			else
			{
				$sql .= "ORDER BY i.`incident_date` DESC ";
			}

			// Has the no. of neigbours been specified
			if (intval($num_neighbours) > 0)
			{
				$sql .= "LIMIT ".intval($num_neighbours);
			}

			// Fetch records and return
			return Database::instance()->query($sql, $latitude, $latitude, $longitude, $incident_id);
		}
		else
		{
			return FALSE;
		}
	}
	
}

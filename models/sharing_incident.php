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
	
	protected $load_with = array('sharing_site');
	
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
			$id = $incident->remote_incident_id;
			$site = isset($incident->sharing_site) ? $incident->sharing_site : ORM::factory('sharing_site', $incident->sharing_site_id);
		}
		else
		{
			return false;
		}
		
		return $site->site_url."/reports/view/$id";
	}
}

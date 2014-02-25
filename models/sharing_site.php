<?php defined('SYSPATH') or die('No direct script access.');

/**
* Model for Sharing
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

class Sharing_Site_Model extends ORM
{
	protected $has_many = array('sharing_incident', 'sharing_category');

	// Database table name
	protected $table_name = 'sharing_site';
}

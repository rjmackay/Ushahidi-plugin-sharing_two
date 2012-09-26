<?php defined('SYSPATH') or die('No direct script access.');

/**
* Model for Sharing_Category
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

class Sharing_Category_Model extends ORM
{
	protected $belongs_to = array('sharing_site');
	protected $has_one = array('category');
	
	// Database table name
	protected $table_name = 'sharing_category';
	
}

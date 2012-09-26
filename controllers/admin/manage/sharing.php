<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sharing Controller
 * Add/Edit Ushahidi Instance Shares
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Admin
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class Sharing_Controller extends Admin_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->template->this_page = 'settings';
		
		// If user doesn't have access, redirect to dashboard
		if ( ! $this->auth->has_permission("manage"))
		{
			url::redirect(url::site().'admin/dashboard');
		}
	}
	
	
	function index()
	{
		$this->template->content = new View('admin/manage/sharing/main');
		$this->template->content->title = Kohana::lang('ui_admin.settings');
		
		// Setup and initialize form field names
		$form = array
	    (
			'site_name' => '',
			'site_url' => '',
			'site_color' => '',
			'site_active' => ''
	    );
		//  Copy the form as errors, so the errors will be stored with keys corresponding to the form field names
	    $errors = $form;
		$form_error = FALSE;
		$form_saved = FALSE;
		$form_action = "";
		$site_id = "";
		
		
		if( $_POST ) 
		{
			$post = Validation::factory($_POST);
			
			 //  Add some filters
			$post->pre_filter('trim', TRUE);
	
			// Add Action
			if ($post->action == 'a')
			{
				// Clean the url before we do anything else
				$post->site_url = sharing_helper::clean_url($post->site_url);
				// Add some rules, the input field, followed by a list of checks, carried out in order
				$post->add_rules('site_name','required', 'length[3,150]');
				$post->add_rules('site_url','required', 'url', 'length[3,255]');
				$post->add_rules('site_color','required', 'length[6,6]');
				$post->add_rules('share_reports', 'range[0,1]');
				$post->add_rules('share_categories', 'range[0,1]');
				$post->add_callbacks('site_url', array($this,'url_exists_chk'));
			}
			
			if( $post->validate() )
			{
				$site_id = $post->site_id;
				
				$site = new Sharing_Site_Model($site_id);
				
				// Delete Action
				if ( $post->action == 'd' )
				{ 
					$site->delete( $site_id );
					$form_saved = TRUE;
					$form_action = utf8::strtoupper(Kohana::lang('ui_admin.deleted'));
				}
				
				// Hide Action
				else if ($post->action=='h')
				{
					if($site->loaded)
					{
						$site->site_active = 0;
						$site->save();
						$form_saved = TRUE;
						$form_action = utf8::strtoupper(Kohana::lang('ui_main.hidden'));
					}	
				}
				
				// Show Action
				else if ($post->action == 'v')
				{ 
					if ($site->loaded)
					{
						$site->site_active = 1;
						$site->save();
						$form_saved = TRUE;
						$form_action = utf8::strtoupper(Kohana::lang('ui_admin.shown'));
					}
				}
				
				// Save Action
				// Must check for action here otherwise passing no action param would also means no validation
				// See validation code above.
				elseif ($post->action == 'a')
				{ 
					$site->site_name = $post->site_name;
					$site->site_url = $post->site_url;
					$site->site_color = $post->site_color;
					$site->share_reports = $post->share_reports;
					$site->share_categories = $post->share_categories;
					$site->save();
					
					$form_saved = TRUE;
					$form_action = utf8::strtoupper(Kohana::lang('ui_admin.created_edited'));
				}
				
			}
			else
			{
				// Repopulate the form fields
				$form = arr::overwrite($form, $post->as_array());

				// Populate the error fields, if any
				$errors = arr::merge($errors, $post->errors('sharing_two'));
				$form_error = TRUE;
			}
		}
		
		// Pagination
		$pagination = new Pagination(array(
			'query_string' => 'page',
			'items_per_page' => $this->items_per_page,
			'total_items'  => ORM::factory('sharing_site')->count_all()
		));
		
		$sites = ORM::factory('sharing_site')
			->orderby('site_name', 'asc')
			->find_all($this->items_per_page,  $pagination->sql_offset);
		
		$this->template->content->form_error = $form_error;
		$this->template->content->form_saved = $form_saved;
		$this->template->content->form_action = $form_action;
		$this->template->content->pagination = $pagination;
		$this->template->content->total_items = $pagination->total_items;
		$this->template->content->sites = $sites;
		$this->template->content->errors = $errors;
		
		
		// Javascript Header
		$this->template->colorpicker_enabled = TRUE;
		$this->template->js = new View('admin/manage/sharing/sharing_js');
	}
	
	
	/**
	 * Checks if url already exists.
     * @param Validation $post $_POST variable with validation rules 
	 */
	public function url_exists_chk(Validation $post)
	{
		// If add->rules validation found any errors, get me out of here!
		if (array_key_exists('site_url', $post->errors()))
			return;
		
		if (!isset($post->site_id))
		{
			$post->site_id = 0;
		}
		
		$share_exists = ORM::factory('sharing_site')
			->where(array(
				'site_url' => $post->site_url,
				'id !=' => $post->site_id
			))
			->find();
		
		if ($share_exists->loaded)
		{
			$post->add_error( 'site_url', 'exists');
		}
	}
}

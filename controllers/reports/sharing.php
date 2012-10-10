<?php defined('SYSPATH') or die('No direct script access.');

/**
 * This controller is used to list/ view and edit reports
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

class Sharing_Controller extends Main_Controller {
	
	/**
	 * Whether an admin console user is logged in
	 * @var bool
	 */
	var $logged_in;

	public function __construct()
	{
		parent::__construct();
		$this->themes->validator_enabled = TRUE;

		// Is the Admin Logged In?
		$this->logged_in = Auth::instance()->logged_in();
	}

	 /**
	 * Displays a report.
	 * @param boolean $id If id is supplied, a report with that id will be
	 * retrieved.
	 */
	public function view($id = FALSE)
	{
		$this->template->header->this_page = 'reports';
		$this->template->content = new View('reports/detail');

		// Load Akismet API Key (Spam Blocker)
		$api_akismet = Kohana::config('settings.api_akismet');

		// Sanitize the report id before proceeding
		$id = intval($id);

		if ($id > 0)
		{
			$incident = ORM::factory('sharing_incident')
				->where('id', $id)
				->where('incident_active',1)
				->find();
				
			// Not Found
			if ( ! $incident->loaded) 
			{
				url::redirect('reports/');
			}

			// Comment Post?
			// Setup and initialize form field names

			$form = array(
				'comment_author' => '',
				'comment_description' => '',
				'comment_email' => '',
				'comment_ip' => '',
				'captcha' => ''
			);

			$captcha = Captcha::factory();
			$errors = $form;
			$form_error = FALSE;

			// Check, has the form been submitted, if so, setup validation

			if ($_POST AND Kohana::config('settings.allow_comments') )
			{
				// Instantiate Validation, use $post, so we don't overwrite $_POST fields with our own things
				$post = Validation::factory($_POST);

				// Add some filters
				$post->pre_filter('trim', TRUE);

				// Add some rules, the input field, followed by a list of checks, carried out in order
				if ( ! $this->user)
				{
					$post->add_rules('comment_author', 'required', 'length[3,100]');
					$post->add_rules('comment_email', 'required','email', 'length[4,100]');
				}
				$post->add_rules('comment_description', 'required');
				$post->add_rules('captcha', 'required', 'Captcha::valid');

				// Test to see if things passed the rule checks
				if ($post->validate())
				{
					// Yes! everything is valid
					if ($api_akismet != "")
					{
						// Run Akismet Spam Checker
						$akismet = new Akismet();

						// Comment data
						$comment = array(
							'website' => "",
							'body' => $post->comment_description,
							'user_ip' => $_SERVER['REMOTE_ADDR']
						);

						if ($this->user)
						{
							$comment['author'] = $this->user->name;
							$comment['email'] = $this->user->email;
						}
						else
						{
							$comment['author'] = $post->comment_author;
							$comment['email'] = $post->comment_email;
						}

						$config = array(
							'blog_url' => url::site(),
							'api_key' => $api_akismet,
							'comment' => $comment
						);

						$akismet->init($config);

						if ($akismet->errors_exist())
						{
							if ($akismet->is_error('AKISMET_INVALID_KEY'))
							{
								// throw new Kohana_Exception('akismet.api_key');
							}
							elseif ($akismet->is_error('AKISMET_RESPONSE_FAILED'))
							{
								// throw new Kohana_Exception('akismet.server_failed');
							}
							elseif ($akismet->is_error('AKISMET_SERVER_NOT_FOUND'))
							{
								// throw new Kohana_Exception('akismet.server_not_found');
							}

							$comment_spam = 0;
						}
						else
						{
							$comment_spam = ($akismet->is_spam()) ? 1 : 0;
						}
					}
					else
					{
						// No API Key!!
						$comment_spam = 0;
					}

					$comment = new Comment_Model();
					$comment->incident_id = 0;
					if ($this->user)
					{
						$comment->user_id = $this->user->id;
						$comment->comment_author = $this->user->name;
						$comment->comment_email = $this->user->email;
					}
					else
					{
						$comment->comment_author = strip_tags($post->comment_author);
						$comment->comment_email = strip_tags($post->comment_email);
					}
					$comment->comment_description = strip_tags($post->comment_description);
					$comment->comment_ip = $_SERVER['REMOTE_ADDR'];
					$comment->comment_date = date("Y-m-d H:i:s",time());

					// Activate comment for now
					if ($comment_spam == 1)
					{
						$comment->comment_spam = 1;
						$comment->comment_active = 0;
					}
					else
					{
						$comment->comment_spam = 0;
						$comment->comment_active = (Kohana::config('settings.allow_comments') == 1)? 1 : 0;
					}
					$comment->save();
					
					// link comment to sharing_incident
					$incident_comment = ORM::factory('sharing_incident_comment');
					$incident_comment->comment_id = $comment->id;
					$incident_comment->sharing_incident_id = $incident->id;
					$incident_comment->save();

					// Event::comment_add - Added a New Comment
					Event::run('ushahidi_action.comment_add', $comment);

					// Notify Admin Of New Comment
					$send = notifications::notify_admins(
						"[".Kohana::config('settings.site_name')."] ".
							Kohana::lang('notifications.admin_new_comment.subject'),
							Kohana::lang('notifications.admin_new_comment.message')
							."\n\n'".utf8::strtoupper($incident->incident_title)."'"
							."\n".url::base().'reports/sharing/view/'.$id
						);

					// Redirect
					url::redirect('reports/sharing/view/'.$id);

				}
				else
				{
					// No! We have validation errors, we need to show the form again, with the errors
					// Repopulate the form fields
					$form = arr::overwrite($form, $post->as_array());

					// Populate the error fields, if any
					$errors = arr::overwrite($errors, $post->errors('comments'));
					$form_error = TRUE;
				}
			}

			// Filters
			$incident_title = $incident->incident_title;
			$incident_description = $incident->incident_description;
			Event::run('ushahidi_filter.report_title', $incident_title);
			Event::run('ushahidi_filter.report_description', $incident_description);

			$this->template->header->page_title .= $incident_title.Kohana::config('settings.title_delimiter');

			// Add Features
			// hardcode geometries to empty
			$this->template->content->features_count = 0;
			$this->template->content->features = array();
			
			$this->template->content->incident_id = $incident->id;
			$this->template->content->incident_title = $incident_title;
			$this->template->content->incident_description = $incident_description;
			$this->template->content->incident_location = $incident->location->location_name;
			$this->template->content->incident_latitude = $incident->location->latitude;
			$this->template->content->incident_longitude = $incident->location->longitude;
			$this->template->content->incident_date = date('M j Y', strtotime($incident->incident_date));
			$this->template->content->incident_time = date('H:i', strtotime($incident->incident_date));
			$this->template->content->incident_category = ORM::factory('sharing_incident_category')->where('sharing_incident_id', $incident->id)->find_all();

			// Incident rating
			$rating = ORM::factory('rating')
					->join('incident','incident.id','rating.incident_id','INNER')
					->where('rating.incident_id',$incident->id)
					->find();
					
			$this->template->content->incident_rating = ($rating->rating == '')
				? 0
				: $rating->rating;

			// Retrieve Media
			$incident_news = array();
			$incident_video = array();
			$incident_photo = array();

			foreach ($incident->media as $media)
			{
				if ($media->media_type == 4)
				{
					$incident_news[] = $media->media_link;
				}
				elseif ($media->media_type == 2)
				{
					$incident_video[] = $media->media_link;
				}
				elseif ($media->media_type == 1)
				{
					$incident_photo[] = array(
						'large' => url::convert_uploaded_to_abs($media->media_link),
						'thumb' => url::convert_uploaded_to_abs($media->media_thumb)
						);
				}
			}

			$this->template->content->incident_verified = $incident->incident_verified;

			// Retrieve Comments (Additional Information)
			$this->template->content->comments = "";
			if (Kohana::config('settings.allow_comments'))
			{
				$this->template->content->comments = new View('reports/comments');
				$incident_comments = array();
				if ($id)
				{
					$incident_comments = Sharing_Incident_Model::get_comments($id);
				}
				$this->template->content->comments->incident_comments = $incident_comments;
			}
		}
		else
		{
			url::redirect('reports');
		}
		
		// Add extra info to meta
		Event::add('ushahidi_action.report_display_media', array($this, 'report_display_media'));

		// Add Neighbors
		$this->template->content->incident_neighbors = Sharing_Incident_Model::get_neighbouring_incidents($id, TRUE, 0, 5);

		// News Source links
		$this->template->content->incident_news = $incident_news;


		// Video links
		$this->template->content->incident_videos = $incident_video;

		// Images
		$this->template->content->incident_photos = $incident_photo;

		// Create object of the video embed class
		$video_embed = new VideoEmbed();
		$this->template->content->videos_embed = $video_embed;

		// Javascript Header
		$this->themes->map_enabled = TRUE;
		$this->themes->photoslider_enabled = TRUE;
		$this->themes->videoslider_enabled = TRUE;
		$this->themes->js = new View('reports/view_js');
		$this->themes->js->incident_id = $incident->id;
		$this->themes->js->incident_json_url = 'json/share/single/'.$incident->id;
		$this->themes->js->default_map = Kohana::config('settings.default_map');
		$this->themes->js->default_zoom = Kohana::config('settings.default_zoom');
		$this->themes->js->latitude = $incident->location->latitude;
		$this->themes->js->longitude = $incident->location->longitude;
		$this->themes->js->incident_zoom = null;//$incident->incident_zoom;
		$this->themes->js->incident_photos = $incident_photo;

		// Initialize custom field array
		$this->template->content->custom_forms = new View('reports/detail_custom_forms');
		$form_field_names = customforms::get_custom_form_fields($id, 1/*$incident->form_id*/, FALSE, "view");
		$this->template->content->custom_forms->form_field_names = $form_field_names;

		// Are we allowed to submit comments?
		$this->template->content->comments_form = "";
		if (Kohana::config('settings.allow_comments'))
		{
			$this->template->content->comments_form = new View('reports/comments_form');
			$this->template->content->comments_form->user = $this->user;
			$this->template->content->comments_form->form = $form;
			$this->template->content->comments_form->form_field_names = $form_field_names;
			$this->template->content->comments_form->captcha = $captcha;
			$this->template->content->comments_form->errors = $errors;
			$this->template->content->comments_form->form_error = $form_error;
		}

		// If the Admin is Logged in - Allow for an edit link
		$this->template->content->logged_in = $this->logged_in;

		// Rebuild Header Block
		$this->template->header->header_block = $this->themes->header_block();
		$this->template->footer->footer_block = $this->themes->footer_block();
	}
	
	/*
	 * Add extra info to report meta: link to origin site
	 * 
	 */
	public function report_display_media()
	{
		$id = Event::$data;
		$incident = ORM::factory('sharing_incident')
			->with('sharing_site')
			->where('sharing_incident.id', $id)
			->find();
		
		echo '<h3>'.html::anchor(
			$incident->sharing_site->site_url.'/reports/view/'.$incident->remote_incident_id,
			Kohana::lang('sharing_two.view_original', array($incident->sharing_site->site_name))
		).'</h3>';
	}
}

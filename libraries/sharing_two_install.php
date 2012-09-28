<?php
/**
 * Performs install/uninstall methods for the Sharing Plugin
 *
 * @package    Ushahidi
 * @author     Ushahidi Team
 * @copyright  (c) 2008 Ushahidi Team
 * @license    http://www.ushahidi.com/license.html
 */
class Sharing_two_Install {

	/**
	 * Constructor to load the shared database library
	 */
	public function __construct()
	{
		$this->db =  new Database();
	}

	/**
	 * Creates the required database tables for the sharing module
	 */
	public function run_install()
	{
		// Create the database tables
		// Include the table_prefix
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_site`
			(
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`site_name` varchar(150) NOT NULL COMMENT 'name that appears on the front end',
				`site_url` varchar(255) NOT NULL COMMENT 'url of the deployment to share with',
				`site_color` varchar(20) DEFAULT 'CC0000' COMMENT 'color that shows the shared reports',
				`site_active` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'sharing active or inactive ',
				`share_categories` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'sharing active or inactive ',
				`share_reports` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'sharing active or inactive ',
				PRIMARY KEY (id)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores sites we are getting shared reports from'
			");

			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_incident`
			(
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`location_id` bigint(20) unsigned NOT NULL,
				`sharing_site_id` INT UNSIGNED NOT NULL,
				`remote_incident_id` BIGINT(20) UNSIGNED NOT NULL,
				`updated` datetime DEFAULT NULL,
				`incident_title` varchar(255) NOT NULL COMMENT 'title of the report',
				`incident_description` longtext,
				`incident_date` datetime DEFAULT NULL,
				`incident_mode` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 - WEB, 2 - SMS, 3 - EMAIL, 4 - TWITTER',
				`incident_active` tinyint(4) NOT NULL DEFAULT '0',
				`incident_verified` tinyint(4) NOT NULL DEFAULT '0',
				PRIMARY KEY (id),
				KEY `location_id` (`location_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores shared reports'
			");

			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_incident_category` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `sharing_incident_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  `category_id` int(11) unsigned NOT NULL DEFAULT '5',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `sharing_incident_category_ids` (`sharing_incident_id`,`category_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores shared reports categories'
			");

			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_incident_media` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `sharing_incident_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  `media_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `sharing_incident_media_ids` (`sharing_incident_id`,`media_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores shared reports media'
			");
			
			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_incident_comment` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `sharing_incident_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  `comment_id` int(11) unsigned NOT NULL DEFAULT '5',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `sharing_incident_comment_ids` (`sharing_incident_id`,`comment_id`)
			) ENGINE=MyISAM AUTO_INCREMENT=14064 DEFAULT CHARSET=utf8 COMMENT='Stores shared reports comments'
			");
			
			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_category`
			(
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`sharing_site_id` INT UNSIGNED NOT NULL,
				`category_id` BIGINT(20) UNSIGNED NOT NULL,
				`remote_category_id` BIGINT(20) UNSIGNED NOT NULL,
				`updated` datetime DEFAULT NULL,
				PRIMARY KEY (id),
			  UNIQUE KEY `category_id` (`category_id`),
			  UNIQUE KEY `remote_category_id` (`sharing_site_id`,`remote_category_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Stores shared categories'
			");
			
			// Create view for querying
			$this->db->query("
			CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=CURRENT_USER SQL SECURITY INVOKER VIEW `sharing_combined_incident` AS
				SELECT `incident`.`id` AS `id`,
					`incident`.`incident_title` AS `incident_title`,
					`incident`.`incident_description` AS `incident_description`,
					`incident`.`incident_date` AS `incident_date`,
					`incident`.`incident_mode` AS `incident_mode`,
					`incident`.`location_id` AS `location_id`,
					`incident`.`incident_active` AS `incident_active`,
					`incident`.`incident_verified` AS `incident_verified`,
					'main' AS `source`
				FROM `incident`
				UNION
				SELECT
					`sharing_incident`.`id` AS `id`,
					`sharing_incident`.`incident_title` AS `incident_title`,
					`sharing_incident`.`incident_description` AS `incident_description`,
					`sharing_incident`.`incident_date` AS `incident_date`,
					`sharing_incident`.`incident_mode` AS `incident_mode`,
					`sharing_incident`.`location_id` AS `location_id`,
					`sharing_incident`.`incident_active` AS `incident_active`,
					`sharing_incident`.`incident_verified` AS `incident_verified`,
					`sharing_incident`.`sharing_site_id` AS `source`
				FROM `sharing_incident`
			");
			
			$this->db->query("
			CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=CURRENT_USER SQL SECURITY INVOKER VIEW `sharing_combined_incident_category` AS
				SELECT `incident_category`.`incident_id` AS `incident_id`,
					NULL AS `sharing_incident_id`,
					`incident_category`.`category_id` AS `category_id`
				FROM `incident_category`
				UNION
				SELECT
					NULL AS `incident_id`,
					`sharing_incident_category`.`sharing_incident_id` AS `sharing_incident_id`,
                  `sharing_incident_category`.`category_id` AS `category_id`
				FROM `sharing_incident_category`
			");
			
			//Dump the sharing scheduler item from bundled SQL dump file
			$this->db->query("DELETE FROM `".Kohana::config('database.default.table_prefix')."scheduler` where scheduler_name = 'Sharing' ");
			
			// Add sharing in to scheduler table
			$this->db->query("INSERT IGNORE INTO `".Kohana::config('database.default.table_prefix')."scheduler`
				(`scheduler_name`,`scheduler_last`,`scheduler_weekday`,`scheduler_day`,`scheduler_hour`,`scheduler_minute`,`scheduler_controller`,`scheduler_active`) VALUES
				('Sharing','0','-1','-1','-1','-1','s_sharing','1')"
			);
	}

	/**
	 * Deletes the database tables for the sharing module
	 */
	public function uninstall()
	{
		$this->db->query("
			DROP TABLE ".Kohana::config('database.default.table_prefix')."sharing_site;
			");
		$this->db->query("
			DROP TABLE ".Kohana::config('database.default.table_prefix')."sharing_incident;
			");

	}
}

<?php
/**
 * Performs install/uninstall methods for the Sharing Plugin
 *
 * @package    Ushahidi
 * @author     Ushahidi Team
 * @copyright  (c) 2008 Ushahidi Team
 * @license    http://www.ushahidi.com/license.html
 */
class Sharing_Install {

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
				PRIMARY KEY (id)
			);");

			$this->db->query("
			CREATE TABLE IF NOT EXISTS `".Kohana::config('database.default.table_prefix')."sharing_incident`
			(
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`sharing_site_id` INT UNSIGNED NOT NULL,
				`remote_incident_id` BIGINT(20) UNSIGNED NOT NULL,
				`incident_title` varchar(255) NOT NULL COMMENT 'title of the report',
				`latitude` double NOT NULL COMMENT 'latitude of the report',
				`longitude` double NOT NULL COMMENT 'longitude of the report',
				`incident_date` datetime DEFAULT NULL,
				PRIMARY KEY (id)
			);");

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

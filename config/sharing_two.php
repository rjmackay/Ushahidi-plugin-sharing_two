<?php defined('SYSPATH') or die('No direct script access allowed.');
/**
 * Sharing Two config
 *
 */

$config = array();

/**
 * Should map markers be combined for all sites
 * or show in different colors
 * (Only applies when filtering to 'all') 
 */
$config['combine_markers'] = TRUE;

/*
 * Which site to show by default
 * Valid options are:
 * 'all' 'main' or a sharing id
 */
$config['default_sharing_filter'] = 'all';

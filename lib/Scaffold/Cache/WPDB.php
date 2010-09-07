<?php

/**
 * Scaffold_Cache_WPDB
 *
 * Uses the file system for caching using ID-based file names
 * 
 * @package 		Scaffold
 * @author 			Paul Clark <pdclark@pdclark.com>
 * @copyright 		2009-2010 Paul Clark. All rights reserved.
 * @license 		http://opensource.org/licenses/bsd-license.php  New BSD License
 * @link 			https://github.com/anthonyshort/csscaffold/master
 */
class Scaffold_Cache_WPDB extends Scaffold_Cache
{
	/**
	 * The unique option name prefix for the key in wp_options
	 * @var string
	 */
	public $prefix;
	
	/**
	 * The maximum age of cache files
	 * @var mixed
	 */
	public $max_age;

	// =========================================
	// = Constructors & Initialization Methods =
	// =========================================

	/**
	 * Constructor
	 *
	 * @param 	$prefix 	The key prefix for the wp_options table
	 * @return 	void
	 */
	public function __construct($prefix, $max_age)
	{
		$this->prefix 		= $prefix;
		$this->max_age 		= $max_age;
		$this->current_time	= time();
		
		// Seems &recache functionality isn't in Scaffold as of 0.0.30? 
		if ( isset( $_GET['recache'] ) ) {
			$this->delete_all();
		}
	}

	// =========================================
	// = Get and set cache methods =
	// =========================================
	
	/**
	 * Retrieve a value based on an id
	 *
	 * @param $id 
	 * @param $relative_time Check the cache relative to this time. Defaults to time()
	 * @param $default [Optional] Default value to return if id not found
	 * @return mixed
	 * @access public
	 */
	public function get($id, $relative_time = false, $default = false)
	{
		$target = $this->prefix.$id;
		
		// Get any existing copy of our transient data
		if (false === ( $data = get_transient( $target ) ) ) {
		    // It wasn't there, so regenerate the data and save the transient
			return $default;
		}else {
			// WordPress handled deserialization.
			return (object) $data;
		}
		
		return $default;
	}

	/**
	 * Set a value based on an id.
	 *
	 * @param string $id 
	 * @param string $data 
	 * @param integer $last_modified When the source file was last modified
	 * @return string The full path of the file that was just used as a cache
	 * @access public
	 */
	public function set($id,$data,$last_modified = false,$expires = true,$encode = true)
	{	
		$target = $this->prefix.$id;

		// For WordPress
		$timeout = ($expires === false OR $this->max_age === false) ? 60*60*24*365 : $this->max_age;
		
		// For Scaffold
		$expires = ($expires === false OR $this->max_age === false) ? false : $this->current_time + $this->max_age;
		$last_modified = ($last_modified === false) ? $last_modified : $this->current_time;
		
		$data = (object) array(
			'contents' => $data,
			'last_modified'	=> $last_modified,
			'expires' 		=> $expires,
		);
		
		// WordPress handles serialization and expiration
		set_transient( $target, $data, $timeout);
		
		return $target;
	}

	/**
	 * Delete a cache entry based on id
	 * @param string $id 
	 * @return boolean
	 * @access public
	 */
	public function delete($id)
	{
		$target = $this->prefix.$id;
		
		delete_transient($target);
	}
	
	/**
	 * Delete all cache entries
	 * @return boolean
	 * @access public
	 */
	public function delete_all()
	{
		global $wpdb;
		
		$query = "DELETE 
					FROM $wpdb->options 
					WHERE option_name LIKE '_transient_timeout_{$this->prefix}%'
						OR option_name LIKE '_transient_{$this->prefix}%' ;";
		
		$wpdb->query($query);
			
		return false;
	}	
	
}
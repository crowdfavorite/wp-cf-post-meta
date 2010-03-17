<?php
/*
Plugin Name: CF Post Meta
Plugin URI: http://crowdfavorite.com/wordpress/
Description: CrowdFavorite Post Metadata Manager: Facilitates adding additinal metadata fields to posts through the standard post entry interface. 
Version: 1.9.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/	
/* Tested back to PHP 4.4.7 & up to PHP 5.2.9 */
	
	/**
	 * Plugin version ID
	 */
	define('CF_META_VERSION', '1.8.1');
	
	// PHP < 4.4 hax
	if(!defined('PHP_EOL')) { define('PHP_EOL',"\n"); }
	
	function cf_meta_request_handler() {
		if(isset($_GET['cf_meta_action'])) {
			switch($_GET['cf_meta_action']) {
				case 'admin_style':
					cf_meta_css();
					exit();
					break;
			}
		}
	}
	add_action('init','cf_meta_request_handler');

	/**
	 * Get required files
	 */
	/* Allow us to be in mu-plugins or plugins */
	define('CF_META_PLUGIN_DIR', trailingslashit(dirname(realpath(__FILE__))));

	require_once(CF_META_PLUGIN_DIR.'classes/cf-input.class.php');
	require_once(CF_META_PLUGIN_DIR.'classes/cf-meta.class.php');
	require_once(CF_META_PLUGIN_DIR.'classes/cf-meta-js.class.php');
	require_once(CF_META_PLUGIN_DIR.'classes/cf-meta-types.class.php');
	require_once(CF_META_PLUGIN_DIR.'classes/cf-input-settings-types.class.php');
	
	function cf_meta_actions() {
		/**
		 * assign post actions
		 */
		if (apply_filters('cf_meta_actions', false)) {
			add_action('admin_head','cf_meta_add_boxes',11);
			add_action('save_post','cf_meta_save_post',10,2);
			if (isset($_GET['cfm_error']) || isset($_GET['cfm_notice'])) {
				add_action('admin_notices','cf_meta_notices');
			}
			/**
			 * Include necessary CSS
			 */
			add_action('admin_head','cf_meta_head_items',10);
		}
	}
	add_action('init', 'cf_meta_actions');
	
	function cf_meta_default_actions($val) {
		return (is_admin() && cf_meta_get_type() !== false);
	}
	add_filter('cf_meta_actions', 'cf_meta_default_actions', 1);
	
	/**
	 * Run in the appropriate context
	 */
	function cf_meta_add_boxes() {
		global $cfmeta,$post;
		$type = cf_meta_get_type();
		if($type == 'post') {
			cf_meta_edit_post();
		}
		elseif($type == 'page') {
			cf_meta_edit_page();
		}
	}
	
	/**
	 * Display post editing functions
	 */
	function cf_meta_edit_post() {
		global $cfmeta,$post;
		$cfmeta = cf_meta_gimme('post',$post->ID);
		$cfmeta->display();
	}
	
	/**
	 * Display page editing functions
	 */
	function cf_meta_edit_page() {
		global $cfmeta,$post;
		$cfmeta = cf_meta_gimme('page',$post->ID);
		$cfmeta->display();
	}
	
	/**
	 * Do the box display code
	 * @param object $post - the post or page object
	 * @param array $set - id,title,callback
	 */
	function cf_meta_show_box($object,$set) {		
		global $cfmeta,$post;
		$cfmeta = cf_meta_gimme(cf_meta_get_type(),$post->ID);
		$cfmeta->show_set_contents($set);
	}

	/**
	 * determine which type we're working on
	 */
	function cf_meta_get_type() {
		global $pagenow;
		switch(true) {
			case in_array($pagenow, array('post.php','post-new.php')):
				return 'post';
				break;
			case in_array( $pagenow, array('page.php','page-new.php')):
				return 'page';
				break;
			default:
				return false;
		}
	}

	/**
	 * Save post handler
	 * @param int $post_id - id of the post being operated on
	 * @param object $post - post data object
	 */
	function cf_meta_save_post($post_id,$post) {
		
		if ($_POST['cf_meta_active']) {
			switch ($post->post_type) {
				case 'revision':
					return;
					break;
				case 'page':
				case 'post':
					$cfmeta = cf_meta_gimme($post->post_type, $post->ID);
					break;
			}
			$cfmeta->save();
		}
	}
	
	/**
	 * not yet implemented
	 * message display handler
	 */
	function cf_meta_notices() {
		if(isset($_GET['cfm_error'])) {
			if($_GET['cfm_type'] == 'page') {
				$cfmeta = cf_meta_gimme('page',$_GET['post']);
			}
			elseif($_GET['cfm_type'] == 'post') {
				$cfmeta = cf_meta_gimme('post',$_GET['post']);
			}
			// show error here
		}
	}
	
	/**
	 * ghetto singleton
	 * Start up the correct class type 
	 * @var string $type - the data type we're dealing with
	 * @return object cf_meta
	 */
	function &cf_meta_gimme($type,$post_id) {
		global $cfmeta;
		if(!isset($cfmeta) || !$cfmeta) {
			if($type == 'post') { 
				$cfmeta = new cf_post_meta('post-meta-config.php',$post_id); 
			}
			elseif($type == 'page') { 
				$cfmeta = new cf_page_meta('post-meta-config.php',$post_id); 
			}
			else { return false; }
		}
		return $cfmeta;
	}
	
	/**
	 * Write head stylesheet link
	 */
	function cf_meta_head_items() {
		echo '<link rel="stylesheet" type="text/css" media="all" href="'.
				get_bloginfo('wpurl').'/index.php?wp-cf-meta&amp;cf_meta_action=admin_style&amp;type='.cf_meta_get_type().'&amp;ver='.CF_META_VERSION.'" />'.PHP_EOL;
	}
	
	/**
	 * Import CSS File
	 * For my sanity the CSS is held in a separate file
	 */
	function cf_meta_css() {
		$filepath = CF_META_PLUGIN_DIR.'css/cf-post-meta.css';
		if(function_exists('file_get_contents')) {
			$css = file_get_contents($filepath);
		}
		else {
			$f = fopen($filepath,'r');
			$css = fread($f,filesize($filepath));
		}
		
		header('Content-Type: text/css');
		header('Content-Length: '.filesize($filepath));
		echo $css;
		exit;
	}
?>
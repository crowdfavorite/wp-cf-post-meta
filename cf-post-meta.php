<?php
/*
Plugin Name: CrowdFavorite Post Metadata Manager
Plugin URI: http://crowdfavorite.com/wordpress/
Description: Facilitates adding additinal metadata fields to posts through the standard post entry interface. 
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/	
/* Tested back to PHP 4.4.7 & up to PHP 5.2.6 */
	
	/**
	 * 
	 */
	
	define('CF_META_VERSION', '1.0');
	
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
	require_once(ABSPATH.PLUGINDIR.'/cf-post-meta/classes/cf-input.class.php');
	require_once(ABSPATH.PLUGINDIR.'/cf-post-meta/classes/cf-meta.class.php');
	
	/**
	 * assign post actions
	 */
	add_action('edit_form_advanced','cf_meta_edit_post');
	add_action('save_post','cf_meta_save_post',10,2);
	if(isset($_GET['cfm_error']) || isset($_GET['cfm_notice'])) {
		add_action('admin_notices','cf_meta_notices');
	}
	/**
	 * assign page actions
	 */
	add_action('edit_page_form','cf_meta_edit_page');
	/**
	 * Include necessary CSS
	 */
	if(is_admin()) {
		add_action('admin_head','cf_meta_head_items');
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

	function cf_meta_save_post($post_id,$post) {
		if(isset($_POST['cf_meta_type'])) {
			if($post->post_type == 'revision') { return; }
			if($_POST['cf_meta_type'] == 'page') {
				$cfmeta = cf_meta_gimme('page',$post->ID);
			}
			elseif($_POST['cf_meta_type'] == 'post') {
				$cfmeta = cf_meta_gimme('post',$post->ID);
			}
			// process
			$cfmeta->save();
		}
	}
	
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
				get_bloginfo('wpurl').'/index.php?wp-cf-meta&cf_meta_action=admin_style&ver='.CF_META_VERSION.'" />'.PHP_EOL;
	}
	
	/**
	 * Import CSS File
	 */
	function cf_meta_css() {
		$filepath = ABSPATH.PLUGINDIR.'/cf-post-meta/css/cf-post-meta.css';
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
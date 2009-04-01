<?php

/**
 * Page heavy lifting
 */
class cf_page_meta extends cf_meta_js {

	var $type = 'page';

	/**
	 * Construct
	 */
	function cf_page_meta($config,$post_id) {
		cf_meta_js::cf_meta_js($config,$post_id);
	}
}

/**
 * Post heavy lifting
 */	
class cf_post_meta extends cf_meta_js {

	var $type = 'post';

	/**
	 * Construct
	 */
	function cf_post_meta($config,$post_id) {
		cf_meta_js::cf_meta_js($config,$post_id);
	}
}

?>
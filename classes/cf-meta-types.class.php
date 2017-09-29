<?php

/**
 * Page heavy lifting
 */
class cf_page_meta extends cf_meta_js {

	var $type = 'page';

	/**
	 * Construct
	 */
	function __construct( $config, $post_id ) {
		cf_meta_js::cf_meta_js($config,$post_id);
	}

	/**
	 * Added support for php <5.3.
	 *
	 * @param array   $config  Config array for this element.
	 * @param integer $post_id Id for this element.
	 *
	 * @return __construct
	 */
	function cf_page_meta( $config, $post_id ) {
		return self::__construct( $config, $post_id );
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
	function __construct( $config, $post_id ) {
		cf_meta_js::cf_meta_js($config,$post_id);
	}

	/**
	 * Added support for php <5.3.
	 *
	 * @param array   $config  Config array for this element.
	 * @param integer $post_id Id for this element.
	 *
	 * @return cf_post_meta
	 */
	function cf_post_meta( $config, $post_id ) {
		return self::__construct( $config, $post_id );
	}
}

class cf_custom_meta extends cf_meta_js {

	var $type = '';

	/**
	 * Construct
	 */
	function __construct( $config, $post_id, $type ) {
		$this->type = $type;
		cf_meta_js::cf_meta_js($config,$post_id);
	}

	/**
	 * Added support for php <5.3.
	 *
	 * @param array    $config  Config array for this element.
	 * @param integer  $post_id Id for this element.
	 * @param string   $type    Type string for this element.
	 *
	 * @return __construct
	 */
	function cf_custom_meta( $config, $post_id, $type ) {
		return self::__construct( $config, $post_id, $type );
	}
}

?>
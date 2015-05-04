<?php

class cf_meta_preview {

	private $doing_preview = false;

	public function __construct() {
		add_filter( 'add_post_metadata', 	array( $this, 'add' ), 10, 5 );
		add_filter( 'update_post_metadata', array( $this, 'update' ), 10, 5 );
		add_filter( 'delete_post_metadata', 	array( $this, 'delete' 	), 10, 5 );
		add_filter( 'get_post_metadata', array( $this, 'get' ), 10, 4 );
	}

	public function is_preview() {
		if ( is_admin() ) {
			return ! $this->doing_preview && isset( $_POST['wp-preview'] ) && $_POST['wp-preview'] == 'dopreview';
		}

		// And on the front end: (props @yrosen)
		return ! $this->doing_preview && isset( $_GET[ 'preview' ] ) && $_GET[ 'preview' ] == 'true';
	}

	private function mod_key( $key ) {
		if ( strlen( $key ) > 50 ) {
			$key = md5( $key );
		}
		return "_preview__{$key}";
	}

	public function __call( $method, $args ) {
		if ( ! $this->is_preview() || ! function_exists( "{$method}_metadata" ) ) {
			return $args[0];
		}

		// replace $check with $meta_type
		$args[ 0 ] = 'post';

		// modify key
		$preview_metafields = apply_filters( 'wp_post_revision_meta_keys', array() );
		global $post;

		// call original function but make sure we don't get stuck in a loop
		$this->doing_preview = true;

		$cfmeta = cf_meta_gimme( $post->post_type, $post->ID );
		if ( $cfmeta ) {
			$preview_metafields = $cfmeta->preview_fields;
		}

		if ( isset( $preview_metafields ) && is_array( $preview_metafields ) && in_array( $args[ 2 ], $preview_metafields ) ) {
			$args[ 2 ] = $this->mod_key( $args[ 2 ] );
		}

		$result = call_user_func_array( "{$method}_metadata", $args );
		$this->doing_preview = false;

		return $result;
	}
}

new cf_meta_preview();

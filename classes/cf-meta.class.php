<?php

/**
 * cf_meta base class
 */
class cf_meta {

	/**
	 * Meta name prefix, for display only
	 */
	var $prefix = 'cf_meta_';

	/**
	 * Our config
	 */
	var $config;
	
	/**
	 * post type
	 */
	var $type;
	
	/**
	 * Error var for validation and saving
	 */
	var $error;
	
	/**
	 * Post ID being operated on
	 */
	var $post_id;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Construct - take the config array and prep for operation
	 */
	function cf_meta($config,$post_id) {
		$this->post_id = $post_id;
		if(is_array($config)) { 
			$this->set_config($config); 
		}
		else { 
			$this->import_config($config); 
		}
	}

	/**
	 * Grab config array and prep it for use
	 * @var string $config - full or partial path to the config file
	 */
	function import_config($config) {
		$cf_meta_config = array();
		if(file_exists($config)) { 
			include_once($config); 
		}
		elseif(file_exists(CF_META_PLUGIN_DIR.$config)) { 
			include_once(CF_META_PLUGIN_DIR.$config); 
		}
		$this->set_config($cf_meta_config);
	}

	/**
	 * parse the proper config type
	 * @todo verify presence of required items
	 * @var array $config - array of page items
	 * @return bool
	 */
	function set_config($config) {
		$this->config = array();
		// loop through config and keep on relevant entries
		$config = apply_filters('cf_meta_config', $config);
		if (count($config)) {
			foreach($config as $conf) {
				// ignore non-relevant types
				if(is_array($conf['type']) && !in_array($this->type,$conf['type'])) { continue; }
				elseif(!is_array($conf['type']) && $conf['type'] != $this->type) { continue; }
				
				// assign this type to any config items that came in with both types as possibilities
				if(is_array($conf['type'])) { $conf['type'] = $this->type; }
				
				// do type validation here - make sure everybody required is present and accounted for
				$conf['prefix'] = $this->prefix;
				$conf['post_id'] = $this->post_id;
				// get the post_id in to the item config now
				foreach($conf['items'] as $key => $item) {
					$item['post_id'] = $this->post_id;
					$conf['items'][$key] = $item;
				}
				$this->config[$conf['id']] = $conf;
			}
		}
		if(count($this->config) > 0) { return true; }
		else { return false; }
	}
	
	/**
	 * Show fields, runs at admin head
	 * Calls the "add" method on the set so that the block is added to the post-meta block
	 */
	function display() {
		if ( ! empty( $this->config ) ) {
			foreach ( $this->config as $conf ) {
				$set = new cf_input_set( $conf );
				$set->add();
			}
		}
	}
	
	/**
	 * Show a box's contents
	 * @param array $box - id,title,callback
	 */
	function show_set_contents($box) {
		// add in a quick little hook we can look for at post processing time
		echo '
			<input type="hidden" name="cf_meta_active" value="1" />
		';			
		$id = str_replace($this->prefix,'',str_replace('_container','',$box['id']));
		$set = new cf_input_set($this->config[$id]);
		$set->display();
	}

	/**
	 * Process incoming post data
	 */
	function save() {
		// loop through sets
		$config = apply_filters('cf_meta_save_config', $this->config);
		if (count($config)) {
			foreach ($config as $set) {
				// process each input in a set
				foreach ($set['items'] as $item) {
					if ($item['type'] == 'block') {
						$item['prefix'] = $this->prefix;
						$block = new cf_input_block($item);
						$block->save();
					}
					else {
						if (!class_exists('cf_input_'.$item['type'])) { continue; }
						$item['prefix'] = $this->prefix;
						$type = 'cf_input_'.$item['type'];
						
						$item = new $type($item); 
						if (!$item->save()) {
							// process errors
							if ($item->error) {
								$item_save_error = "<h5 style=\"color:brown\">error on $item->save</h5>";
								die($item_save_error);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * set an error
	 */
	function set_error_and_redirect() {}

	/**
	 * Show errors
	 */
	function show_errors() {}

}

?>
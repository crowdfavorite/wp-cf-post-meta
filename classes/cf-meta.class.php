<?php

	/**
	 * Page heavy lifting
	 */
	class cf_page_meta extends cf_meta {
	
		var $type = 'page';
	
		/**
		 * Construct
		 */
		function cf_page_meta($config,$post_id) {
			cf_meta::cf_meta($config,$post_id);
		}
	}

	/**
	 * Post heavy lifting
	 */	
	class cf_post_meta extends cf_meta {
	
		var $type = 'post';
	
		/**
		 * Construct
		 */
		function cf_post_meta($config,$post_id) {
			cf_meta::cf_meta($config,$post_id);
		}
	}

	
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
			if(file_exists($config)) { 
				include_once($config); 
			}
			elseif(file_exists(ABSPATH.PLUGINDIR.'/cf-post-meta/'.$config)) { 
				include_once(ABSPATH.PLUGINDIR.'/cf-post-meta/'.$config); 
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
					elseif($conf['type'] != $this->type) { continue; }
				
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
		 * Show fields
		 */
		function display() {
			foreach($this->config as $conf) {
				// add in a quick little hook we can look for at post processing time
				echo '
					<input type="hidden" name="cf_meta_type" value="'.$this->type.'" />
				';
				$set = new cf_input_set($conf);
				$set->display();
			}
		}
	
		/**
		 * Process incoming post data
		 */
		function save() {
			// loop through sets
			foreach($this->config as $set) {
				// process each input in a set
				foreach($set['items'] as $item) {
					if(!class_exists('cf_input_'.$item['type'])) { continue; }
					$item['prefix'] = $this->prefix;
					$type = 'cf_input_'.$item['type'];
					$item = new $type($item); 
										
					if(!$item->save()) {
						// process errors
						if($item->error) {
							
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
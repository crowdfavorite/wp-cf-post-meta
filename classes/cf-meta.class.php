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
		 * Whether we've loaded the WYSIWYG javascript libraries for TinyMCE
		 */
		var $wysiwyg = false;
		
		/**
		 * Items to apply tiny_mce to
		 */
		var $wysiwyg_items;
	
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
						
						if($conf['type'] == 'block') { 
							wp_enqueue_script('jquery');
						}
						$this->check_wysiwyg($item);
					}
					
					$this->config[$conf['id']] = $conf;
				}
			}
			if(count($this->config) > 0) { return true; }
			else { return false; }
		}
		
		/**
		 * Check to see if we need to enqeue tinyMCE scripts or not
		 * @param array $item - a config item
		 */
		function check_wysiwyg($item) {
			// make sure wysiwyg_items is an array before we put anything in it
			if(!is_array($this->wysiwyg_items)) { $this->wysiwyg_items = array(); }
			
			// check item for WYSIWYG requirement
			$enqueue = false;
			if($item['type'] == 'block') {
				foreach($item['items'] as $block_item) {
					if($this->has_wysiwyg($block_item)) {
						$this->wysiwyg_items[] = $this->prefix.$block_item['name'];
						$enqueue = true;
						break; // no need to check any further
					}
				}
			}
			elseif($this->has_wysiwyg($item)) {
				$this->wysiwyg_items[] = $this->prefix.$item['name'];
				$enqueue = true;
			}
			
			// enqueue script if necessary and return
			if($enqueue) {
				wp_enqueue_script('tiny_mce');
				wp_enqueue_script('word-count');
				return true;
			}
			return false;
		}
		
		/**
		 * Check an individual item to see if it has WYSIWIG requirments
		 * Restricted to Textarea items only
		 * @param array $item - single post-meta item
		 * @return bool
		 */
		function has_wysiwyg($item) {
			return $item['type'] == 'textarea' && isset($item['wysiwyg']) && $item['wysiwyg'] == true;
		}
		
		/**
		 * Add wysiwyg editor (TinyMCE) init binding
		 * This pretty much mimics the default wordpress init so we don't bork its toolbar
		 * Only differences are:
		 * 		- removal of the autosave plugin, not sure of the repricussions of this yet
		 *		- setting mode:"exact", not sure of the repricussions yet
		 */
		function add_wysiwyg() {
			echo '
				<script type="text/javascript">
					//<![CDATA[
						// must init what we want and run before the WordPress onPageLoad function.
						// After this function redo the WordPress init so the main editor picks up the WordPress config. 
						// that is the only way I could get this to work.
						tinyMCE.init({
								mode:"exact",
								elements:"'.implode(',',$this->wysiwyg_items).'", 
								onpageload:"", 
								width:"100%", 
								theme:"advanced", 
								skin:"wp_theme", 
								theme_advanced_buttons1:"bold,italic,underline,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,|,charmap,spellchecker,code,wp_help", 
								theme_advanced_buttons2:"", 
								theme_advanced_buttons3:"", 
								theme_advanced_buttons4:"", 
								language:"en", 
								spellchecker_languages:"+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv", 
								theme_advanced_toolbar_location:"top", 
								theme_advanced_toolbar_align:"left", 
								theme_advanced_statusbar_location:"", 
								theme_advanced_resizing:"", 
								theme_advanced_resize_horizontal:"", 
								dialog_type:"modal", 
								relative_urls:"", 
								remove_script_host:"", 
								convert_urls:"", 
								apply_source_formatting:"", 
								remove_linebreaks:"1", 
								paste_convert_middot_lists:"1", 
								paste_remove_spans:"1", 
								paste_remove_styles:"1", 
								gecko_spellcheck:"1", 
								entities:"38,amp,60,lt,62,gt", 
								accessibility_focus:"1", 
								tab_focus:":prev,:next", 
								content_css:"'.get_bloginfo('wpurl').'/wp-includes/js/tinymce/wordpress.css", 
								save_callback:"", 
								wpeditimage_disable_captions:"", 
								plugins:"safari,inlinepopups,spellchecker,paste"
							});

						// redo the WordPress init, this is only needed on page and post edit screens
						// to make sure that the main edit area is initialized properly. If you copy
						// and paste this code for use in another spot then you do not need this next line
						tinyMCE.init(tinyMCEPreInit.mceInit);
					//]]>
				</script>
				';
			return;
		}
		
		/**
		 * Show fields, runs at admin head
		 * Calls the "add" method on the set so that the block is added to the post-meta block
		 */
		function display() {
			foreach($this->config as $conf) {
				$set = new cf_input_set($conf);
				$set->add();
			}
			if(is_array($this->wysiwyg_items)) {
				$this->add_wysiwyg();
			}
		}
		
		/**
		 * Show a box's contents
		 * @param array $box - id,title,callback
		 */
		function show_set_contents($box) {
			// add in a quick little hook we can look for at post processing time
			echo '
				<input type="hidden" name="cf_meta_type" value="'.$this->type.'" />
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
			foreach($this->config as $set) {
				// process each input in a set
				foreach($set['items'] as $item) {
					if($item['type'] == 'block') {
						$block = new cf_input_block($item);
						$block->save();
					}
					else {
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
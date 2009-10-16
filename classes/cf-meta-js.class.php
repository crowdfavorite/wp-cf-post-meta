<?php

/**
 * Manipulate the user prefs to always show our manipulated boxes, otherwise
 * a user can destroy the functionality of this plugin's show/hide conditionals.
 *
 * @param array $prefs 
 * @param string $option 
 * @param ing $user 
 * @return array - modified user prefs
 */
function cf_meta_metaboxclear($prefs,$option,$user) {
	global $cfmeta,$post;
	$cfmeta = cf_meta_gimme(cf_meta_get_type(),$post->ID);
	if (isset($cfmeta->conditions) && is_array($cfmeta->conditions) && is_array($prefs) && count($prefs)) {
		foreach ($prefs as $k => $v) {
			if (array_key_exists(str_replace('_container','',$v), $cfmeta->conditions)) {
				unset($prefs[$k]);
			}
		}
	}
	return $prefs;
}
add_filter('get_user_option_metaboxhidden_page','cf_meta_metaboxclear',10,3);
add_filter('get_user_option_metaboxhidden_page','cf_meta_metaboxclear',10,3);

/**
 * Process a config array in to JS conditionals for toggling
 * the display of items on the page based on the page state
 * Required by cf_meta to function
 */
class cf_meta_js extends cf_meta {

	/**
	 * Whether we've loaded the WYSIWYG javascript libraries for TinyMCE
	 */
	var $wysiwyg = false;
	
	/**
	 * Items to apply tiny_mce to
	 */
	var $wysiwyg_items;
	
	/**
	 * Our conditions to process
	 *
	 * @var array
	 */
	var $conditions;
	
	/**
	 * Array of comparison inversions
	 *
	 * @var array
	 */
	var $inverted = array();
	
	/**
	 * Array of comparison functions to output to consolidate code
	 *
	 * @var string
	 */
	var $comparison_funcs = array();
	
	/**
	 * Construct
	 * Set the internal conditions value as well as a comparison inversion chart
	 *
	 * @param array $conditions 
	 * @return bool
	 */
	function cf_meta_js($config,$post_id) {
		cf_meta::cf_meta($config,$post_id);
		wp_enqueue_script('jquery');
		$this->inverted = array(
			'&&' => '||',
			'||' => '&&',
			'<'  => '>',
			'>'  => '<',
			'>=' => '<=',
			'<=' => '>='
		);
		return true;
	}
	
	/**
	 * Extend the set config function
	 *
	 * @param array $config 
	 * @return bool
	 */
	function set_config($config) {
		if(cf_meta::set_config($config)) {
			foreach($this->config as $conf) {
				// check display conditions
				if($this->has_js_condition($conf)) {
					$this->add_condition($conf); 
				}
			
				foreach($conf['items'] as $key => $item) {
					$this->check_wysiwyg($item);
				}
			}
		}
		return true;
	}
	
	/**
	 * Extend the display function to dump the JS to page
	 */
	function display() {
		global $wp_version;
		cf_meta::display();
		if(is_array($this->wysiwyg_items)) {
			// Move JS to last item after admin footer scripts for WP 2.8+ compatability
			if(version_compare($wp_version,'2.8', '>=')) {
				add_action('admin_print_footer_scripts',array($this,'add_wysiwyg'),999);
			}
			else {
				$this->add_wysiwyg();
			}
		}
		if(is_array($this->conditions)) {
			$this->display_conditions();
		}
	}
	
	/**
	 * Parent display function
	 * dispatches to other functions to build dynamic JS
	 */
	function display_conditions() {
		$output = '';
		foreach($this->conditions as $id => $set) {
			if(is_array($set)) {
				$ret = $this->do_set($set,$id.'_container');
				if(!$ret) { 
					return false;
				}
				$output .= $ret;
			}
		}
//echo '<pre>'.htmlentities($output).'</pre>';
		if(strlen(trim($output))) {
			echo '
<script type="text/javascript">
//<![CDATA[
	// Dynamically Generated Javascript for CF-Meta box display control
	jQuery(document).ready(function(){
'.$output.'
	});'.PHP_EOL;
			if(is_array($this->comparison_funcs)) {
				foreach($this->comparison_funcs as $id => $func) {
					echo PHP_EOL.'	// CF-Meta comparison function for '.$id.$func;					
				}
			}
			echo '
//]]>
</script>'.PHP_EOL;
		}
	}
	
	/**
	 * Process an Input Set's javascript
	 *
	 * @param array $set - config values for building the JS 
	 * @param string $id - id of the set being targeted
	 * @return string javascript
	 */
	function do_set($set,$id) {
		// if we have a comparison then we can do multiple comparisons
		if(isset($set['method'])) {
			$method = $set['method'];
			unset($set['method']);
		}
		elseif(!isset($set['method']) && count($set) > 1) {
			// malformed config
			return false;
		}
		
		// build page load comparison for initial show/hide
		$comparison = $this->build_comparison_func($method,$id,$set);
		// build toggle function 
		$toggle = $this->build_toggle_func($method,$id,$set);
		
		// don't proceed if we encountered an error in processing
		if($comparison == false || $toggle == false) { 
			return false; 
		}
		
		// start output with an identifier and comparision function
		$output = '		// CF-Meta Dynamic JS for #'.$id.$comparison;
		
		// make click conditions
		foreach($set as $cond) {
			$func = $cond['type'];
			if(method_exists($this,$func)) {
				$identifier = '#'.$this->$func(true);
			}
			else {
				if (isset($cond['bind-change'])) {
					$identifier = $cond['bind-change'];
				} else {
					$identifier = $func;
				}
			}
			$output .= '
		jQuery("'.$identifier.'").change(function(){
			'.$toggle.'();
		});'.PHP_EOL;
		}
		return $output.PHP_EOL;
	}
	
	/**
	 * Build a comparison function to handle the item display on initial page load
	 * requires inverting the comparison methods to explicitly hide when conditions aren't met.
	 * Code returned is designed to be called at dom ready.
	 *
	 * @param string $method 
	 * @param string $id 
	 * @param array $set 
	 * @return string
	 */
	function build_comparison_func($method,$id,$set) {
		$items = array();
		foreach($set as $cond) {
			$ret = $this->build_comparison($cond,true);
			if(!$ret) { 
				return false; 
			}
			$items[] = $ret;
		}
		$comparison = '
		if('.implode(' '.$this->inverted[$method].' ',$items).') {
			jQuery("#'.$id.'").hide();
		}
		jQuery(".metabox-prefs label[for='.$id.'-hide]").hide();
		'.PHP_EOL;
		return $comparison;
	}
	
	/**
	 * Build the toggle function for a set
	 * After building the comparison function the func is logged to an array for later output
	 * The function name is returned so it can be used inside click events
	 *
	 * @param string $method 
	 * @param string $id 
	 * @param array $set 
	 * @return string function name
	 */
	function build_toggle_func($method,$id,$set) {
		// build function name
		$funcname = str_replace(array('-'),'_',strtolower($id)).'_comparison';
		
		// build toggle comparison for input change show/hide
		$items = array();
		foreach($set as $cond) {
			$ret = $this->build_comparison($cond);
			if(!$ret) { 
				return false; 
			}
			$items[] = $ret; 
		}

		// page load comparison function for this set
		$toggle = '
	function '.$funcname.'() {
		if('.implode(' '.$method.' ',$items).') {
			jQuery("#'.$id.'").show();
		}
		else {
			jQuery("#'.$id.'").hide();
		}
	}'.PHP_EOL;
		
		$this->comparison_funcs[$id] = $toggle;	
		return $funcname;
	}
	
	/**
	 * Build the JS comparisons based on the config values
	 * Needs to accept the invert parameter to be able to do opposite comparisons
	 * This is needed to handle negative conditions on page load and positive for toggles
	 *
	 * @param array $cond - array of conditional values 
	 * @param bool $invert - whether to invert the comparison operators or not
	 * @return string
	 */
	function build_comparison($cond,$invert=false) {
		if(is_array($cond['value']) && !isset($cond['method'])) {
			// no multi-value comparison value was given for multiple values, malformed condition
			return false;
		}

		// only operate if we know how to address the target element's value
		$func = $cond['type'];
		$jquery = false;
		if(method_exists($this,$func)) {
			$jquery = $this->$func();
		}
		else {
			$jquery = $this->generic_func($func);
		}
		if(!$jquery) { return false; }
		
		// build the comparison
		$method = $invert ? $this->inverted[$cond['method']] : $cond['method'];
		unset($cond['method']);
		
		if(is_array($cond['value'])) {
			// multi-condition given and multi-value comparison given
			$ret = array();
			foreach($cond['value'] as $value) {
				$ret[] = $jquery.' '.($invert ? '!' : '').$cond['comparison'].' "'.$value.'"';
			}
			return is_array($ret) ? '('.implode(' '.$method.' ',$ret).')' : null;
		}
		else {
			// single comparison value given, single return
			return $jquery.' '.($invert ? '!' : '').$cond['comparison'].' "'.$cond['value'].'"';
		}
	}
	
	/**
	 * We've been given an ID, use it to build a generic jQuery func call
	 *
	 * @param string $func 
	 * @return string
	 */
	function generic_func($identifier) {
		return 'jQuery("'.$identifier.'").val()';
	}
	
	/**
	 * The following functions target specific WordPress Admin UI elements.
	 * WordPress elements are targeted specifically so that any upgrades to
	 * the WP UI are easily managed.
	 */
	
		/**
		 * Returns id and/or javascript for targeting the page_template value
		 *
		 * @param bool $id_only 
		 * @return string
		 */
		function page_template($id_only=false) {
			$id = 'page_template';
			if($id_only) {
				return $id;
			}
			return 'jQuery("#'.$id.' option:selected").val()';
		}

		/**
		 * Returns id and/or javascript for targeting the page_parent value
		 *
		 * @param bool $id_only 
		 * @return string
		 */
		function page_parent($id_only=false) {
			$id = 'parent_id';
			if($id_only) {
				return $id;
			}
			return 'jQuery("#'.$id.' option:selected").val()';
		}
	
		/**
		 * Returns id and/or javascript for targeting the post_status value
		 *
		 * @param bool $id_only 
		 * @return string
		 */
		function post_status($id_only=false) {
			$id = 'post_status';
			if($id_only) {
				return $id;
			}
			return 'jQuery("#'.$id.' option:selected").val()';
		}

		/**
		 * Returns id and/or javascript for targeting the category value
		 *
		 * @param bool $id_only 
		 * @return string
		 */	
		function category($id_only=false) {
			if($id_only) {
				return $id;
			}
			return '//category stub';
		}

	/**
	 * Check to see if an item has any JS conditionals for its display
	 *
	 * @param array $item 
	 * @return bool
	 */
	function has_js_condition($item) {
		return isset($item['condition']) && is_array($item['condition']);
	}
	
	/**
	 * Add condition to the global conditions for processing
	 *
	 * @param array $item 
	 * @return bool
	 */
	function add_condition($item) {
		return $this->conditions[$this->prefix.$item['id']] = $item['condition'];
	}

	// WYSIWYG Functions
	
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
			//wp_enqueue_script('tiny_mce');
			//wp_enqueue_script('word-count');
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
	// must init what we want and run before the WordPress onPageLoad function.
	// After this function redo the WordPress init so the main editor picks up the WordPress config. 
	// that is the only way I could get this to work.
	tinyMCE.init({ ';
		// compress output whitespace a bit...
		echo preg_replace('/(\n|\t)/','','
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
			save_callback:"", 
			wpeditimage_disable_captions:"", 
			plugins:"safari,inlinepopups,spellchecker,paste"
		');
		echo '
	});
</script>
			';
		return;
	}
}

?>
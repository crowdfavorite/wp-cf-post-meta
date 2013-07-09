<?php

// Sets and Blocks

	/**
	 * Output a set of inputs under an admin set
	 */
	class cf_input_set {
		var $set;
		
		/**
		 * Construct
		 * Adds some vars manually to accommodate legacy config arrays
		 */
		function __construct($set) {
			if(!isset($set['priority'])) { $set['priority'] = 'core'; }
			if(!isset($set['context'])) { $set['context'] = 'normal'; }
			$this->set = $set;
		}
		
		/**
		 * display the set contents
		 */
		function display() {
			// Hey, you can put javascript here now
			$html = apply_filters('cf_meta_box_head', '', $this->set['id']);
			if(isset($this->set['description'])) { 
				$html .= '<p>'.$this->set['description'].'</p>'.PHP_EOL; 
			}
			$html .= '<div class="cf_meta_set">';
			foreach($this->set['items'] as $item) {
				$item['prefix'] = $this->set['prefix'];
				// start up a new object or continue on invalid type
				if(class_exists('cf_input_'.$item['type'])) { 
					$type = 'cf_input_'.$item['type'];
					$item = new $type($item); 
				}
				else { continue; }
				$html .= $item->display();
			}
			$html .= '</div>';
			echo $html;
		}
		
		/**
		 * Add this set as a meta box
		 */
		function add() {
			return add_meta_box(
							$this->set['prefix'].$this->set['id'].'_container',
							__($this->set['title']),
							'cf_meta_show_box',
							$this->set['type'],
							$this->set['context'],
							$this->set['priority']
						);
		}
	}

	/**
	 * Creates a block repeater item
	 * Similar to a set, but each block can expand to hold many items
	 */
	class cf_input_block {
		
		var $repeater_index_placeholder = '###INDEX###';
		
		function __construct($conf) {
			// default to saving block data as a single entry
			if(!isset($conf['process_group'])) { $conf['process_group'] = true; }
			if(!isset($conf['block_label_group'])) { $conf['block_label_group'] = true; }
			if(!isset($conf['name'])) { $conf['name'] = $conf['block_id']; }
			if(!isset($conf['prefix'])) { $conf['prefix'] = $conf['name']; }
			$this->config = $conf;
		}
		
		/**
		 * Build a block item
		 * if no data is passed, make an empty block item based on config data
		 * 
		 * @param array $item
		 * @return string $html
		 */
		function make_block_item($data=array()) {
			// set the defaults to accommodate creating the empty template row
			$data_defaults = array(
					'index' => $this->repeater_index_placeholder
				);
			$data = array_merge($data_defaults,$data);
				
			$html = '<fieldset class="type_block" id="'.$this->config['name'].'_'.$data['index'].'">'.
					'<div class="inside">';

			foreach($this->config['items'] as $item) {
				// grab item data if available
				$item_data = (isset($data['items']) && isset($data['items'][$item['name']]) ? $data['items'][$item['name']] : array());

				// override item name and prefix
				$item['key_name'] = $this->config['name'];
				$item['item_name'] = $item['name'];
				$item['item_index'] = $data['index'];
				$item['name'] = 'blocks['.$this->config['name'].']['.$data['index'].']['.$item['name'].']';
				$item['prefix'] = $this->config['prefix'];

				if(class_exists('cf_input_'.$item['type'])) {
					$type = 'cf_input_'.$item['type'];
					$item['post_id'] = $this->config['post_id'];
					$item = new $type($item);
				}
				else { continue; }
				
				$html .= $item->display($item_data);
			}
			
			$html .= '<a href="#" onclick="delete'.$this->config['name'].'(jQuery(this).parent()); return false;" class="icon delete">Delete</a>'.
					 '</div>'.
					 '</fieldset>';
			
			return $html;
		}
		
		/**
		 * Create a block "repeater" element
		 * Also generates the "tempalte" repeater element + the javascript used to control it
		 */
		function display() {
			
			// retrieve previously saved values
			$data = apply_filters('cfinput_get_value',get_post_meta($this->config['post_id'],$this->config['name'], true),$this->config);
			if ($data != '') {
				$data = maybe_unserialize($data);
			}
			// kick off the repeater block with a wrapper div to contain everything
			$html = '<div class="block_wrapper'.(!empty($this->config['block_class']) ? ' '.$this->config['block_class'] : null).'">'.
					 '<h4>';
			if (isset($this->config['block_label']) && !empty($this->config['block_label'])) {
				// Check and see if the block label group needs to be added
				if (isset($this->config['block_label_group']) && $this->config['block_label_group'] == true) {
					$html .= 'Group for ';
				}
				$html .= $this->config['block_label'];
			}
			else {
				$html .= '&nbsp;';
			}
			$html .= '</h4>';
			
			// this div just contains the actual items in the group and it's where new elements are inserted
			$html .= '<div id="'.$this->config['name'].'" class="insert_container">';
						
			if (is_array($this->config['items'])) {
				// if we have data then we need to display it first
				if (is_array($data) && count($data)) {
					foreach ($data as $index => $each) {
						$html .= $this->make_block_item(array('index' => $index++, 'items' => $each));
					}
				}
				// else we can just display an empty set 
				else {
					$html .= $this->make_block_item(array('index' => 0));
				}
			}
			$html .= '</div>'; // this is the end of the .insert_container div
			
			// JS for inserting and removing new elements for repeaters
			$html .= '
				<script type="text/javascript" charset="utf-8">
					function addAnother'.$this->config['name'].'() {
						insert_element = \''.str_replace(PHP_EOL,'',trim($this->make_block_item())).'\';
						insert_element = insert_element.replace(/'.$this->repeater_index_placeholder.'/g, jQuery("#'.$this->config['name'].'").children().length);
						jQuery(insert_element).appendTo(\'#'.$this->config['name'].'\');
					}
					function delete'.$this->config['name'].'(del_el) {
						if(confirm(\'Are you sure you want to delete this?\')) {
							jQuery(del_el).parent().remove();
						}
					}
				</script>';

			$html .= '<p class="cf_meta_actions"><a href="#" onclick="addAnother'.$this->config['name'].'(); return false;" '.
				     'class="add_another button-secondary">Add Another '.(!empty($this->config['block_label_singular']) ? $this->config['block_label_singular'] : $this->config['block_label']).'</a></p>'.
					 '</div><!-- close '.$this->config['name'].' wrapper -->';
			
			return $html;
		}
		
		/**
		 * Save the block data
		 * default is to save the entire block as a single post-meta item
		 */
		function save() {
			switch ($this->config['process_group']) {
				case true:
					$this->save_group($_POST);
					break;
				
				case false:
					$this->save_each($_POST);
					break;
			}
		}
		
		/**
		 * Save each - not implemented, maybe never will be
		 */
		function save_each() {
			return false;
		}
		
		/**
		 * Save entire block repeater as a single post-meta item
		 * If all values for a block item are null then the item is not saved
		 */
		function save_group($post) {
			$save_array = array();
			if (isset($post['blocks'][$this->config['name']])) {
				foreach ($post['blocks'][$this->config['name']] as $value) {
					// keep items where all values are empty from being saved
					$control = '';
					foreach($value as $item) { $control .= $item; }
					if(strlen(trim($control)) > 0) { 
						$save_array[] = $value;
					}
				}
				$save_array = apply_filters('cfinput_save_group', $save_array, $this->config['name'], $this->config);
			}
			if (count($save_array)) {
				return update_post_meta($this->config['post_id'],$this->config['name'],$save_array); 
			}
			else {
				return delete_post_meta($this->config['post_id'],$this->config['name']); 
			}
		}
	}


// Individual Input Classes

	/**
	 * Generic input class template
	 */
	class cf_input {
	
		var $config;
		var $value;
		var $post_id;
		var $error;
		var $required = false;
	
		/**
		 * Basic constructor
		 * @var array $conf - config array for this element
		 */
		function cf_input($conf) {
			// require name
			if(!isset($conf['name'])) { return false; }
			if(!isset($conf['key_name'])) { $conf['key_name'] = $conf['name']; }
			// are we required?
			if(isset($conf['required'])) { 
				$this->config['required'] = $conf['required'];
				unset($conf['required']); 
			}
			// move config in to local array
			$this->config = array();
			foreach($conf as $key => $value) {
				$this->config[$key] = $value;
			}
			$this->post_id = $conf['post_id'];
			$this->value = $this->get_value();
			return true;
		}
		
		/**
		 * verify presence of data and throw any necessary errors
		 */
		function save() {
			if(isset($_POST[$this->get_name()])) {
				// check required
				if($_POST[$this->get_name()] == '' && $this->required) {
					$this->error = 'required';
					return false;
				}

				// do validation here

				// write to db
				return $this->save_data($_POST[$this->get_name()]);
			} else {
				delete_post_meta($this->post_id,$this->get_name());
			}
			return false;
		}
		
		/**
		 * Do save action
		 */
		function save_data($value) {
			$value = apply_filters('cfinput_save_data', $value, $this->config['name'], $this->config);
			// delete meta entry on empty value
			if($value == '') { 
				return delete_post_meta($this->post_id,$this->config['name']); 
			}
			else { 
				return update_post_meta($this->post_id,$this->config['name'],$value); 
			}
		}
		
		/**
		 * Do data validation
		 */
		function validate_data() {
			
		}
	
		/**
		 * add prefix to name to avoid namespacing conflicts
		 */
		function get_name() {
			return $this->config['prefix'].$this->config['name'];
		}
	
		/**
		 * id and name can be the same
		 */
		function get_id() {
			return $this->get_name();
		}
	
		/**
		 * return label value
		 */
		function get_label($class) {
			if(isset($this->config['label']) && !empty($this->config['label'])) {
				return '<label for="'.$this->get_id().'" class="'.$class.'">'.$this->config['label'].'</label>';
			}
		}
	
		function get_input($value=false) {
			$value = ($value) ? $value : $this->get_value();
			$class = isset($this->config['label']) ? null : 'class="full" ';

			return '<input type="'.$this->config['type'].'" name="'.$this->get_id().'" id="'.$this->get_id().'" value="'.htmlspecialchars($value).'" '.$this->attributes().$class.'/>';
		}
	
		function attributes() {
			$atts = array();
			if (!empty($this->config['attributes'])) {
				foreach($this->config['attributes'] as $att_name => $att_value) {
					$atts[] = esc_attr($att_name).'="'.esc_attr($att_value).'"';
				}
				$atts[] = ''; // fake last element to force a space at the end of the string
			}
			return implode(' ', $atts);
		}
	
		/**
		 * show the input
		 * uses 2.6 admin as template
		 */
		function display($value=false) {
			$html = '';
			if(isset($this->config['before'])) { 
				$html .= $this->config['before'].PHP_EOL; 
			}

			$html .= '<p class="cf_meta_kv cf_meta_kv_'.esc_attr($this->config['type']).'">';
			if(!isset($this->config['label_position']) || $this->config['label_position'] == 'before' || $this->config['label_position'] != 'after') {
				$html .= $this->get_label('before').$this->get_input($value);
			}
			elseif($this->config['label_position'] == 'after') {
				$html .= $this->get_input().$this->get_label('after');
			}
			$html .= (!empty($this->config['help']) ? '<span class="help">' . $this->config['help'] . '</span>' : '');
			$html .= ' </p>'.PHP_EOL;
			
			if(isset($this->config['after'])) { 
				$html .= $this->config['after'].PHP_EOL; 
			}
		
			return $html;
		}
	
		/**
		 * Get current field value, if any
		 */
		function get_value() {
			if(!$this->value) {

				$value = get_post_meta($this->post_id,$this->config['key_name'],true);

				// if we're a repeater element we need to fish out our individual value
				if (isset($this->config['item_index']) && isset($this->config['item_name'])) {
					// make sure we have an array to work with
					if (!is_array($value) && empty($value)) {
						$value = array();
					}
					else {
						// what do we do if we find a value?
					}

					// get the value or assign to empty				
					if (isset($value[$this->config['item_index']][$this->config['item_name']])){
						$value = $value[$this->config['item_index']][$this->config['item_name']];
					}
					else {
						$value = '';
					}
				}

				if(!empty($value)) { 
					$this->value = $value; 
				}
				else if (!empty($this->config['default_value']) && !in_array($this->config['type'], array('checkbox'))) {
					$this->value = $this->config['default_value'];
				}
				else { 
					$this->value = null; 
				}
			}
			return apply_filters('cfinput_get_value',$this->value,$this->config);
		}
	
		/**
		 * Get default value, if any
		 */
		function get_default_value() {
			if(!$this->config['default_value']) {
				$this->config['default_value'] = null; 
			}
			return $this->config['default_value'];
		}
	
			/**
			 * Getters and setters
			 */
			function __get($var) {
				if(isset($this->config[$var])) { 
					return $var; 
				}
				else { 
					return false; 
				}
			}
		
			function __set($var,$val) {
				if(isset($this->config[$var])) { 
					return $this->config[$var] = $val; 
				}
				else { 
					return false; 
				}
			}
		
			function __isset($var) {
				return isset($this->config[$var]);
			}
		
			function __unset($var) {
				if(isset($this->config[$var])) { 
					unset($this->config[$var]); 
					return true;
				}
				else { 
					return false; 
				}
			}
		
			function __clone() {
				return false;
			}
	}

	/**
	 * Text input - no overrides
	 */
	class cf_input_text extends cf_input {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
	}
	
	/**
	 * Hidden Input
	 */
	class cf_input_hidden extends cf_input {
		function cf_input_hidden($conf) {
			return cf_input::cf_input($conf);
		}
		
		/**
		 * hidden inputs will always use the given value in the config
		 */
		function get_value() {
			return apply_filters('cfinput_get_value',$this->config['value'],$this->config);
		}
	}

	/**
	 * Password input - no overrides
	 */
	class cf_input_password extends cf_input {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
	}

	/**
	 * Textarea - simple type, no overrides
	 */
	class cf_input_textarea extends cf_input  {
		var $cols = 40;
		var $rows = 1;
		
		function cf_input_textarea($conf) {
			if(isset($conf['cols'])) { $this->cols = $conf['cols']; }
			if(isset($conf['rows'])) { $this->rows = $conf['rows']; }
			return cf_input::cf_input($conf);
		}	
	
		function get_label($class) {
			if(isset($this->config['wysiwyg']) && $this->config['wysiwyg'] == true) {
				$class .= ' cf-rich-edit';
			}
			return cf_input::get_label($class);
		}
	
		/**
		 * Override the input output
		 */
		function get_input() {
			/**
			 * setup the tinyMCE wysiwyg on text areas if configed
			 */
			$before = $after = '';
			if(isset($this->config['wysiwyg']) && $this->config['wysiwyg'] == true) {
				// tinyMCE needs a spacer to help keep it in line, this can probably be solved with css but for now its good
				$before = '<div style="clear:both">&nbsp;</div>'.
						  '<div><div id="'.$this->get_id().'_container">';
						
				$after = '</div></div>
						   <div style="clear:both">&nbsp;</div>';
			}
			
			return $before.'<textarea name="'.$this->get_name().'" id="'.$this->get_id().'" cols="'.$this->cols.'" rows="'.$this->rows.'" class="cf_tiny_mce">'.
				   htmlspecialchars($this->get_value()).'</textarea>'.$after;
		}	
	}
	
	class cf_input_radio extends cf_input  {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
		function get_input() {
			$output = '';
			if (is_array($this->config['options']) && count($this->config['options'])) {
				foreach ($this->config['options'] as $k => $v) {
					$k == $this->get_value() ? $checked = ' checked="checked"' : $checked = '';
					$output .= '<span class="cf_meta_radio_group"><input type="radio" name="'.$this->get_name().'"id="'.$this->get_id().'" value="'.$k.'"'.$checked.' /> <span class="cf_meta_radio_label">'.$v.'</span></span>';
				}
			}
			return $output;
		}
	}

	class cf_input_checkbox extends cf_input {
		
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
		
		function get_input() {
			$this->get_value() == $this->get_default_value() ? $checked = ' checked="checked"' : $checked = '';
			return '<input type="checkbox" class="cf_meta_cb" name="'.$this->get_name().'" id="'.$this->get_id().'" value="'.$this->get_default_value().'"'.$checked.' />';
		}
		
		// this removes the post_meta altogether if there isn't a value present in the $_POST array, thereby allowing unchecked boxes to save.
		function save() {
			if (empty($_POST[$this->get_name()])) {
				return delete_post_meta($this->config['post_id'], $this->config['name']);
			} else {
				return update_post_meta($this->config['post_id'], $this->config['name'], $_POST[$this->get_name()]);
			}
		}
	}

	class cf_input_select extends cf_input  {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
		function get_input() {
			$output = '<select name="'.$this->get_name().'" id="'.$this->get_id().'">';
			if (is_array($this->config['options']) && count($this->config['options'])) {
				foreach ($this->config['options'] as $k => $v) {
					$k == $this->get_value() ? $selected = ' selected="selected"' : $selected = '';
					$output .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
				}
			}
			$output .= '</select>';
			return $output;
		}	
	}

	class cf_input_file extends cf_input {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
	}

?>

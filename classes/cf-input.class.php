<?php
	/**
	 * Output a set of inputs under an admin set
	 */
	class cf_input_set {
		var $set;
		function cf_input_set($set) {
			$this->set = $set;
		}
		function display() {
			$html = '
				<div id="'.$this->set['prefix'].$this->set['id'].'_container" class="postbox cf_meta_set">
					<h3>'.$this->set['title'].'</h3>
					<div class="inside">
						';
			if(isset($this->set['description'])) { 
				$html .= '<p>'.$this->set['description'].'</p>'.PHP_EOL; 
			}
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
			$html .= '
					</div>
				</div>
				';
		
			echo $html;
		}
	}

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
			}
			return false;
		}
		
		/**
		 * Do save action
		 */
		function save_data($value) {
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
	
		function get_input() {
			$class = isset($this->config['label']) ? null : 'class="full" ';
			return '<input type="'.$this->config['type'].'" name="'.$this->get_id().'" id="'.$this->get_id().'" value="'.$this->get_value().'" '.$class.'/>';
		}
	
		/**
		 * show the input
		 * uses 2.6 admin as template
		 */
		function display() {
			$html = '';
			if(isset($this->config['before'])) { 
				$html .= $this->config['before'].PHP_EOL; 
			}
			$html .= '<p>';
			if(!isset($this->config['label_position']) || $this->config['label_position'] == 'before' || $this->config['label_position'] != 'after') {
				$html .= $this->get_label('before').$this->get_input();
			}
			elseif($this->config['label_position'] == 'after') {
				$html .= $this->get_input().$this->get_label('after');
			}
			$html .= '<div class="clear"></div></p>'.PHP_EOL;
			
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
				$value = get_post_meta($this->post_id,$this->config['name'],true);
				if(!empty($value)) { 
					$this->value = $value; 
				}
				else { 
					$this->value = null; 
				}
			}
			return $this->value;
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
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}	
	
		/**
		 * Override the input output
		 */
		function get_input() {
			return '<textarea name="'.$this->get_name().'" id="'.$this->get_id().'" cols="'.$this->cols.'" rows="'.$this->rows.'">'.$this->get_value().'</textarea>';
		}	
	}

	class cf_input_radio extends cf_input  {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
	}

	class cf_input_checkbox extends cf_input {
		function cf_input_text($conf) {
			return cf_input::cf_input($conf);
		}
		function get_input() {
			$this->get_value() == $this->get_default_value() ? $checked = ' checked="checked"' : $checked = '';
			return '<input type="checkbox" name="'.$this->get_name().'" id="'.$this->get_id().'" value="'.$this->get_default_value().'"'.$checked.' />';
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
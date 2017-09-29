<?php
/**
 * Extensions of some baic post meta input type classes to allow reuse of input
 * types in the Settings screens
**/

/**
* Extension of the basic cf_input_block class
*/
class cfs_input_block extends cf_input_block {
	function __construct( $conf ) {
		return cf_input_block::cf_input_block( $conf );
	}

	/**
	 * Added support for php 5.3-
	 *
	 * @param $conf
	 *
	 * @return cf_input_block
	 */
	function cfs_input_block( $conf ) {
		return parent::__construct( $conf );
	}

	function display() {
		
		// retrieve previously saved values
		if (!empty($this->config['blog_id'])) {
			$default_data = get_blog_option($this->config['blog_id'], $this->config['name']);
		}
		else {
			$default_data = get_option($this->config['name']);
		}
		$data = apply_filters('cfinput_get_settings_value',$default_data,$this->config);
		if ($data != '') {
			$data = maybe_unserialize($data);
		}
		// kick off the repeater block with a wrapper div to contain everything
		$html .= '<div class="block_wrapper">'. 
				 '<h4>'.
				 (isset($this->config['block_label']) && !empty($this->config['block_label']) ? $this->config['block_label'] : '&nbsp;').
				 '</h4>';
		
		// this div just contains the actual items in the group and it's where new elements are inserted
		$html .= '<div id="'.$this->config['name'].'" class="insert_container">';
					
		if (is_array($this->config['items'])) {
			// if we have data then we need to display it first
			if (!empty($data)) {
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
					if (jQuery(\'#'.$this->config['name'].'\').children().length > 0) {
						last_element_index = jQuery(\'#'.$this->config['name'].' fieldset:last\').attr(\'id\').match(/'.$this->config['name'].'_([0-9])/);
						next_element_index = Number(last_element_index[1])+1;
					} else {
						next_element_index = 1;
					}
					insert_element = \''.str_replace(PHP_EOL,'',trim($this->make_block_item())).'\';
					insert_element = insert_element.replace(/'.$this->repeater_index_placeholder.'/g, next_element_index);
					jQuery(insert_element).appendTo(\'#'.$this->config['name'].'\');
				}
				function delete'.$this->config['name'].'(del_el) {
					if(confirm(\'Are you sure you want to delete this?\')) {
						jQuery(del_el).parent().remove();
					}
				}
			</script>';

		/* If we have add_another button text set, use that instead of the block_label */
		if (isset($this->config['add_another_button_text']) && !empty($this->config['add_another_button_text'])) {
			$add_another_text = $this->config['add_another_button_text'];	
		}
		else {
			$add_another_text = 'Add Another '.$this->config['block_label'];	
		} 
		
		$html .= '<p class="cf_meta_actions"><a href="#" onclick="addAnother'.$this->config['name'].'(); return false;" '.
			     'class="add_another button-secondary">'.$add_another_text.'</a></p>'.
				 '</div><!-- close '.$this->config['name'].' wrapper -->';
		
		return $html;
	}
}

?>

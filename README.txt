# CF Post Meta Plugin

This plugin adds post-meta entry fields to the post and/or page edit screen and automatically saves the meta information on post-save. Post meta item groups are defined via a filter or alternatively through a config file. Post meta additions/modifications/updates are all handled through standard wordpress functions. Uses an OOP structure to define option groups and form fields.


## Currently Supports

- text, textarea, checkbox, radio, select, password & hidden input fields
- repeating elements
- conditional display of meta boxes


## Basic Usage

Adding a meta box to the wordpress page/post edit screen requires little code.

**Example:**

	/**
	 * Add inputs to the post-edit screen
	 *
	 * @param array $config
	 * @return array
	 */
	function my_add_metabox($config) {
		
		$config[] = array(
			'title' => 'Block title',	// required, Title of the Meta Box
			'description' => '', 		// optional, Description text that appears at the top of the Meta Box
			'type' => array('post'), 	// required, Which edit screen to add to. Use array('page','post') to add to both at the same time
			'id' => 'unique-id', 		// required, unique id for the Meta Box
			'add_to_sortables' => true,	// optional, this is the default behavior
			'context' => 'normal'		// optional, sets the location of the metabox in the edit page.  Other posibilites are 'advanced' or 'side' (this sets the meta box to apear in the rt sidebar of the edit page)
			'items' => array(
				// text input
				array(
					'name' => '_unique_meta_name',			// required, this is the meta_key that will be saved by WordPress
					'label' => 'Label Text', 				// optional, label only printed if text is not empty
					'label_position' => 'before',			// optional, label position in relation to the input, default: 'before'
					'type' => 'text',						// required, input type
					'before' => '<div class="special">',	// optional, html to put before the field
					'after' => '</div>',					// optional, html to put after the field
				),
				// textarea
				array(
					'name' => '_unique_meta_name',
					'type' => 'textarea',			
					'label' => 'Label Text'			
				),
				// select
				array(
					'name' => '_unique_meta_name',
					'type' => 'select',
					'label' => 'Choose One',
					'options' => array( 					// required for select inputs, values for options
						'key1' => 'value1',
						'key2' => 'value2',
						'key3' => 'value3'
					)
				),
				// hidden input
				array(
					'name' => '_unique_meta_name',
					'type' => 'hidden',
					'default_value' => 'value'				// required for hidden elements, element value
				),
				// Checkbox
				array(
					'name' => '_unique_meta_value',
					'type' => 'checkbox',
					'label' => 'Checkbox',
					'label_position' => 'after',
					'default_value' => 'val'				// required for checkboxes, element value
				),
				// Radio
				array(
					'name' => '_unique_meta_value',
					'type' => 'radio',
					'label' => 'Radio',
					'options' => array(
						'key1' => 'value1',
						'key2' => 'value2',
						'key3' => 'value3'
					),
				),
				// password input
				array(
					'name' => '_unique_meta_name',
					'label' => 'Secret',
					'type' => 'password'
				),
			)
		);
		
		return $config;
	}
	add_filter('cf_meta_config','my_add_metabox');
	
## Repeater Blocks

The Post Meta plugin has a special "block" repeater type that allows a set of elements to be duplicated as many times as needed by the user to add an array of values under a certain type. These values are all stored in an array under a single WordPress meta_value. Repeater element types can co-exists with any of the other element types. Items in the block are defined the same way as normal input elements.

**Example:**

	/**
	 * Add a repeater element type
	 *
	 * @param array $config
	 * @return array
	 */
	function my_add_metabox($config) {
		
		$config[] = array(
			'title' => 'Block title',	// required, Title of the Meta Box
			'description' => '', 		// optional, Description text that appears at the top of the Meta Box
			'type' => array('post'), 	// required, Which edit screen to add to. Use array('page','post') to add to both at the same time
			'id' => 'unique-id', 		// required, unique id for the Meta Box
			'add_to_sortables' => true,	// optional, this is the default behavior
			'items' => array(
				// text input
				array(
					'name' => '_unique_meta_name',			// required, this is the meta_key that will be saved by WordPress
					'label' => 'Label Text', 				// optional, label only printed if text is not empty
					'label_position' => 'before',			// optional, label position in relation to the input, default: 'before'
					'type' => 'text',						// required, input type
					'before' => '<div class="special">',	// optional, html to put before the field
					'after' => '</div>',					// optional, html to put after the field
				),
				// repeater block
				array(
					'name' => '_unique_meta_key',
					'type' => 'block',
					'block_label' => 'Items',			// adds a label to the block of elements
					'block_label_singular' => 'Item',	// adds a singular label to the "add new" button (otherwise defaults to block_label)
					'items' => array(						// required, elements in the repeatable block
						array(
							'name' => '_unique_element_name',
							'label' => 'Label Text',
							'type' => 'text'
						),
						array(
							'name' => '_unique_element_name',
							'label' => 'Label Text',
							'type' => 'checkbox',
							'label_position' => 'after',
							'default_value' => 'val'
						)
					)
				)
			)
		);
		
		return $config;
	}
	add_filter('cf_meta_config','my_add_metabox');
	
		
## Conditional Display of Meta Boxes

The Post Meta plugin allows for the conditional display of Meta Boxes based on the status of other elements in the post/page edit UI. Currently supported options are: 

- `page_parent`
- `page_template`
- `post_status`
- `#id_of_element` whose value to check

**Example:**

	/**
	 * Conditional Box Display
	 *
	 * @param array $config
	 * @return array
	 */
	function my_add_metabox($config) {
		
		// Simple comparison check
		$config[] = array(
			'title' => 'Block title',	// required, Title of the Meta Box
			'description' => '', 		// optional, Description text that appears at the top of the Meta Box
			'type' => array('post'), 	// required, Which edit screen to add to. Use array('page','post') to add to both at the same time
			'id' => 'unique-id', 		// required, unique id for the Meta Box
			'add_to_sortables' => true,	// optional, this is the default behavior
			'items' => array(
				// text input
				array(
					'name' => '_unique_meta_name',			// required, this is the meta_key that will be saved by WordPress
					'label' => 'Label Text', 				// optional, label only printed if text is not empty
					'label_position' => 'before',			// optional, label position in relation to the input, default: 'before'
					'type' => 'text',						// required, input type
					'before' => '<div class="special">',	// optional, html to put before the field
					'after' => '</div>',					// optional, html to put after the field
				)
			),
			'condition' => array( // simple comparison check
				array(
					'type' => 'page_template',
					'comparison' => '==',
					'value' => 'page_template.php'
				)
			)
		);
		
		// Complex comparison check
		$config[] = array(
			'title' => 'Block title',	// required, Title of the Meta Box
			'description' => '', 		// optional, Description text that appears at the top of the Meta Box
			'type' => array('post'), 	// required, Which edit screen to add to. Use array('page','post') to add to both at the same time
			'id' => 'unique-id', 		// required, unique id for the Meta Box
			'add_to_sortables' => true,	// optional, this is the default behavior
			'items' => array(
				// text input
				array(
					'name' => '_unique_meta_name',			// required, this is the meta_key that will be saved by WordPress
					'label' => 'Label Text', 				// optional, label only printed if text is not empty
					'label_position' => 'before',			// optional, label position in relation to the input, default: 'before'
					'type' => 'text',						// required, input type
					'before' => '<div class="special">',	// optional, html to put before the field
					'after' => '</div>',					// optional, html to put after the field
				)
			),
			'condition' => array( // complex comparison
				'method' => '&&', // comparison operator to apply to the following conditions
				array(
					'type' => 'page_template',
					'comparison' => '==',
					'value' => 'page_template.php'
				),
				array(
					'type' => 'page_parent',
					'comparison' => '!=',
					'value' => '22'
				)
			)
		);
		
		return $config;
	}
	add_filter('cf_meta_config','my_add_metabox');

## Advanced example of conditional with an arbitrary jQuery selector


The Post Meta plugin can match arbitrary elements in the DOM, allowing access to all elements displayed in the WordPress admin UI in the page and post editors.  The example below shows conditional display of Meta Boxes based on the status of check boxes (Categories in this case).  

**Example:**
	/**
	 * Conditional box display based on categories
	 * Display meta box for the 'Featured Article' category
	 * @param array $config
	 * @return array
	 */
	function my_add_metabox($config) {
	// Simple comparison check
	$feature_cat_id = get_cat_ID('Featured Article');
	$config[] = array(
		'title' => 'Featured Article',
		'description' => 'Fields available for Featured Articles',
		'type' => array('post'),
		'id' => 'abcd-feature-article-fields',
		'add_to_sortables' => true,
		'items' => array(
			array(
				'name' => '_feature_sub_head',
				'label' => 'Sub Headline',
				'type' => 'text'
			)
		),
		'condition' => array(
			array(
				'type' => '#in-category-' . $feature_cat_id . ':checked',
				'comparison' => '==',
				'bind-change' => '#in-category-'. $feature_cat_id, // optional; element to bind the change event
				'value' => $feature_cat_id
			)
		)
	);
	
	return $config;
}

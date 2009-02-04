<?php

/* 
// Sample config item
$group[] = array(
	'title' => 'Title for page block', // required
	'description' => 'Description, inline elements',
	'type' => array('page','post') // required, or just a string of 'page' or 'post'
	'id' => 'unique_id', // required
	'add_to_sortables' => bool, // whether the group is added to the sortables array or not
	'items' => array(
		array(
			'name' => '_unique meta_name', // required
			'label' => 'text for field label',
			'label_position' => 'before/after',
			'type' => 'checkbox/radio/int(x)/varchar(x)/text/textarea/password(x)', // required
			'length' => array('low','high'), // min/max length for input value - not yet implemented
			'required' => false,
			'values' => array('optional','values','for','radio','and','check','boxes') // required for radio/checkbox
			'before' => 'html to put before the element',
			'after' => 'html to put after the element',
			'wysiwyg' => bool // only valid on textarea elements, will be ignored on all others
		),
		array(
			'block_id' => '_unique_id', // required
			'block_label' => 'block title string',
			'type' => 'block',
			'items' => array(
							// inputs
						),
			'process_group' => true // save the entire group as a serialized array, not yet implemented
		)
	),
	'callback' => '! not-implemented !'
);
*/

?>
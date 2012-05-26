<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*------------------------------------------
 *	Version
/* -------------------------------------- */

$config['AUTO_IMPORT_VERSION'] = '0.1.0';

$config['auto_import_upload_fields'] = array(

	'settings' => array(
		'title' => 'Settings',
		'attributes'  => array(
			'class' => 'mainTable padTable',
			'border' => 0,
			'cellpadding' => 0,
			'cellspacing' => 0
		),
		'wrapper' => 'div',
		'fields'  => array(
			'author_id' => array(
				'label'       => 'Author ID',
				'description' => 'This is the author id that will be assigned to the uploaded entries.',
				'type'        => 'input'
			),
			'products_channel' => array(
				'label'       => 'Products Channel',
				'description' => 'This is the channel that stores your products',
				'type'        => 'select',
				'settings' => array(
					'options' => 'CHANNEL_DROPDOWN'
				)
			),
			'products_title_field' => array(
				'label'       => 'Product Title Field',
				'description' => 'This is the channel field used for the product title.',
				'type'        => 'input'
			),
			'products_path' => array(
				'label'       => 'Products .CSV File Path',
				'description' => 'This is the absolute file path to the products .csv file.',
				'type'        => 'input'
			),
			'product_category_group' => array(
				'label'       => 'Products Category Group',
				'description' => 'This is the product category group.',
				'type'        => 'select',
				'settings' => array(
					'options' => 'CATEGORY_GROUPS_DROPDOWN'
				)
			),
			'category_id_field' => array(
				'label'       => 'Category ID Field',
				'description' => 'This is the category field that stored the ID from the POS.',
				'type'        => 'input'
			),
			'category_id_column' => array(
				'label'       => 'Category ID Column',
				'description' => 'This is the column that stores the ID of the category.',
				'type'        => 'input'
			),
			'category_parent_id_column' => array(
				'label'       => 'Category Parent ID Column',
				'description' => 'This is the column that stores the Pareitn ID for the sub-category.',
				'type'        => 'input'
			),
			'category_name_column' => array(
				'label'       => 'Category Name Column',
				'description' => 'This is the column that stores the name of the category.',
				'type'        => 'input'
			),
			'category_description_column' => array(
				'label' 	  => 'Category Description Column',
				'description' => 'This is the column that stores the description of the category.',
			),
			'categories_path' => array(
				'label'       => 'Categories .CSV File Path',
				'description' => 'This is the absolute file path to the categories .csv file.',
				'type'        => 'input'
			),
			'sub_categories_path' => array(
				'label'       => 'Sub Categories .CSV File Path',
				'description' => 'This is the absolute file path to the sub categories .csv file.',
				'type'        => 'input'
			),
			'vendors_channel' => array(
				'label'       => 'Vendor Channel',
				'description' => 'This is the channel that stores your vendors',
				'type'        => 'select',
				'settings' => array(
					'options' => 'CHANNEL_DROPDOWN'
				)
			),
			'vendors_id_field' => array(
				'label'       => 'Vendors ID Field',
				'description' => 'This field will store the POS vendor ID.',
				'type'        => 'input'
			),
			'vendors_id_column' => array(
				'label'       => 'Vendors ID Column',
				'description' => 'This is the name of the vendor ID column.',
				'type'        => 'input'
			),
			'vendors_title_column' => array(
				'label'       => 'Vendors Title Column',
				'description' => 'This is the name of the vendor title column.',
				'type'        => 'input'
			),
			'vendors_path' => array(
				'label'       => 'Vendors .CSV File Path',
				'description' => 'This is the absolute file path to the vendors .csv file.',
				'type'        => 'input'
			),
			'channel_fields' => array(
				'label' => 'Channel Fields',
				'description' => 'Use this field to pair up your channel fields with the columns in the .csv file. Be sure to make sure the names are an exact match.',
				'id'    => 'field_map_fields',
				'type'	=> 'matrix',
				'settings' => array(
					'columns' => array(
						0 => array(
							'name'  => 'field_name',
							'title' => 'Channel Field Name'
						),
						1 => array(
							'name'  => 'column_name',
							'title' => 'CSV Column Name'
						)
					),
					'attributes' => array(
						'class'       => 'mainTable padTable',
						'border'      => 0,
						'cellpadding' => 0,
						'cellspacing' => 0
					)
				)
			),
			'products_id_field' => array(
				'label'       => 'Product ID Field',
				'description' => 'This is the channel that stores the ID of the product from the POS. Your data will be grouped by this ID. Any similar products will be added to the product options matrix.',
				'type'        => 'input'
			),
			'products_id_column' => array(
				'label'       => 'Products ID Column',
				'description' => 'This is the column name that stores the product ID in the POS.',
				'type'        => 'input'
			),
			'matrix_field_name' => array(
				'label'       => 'Matrix Field Name',
				'description' => 'This is the name of your Matrix field.',
				'type'        => 'input'
			),
			'matrix_unique_column' => array(
				'label'       => 'Unique Options Identifier',
				'description' => 'This is the name of the matrix column that will be used to check if an option exists.',
				'type'        => 'input'
			),
			'matrix_fields' => array(
				'label' => 'Matrix Fields',
				'description' => 'Use this field to pair up your matrix columns with the columns in the .csv file. Be sure to make sure the names are an exact match.',
				'id'    => 'matrix_fields',
				'type'	=> 'matrix',
				'settings' => array(
					'columns' => array(
						0 => array(
							'name'  => 'field_name',
							'title' => 'Matrix Column Name'
						),
						1 => array(
							'name'  => 'column_name',
							'title' => 'CSV Column Name'
						)
					),
					'attributes' => array(
						'class'       => 'mainTable padTable',
						'border'      => 0,
						'cellpadding' => 0,
						'cellspacing' => 0
					)
				)
			)
		)
	)
);
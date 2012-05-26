<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Package_exporter_field_groups extends Package_exporter_driver
{
	public static $master_node = 'field_group';
	
	public static $sub_nodes = array(
		'field' => NULL,
	);
	
	public static function export($group_ids)
	{
		$EE =& get_instance();
		
		$field_groups = array();
		
		if ($group_ids)
		{
			$field_group_query = $EE->db->where_in('group_id', $group_ids)
						    ->get('field_groups');

			$field_group_rows = $field_group_query->result_array();

			if ($field_group_query->num_rows() > 0)
			{
				foreach ($field_group_rows as $field_group_row)
				{
					$field_group_id = $field_group_row['group_id'];
					
					unset($field_group_row['group_id'], $field_group_row['site_id']);
	
					$field_query = $EE->db->where('group_id', $field_group_id)
							      ->order_by('field_order')
							      ->get('channel_fields');
					
					foreach ($field_query->result_array() as $field_row)
					{
						$field_id = $field_row['field_id'];
	
						unset($field_row['field_id'], $field_row['group_id'], $field_row['site_id']);
	
						$field_group_row['field'][] = $field_row;
					}
					
					$field_groups[] = $field_group_row;
				}
			}
		}
		
		return $field_groups;
	}
}
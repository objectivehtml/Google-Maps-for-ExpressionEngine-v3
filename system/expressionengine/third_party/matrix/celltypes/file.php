<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * File Celltype Class for EE2
 * 
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_file_ft {

	var $info = array(
		'name' => 'File'
	);

	var $default_settings = array(
		'content_type' => 'any',
		'directory'    => 'all'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']['celltypes']['file']))
		{
			$this->EE->session->cache['matrix']['celltypes']['file'] = array();
		}
		$this->cache =& $this->EE->session->cache['matrix']['celltypes']['file'];
	}

	/**
	 * Prep Settings
	 */
	private function _prep_settings(&$settings)
	{
		$settings = array_merge($this->default_settings, $settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Upload Preferences
	 * @param  int $group_id Member group ID specified when returning allowed upload directories only for that member group
	 * @param  int $id       Specific ID of upload destination to return
	 * @return array         Result array of DB object, possibly merged with custom file upload settings (if on EE 2.4+)
	 */
	private function _get_upload_preferences($group_id = NULL, $id = NULL)
	{
		if (version_compare(APP_VER, '2.4', '>='))
		{
			$this->EE->load->model('file_upload_preferences_model');
			return $this->EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id);
		}

		if (version_compare(APP_VER, '2.1.5', '>='))
		{
			$this->EE->load->model('file_upload_preferences_model');
			$result = $this->EE->file_upload_preferences_model->get_upload_preferences($group_id, $id);
		}
		else
		{
			$this->EE->load->model('tools_model');
			$result = $this->EE->tools_model->get_upload_preferences($group_id, $id);
		}

		// If an $id was passed, just return that directory's preferences
		if ( ! empty($id))
		{
			return $result->row_array();
		}

		// Use upload destination ID as key for row for easy traversing
		$return_array = array();
		foreach ($result->result_array() as $row)
		{
			$return_array[$row['id']] = $row;
		}

		return $return_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$this->_prep_settings($data);

		if (version_compare(APP_VER, '2.2', '>='))
		{
			$directory_options['all'] = lang('all');

			$filedirs = $this->_get_upload_preferences(1);

			foreach ($filedirs as $filedir)
			{
				$directory_options[$filedir['id']] = $filedir['name'];
			}

			$r[] = array(
				lang('allowed_dirs_file'),
				form_dropdown('directory', $directory_options, $data['directory'])
			);
		}

		$content_type_options = array('all' => lang('all'), 'image' => lang('type_image'));

		$r[] = array(
			str_replace(' ', '&nbsp;', lang('field_content_file')),
			form_dropdown('content_type', $content_type_options, $data['content_type'])
		);

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Data exists?
	 */
	private function _data_exists($data)
	{
		return (isset($data['filename']) && $data['filename']);
	}

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		$this->_prep_settings($this->settings);

		if (! isset($this->cache['displayed']))
		{
			// include matrix_text.js
			$theme_url = $this->EE->session->cache['matrix']['theme_url'];
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'scripts/matrix_file.js"></script>');

			$this->EE->lang->loadfile('matrix');

			$this->cache['displayed'] = TRUE;
		}

		$r['class'] = 'matrix-file';

		// -------------------------------------------
		//  Get the upload directories
		// -------------------------------------------

		$upload_dirs = array();

		$upload_prefs = $this->_get_upload_preferences($this->EE->session->userdata('group_id'));

		foreach ($upload_prefs as $row)
		{
			$upload_dirs[$row['id']] = $row['name'];
		}

		// -------------------------------------------
		//  Existing file?
		// -------------------------------------------

		if ($this->_data_exists($data))
		{
			if (is_array($data))
			{
				$filedir = $data['filedir'];
				$filename = $data['filename'];
			}
			else if (preg_match('/^{filedir_([0-9]+)}(.*)/', $data, $matches))
			{
				$filedir  = $matches[1];
				$filename = $matches[2];
			}
		}

		if (isset($filedir))
		{
			if (version_compare(APP_VER, '2.1.5', '>='))
			{
				$this->EE->load->library('filemanager');
				$thumb_info = $this->EE->filemanager->get_thumb($filename, $filedir);
				$thumb_url = $thumb_info['thumb'];

				if (! isset($thumb_info['thumb_path']))
				{
					$filedir_info = $this->_get_upload_preferences(1, $filedir);
					$thumb_info['thumb_path'] = $filedir_info['server_path'].'_thumb/'.$filename;
				}

				$thumb_size = file_exists($thumb_info['thumb_path']) ? getimagesize($thumb_info['thumb_path']) : array(64, 64);
			}
			else
			{
				$filedir_info = $this->_get_upload_preferences(1, $filedir);
				$thumb_filename = $filedir_info['server_path'].'_thumbs/thumb_'.$filename;

				if (file_exists($thumb_filename))
				{
					$thumb_url = $filedir_info['url'].'_thumbs/thumb_'.$filename;
					$thumb_size = getimagesize($thumb_filename);
				}
				else
				{
					$thumb_url = PATH_CP_GBL_IMG.'default.png';
					$thumb_size = array(64, 64);
				}
			}

			$r['data'] = '<div class="matrix-thumb" style="width: '.$thumb_size[0].'px;">'
			           .   '<a title="'.lang('remove_file').'"></a>'
			           .   '<img src="'.$thumb_url.'" width="'.$thumb_size[0].'" height="'.$thumb_size[1].'" />'
			           . '</div>'
			           . '<div class="matrix-filename">'.$filename.'</div>';

			$add_style = ' style="display: none;"';
		}
		else
		{
			$filedir = '';
			$filename = '';
			$r['data'] = '';
			$add_style = '';
		}

		$add_line = ($this->settings['content_type'] != 'image') ? 'add_file' : 'add_image';

		$r['data'] .= '<input type="hidden" name="'.$this->cell_name.'[filedir]"  value="'.$filedir .'" class="filedir" />'
		            . '<input type="hidden" name="'.$this->cell_name.'[filename]" value="'.$filename.'" class="filename" />'
		            . '<a class="matrix-btn matrix-add"'.$add_style.'>'.$this->EE->lang->line($add_line).'</a>';

		// pass along the EE version in the settings
		$r['settings']['ee22plus'] = version_compare(APP_VER, '2.2', '>=');

		if (APP_VER == '2.1.5')
		{
			$this->EE->cp->add_js_script(array(
					'plugin' => array('tmpl')
				)
			);
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Cell
	 */
	function validate_cell($data)
	{
		// is this a required column?
		if ($this->settings['col_required'] == 'y' && ! $this->_data_exists($data))
		{
			return lang('col_required');
		}

		return TRUE;
	}

	/**
	 * Save Cell
	 */
	function save_cell($data)
	{
		if ($this->_data_exists($data))
		{
			return '{filedir_'.$data['filedir'].'}'.$data['filename'];
		}

		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if (! $data) return '';

		// -------------------------------------------
		//  Get the file info
		//   - Down the road Matrix should support a
		//     pre_process_cell() method that this could go in
		// -------------------------------------------

		$file_info['path'] = '';

		if (preg_match('/^{filedir_(\d+)}(.*)$/', $data, $matches))
		{
			$file_paths = $this->EE->functions->fetch_file_paths();

			if (isset($file_paths[$matches[1]]))
			{
				$file_info['path'] = $file_paths[$matches[1]];

				// while we're here, get the filesize?
				if ($tagdata && strpos($tagdata, 'filesize') !== FALSE)
				{
					$file_info['filesize'] = $this->_get_filesize($matches[1], $matches[2], array('format' => 'no'));
				}
			}

			$data = $matches[2];
		}

		$file_info['extension'] = $this->replace_extension($data);
		$file_info['filename'] = basename($data, '.'.$file_info['extension']);

		// -------------------------------------------
		//  Tagdata
		// -------------------------------------------

		if ($tagdata)
		{
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $file_info);
			$tagdata = $this->EE->functions->var_swap($tagdata, $file_info);

			return $tagdata;
		}

		$full_url = $file_info['path'].$data;

		if (isset($params['wrap']))
		{
			if ($params['wrap'] == 'link')
			{
				return '<a href="'.$full_url.'">'.$file_info['filename'].'</a>';
			}

			if ($params['wrap'] == 'image')
			{
				return '<img src="'.$full_url.'" alt="'.$file_info['filename'].'" />';
			}
		}

		return $full_url;
	}

	/**
	 * Replace File Name
	 */
	function replace_filename($data, $params = array())
	{
		if (preg_match('/^{filedir_(\d+)}(.*)$/', $data, $matches))
		{
			$data = $matches[2];
		}

		$extension = $this->replace_extension($data);
		$filename = basename($data, '.'.$extension);

		return $filename;
	}

	/**
	 * Replace Extension
	 */
	function replace_extension($data)
	{
		return substr(strrchr($data, '.'), 1);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Filesize
	 */
	private function _get_filesize($upload_dir, $filename, $params)
	{
		$this->EE->db->select('server_path');
		$query = $this->EE->db->get_where('upload_prefs', array('id' => $upload_dir));

		if ($query->num_rows())
		{
			$full_path = $query->row('server_path') . $filename;

			if (file_exists($full_path))
			{
				// get the filesize in bytes
				$filesize = filesize($full_path);

				// unit conversions
				if (isset($params['unit']))
				{
					switch (strtolower($params['unit']))
					{
						case 'kb': $filesize /= 1024; break;
						case 'mb': $filesize /= 1048576; break;
						case 'gb': $filesize /= 1073741824; break;
					}
				}

				// commas
				if (! isset($params['format']) || $params['format'] == 'yes')
				{
					$decimals = isset($params['decimals']) ? $params['decimals'] : 0;
					$dec_point = isset($params['dec_point']) ? $params['dec_point'] : '.';
					$thousands_sep = isset($params['thousands_sep']) ? $params['thousands_sep'] : ',';

					$filesize = number_format($filesize, $decimals, $dec_point, $thousands_sep);
				}

				return $filesize;
			}
		}

		return '';
	}

	/**
	 * Replace File Size
	 */
	function replace_filesize($data, $params = array())
	{
		if (preg_match('/^{filedir_(\d+)}(.*)$/', $data, $matches))
		{
			return $this->_get_filesize($matches[1], $matches[2], $params);
		}

		return '';
	}
}

<?php

if ( ! defined('EXT')) exit('Invalid file request');

/**
 * Img Saver Class for FieldFrame
 *
 * @package   MS Img Saver
 * @author    MeanStudios <http://meanstudios.com>
 * @copyright Copyright (c) 2009 MeanStudios
 * @license   http://creativecommons.org/licenses/by-sa/3.0/ Attribution-Share Alike 3.0 Unported
 */

class Ff_ms_img_saver extends Fieldframe_Fieldtype {

	/**
	 * Register FieldType Information
	 * @var array
	 */
    var $info = array(
        'name' 		=> 'MS Img Saver',
        'version' 	=> '0.9.0',
        'desc' 		=> 'Provides an image upload field',
        'docs_url' 	=> 'http://github.com/MeanStudios/ms.ff.img_saver.ee_addon/tree/master'
    );

	/**
	 * Register FieldType Requirements
	 * @var array
	 */
    var $requires = array(
        'ff'        => '0.9.6',
        'cp_jquery' => '1.1'
    );

	/**
	 * Register FieldType Hooks
	 * @var array
	 */
	 var $hooks = array(
        'control_panel_home_page',
		'submit_new_entry_absolute_end',
		'delete_entries_loop'
    );

	/**
	 * Default Field Settings
	 * @var array
	 */
	var $default_field_settings = array(
		'img_width' 	=> '200',
		'img_height' 	=> '200',
		'upload_id' 	=> '1'
	);

	/**
	 * Default Cell Settings
	 * @var array
	 */
	var $default_cell_settings = array(
		'img_width' 	=> '200',
		'img_height' 	=> '200',
		'upload_id' 	=> '1'
	);

	var $xid = '';
	var	$field_names = array();
	var	$field_array = array();
	var	$cell_names = array();
	var	$cell_array = array();

	/**
	 * Display Field Settings
	 *
	 * @param  array  $field_settings  The field's settings
	 * @return array  Settings HTML (cell1, cell2, rows)
	 */
	function display_field_settings($field_settings)
	{
		global $DSP, $LANG;

		$query = $this->_get_upload_prefs();

		$width = $field_settings['img_width'];
		$height = $field_settings['img_height'];
		$upload_id = $field_settings['upload_id'];

		$cell2 = $DSP->qdiv('defaultBold', $LANG->line('img_saver_upload_label'))
		       . $DSP->input_select_header('upload_id');
		foreach ($query->result as $row)
		{
			if ($upload_id == $row['id'])
			{
        		$cell2 .= $DSP->input_select_option($row['id'], $row['name'], 'y');
			} else {
				$cell2 .= $DSP->input_select_option($row['id'], $row['name']);
			}
		}
		$cell2 .= $DSP->input_select_footer()
		        . $DSP->qdiv('default', $LANG->line('img_saver_more_uploads'))
				. BR
                . $DSP->qdiv('defaultBold', $LANG->line('img_saver_width_label'))
		        . $DSP->input_text('img_width', $width, 4, '4', 'input', '50px')
			    . $DSP->qdiv('default', $LANG->line('img_saver_proportions'))
				. BR
			    . $DSP->qdiv('defaultBold', $LANG->line('img_saver_height_label'))
		        . $DSP->input_text('img_height', $height, 4, '4', 'input', '50px')
			    . $DSP->qdiv('default', $LANG->line('img_saver_proportions'));

		return array('cell2' => $cell2);
	}

	/**
	 * Display Field
	 *
	 * @param  string  $field_name      The field's name
	 * @param  mixed   $field_data      The field's current value
	 * @param  array   $field_settings  The field's settings
	 * @return string  The field's HTML
	 */
    function display_field($field_name, $field_data, $field_settings)
    {
        global $DSP, $PREFS, $SESS, $IN;

		//Insert JS
		$this->include_js('js/livequery.js');
		$this->include_js('js/jquery.form.js');

		//Grab field_id
		if (($IN->GBL('C', 'GET' ) == 'edit') || ($IN->GBL('C', 'GET' ) == 'publish')) $f_ids = $this->_get_ids($field_name);

		//Grab Settings
		$upload_id = $field_settings['upload_id'];
		$upload_prefs = $this->_get_upload_prefs($upload_id);
		$cp_url = $PREFS->ini('cp_url').'?S='.$SESS->userdata['session_id'];
		$img_url = $upload_prefs['url'];
		if ($this->xid == '') $this->xid = $this->_get_xid();

		//Grab Filename
		$explode = explode("/", $field_data);
		$file_name = $explode[count($explode)-1];
		$img_url = str_replace($file_name, "", $field_data);


ob_start();
?>
$(document).ready(function(){
	$("input[name='submit']").attr('name', 'susbmit');
	$('.img_upload').livequery('change', function(){
		el = $(this);
		target = el.next().next();
		$('form').ajaxSubmit({
			url: '<?= $cp_url?>&ms_img_saver=upload',
			target: target,
			type: 'post',
			iframe: true,
			data: {
				field_name : el.attr('name'),
				XID : '<?= $this->xid ?>'
			},
			success: function(){
				el.hide();
				el.next().show();

			}
		});
		return false;
	});
	$('.img_delete').livequery('click', function(){
		el = $(this);
		target = el.next();
		$('form').ajaxSubmit({
			url: '<?= $cp_url?>&ms_img_saver=delete',
			type: 'post',
			iframe: true,
			data: {
				field_name : el.prev().attr('name'),
				XID : '<?= $this->xid ?>'
			},
			success: function(response){
				el.hide();
				el.prev().show();
				target.html(response);
			}
		});
		return false;
	});
});
<?php
$js = ob_get_contents();
ob_end_clean();

		$this->insert_js($js);

		if (!$field_data)
		{
			$r = '<input name="'.$field_name.'" type="file" class="img_upload" /><div class="img_delete" style="display: none;"><img src="' . FT_URL . 'ff_ms_img_saver/imgs/file_delete.png" />&nbsp;&nbsp;Delete Image</div>';
			$r .= NL . '<div>';
			$r .= (!isset($f_ids['field_type_key'])) ?  $DSP->input_hidden($field_name, $field_data) : $DSP->input_hidden($field_name.'[url]', $field_data);
			$r .= ((!isset($f_ids['field_type_key'])) && (($IN->GBL('C', 'GET' ) == 'edit') || ($IN->GBL('C', 'GET' ) == 'publish'))) ?  $DSP->input_hidden('file_name', $file_name) : $DSP->input_hidden($field_name.'[file_name]', $file_name);
		} else {
			$r = '<input style="display: none;" name="'.$field_name.'" type="file" class="img_upload" /><div class="img_delete"><img src="' . FT_URL . 'ff_ms_img_saver/imgs/file_delete.png" />&nbsp;&nbsp;Delete Image</div>';
			$r .= NL . '<div><p><img src="'.$img_url.'thumb_'.$file_name.'" /></p>';
			$r .= (!isset($f_ids['field_type_key'])) ?  $DSP->input_hidden($field_name, $field_data) : $DSP->input_hidden($field_name.'[url]', $field_data);
			$r .= (!isset($f_ids['field_type_key'])) ?  $DSP->input_hidden('file_name', $file_name) : $DSP->input_hidden($field_name.'[file_name]', $file_name);
		}
		$r .= "</div>";

		$r .= ((!isset($f_ids['field_type_key'])) && (($IN->GBL('C', 'GET' ) == 'edit') || ($IN->GBL('C', 'GET' ) == 'publish'))) ? $DSP->input_hidden('delete[]', $file_name) : $DSP->input_hidden($field_name.'[delete]', $file_name);

        return $r;
    }

	/**
	 * Display Cell Settings
	 *
	 * @param  array  $cell_settings  The cell's settings
	 * @return string  Settings HTML
	 */
	function display_cell_settings($cell_settings)
	{
		global $DSP, $LANG;

		$width = $cell_settings['img_width'];
		$height = $cell_settings['img_height'];
		$upload_id = $cell_settings['upload_id'];

		$query = $this->_get_upload_prefs();

		$r = $DSP->qdiv('defaultBold', $LANG->line('img_saver_upload_label'))
		   . $DSP->input_select_header('upload_id');

		foreach ($query->result as $row)
		{
			if ($upload_id == $row['id'])
			{
        		$r .= $DSP->input_select_option($row['id'], $row['name'], 'y');
			} else {
				$r .= $DSP->input_select_option($row['id'], $row['name']);
			}
		}
		$r .= $DSP->input_select_footer()
		   . $DSP->qdiv('default', $LANG->line('img_saver_more_uploads'))
           . BR
           . $DSP->qdiv('defaultBold', $LANG->line('img_saver_width_label'))
		   . $DSP->input_text('img_width', $width, 4, '4', 'input', '50px')
           . $DSP->qdiv('default', $LANG->line('img_saver_proportions'))
           . BR
           . $DSP->qdiv('defaultBold', $LANG->line('img_saver_height_label'))
           . $DSP->input_text('img_height', $height, 4, '4', 'input', '50px')
           . $DSP->qdiv('default', $LANG->line('img_saver_proportions'));

		return $r;
	}

	/**
	 * Display Cell
	 *
	 * @param  string  $cell_name      The cell's name
	 * @param  mixed   $cell_data      The cell's current value
	 * @param  array   $cell_settings  The cell's settings
	 * @return string  The cell's HTML
	 */
	function display_cell($cell_name, $cell_data, $cell_settings)
	{
		return $this->display_field($cell_name, $cell_data, $cell_settings);
	}

	/**
	 * Save Field
	 *
	 * @param  mixed  $field_data      The field's current value
	 * @param  array  $field_settings  The field's settings
	 * @return array  Modified $field_data
	 */
	function save_field($field_data, $field_settings)
	{
		global $FF;

		$this->field_names[] = $FF->field_name;
		$this->field_array[$FF->field_name]['field_data'] = $field_data;
		$this->field_array[$FF->field_name]['field_settings'] = $field_settings;

		return $field_data;
	}

	function save_cell($cell_data, $cell_settings)
	{
		global $FF, $FFM;

		$row_count = $FFM->row_count;
		$col_id = $FFM->col_id;

		$cell_array = array(
			'col_id' => $col_id,
			'row_count' => $row_count,
			'cell_data' => $cell_data['url'],
			'file_name' => $cell_data['file_name'],
			'file_delete' => $cell_data['delete'],
			'cell_settings' => $cell_settings
		);

		$this->cell_names[] = $FF->field_name;
		$this->cell_array[] = $cell_array;

		return $cell_data['url'];
	}

	/**
	 * Hooks
	 */

	/**
	 * Delete Entries Loop
	 *
	 * Add additional processing for entry deletion in loop.
	 *
	 * @param string $val = Entry ID for entry being deleted during this loop
	 * @param string $weblog_id = Weblog ID for entry being deleted
	 *
	 * @see    http://expressionengine.com/developers/extension_hooks/delete_entries_loop/
	 */
	function delete_entries_loop($val, $weblog_id)
	{
		//Lets delete the Field Data
		foreach ($this->_get_field_settings() as $field => $row)
		{
			$upload_prefs = $this->_get_upload_prefs($row['upload_id']);
			$dir = $upload_prefs['server_path'].$val.'/';
			$this->_delete_all($dir);
		}

		//Lets delete the Cell Data
		foreach ($this->_get_cell_settings() as $field => $row)
		{
			$count = 1;
			foreach ($row as $key => $value)
			{
				if ($value[$count]['type'] == 'ff_ms_img_saver')
				{
					$upload_prefs = $this->_get_upload_prefs($value[$count]['settings']['upload_id']);
					$dir = $upload_prefs['server_path'].$val.'/';
					$this->_delete_all($dir);
				}
				$count++;
			}
		}
	}

	/**
	 * Submit New Entry Absolute End
	 *
	 * Absolute end of all submission stuff for new entry including trackback/ping errors and right before the redirect.
	 *
	 * @param string $entry_id - Entry's ID
	 * @param array $data - Array of data about entry (title, url_title)
	 *
	 * @see    http://expressionengine.com/developers/extension_hooks/submit_new_entry_absolute_end/
	 */
	function submit_new_entry_absolute_end($entry_id, $data)
	{
		global $DB, $REGX;

		$field_count = 0;


		// Lets loop through the Field Data
		foreach ($this->field_array as $field => $row)
		{
			$upload_prefs = $this->_get_upload_prefs($row['field_settings']['upload_id']);

			$save_path = $upload_prefs['server_path'];
			$save_url = $upload_prefs['url'];

			$explode = explode("/", $row['field_data']);
			$file_name = $explode[count($explode)-1];

			if (@is_dir($save_path.'/'.$entry_id.'/') === FALSE) @mkdir($save_path.'/'.$entry_id.'/', 0777);

			@rename($save_path.'tmp/'.$file_name, $save_path.$entry_id.'/'.$file_name);
			@rename($save_path.'tmp/thumb_'.$file_name, $save_path.$entry_id.'/thumb_'.$file_name);

			$delete = $_POST['delete'];

			if ($delete[$field_count] != $file_name)
			{
				@unlink($save_path.$entry_id.'/'.$delete[$field_count]);
				@unlink($save_path.$entry_id.'/thumb_'.$delete[$field_count]);
			}

			if ($row['field_data'])
			{
				$sql = 'UPDATE exp_weblog_data
						SET '.$this->field_names[$field_count].' = "'.$save_url.$entry_id.'/'.$file_name.'"
						WHERE entry_id = '.$entry_id;

				$DB->query($sql);
			}
			$field_count++;
		}

		// Lets loop through the Cell Data

		$cell_count = 0;

		foreach ($this->cell_array as $key => $row)
		{
			$upload_prefs = $this->_get_upload_prefs($row['cell_settings']['upload_id']);

			$save_path = $upload_prefs['server_path'];
			$save_url = $upload_prefs['url'];

			$file_name = $row['file_name'];

			if (@is_dir($save_path.'/'.$entry_id.'/') === FALSE) @mkdir($save_path.'/'.$entry_id.'/', 0777);

			@rename($save_path.'tmp/'.$file_name, $save_path.$entry_id.'/'.$file_name);
			@rename($save_path.'tmp/thumb_'.$file_name, $save_path.$entry_id.'/thumb_'.$file_name);

			if (($row['file_delete'] != '') && ($row['file_delete'] != $file_name))
			{
				@unlink($save_path.$entry_id.'/'.$row['file_delete']);
				@unlink($save_path.$entry_id.'/thumb_'.$row['file_delete']);
			}

			if ($row['cell_data'])
			{
				$sql = "SELECT {$this->cell_names[$cell_count]} FROM exp_weblog_data WHERE entry_id = {$entry_id}";
				$query = $DB->query($sql);
				$update = $REGX->array_stripslashes(unserialize($query->row[$this->cell_names[$cell_count]]));

				$update[$row['row_count']][$row['col_id']] = $save_url.$entry_id.'/'.$file_name;
				$update = addslashes(serialize($update));

				$sql = 'UPDATE exp_weblog_data
						SET '.$this->cell_names[$cell_count].' = "'.$update.'"
						WHERE entry_id = '.$entry_id;

				$DB->query($sql);
			}

			$cell_count++;
		}
	}

	/**
	 * Control Panel Home
	 *
	 * Allows complete rewrite of CP Home Page
	 *
	 * @see    http://expressionengine.com/developers/extension_hooks/control_panel_home_page/
	 */
    function control_panel_home_page()
	{
		global $IN;

    	switch(strtolower($IN->GBL( 'ms_img_saver', 'GET' )))
    	{
    		case "upload" :
    			return $this->_ajax_upload();

    		case "delete" :
    			return $this->_ajax_delete();
    	}
    	return;
	}

	/**
	 * Supporting Functions
	 */

	/**
	 * AJAX Upload
	 *
	 * @access private
	 * @returns string The response for uploading image.
	 */
    function _ajax_upload()
    {
		global $FNS, $DSP, $DB, $REGX, $FF;

		$field_name = $_POST['field_name'];
		$f_ids = $this->_get_ids($field_name);

		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_id = {$f_ids['field_id']}";
		$query = $DB->query($sql);
		$field_settings = $REGX->array_stripslashes(unserialize($query->row['ff_settings']));
		if (isset($field_settings['cols']))
		{
			$upload_prefs = $this->_get_upload_prefs($field_settings['cols'][$f_ids['field_type_key']]['settings']['upload_id']);
			$width = $field_settings['cols'][$f_ids['field_type_key']]['settings']['img_width'];
			$height = $field_settings['cols'][$f_ids['field_type_key']]['settings']['img_height'];
		} else {
			$upload_prefs = $this->_get_upload_prefs($field_settings['upload_id']);
			$width = $field_settings['img_width'];
			$height = $field_settings['img_height'];
		}

        $tmp_upload_dir = $upload_prefs['server_path'] . "tmp/";
		$tmp_upload_url = $upload_prefs['url'] . "tmp/";

		$filename = (!isset($f_ids['field_type_key'])) ? strtolower($FNS->filename_security(str_replace(' ', '_', $_FILES[$field_name]['name']))) : strtolower($FNS->filename_security(str_replace(' ', '_', $_FILES['field_id_'.$f_ids['field_id']]['name'][$f_ids['field_row_key']][$f_ids['field_type_key']])));

        $img_file = $tmp_upload_dir . basename($filename);
		$img_url = $tmp_upload_url . basename($filename);

		$thumb_file = $tmp_upload_dir . basename('thumb_' . $filename);
		$thumb_url =  $tmp_upload_url . basename('thumb_' . $filename);

		if (@is_dir($tmp_upload_dir) === FALSE)
		{
			@mkdir($tmp_upload_dir, 0777);
		}
		if (!isset($f_ids['field_row_key']))
		{
			if (@move_uploaded_file($_FILES['field_id_'.$f_ids['field_id']]['tmp_name'], $img_file))
			{
				$this->_resize($img_file, $width, $height, true, false, $img_file);
				$this->_resize($img_file, 80, 80, false, true, $thumb_file);

				$r = "<p><img src=\"{$thumb_url}\" /></p>";
				$r .= NL . $DSP->input_hidden($field_name, $img_url);
				$r .= NL . $DSP->input_hidden('file_name', $filename);

				echo $r;
				exit();
			} else {
				echo "error";
				exit();
			}
		} else {
			if (@move_uploaded_file($_FILES['field_id_'.$f_ids['field_id']]['tmp_name'][$f_ids['field_row_key']][$f_ids['field_type_key']], $img_file))
			{
				$this->_resize($img_file, $width, $height, true, false, $img_file);
				$this->_resize($img_file, 80, 80, false, true, $thumb_file);

				$r = "<p><img src=\"{$thumb_url}\" /></p>";
				$r .= NL . $DSP->input_hidden($field_name.'[url]', $img_url);
				$r .= NL . $DSP->input_hidden($field_name.'[file_name]', $filename);

				echo $r;
				exit();
			} else {
				echo "error";
				exit();
			}
		}
    }

	/**
	 * AJAX Delete
	 *
	 * @access private
	 */
	function _ajax_delete()
    {
		global $FNS, $DSP, $DB, $REGX;

		$field_name = $_POST['field_name'];
		$f_ids = $this->_get_ids($field_name);

		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_id = {$f_ids['field_id']}";
		$query = $DB->query($sql);
		$field_settings = $REGX->array_stripslashes(unserialize($query->row['ff_settings']));
		if (isset($field_settings['cols']))
		{
			$upload_prefs = $this->_get_upload_prefs($field_settings['cols'][$f_ids['field_type_key']]['settings']['upload_id']);
		} else {
			$upload_prefs = $this->_get_upload_prefs($field_settings['upload_id']);
		}

        $tmp_upload_dir = $upload_prefs['server_path'] . "tmp/";


		$file_name = (!isset($f_ids['field_type_key'])) ? $_POST['field_id_'.$f_ids['field_id']]['file_name'] : $_POST['field_id_'.$f_ids['field_id']][$f_ids['field_row_key']][$f_ids['field_type_key']]['file_name'];

		@unlink($tmp_upload_dir . $file_name);
		@unlink($tmp_upload_dir . 'thumb_' . $file_name);

		$r = (!isset($f_ids['field_row_key'])) ? NL . $DSP->input_hidden($field_name, '') : $DSP->input_hidden($field_name.'[url]', '');
		$r .= (!isset($f_ids['field_type_key'])) ?  $DSP->input_hidden('file_name', '') : $DSP->input_hidden($field_name.'[file_name]', '');

		echo $r;
		exit();
    }

	/**
	 * Delete All
	 *
	 * @access private
	 */
	function _delete_all($dirname)
	{
		if (is_file($dirname) || is_link($dirname))
	    {
	        return @unlink($dirname);
	    }
		if (!is_dir($dirname))
		{
			return false;
		}

		$dir = dir($dirname);
		while (false !== $entry = $dir->read())
		{
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}

			// Recurse
			$this->_delete_all($dirname . $entry);
		}

		// Clean up
		$dir->close();
		rmdir($dirname);
	}
	/**
	 * Get MS Img Saver FieldType Field Settings
	 *
	 * @access private
	 * @returns array The saved settings for all MS Img Saver FieldTypes.
	 */
	function _get_field_settings()
	{
		global $DB, $REGX;

		$field_settings = array();

		$sql = "SELECT fieldtype_id FROM exp_ff_fieldtypes WHERE class = 'ff_ms_img_saver'";
		$results = $DB->query($sql);

		$field_id = "ftype_id_".$results->row['fieldtype_id'];

		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_type = '{$field_id}'";
		$query = $DB->query($sql);

		foreach($query->result as $row)
		{
			$field_settings[] = $REGX->array_stripslashes(unserialize($row['ff_settings']));
		}
		return $field_settings;
	}

	/**
	 * Get MS Img Saver FieldType Cell Settings
	 *
	 * @access private
	 * @returns array The saved settings for all MS Img Saver FieldTypes.
	 */
	function _get_cell_settings()
	{
		global $DB, $REGX;

		$cell_settings = array();

		$sql = "SELECT fieldtype_id FROM exp_ff_fieldtypes WHERE class = 'ff_matrix'";
		$results = $DB->query($sql);

		$field_id = "ftype_id_".$results->row['fieldtype_id'];

		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_type = '{$field_id}'";
		$query = $DB->query($sql);

		foreach($query->result as $row)
		{
			$cell_settings[] = $REGX->array_stripslashes(unserialize($row['ff_settings']));
		}
		return $cell_settings;
	}

	/**
	 * Get Upload Prefs
	 *
	 * @access private
	 * @returns array The upload prefs for EE.
	 */
	function _get_upload_prefs($id='')
	{
		global $DB, $PREFS;

		if (!$id)
		{
			$site_id = $PREFS->ini('site_id');
			$sql = "SELECT id, name FROM exp_upload_prefs WHERE site_id = {$site_id}";
			return $DB->query($sql);
		} else {
			$site_id = $PREFS->ini('site_id');
			$sql = "SELECT id, name, server_path, url FROM exp_upload_prefs WHERE id = {$id}";
			$query = $DB->query($sql);
			return $query->row;
		}
	}

	/**
	 * Get XID
	 *
	 * @access private
	 * @returns string The XID needed to use AjaxSubmit
	 */
	function _get_xid()
	{
		global $DB, $FNS, $LOC, $IN;

		$xid = $FNS->random('encrypt');
		$arr = array(
			'date'			=> $LOC->now,
			'ip_address'	=> $IN->IP,
			'hash'			=> $xid
		);

		$DB->query($DB->insert_string('exp_security_hashes', $arr));

		return $xid;
	}

	/**
	 * Parse Field ID's
	 *
	 * @access private
	 * @returns array Parsed field_id.
	 */
	function _get_ids($field_name)
	{
		if (preg_match('/^(field_id_)([0-9]+)\[([0-9]+)\]\[([0-9]+)\]/', $field_name, $matches))
		{
			$id_array = array(
				'field_id' => $matches[2],
				'field_row_key' => $matches[3],
				'field_type_key' => $matches[4]
			);
            return $id_array;
		} else {
			preg_match('/^(field_id_)([0-9]+)/', $field_name, $matches);

			$id_array = array(
				'field_id' => $matches[2]
			);
            return $id_array;
		}
    }

	// Resizing and Croping Function
	//  Image Resizer
 	//  by: David Rencher
 	//  http://www.lumis.com/ drencher[at]gmail[dot]com

	function _resize($file, $width = 0, $height = 0, $proportional = false, $crop = false, $output, $quality = 75)
	{
		if ( $height <= 0 && $width <= 0 ) {
            return false;
        }

		$info = getimagesize($file);
		$image = '';
		$mime = $info['mime'];

		$final_width = 0;
        $final_height = 0;
        list($width_old, $height_old) = $info;

		if($width_old < $width) $width = $width_old;
		if($height_old < $height) $height = $height_old;

        if ($proportional)
		{
            if ($width == 0) $factor = $height/$height_old;
            elseif ($height == 0) $factor = $width/$width_old;
            else $factor = min ( $width / $width_old, $height / $height_old);

			$final_width = round ($width_old * $factor);
			$final_height = round ($height_old * $factor);

        }else {
			$final_width = ( $width <= 0 ) ? $width_old : $width;
			$final_height = ( $height <= 0 ) ? $height_old : $height;
        }

		if ($crop) {


			$int_width = 0;
			$int_height = 0;

			$adjusted_height = $final_height;
			$adjusted_width = $final_width;

			$wm = $width_old/$width;
			$hm = $height_old/$height;
			$h_height = $height/2;
			$w_height = $width/2;

			$ratio = $width/$height;
			$old_img_ratio = $width_old/$height_old;

			if ($old_img_ratio > $ratio)
			{
				$adjusted_width = $width_old / $hm;
				$half_width = $adjusted_width / 2;
				$int_width = $half_width - $w_height;
			}
			else if($old_img_ratio <= $ratio)
			{
				$adjusted_height = $height_old / $wm;
				$half_height = $adjusted_height / 2;
				$int_height = $half_height - $h_height;
			}
		}

		@ini_set("memory_limit","12M");
		@ini_set("memory_limit","16M");
		@ini_set("memory_limit","32M");
		@ini_set("memory_limit","64M");

		switch (
			$info[2] ) {
				case IMAGETYPE_GIF:
					$image = imagecreatefromgif($file);
				break;
				case IMAGETYPE_JPEG:
					$image = imagecreatefromjpeg($file);
				break;
				case IMAGETYPE_PNG:
					$image = imagecreatefrompng($file);
				break;
				default:
					return false;
		}

		$image_resized = imagecreatetruecolor( $final_width, $final_height );

		if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
			$trnprt_indx = imagecolortransparent($image);

			// If we have a specific transparent color
			if ($trnprt_indx >= 0) {

				// Get the original image's transparent color's RGB values
				$trnprt_color    = imagecolorsforindex($image, $trnprt_indx);

				// Allocate the same color in the new image resource
				$trnprt_indx    = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);

				// Completely fill the background of the new image with allocated color.
				imagefill($image_resized, 0, 0, $trnprt_indx);

				// Set the background color for new image to transparent
				imagecolortransparent($image_resized, $trnprt_indx);


			}
			// Always make a transparent background color for PNGs that don't have one allocated already
			elseif ($info[2] == IMAGETYPE_PNG) {

				// Turn off transparency blending (temporarily)
				imagealphablending($image_resized, false);

				// Create a new transparent color for image
				$color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);

				// Completely fill the background of the new image with allocated color.
				imagefill($image_resized, 0, 0, $color);

				// Restore transparency blending
				imagesavealpha($image_resized, true);
			}
		}



		if ($crop)
		{
			imagecopyresampled($image_resized, $image, -$int_width, -$int_height, 0, 0, $adjusted_width, $adjusted_height, $width_old, $height_old);
		}else{
			imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
		}

		switch ($info[2] ) {
			case IMAGETYPE_GIF:
				imagegif($image_resized, $output);
			break;
			case IMAGETYPE_JPEG:
				imagejpeg($image_resized, $output, $quality);

			break;
			case IMAGETYPE_PNG:
				imagepng($image_resized, $output);
			break;
			default:
				return false;
		}

		$out_sized = array (
			'img_width'	    =>	$final_width,
			'img_height'	=>  $final_height
		);

		if($mime == "image/jpeg")
		{
			$this->UnsharpMask($output, '', 0.5, 3);
		}

	   return $out_sized;
    }

	function UnsharpMask($src, $amount, $radius, $threshold)
	{

		////////////////////////////////////////////////////////////////////////////////////////////////
		////
		////                  Unsharp Mask for PHP - version 2.1.1
		////
		////    Unsharp mask algorithm by Torstein Hønsi 2003-07.
		////             thoensi_at_netcom_dot_no.
		////               Please leave this notice.
		////
		///////////////////////////////////////////////////////////////////////////////////////////////

		$img = @imagecreatefromjpeg($src);

		if(!$img){
		 return '';
		}

		// Attempt to calibrate the parameters to Photoshop:
		if ($amount > 500)    $amount = 500;
		$amount = $amount * 0.016;
		if ($radius > 50)    $radius = 50;
		$radius = $radius * 2;
		if ($threshold > 255)    $threshold = 255;

		$radius = abs(round($radius));     // Only integers make sense.
		if ($radius == 0)
		{
			return $img;
			imagedestroy($img);
			break;
		}
		//echo imagesx($img);
		$w = imagesx($img);
		$h = imagesy($img);
		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur = imagecreatetruecolor($w, $h);
		imageantialias ($img, true);


		// Gaussian blur matrix:
		//
		//    1    2    1
		//    2    4    2
		//    1    2    1
		//
		//////////////////////////////////////////////////


		if (function_exists('imageconvolution')) { // PHP >= 5.1

				$matrix = array(
				array( 1, 2, 1 ),
				array( 2, 4, 2 ),
				array( 1, 2, 1 )
			);
			imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h);
			imageconvolution($imgBlur, $matrix, 16, 0);
		}
		else {

		// Move copies of the image around one pixel at the time and merge them with weight
		// according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++)    {
				imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
				imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
				imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
				imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
			}
		}

		if($threshold>0){
			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for ($x = 0; $x < $w-1; $x++)    { // each row
				for ($y = 0; $y < $h; $y++)    { // each pixel


					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold)
						? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
						: $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold)
						? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
						: $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold)
						? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
						: $bOrig;



					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
							$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
							ImageSetPixel($img, $x, $y, $pixCol);
						}
				}
			}
		}
		else{
			for ($x = 0; $x < $w; $x++)    { // each row
				for ($y = 0; $y < $h; $y++)    { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
						if($rNew>255){$rNew=255;}
						elseif($rNew<0){$rNew=0;}
					$gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
						if($gNew>255){$gNew=255;}
						elseif($gNew<0){$gNew=0;}
					$bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
						if($bNew>255){$bNew=255;}

						elseif($bNew<0){$bNew=0;}
					$rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
						ImageSetPixel($img, $x, $y, $rgbNew);
				}
			}
		}

		imagedestroy($imgCanvas);
		imagedestroy($imgBlur);

		imagejpeg($img, $src, 100);

		return '';
	}
/* END class */
}
/* End of file ft.ff_ms_img_saver.php */
/* Location: ./system/extensions/fieldtypes/ft.ff_ms_img_saver.php */
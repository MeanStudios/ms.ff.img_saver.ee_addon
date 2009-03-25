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

    var $info = array(
        'name' => 'MS Img Saver',
        'version' => '0.2.0',
        'desc' => 'Provides an image upload field',
        'docs_url' => ''
    );

    var $requires = array(
        'ff'        => '0.9.6',
        'cp_jquery' => '1.1'
    );

    var $hooks = array(
        'publish_form_headers',
        'control_panel_home_page',
		'submit_new_entry_absolute_end',
		'delete_entries_loop'
    );

	/**
	 * Default Field Settings
	 * @var array
	 */
	var $default_field_settings = array(
		'width' => '200',
		'height' => '200',
		'save_path' => '/home/username/public_html/system/images/uploads/',
		'save_url' => 'http://example.com/system/images/uploads/'
	);

	/**
	 * Display Field Settings
	 * 
	 * @param  array  $field_settings  The field's settings
	 * @return array  Settings HTML (cell1, cell2, rows)
	 */
	function display_field_settings($field_settings)
	{
		global $DSP, $LANG;

		$width = $field_settings['width'];
		$height = $field_settings['height'];
		$save_path = $field_settings['save_path'];
		$save_url = $field_settings['save_url'];

		$cell2 = $DSP->qdiv('defaultBold', $LANG->line('img_saver_width_label'))
		       . $DSP->input_text('width', $width, 4, '4', 'input', '50px')
			   . $DSP->qdiv('default', $LANG->line('img_saver_proportions'))
			   . $DSP->qdiv('defaultBold', $LANG->line('img_saver_height_label'))
		       . $DSP->input_text('height', $height, 4, '4', 'input', '50px')
			   . $DSP->qdiv('default', $LANG->line('img_saver_proportions'))
			   . $DSP->qdiv('defaultBold', $LANG->line('img_saver_save_path_label'))
		       . $DSP->input_text('save_path', $save_path, 200, '200', 'input', '450px')
			   . $DSP->qdiv('default', $LANG->line('img_saver_include_slash'))
			   . $DSP->qdiv('defaultBold', $LANG->line('img_saver_save_url_label'))
		       . $DSP->input_text('save_url', $save_url, 200, '200', 'input', '450px')
			   . $DSP->qdiv('default', $LANG->line('img_saver_include_slash'));

		return array('cell2' => $cell2);
	}

	/**
	 * Save Field Settings
	 *
	 * Turn the options textarea value into an array of option names and labels
	 * 
	 * @param  array  $field_settings  The user-submitted settings, pulled from $_POST
	 * @return array  Modified $field_settings
	 */
	function save_field_settings($field_settings)
	{
		$r = array();
		
		$r['width'] = $field_settings['width'];
		$r['height'] = $field_settings['height'];
		$r['save_path'] = $field_settings['save_path'];
		$r['save_url'] = $field_settings['save_url'];
		
		return $r;
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
        global $DSP, $PREFS, $SESS, $FNS, $DB, $LOC, $IN;
		
		$xid	= $FNS->random('encrypt');
		$arr	= array(
						'date'			=> $LOC->now,
						'ip_address'	=> $IN->IP,
						'hash'			=> $xid
						);

		$DB->query(  $DB->insert_string( 'exp_security_hashes', $arr ) );
		
		//Grab Settings
		preg_match('/^(field_id_)(.+)/', $field_name, $matches);		
        $field_id = $matches[2];
		$cp_url = str_replace('&amp;', '&', $PREFS->ini('cp_url') . '?S=' . $SESS->userdata['session_id']);
		$img_url = $field_settings['save_url'];
		//Grab Filename		
		$explode = explode("/", $field_data);
		$file_name = $explode[count($explode)-1];
		$img_url = str_replace($file_name, "", $field_data);
		
		
ob_start();
?>
<script type="text/javascript">
$(document).ready( function(){
	$("input[name='submit']").attr('name', 'susbmit');
	$('#<?= $field_name ?>_upload').livequery('click', img_saver_<?= $field_id ?>.upload_img);
	$('#<?= $field_name ?>_delete').livequery('click', img_saver_<?= $field_id ?>.delete_img);
});

var img_saver_<?= $field_id ?> = new function(){
	this.upload_img = function(){
		$('form').ajaxSubmit({
			url: '<?= $cp_url?>&ms_img_saver=upload',
			target: '#response_<?= $field_id ?>',
			type: 'post',
			iframe: true,
			data: {
				field_id : '<?= $field_id ?>',
				XID : '<?= $xid ?>'
			},
			success: function(){
				$('#<?= $field_name ?>_upload').attr('id', '<?= $field_name ?>_delete').html('<img src="<?= FT_URL ?>ff_ms_img_saver/imgs/file_delete.png" />&nbsp;&nbsp;Delete Image');
				$('#upload_input_<?= $field_id ?>').hide();
			}
		});
		return false;
	};

	this.delete_img = function(){
		$('form').ajaxSubmit({
			url: '<?= $cp_url?>&ms_img_saver=delete',
			type: 'post',
			iframe: true,
			data: {
				field_id : '<?= $field_id ?>',
				XID : '<?= $xid ?>'
			},
			success: function(response){
				$('#<?= $field_name ?>_delete').attr('id', '<?= $field_name ?>_upload').html('<img src="<?= FT_URL ?>ff_ms_img_saver/imgs/file_add.png" />&nbsp;&nbsp;Upload Image');
				$('#upload_input_<?= $field_id ?>').show();
				$('#response_<?= $field_id ?>').html(response);
			}
		});
		return false;
	};
}
</script>

<?php
$r = ob_get_contents();
ob_end_clean();
		if (!$field_data)
		{
			$r .= '<input name="'.$field_name.'" type="file" id="upload_input_'.$field_id.'" />&nbsp;&nbsp;<div id="'.$field_name.'_upload"><img src="'.FT_URL.'ff_ms_img_saver/imgs/file_add.png" />&nbsp;&nbsp;Upload Image</div>';
			$r .= NL . '<div id="response_'.$field_id.'">';
		} else {
			$r .= '<input style="display: none;" name="'.$field_name.'" type="file" id="upload_input_'.$field_id.'" />&nbsp;&nbsp;<div id="' . $field_name . '_delete"><img src="' . FT_URL . 'ff_ms_img_saver/imgs/file_delete.png" />&nbsp;&nbsp;Delete Image</div>';
			$r .= NL . '<div id="response_'.$field_id.'"><p><img src="'.$img_url.'thumb_'.$file_name.'" /></p>';
			$r .= $DSP->input_hidden($field_name, $field_data);
			$r .= $DSP->input_hidden('file_name_'.$field_id, $file_name);
		}
		$r .= "</div>";
		$r .= $DSP->input_hidden('old_file[]', $file_name);
        return $r;
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
	
	/**
	 * Hooks
	 */
	function delete_entries_loop($val, $weblog_id)
	{
		foreach ($this->_get_settings() as $field => $row)
		{
			$dir = $row['save_path'].$val.'/';
			$this->_delete_all($dir);
		}
	}
	
	function submit_new_entry_absolute_end($entry_id, $data)
	{
		global $DB;
		$count = 0;
		$old_file = $_POST['old_file'];
		
		foreach ($this->field_array as $field => $row)
		{
			$save_path = $row['field_settings']['save_path'];
			$save_url = $row['field_settings']['save_url'];
			
			$explode = explode("/", $row['field_data']);
			$file_name = $explode[count($explode)-1];
			
			if (@is_dir($save_path.'/'.$entry_id.'/') === FALSE)
			{
				@mkdir($save_path.'/'.$entry_id.'/', 0777);
			}
			
			@rename($save_path.'tmp/'.$file_name, $save_path.$entry_id.'/'.$file_name);
			@rename($save_path.'tmp/thumb_'.$file_name, $save_path.$entry_id.'/thumb_'.$file_name);
			
			if ($old_file[$count] != $file_name)
			{
				@unlink($save_path.$entry_id.'/'.$old_file[$count]);
				@unlink($save_path.$entry_id.'/thumb_'.$old_file[$count]);
			}
			if ($row['field_data'])
			{
				$sql = 'UPDATE exp_weblog_data
						SET '.$this->field_names[$count].' = "'.$save_url.$entry_id.'/'.$file_name.'"
						WHERE entry_id = '.$entry_id;
	
				$DB->query($sql);
			}
				$count++;
		}
	}
	
    function publish_form_headers()
    {
        $r = $this->get_last_call('') . NL . NL;

        $r .= '<script src="'.FT_URL.'ff_ms_img_saver/js/livequery.js" type="text/javascript"></script>' .NL;
		$r .= '<script src="'.FT_URL.'ff_ms_img_saver/js/jquery.form.js" type="text/javascript"></script>' .NL .NL;

        return $r;
    }

    function control_panel_home_page()
	{
		global $EXT, $IN, $PREFS;
		if ( $IN->GBL( 'ms_img_saver', 'GET' ) === FALSE )
		{
			return;
		}
		
		$EXT->end_script = TRUE;

		if (class_exists('Fieldframe_Base') === FALSE)
		{
			include EXT_MOD.'ext.fieldframe.php';
		}

		$AJAX = new Ff_ms_img_saver();

    	switch(strtolower($IN->GBL( 'ms_img_saver', 'GET' )))
    	{
    		case "upload" :
    			return $AJAX->_ajax_upload();

    		case "delete" :
    			return $AJAX->_ajax_delete();
    	}

    	return;

	}
	
	/**
	 * Supporting Functions
	 */
    function _ajax_upload()
    {
		global $FNS, $DSP, $DB, $REGX;
		
		$field_id = $_POST['field_id'];
		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_id = {$field_id}";
		$query = $DB->query($sql);
		
		foreach($query->result as $row)
		{
			$field_settings = $REGX->array_stripslashes(unserialize($row['ff_settings']));
		}
		
		$width = $field_settings['width'];
		$height = $field_settings['height'];
        $tmp_upload_dir = $field_settings['save_path'] . "tmp/";
		$tmp_upload_url = $field_settings['save_url'] . "tmp/";
        $field_name = "field_id_" . $field_id;
		
		$filename = strtolower($FNS->filename_security(str_replace(' ', '_', $_FILES[$field_name]['name'])));

        $img_file = $tmp_upload_dir . basename($filename);
		$img_url = $tmp_upload_url . basename($filename);
		
		$thumb_file = $tmp_upload_dir . basename('thumb_' . $filename);
		$thumb_url =  $tmp_upload_url . basename('thumb_' . $filename);
		
		if (@is_dir($tmp_upload_dir) === FALSE)
		{
			@mkdir($tmp_upload_dir, 0777);
		}

        if (@move_uploaded_file($_FILES[$field_name]['tmp_name'], $img_file))
		{
			$this->_resize($img_file, $width, $height, true, false, $img_file);
			$this->_resize($img_file, 80, 80, false, true, $thumb_file);
			
			$r = "<p><img src=\"{$thumb_url}\" /></p>";
			$r .= NL . $DSP->input_hidden($field_name, $img_url);
			$r .= NL . $DSP->input_hidden('file_name_'.$field_id, $filename);

			echo $r;
            exit();
        } else {
            echo "error";
            exit();
        }
    }

	function _ajax_delete()
    {
		global $FNS, $DSP, $DB, $REGX;
		
		$field_id = $_POST['field_id'];
		$field_name = "field_id_" . $field_id;
		
		$sql = "SELECT ff_settings FROM exp_weblog_fields WHERE field_id = {$field_id}";
		$query = $DB->query($sql);
		
		foreach($query->result as $row)
		{
			$field_settings = $REGX->array_stripslashes(unserialize($row['ff_settings']));
		}

        $tmp_upload_dir = $field_settings['save_path'] . "tmp/";
        $file_name = $_POST['file_name_'.$field_id];

		@unlink($tmp_upload_dir . $file_name);
		@unlink($tmp_upload_dir . 'thumb_' . $file_name);
		
		$r = NL . $DSP->input_hidden($field_name, '');
		echo $r;
		exit();
    }
	
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
	
	function _get_settings()
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
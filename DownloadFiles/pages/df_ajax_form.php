<?php
class DownloadFiles
{
	/**
	* @array with information on files 
	*/
	var $t_attachments_information;
	
	/**
	* @path temp file 
	*/
	var $t_path_file_zip;
	
	/**
	* @id bug  
	*/
	var $t_bug_id;
	
	
	/**
	* allows the user to download files from the bug
	* @return - void
	*/
	function files_download (){
		$this->html_prepare();
		if ( !array_key_exists( 'id_attachment', $_POST ) ){
			if ( $this->t_bug_id =='' ){
				header("Location: ".$_SERVER['HTTP_REFERER']);
			}
			return;
		}
		$t_post_id_attachment = $_POST['id_attachment'];
		if ( !$t_post_id_attachment ){
			header("Location: ".$_SERVER['HTTP_REFERER']);
		}
		$t_content_files = $this->retrieve_contents_files( $t_post_id_attachment );
		$this->zip_create( $t_content_files );
		$this->downloud_user();
		if  (file_exists( $this->t_path_file_zip ) ) {
			unlink( $this->t_path_file_zip );
		}
	}
	
	/**
	 * preparation a file selection form for downloading
	 * @return void
	 */
	function html_prepare (){
		echo '<form id="DownloadFiles" method="post" action="' . plugin_page( 'df_ajax_form' ) . '">';
		echo '<input type="submit" class="button_download" value="' . plugin_lang_get('submit_download') . '" >';
		echo '<div class="files_download">';
		echo '<fieldset>';
		echo '<table id="DownloadFiles" class="table table-bordered table-condensed table-hover table-striped">';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="column-selection"><div class="checkbox no-padding no-margin"><label><input name="all_attachment[]" value="all_attachment" class="selection-all-files-df" type="checkbox" checked="checked"></label></div></th>';
		echo '<th class="column-file">' . plugin_lang_get('file'). '</th>';
		echo '<th class="column-file-size">' . plugin_lang_get('file_size'). '</th>';
		echo '<th class="column-file-type">' . plugin_lang_get('file_type'). '</th>';
		echo '<th class="column-date">' . plugin_lang_get('date'). '</th>';
		echo '<th class="column-user">' . plugin_lang_get('user') . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ( $this->t_attachments_information as $t_attachment ) {
			if (!$t_attachment['can_download']) {
				continue;
			}
			echo '<tr>';
			echo '<td class="column-selection"><div class="checkbox no-padding no-margin"><label><input name="id_attachment[]" value="' . $t_attachment['id'] . '" class="selection_file" type="checkbox" checked="checked"></label></div></td>';
			echo '<td class="column-file"><a href="file_download.php?file_id=' . $t_attachment['id'] . '&amp;type=bug">' . $t_attachment['display_name'] . '</a></td>';
			echo '<td class="column-file-size">' . round ($t_attachment['size']/1024, 2) . ' ' . plugin_lang_get('size') . '</td>';
			echo '<td class="column-file-type"><a href="file_download.php?file_id=' . $t_attachment['id'] . '&amp;type=bug">';
			print_file_icon( $t_attachment['display_name'] );
			echo '</a> ' . substr($t_attachment['display_name'], strrpos($t_attachment['display_name'], '.')+1) . '</td>';
			echo '<td class="column-date">' . date ( 'Y-m-d H:i:s' , $t_attachment['date_added']) . '</td>';
			echo '<td class="column-user"><a title="' . user_get_field( $t_attachment['user_id'], 'username' ) . '" href="view_user_page.php?id=' . $t_attachment['user_id'] . '">' . user_get_field( $t_attachment['user_id'], 'realname' ) . '</a></td>';
			echo '</tr>';
		}
		
		echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '</div>';
		echo '<textarea name="number_issue" hidden>' . $this->t_bug_id . '</textarea>';
		echo '</form>';
	}
	
	/**
	* retrieve the contents of files
	* @param p_post_id_attachment - array id files
	* @return array of files containing: filename - the full name of the file and content - the contents of the file
	*/
	function retrieve_contents_files ($p_post_id_attachment){
		
		if (!$p_post_id_attachment){
			return;
		}
		$t_content_files = array();
		$this->tempfile_created ();
		foreach ( $p_post_id_attachment as $t_file_id ) {
			$c_file_id = (integer)$t_file_id;
			$t_query = 'SELECT * FROM {bug_file} WHERE id=' . db_param();
			$t_result = db_query( $t_query, array( $c_file_id ) );
			$t_row = db_fetch_array( $t_result );
			if( false === $t_row ) {
				# Attachment not found
				error_parameters( $c_file_id );
				trigger_error( ERROR_FILE_NOT_FOUND, ERROR );
			}
			extract( $t_row, EXTR_PREFIX_ALL, 'v' );
			$t_project_id = bug_get_field( $v_bug_id, 'project_id' );
			$t_upload_method = config_get( 'file_upload_method' );
			switch( $t_upload_method ) {
				case DISK:
					$t_local_disk_file = file_normalize_attachment_path( $v_diskfile, $t_project_id );
					$t_content = file_get_contents($t_local_disk_file);
					break;
				case DATABASE:
					$t_content = $v_content;
			}
			$t_content_files[] = array('filename'=>$v_filename, 'content'=>$t_content);
		}
		
		return $t_content_files;
	}
	
	/**
	 * create a temporary file in the temporary files directory
	 * @return void
	 */
	function tempfile_created (){
		try{
			$this->t_path_file_zip = tempnam(sys_get_temp_dir(), 'arhive_' . $this->t_bug_id);
		}catch(Exception  $e ){
			error_parameters( $this->t_path_file_zip );
			trigger_error( ERROR_FILE_NOT_FOUND, ERROR );
		}
	}
	
	/**
	* adds the contents of files to the archive
	* @param p_content_files - An array of files containing: filename - the full name of the file and content - the contents of the file
	* @return void
	*/
	function zip_create( $p_content_files){
		$zip = new ZipArchive();
		if ($zip->open($this->t_path_file_zip, ZipArchive::CREATE)!==TRUE) {
			error_parameters( $this->t_path_file_zip );
			trigger_error( ERROR_FILE_NOT_FOUND, ERROR );
		}
		foreach ( $p_content_files as $t_content ){
			$zip -> addFromString($t_content['filename'], $t_content['content']); 
		}
		$zip -> close();
	}
	
	/**
	 * show file download form
	 * @return void
	 */
	function downloud_user(){
		if (file_exists($this->t_path_file_zip)) {
			$g_bypass_headers = true; # suppress headers as we will send our own later
			define( 'COMPRESSION_DISABLED', true );
			auth_ensure_user_authenticated();

			$f_show_inline = gpc_get_bool( 'show_inline', false );
			if( $f_show_inline ) {
				if( !@form_security_validate( 'file_show_inline' ) ) {
					http_all_headers();
					trigger_error( ERROR_FORM_TOKEN_INVALID, ERROR );
				}
			}
			
			while( @ob_end_clean() ) {
			}
			if( ini_get( 'zlib.output_compression' ) && function_exists( 'ini_set' ) ) {
				ini_set( 'zlib.output_compression', false );
			}
			// http_security_headers();
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $_POST['number_issue'] . '.zip"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			global $g_allow_file_cache;
			if( http_is_protocol_https() && is_browser_internet_explorer() ) {
			} else {
				if( !isset( $g_allow_file_cache ) ) {
					header( 'Pragma: no-cache' );
				}
			}
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s \G\M\T', time() ) );
			header('Content-Length: ' . filesize($this->t_path_file_zip));
			header( 'X-Content-Type-Options: nosniff' );
			readfile($this->t_path_file_zip);
		}
	}
	
}

$d_f = new DownloadFiles ();
$d_f->t_bug_id = $_GET['bug_id'];
$d_f->t_attachments_information = file_get_visible_attachments( $d_f->t_bug_id );
$d_f->files_download ();


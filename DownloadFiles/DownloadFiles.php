<?php

# Copyright (c) 2018 Aantoly Kabakov (anatoly.kabakov.inbev@gmail.com)

# Download files for MantisBT is free software: 
# you can redistribute it and/or modify it under the terms of the GNU
# General Public License as published by the Free Software Foundation, 
# either version 3 of the License, or (at your option) any later version.
#
# Download files plugin for MantisBT is distributed in the hope 
# that it will be useful, but WITHOUT ANY WARRANTY; without even the 
# implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
# See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Inline column configuration plugin for MantisBT.  
# If not, see <http://www.gnu.org/licenses/>.
require_once( config_get_global( 'class_path' ) . 'MantisPlugin.class.php' );
class DownloadFilesPlugin extends MantisPlugin 
	{
	
	/**
	* @array with information on files 
	*/
	var $t_is_attachments;
	
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = 'DownloadFiles';

		$this->version = '1.0';
		$this->requires = array(
			'MantisCore' => '2.0, < 3.0',
		);

		$this->author = 'Anatoly Kabakov';
		$this->contact = 'anatoly.kabakov.inbev@gmail.com';
		$this->url = '';
		
		$this->scripts = array(
			'view.php',
		);
	}
	
	#Check for conditions when this plugin is allowed to hook
	function check_page() {
		return auth_is_user_authenticated() && !current_user_is_protected() &&  in_array( basename( $_SERVER['SCRIPT_NAME'] ), $this->scripts );
	}
	
	function hooks() {
		return array(
			'EVENT_LAYOUT_RESOURCES' => 'resources',
			'EVENT_MENU_ISSUE' => 'link_get_download_file',
			'EVENT_VIEW_BUG_EXTRA'  => 'add_files_download_dialog'
		);
	}
	
	public function resources( $p_event ) {
		if( !$this->check_page() ) {
			return;
		}
		return '<script type="text/javascript" src="' . plugin_file( 'download-files.js' ) . '" async></script>'
			 . '<link rel="stylesheet" type="text/css" href="'. plugin_file( 'download-files.css' ) .'"/>';
	}
	
	/**
	* adds a link to display the button
	* @param p_event - type event
	* @param p_bug_id - id issue
	* @return t_link button
	*/
	function link_get_download_file( $p_event,$p_bug_id ) {
		if( !$this->check_page() ) {
			return;
		}
		$this->t_is_attachments = file_bug_has_attachments( $p_bug_id );
		if ( !$this->t_is_attachments ) {
			return;
		} 
		$t_token = form_security_token( 'ajax_form' );
		$t_url = $_SERVER['REQUEST_URI'];
		$t_link = '<a href="' . $t_url . '&DownloadFiles=1' .'" data-remote="' . plugin_page( 'df_ajax_form' ) . '&bug_id=' . $p_bug_id  . '&ajax_form_token=' . $t_token . '" class="download_files_trigger btn btn-sm btn-primary btn-white btn-round">'
				. plugin_lang_get( 'submit_download' )
				. '</a>';
		return array($t_link);
	}
	
	/**
	* add a form for downloading files
	* @param p_event - type event
	* @param p_bug_id - id issue
	* @return - void
	*/
	public function add_files_download_dialog( $p_event, $p_bug_id ) {
		if( !$this->check_page() ) {
			return;
		}
		if (!$this->t_is_attachments) {
			return;
		}
		$t_title = plugin_lang_get('name_link') . ' ' . $p_bug_id;
		echo '<div id="download_files_dialog" class="dialog" title="' . $t_title . '">';
		echo plugin_lang_get( 'please_wait' );
		echo '</div>';
	}
}

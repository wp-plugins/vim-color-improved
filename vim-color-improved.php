<?php
/*
Plugin Name: Vim Color Improved
Plugin URI: http://www.zacharyfox.com
Description: Creates syntax highlighted html in posts from external code files using vim. Works with local and remote code files (via http). Use plugin options (In menu Options>Vim Color Improved) to configure the plugin. You must be able to exec(vim) from php for this to work.
Version: 0.3.2
Author: Zachary Fox
Author URI: http://www.zacharyfox.com

Author's note:

Good artists borrow, great artists steal.

I've borrowed heavily from the work of many people here.

*/

if (!class_exists('vim_color_improved')){
	/**
	 * Vim Color Improved. Wordpress plugin
	 *
	 * @author Zachary Fox
	 * @version 0.3.2
	 */
	class vim_color_improved{

		function vim_color_improved(){
			global $wpdb;
			$this->db = &$wpdb;
			$this->vci_version = "0.3.2";
			$this->vci_temp_dir = ABSPATH.'wp-content/plugins/vim-color-improved/tmp/';
			$this->vci_cache_table = $this->db->prefix . 'vci_cache';

			// Default options
			$this->vci_use_caching = get_option('vci_use_caching');
			$this->vci_vim_path = get_option('vci_vim_path');
			$this->vci_default_path = get_option('vci_default_path');
			$this->vci_default_download_path = get_option('vci_default_download_path');
			$this->vci_showsyntax = get_option('vci_showsyntax');
			$this->vci_scroll = get_option('vci_scroll');
			$this->vci_scrollheight = get_option('vci_scrollheight');


			add_action('activate_vim-color-improved/vim-color-improved.php', array(&$this, 'vci_activate'));
			add_action('deactivate_vim-color-improved/vim-color-improved.php', array(&$this, 'vci_deactivate'));
			add_action('wp_head', array(&$this, 'vci_add_css'));
			add_action('admin_menu', array(&$this, 'vci_add_pages'));
			add_filter('the_content', array(&$this, 'vci_color'), 9);
		}

	 /**
		* Accepts text as a parameter, parses the block for [viewcode] statements, and returns formated text
		* This is the main callback for the_content.
		*
		* @param String $text This is the_content from the wordpress post
		* @return String Returns the_content with the codeblocks formatted
		*/
		function vci_color($text){

			// Find all the code block definitions and step through them
			// Changed to allow optional whitespace after [viewcode] start tag
			$count = preg_match_all('%\[viewcode\][\s]{0,}(src=[^\[]*)\[/viewcode\]%', $text, $matches);

			for ($i = 0; $i < $count; $i++) {
				// Parse the parameters into $this->params
				$this->vci_parse_params($matches[1][$i]);

				// Determine if the specified path is absolute, or relative to the root path
				// If it's neither, assume it's relative to the default path set on line 12
				// This code is from codeviewer 1.4, but I added the $download_path to correctly allow
				// downloads of locally sourced files.

				if (strpos(($this->params['src']), 'http://') !== false) {
					$path = $this->params['src'];
					$download_path = $path;
				} else if (substr(($this->params['src']), 0, 1) == '/') {
					$path = $_SERVER['DOCUMENT_ROOT'] . $this->params['src'];
					$download_path = 'http://'.$_SERVER['HTTP_HOST'] . $this->params['src'];
				} else {
					$path = $this->vci_default_path . $this->params['src'];
					if (isset($this->vci_default_download_path) && strlen($this->vci_default_download_path) > 0){
						$download_path = 'http://'. $this->vci_default_download_path . '/' . $this->params['src'];
					}
				}

				// Set up our files here. Unfortunatly, there is no way to use vim other than writing to a file.
				// I almost had it working by piping in the string, but something in the command I had to exec was getting messed up.
				// Email me if you know a better way, please. I'd love to eliminate disk access except for the cache.

				$tempfile1 = $this->vci_temp_dir.'/'.basename($path); // saves the code file in the tmp directory
				$tempfile2 = $this->vci_temp_dir.'/vim-color-improved.tmp'; // this will hold the vim output

				if ($this->params['showsyntax'] == "yes") {
					$codehead = '<p class="vci_info">[viewcode]'.$matches[1][$i].'[/viewcode]</p>' ;
				} else {
					$codehead = '';
				}

				if ($this->params['scroll'] == "yes") {
					$codelist = '<pre class="vci_code" style="height:'.$this->params['scrollheight'].';overflow:auto;">';
				} else {
					$codelist = '<pre class="vci_code">';
				}

				if ($lines = @file($path)) {
					$this->last_modified = $this->vci_filemtime($path);

					if ($this->params['cache'] == 'yes' && (($cache_file = $this->vci_get_cache_file($path,$this->last_modified)) !== false)){
						$codelist .= $cache_file['code'];
					} else {
						// Set the sourcecode to use. Either lines or the complete file.
						if (isset($this->params['lines'])){
							$sourcecodelines = array();
							foreach ($this->params['lines'] as $key => $val){
								$sourcecodelines[] = $lines[$val-1];
							}
							$sourcecode = join($sourcecodelines);
						} else {
							$sourcecode = join($lines);
						}

						// Try to open the tempfile
						if ($fh = @fopen($tempfile1,w)){
							fwrite($fh,$sourcecode);
							fclose($fh);
							$runvim = $this->vci_vim_path.' -c "run syntax/2html.vim" -c "wq! '.$tempfile2.'" '.$tempfile1;
							if ($status = exec($runvim)){
								// Clean the html up
								list($header,$output) = split('<pre>',@file_get_contents($tempfile2));
								list($output,) = split('</pre>',$output);
								$search = '%<font color\=\"\#([\d|\w]{6})\">(.*)</font>%U';
								$replace = '<span style="color:#\\1">\\2</span>';
								$output = preg_replace($search,$replace,$output);

								// Write the cache to the db and filesystem
								if ($this->params['cache'] == 'yes'){
									$this->vci_cache_file($path,$output,$this->last_modified);
								}

								// Get rid of the temp files
								unlink($tempfile1);
								unlink($tempfile2);
								$codelist .= $output;
							} else {
								$codelist .= '<p class="vci_warning">[Unable to exec vim for highlighting.]</p>';
							}
						} else {
							$codelist .= '<p class="vci_warning">[The tempfile <kbd>'.$tempfile1.'</kbd> could not be opened]</p>';
						}
					}
				} else {
					// If the file can't be opened, try for a cached version using the path and parameters, ignoring the last_modified date
					if (($cache_file = $this->vci_get_cache_file($path)) !== false){
						$codehead .= '<p class="vci_warning">Unable to open source file. Using cache from: '.date("M j, Y",$cache_file['file']->last_modified).'</p>';
						$codelist .= $cache_file['code'];
					} else {
						// Unable to open source file or use cached version
					}
				}
				$codelist .= '</pre>';
				$codefoot = '<p class="vci_info">HTML code generated by <a href="http://www.zacharyfox.com/blog/free-tools/vim-color-improved">vim-color-improved v.'.$this->vci_version.'.</a>';

				if ($this->params['link'] == 'yes' && isset($download_path)){
					$codefoot .= '<strong>Download this code:</strong> <a href="' . $download_path . '">' . basename($path) . '</a>';
				}

				$codefoot .= '</p>';
				$text = str_replace(($matches[0][$i]), $codehead.$codelist.$codefoot, $text);
			}

			return $text;
		}

	/**
	 * Parses the parameters of the viewcode tag
	 * 
	 * @param String $match Subpattern match containing the parameters. Sets $this->params
	 */
		function vci_parse_params($params){
			$this->params = array();
			$this->params['cache'] = $this->vci_use_caching;
			$this->params['scroll'] = $this->vci_scroll;
			$this->params['scrollheight'] = $this->vci_scrollheight;
			$this->params['showsyntax'] = $this->vci_showsyntax;
			$params = preg_split('%\s+%',$params); //Split at one or more space-character
			foreach ($params as $key=>$val) {
				$temp = split("=",$val);
				// Cleaned this up to only call preg_replace once
				$search = array('%\"(.*)\"%','%\'(.*)\'%','%&quot;(.*)&quot;%');
				$replace = "$1";
				$this->params[$temp[0]] = preg_replace($search,$replace,$temp[1]); // Remove ", HTML " (=&quot;), and '
				if (strtolower($temp[0]) != 'src'){
					$this->params[$temp[0]] = strtolower($this->params[$temp[0]]);
				}
			}

			// Parse the lines parameter if it exists and set this->params['lines'] to an array of line numbers to include
			if (isset($this->params['lines'])){
				$include_lines = array();
				$showlines_ar = split(',',$this->params['lines']);
				foreach ($showlines_ar as $showlines){
					$showline = split('-',$showlines);
					if (count($showline) > 1){
						for($j=$showline[0];$j<=$showline[1];$j++){
							$include_lines[] = $j;
						}
					} else {
						$include_lines[] = $showline[0];
					}
				}
				$this->params['lines'] = $include_lines;
			}
		}

	/**
   *  Deletes a cached file from db and filesystem
   * 
   * @param Integer $id Id of cached file to delete
   */
		function vci_remove_cache_file($id){
			$this->db->query('delete from '.$this->vci_cache_table.' where id='.$id);
			if (is_file($this->vci_temp_dir.$id.".html")){
				unlink($this->vci_temp_dir.$id.".html");
			}
		}

	/**
   * Retrieves a cached file from the filesystem.
   *
   * This method retrives a cached file from the filesystem, and will try to find
   * the last modified version if available. In addition, it will delete cached files
   * that have been modified if a newer version is available. Without the last modified
   * parameter, it simply returns the newest version from the cache that matches
   * the other parameters
   *
   * @param String $path The path to the original source code file
   * @param String $last_modified Optional unix timestamp of last modified date of original file
   * @return Array|Boolean Returns an array containing the db info and the code, or returns false on failure
   */
		function vci_get_cache_file($path,$last_modified = false){
			$params = $this->vci_serialize_params();

			$sql = 'select id,path,UNIX_TIMESTAMP(last_modified) as last_modified from '.$this->vci_cache_table.' where path = "'.$path.'" and params = "'.addslashes($params).'"';


			$files = $this->db->get_results($sql);

			if ($last_modified !== false){
				foreach ($files as $temp){
					if ($temp->last_modified == $last_modified){
						$file = $temp;
					} elseif ($temp->last_modified < $last_modified){
						$this->vci_remove_cache_file($temp->id);
					}
				}
			} else {
				$file = $files[count($files)-1];
			}

			if(!isset($file)){
				return false;
			} else {

				if ($code = @file_get_contents($this->vci_temp_dir.$file->id.".html")){
					return array('code' => $code, 'file' => $file);
				} else {
					return false;
				}
			}
		}

	/**
   * Stores a cached file on the filesystem
   * 
   * @param String $path Complete path to file to store
   * @param String $file Contents of file to store
   * @return Boolean On sucess or failure
   */
		function vci_write_cache_file($path,$file){
			if ($cache_file = @fopen($path,w)){
				fwrite($cache_file,$file);
				fclose($cache_file);
				return true;
			} else {
				return false;
			}
		}

	/**
   * Stores a file in the cache database, then filesystem
   * 
   * @param String $path Complete path to file to store
   * @param String $file Contents of file to store
   * @param String $last_modified Unix timestamp of last modified time of the original file
   * @return Boolean On success or failure
   */
		function vci_cache_file($path,$file,$last_modified){
			$params = $this->vci_serialize_params();
			$sql = "insert into ".$this->vci_cache_table." (path,params,last_modified) values ('$path','$params',FROM_UNIXTIME($last_modified))";
			if ($this->db->query($sql)){
				$insert_id = $this->db->insert_id;
				if ($this->vci_write_cache_file($this->vci_temp_dir.$insert_id.".html",$file)){
					return true;
				} else {
					$this->vci_remove_cache_file($insert_id);
					$this->db->query($sql);
					return false;
				}
			}
		}

	/**
	 * Adds the options to the database when activating the plugin, also adds database table for cache information
	 */
		function vci_activate(){
			add_option('vci_version', $this->vci_version);
			add_option('vci_use_caching', 'yes', 'Use caching for generated HTML? You can always override inline');
			add_option('vci_vim_path', 'vim', 'Path to vim');
			add_option('vci_default_path', ABSPATH);
			// Figure out the default download path using ABSPATH and the $_SERVER[DOCUMENT_ROOT]
			$vci_default_download_path = $_SERVER['HTTP_HOST'].str_replace($_SERVER['DOCUMENT_ROOT'],'',ABSPATH);
			add_option('vci_default_download_path', $vci_default_download_path);
			add_option('vci_showsyntax', 'no', 'Show syntax by default');
			add_option('vci_scroll','no','Use scrolling by default');
			add_option('vci_scrollheight', '200px', 'Default height of scroll window');

			$this->vci_add_cache_table();
		}

	/**
   *  Removes the options from the database when uninstalling, also clears the cache
   */
		function vci_deactivate(){
			delete_option('vci_version');
			delete_option('vci_use_caching');
			delete_option('vci_vim_path');
			delete_option('vci_default_path');
			delete_option('vci_default_download_path');
			delete_option('vci_showsyntax');
			delete_option('vci_scroll');
			delete_option('vci_scrollheight');

			$this->vci_clear_cache();
			$this->vci_remove_cache_table();
		}

	/**
   * Adds the database table for cache information
   */
		function vci_add_cache_table(){

			if($this->db->get_var("SHOW TABLES LIKE '".$this->vci_cache_table."'") != $this->vci_cache_table) {
				$sql = "CREATE TABLE ".$this->vci_cache_table." (
                      id mediumint(9) NOT NULL AUTO_INCREMENT,
	                    path varchar(255) DEFAULT '' NOT NULL,
	                    params varchar(255) NOT NULL,
	  	                last_modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	                    PRIMARY KEY  id (id),
                      KEY path_params (path,params),
                      KEY mtime (last_modified)
	              )";
				$status = $this->db->query($sql);
			}
		}

	/**
   * Removes the database table for cache information
   */
		function vci_remove_cache_table(){
			if($this->db->get_var("SHOW TABLES LIKE '".$this->vci_cache_table."'") == $this->vci_cache_table) {
				$sql = "drop table `".$this->vci_cache_table."`";
				$this->db->query($sql);
			}
		}

	/**
	 * Adds the css file into the head of the output
	 */
		function vci_add_css(){
			echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/vim-color-improved/style.css" />' . "\n";
		}

	/**
	 * Adds the options page to the admin interface
	 */
		function vci_add_pages(){
			add_options_page('Vim Color Improved Options', 'Vim Color Improved', 8, 'vim-color-improved.php', array(&$this, 'vci_option_menu'));
		}

	/**
	 * Create a yes/no select element
	 *
	 * @param String $name Name of the element
	 * @param String $selected yes or no for selected
	 * @return String Returns an html select element with yes or no options
	 */
		function vci_select($name, $selected = false){
			$html = '<select name="'.$name.'">';
			$options = array('Yes' => 'yes','No' => 'no');
			foreach ($options as $key => $val){
				$html.= '<option value="'.$val.'"';
				if ($selected == $val) $html.= ' selected="selected"';
				$html.=  '>'.$key.'</option>';
			}
			$html.= '</select>';
			return $html;
		}

	/**
	 * Gets a list of the cached files from the database. Also checks the cache on the filesystem and
	 * removes any db entries for files that are not present
   *
	 * @return Array Array of objects containing the files in the cache dir
	 */
		function vci_files_cached_list(){
			$sql = 'select id,path,params,last_modified from '.$this->vci_cache_table;
			$cached_files = $this->db->get_results($sql);
			foreach ($cached_files as $file){
				if (is_file($this->vci_temp_dir.$file->id.".html")){
					$return[] = $file;
				} else {
					$this->vci_remove_cache_file($file->id);
				}
			}
			return $return;
		}

	/**
	 * @return Integer Number of files in the cache dir
	 */
		function vci_files_cached(){
			return count($this->vci_files_cached_list());
		}

	/**
	 * Clears all files from the cache.
	 */
		function vci_clear_cache(){
			$filelist = $this->vci_files_cached_list();
			if (count($filelist) > 0){
				foreach ($filelist as $file){
					unlink($this->vci_temp_dir.$file->id.".html");
				}
				$sql = "truncate table ".$this->vci_cache_table;
				$this->db->query($sql);
			}
		}

	/**
	 * Gets file modified time. Works on local and remote files
	 * 
	 * @param String $path Filesystem path or URI of a file
	 * @return String Unix timestamp of last modified time
	 */
		function vci_filemtime($path){
			switch(substr($this->params['src'],0,4)){
				case 'http':
				return $this->vci_filemtime_remote($path);
				break;

				default:
				return  filemtime($path);
				break;
			}
		}
	
	/**
   * Returns a serialized set of parameters for storage in the database. Includes all the parameters that affect the vim output
   *
   * @return String Serialized parameters for storage in the database
   */
		function vci_serialize_params(){
			// As of now, lines is the only parameter that would change the cached file, so we're only including that
			$params = array('lines' => $this->params['lines']);
			foreach ($params as $key => $val){
				if (is_array($val)){
					$val = join(",",$val);
				}
				$return[] = $key."=".$val;
			}
			return join(';',$return);
		}

	/**
	 * Gets remote file modified time - taken from php.net, thanks to solarijj at gmail dot com
	 * 
	 * @param String $uri uri to find mtime
	 * @return String Unix timestamp of last modified time
	 */
		function vci_filemtime_remote( $uri ){
			// default
			$unixtime = 0;
			$fp = fopen( $uri, "r" );
			if( !$fp ) {return;}
			$MetaData = stream_get_meta_data( $fp );
			foreach( $MetaData['wrapper_data'] as $response )
			{
				// case: redirection
				if( substr( strtolower($response), 0, 10 ) == 'location: ' )
				{
					$newUri = substr( $response, 10 );
					fclose( $fp );
					return $this->vci_filemtime_remote( $newUri );
				}
				// case: last-modified
				elseif( substr( strtolower($response), 0, 15 ) == 'last-modified: ' )
				{
					$unixtime = strtotime( substr($response, 15) );
					break;
				}
			}
			fclose( $fp );
			return $unixtime;
		}
	
	/**
	 * Defines the options page for the admin interface
	 */
		function vci_option_menu(){
			if (isset($_POST['vci_action'])){
				switch ($_POST['vci_action']){
					case 'vci_clear_cache':
					$this->vci_clear_cache();
					break;
				}
			}
			// Mixed HTML/PHP for page is in an included file
			include('pages/vim-color-improved-options.php');
		}
	}
}

if (class_exists("vim_color_improved")) {
	$vim_color_improved = new vim_color_improved();
}

?>
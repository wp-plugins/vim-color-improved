<?php
/*
Plugin Name: Vim Color Improved
Plugin URI: http://www.zacharyfox.com
Description: Creates syntax highlighted html in posts from external code files using vim. Works with local and remote code files (via http). Use plugin options (In menu Options>Vim Color Improved) to configure the plugin, and (menu Manage->Vim Color Improved Cache) to clear files from the cache and see cache information. You must be able to exec(vim) from php for this to work.
Version: 0.4.0
Author: Zachary Fox
Author URI: http://www.zacharyfox.com

Author's note:

Good artists borrow, great artists steal.

I've borrowed heavily from the work of many people here.

*/

include_once('functions.php');

if (!class_exists('vim_color_improved')){
	/**
	 * Vim Color Improved. Wordpress plugin that provides syntax highlighting of external files through [viewcode] tag.
	 *
	 * @package vim-color-improved
	 * @author Zachary Fox
	 * @version 0.4.0
	 */
	class vim_color_improved{
	  var $db;
	  var $vci_version = "0.4.0";
	  var $vci_temp_dir;
	  var $vci_cache_table;
	  var $vci_use_caching;
	  var $vci_use_system_temp_dir;
	  var $vci_vim_path;
	  var $vci_default_path;
	  var $vci_default_download_path;
	  var $vci_showsyntax;
	  var $vci_scroll;
	  var $vci_scrollheight;
	  var $vci_html_use_css;
	  var $vci_link;

    function vim_color_improved(){
		  global $wpdb;
      $this->db =& $wpdb;
      $this->vci_temp_dir = sys_get_temp_dir().'/';
			$this->vci_cache_table = $this->db->prefix . 'vci_cache';

			// Default options
			$this->vci_use_caching = get_option('vci_use_caching');
			$this->vci_vim_path = get_option('vci_vim_path');
			$this->vci_default_path = get_option('vci_default_path');
			$this->vci_default_download_path = get_option('vci_default_download_path');
			$this->vci_showsyntax = get_option('vci_showsyntax');
			$this->vci_scroll = get_option('vci_scroll');
			$this->vci_scrollheight = get_option('vci_scrollheight');
			$this->vci_html_use_css = get_option('vci_html_use_css');
			$this->vci_link = get_option('vci_link');
      
			// Hook into WordPress
			add_action('wp_head', array(&$this, 'vci_add_css'));
			add_filter('the_content', array(&$this, 'vci_color'), 9);
			
			// This is for the admin section
			add_action('activate_vim-color-improved/vim-color-improved.php', array(&$this, 'vci_activate'));
			add_action('deactivate_vim-color-improved/vim-color-improved.php', array(&$this, 'vci_deactivate'));
			add_action('admin_menu', array(&$this, 'vci_add_pages'));
			add_action('admin_head', array(&$this, 'vci_add_admin_css'));

			// For tinymce
			add_filter('mce_plugins', array(&$this,'vci_add_mce_plugin'));
			add_filter('mce_buttons_2', array(&$this, 'vci_add_mce_button'));
			
		}

	 /**
		* Accepts text as a parameter, parses the block for [viewcode] tags, and returns formated text
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
        
				// Sets the $path and $download_path from $this->params['src'];		
				$this->vci_parse_src(&$path, &$download_path);

        switch($this->params['showsyntax']){
				  case "yes":
					 $codehead = '<p class="vci_info">[viewcode]'.$matches[1][$i].'[/viewcode]</p>' ;
					 break;
					 
				  default:
				    $codehead = '';
				    break;
				}

				switch($this->params['scroll']){
          case"yes":
            $codelist = '<pre class="vci_code" style="height:'.$this->params['scrollheight'].';overflow-y:auto;">';
            break;
          
  			  default:
  					$codelist = '<pre class="vci_code">';
  					break;
				}

				if ($lines = @file($path)) {
					$this->last_modified = $this->vci_filemtime($path); // Get the last modified time of the original file
					
					if ($this->params['cache'] == 'yes' && (($cache_file = $this->vci_get_cache_file($this->last_modified)) !== false)){
						$codelist .= $cache_file['code']; // Display the cached version if it is up to date and present
					} else {
						$sourcecode = $this->vci_set_sourcecode($lines); // Set the sourcecode to use. Either lines or the complete file
						
						// Set up our files here. Unfortunatly, there is no way to use vim other than writing to a file.
    				// I almost had it working by piping in the string, but something in the command I had to exec was getting messed up.
    				// Email me if you know a better way, please. I'd love to eliminate disk access except for the cache.
    
    				$tempfile1 = $this->vci_temp_dir.'vci_'.basename($path); // saves the code file in the tmp directory
    				$tempfile2 = $this->vci_temp_dir.'vci_temporary';        // this will hold the vim output
    				
						if ($this->vci_write_cache_file($tempfile1,$sourcecode) !== false){ // Try to write the tempfile
						  $codelist .= $this->vci_highlight_with_vim($tempfile1,$tempfile2);
						} else {
							$codelist .= '<p class="vci_warning">[The tempfile <kbd>'.$tempfile1.'</kbd> could not be opened]</p>';
						}
					}
				} else {
					// If the file can't be opened, try for a cached version using the path and parameters, ignoring the last_modified date
					if (($cache_file = $this->vci_get_cache_file()) !== false){
						$codehead .= '<p class="vci_warning">Unable to open source file. Using cache from: '.date("M j, Y",$cache_file['file']->last_modified).'</p>';
						$codelist .= $cache_file['code'];
					} else {
						// Unable to open source file or use cached version
						$codehead .= '<p class="vci_warning">Unable to open source file: '.$this->params['src'].'</p>';
						unset($codelist);
					}
				}
				if (isset($codelist)){
  				$codelist .= '</pre>';
  				$codefoot = '<p class="vci_info">HTML code generated by <a href="http://www.zacharyfox.com/blog/free-tools/vim-color-improved">vim-color-improved v.'.$this->vci_version.'.</a>';
  
  				if ($this->params['link'] == 'yes' && isset($download_path)){
  					$codefoot .= '<strong>Download this code:</strong> <a href="' . $download_path . '">' . basename($path) . '</a>';
  				}
  
  				$codefoot .= '</p>';
				}
				$text = str_replace(($matches[0][$i]), $codehead.$codelist.$codefoot, $text);
				unset($codehead,$codelist,$codefoot);
			}
			return $text;
		}
		
		/**
		 * Executes vim and performs the actual highlighting, returns the highlighted code or HTML error message
		 * 
		 * @since 0.3.3
		 * 
		 * @param String $tempfile1 Temporary file holding source code to highlight
		 * @param String $tempfile2 Temporary file to hold output of vim
		 * @return String HTML highlighted sourcecode, or HTML formatted error message
		 */
		function vci_highlight_with_vim($tempfile1,$tempfile2){
		  // Build the vim command
			$vim_command_array = array();
			$vim_command_array[] = $this->vci_vim_path;
			$vim_command_array[] = '-n'; // No swap file, this should speed it up a little
			$vim_command_array[] = '-i NONE'; // This prevents vim from writing or reading a .viminfo file
			$vim_command_array[] = '-X'; // Don't look for a terminal window
			$vim_command_array[] = '-U NONE'; // Don't read .vimrc
			if ($this->params['html_use_css'] == "yes") { $vim_command_array[] = '-c "let html_use_css=1"'; }
			/**
			 * @todo Add parameter for line numbers
			 */
			$vim_command_array[] = '-c "run syntax/2html.vim" -c "wq! '.escapeshellcmd($tempfile2).'" -c "q" '.escapeshellcmd($tempfile1);
			$vim_exec = join(" ",$vim_command_array);
			
			// Let's do this!
			
			if (exec($vim_exec)){
				// Clean the html up
				list(,$output) = split('<pre>',@file_get_contents($tempfile2));
				list($output,) = split('</pre>',$output);
				
				if ($this->params['html_use_css'] != "yes") { 
					$search = '%<font color\=\"\#([\d|\w]{6})\">(.*)</font>%U';
					$replace = '<span style="color:#\\1">\\2</span>';
					$output = preg_replace($search,$replace,$output);
				}

				// Get rid of the temp files
				unlink($tempfile1);
				unlink($tempfile2);
				
				// Write the cache to the db and filesystem
				if ($this->params['cache'] == 'yes') $this->vci_cache_file($output);
				return $output;

			} else {
			  // Return an Error
			  $output = '<p class="vci_warning">[Unable to exec vim for highlighting.]</p>';
			  return $output;
			}
		}
		
    /**
     * Sets the source code to highlight
     * 
     * @since 0.3.3
     * 
     * @param Array $lines Lines of code from original file
     * @return String|Boolean Returns source code to highlight based on the lines parameter, or false if $lines is not an array
     */
    function vci_set_sourcecode($lines){
      if (!is_array($lines)) return false;
      if (isset($this->params['lines'])){
  			$sourcecodelines = array();
  			foreach ($this->params['lines'] as $key => $val){
  				$sourcecodelines[] = $lines[$val-1];
  			}
  			$sourcecode = join($sourcecodelines);
  		} else {
  			$sourcecode = join($lines);
  		}
  		return $sourcecode;
    }
  
  	/**
     * Parses the src parameter and sets the path and download path
     * 
     * @since 0.3.3
     * 
     * @param String &$path Empty string reference to path, will be set by function
     * @param String &$download_path Empty string reference to download_path, will be set by function
     */
    function vci_parse_src(&$path, &$download_path){
      if (substr($this->params['src'], 0, 7) == 'http://')  {
  			$path = $this->params['src'];
  			$download_path = $path;
  		} else if (substr($this->params['src'], 0, 1) == '/') {
  			$path = $_SERVER['DOCUMENT_ROOT'] . $this->params['src'];
  			$download_path = 'http://'.$_SERVER['HTTP_HOST'] . $this->params['src'];
  		} else {
  			$path = $this->vci_default_path . $this->params['src'];
  			if (isset($this->vci_default_download_path) && strlen($this->vci_default_download_path) > 0){
  				$download_path = 'http://'. $this->vci_default_download_path . '/' . $this->params['src'];
  			}
  		}
    }

  	/**
  	 * Parses the parameters of the viewcode tag
  	 * 
  	 * @param String $params Subpattern match containing the parameters. Sets $this->params
  	 */
		function vci_parse_params($params){
		  // Sets the default parameters
			$this->params = array('cache'        => $this->vci_use_caching,
                            'scroll'       => $this->vci_scroll,
                            'scrollheight' => $this->vci_scrollheight,
                            'showsyntax'   => $this->vci_showsyntax,
                            'html_use_css' => $this->vci_html_use_css,
                            'link'         => $this->vci_link);
			
			$params = preg_split('%\s+%',$params); //Split at one or more space-character
			foreach ($params as $key => $val) {
				$temp = split("=",$val);
				// Cleaned this up to only call preg_replace once
				$search = array('%\"(.*)\"%','%\'(.*)\'%','%&quot;(.*)&quot;%');
				$replace = "$1";
				$this->params[$temp[0]] = preg_replace($search,$replace,$temp[1]); // Remove ", HTML " (=&quot;), and '
				if (strtolower($temp[0]) != 'src'){ $this->params[$temp[0]] = strtolower($this->params[$temp[0]]); } // Set everything to lower case, except src
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
		  $sql = $this->db->prepare("SELECT * FROM $this->vci_cache_table WHERE id = %d",$id);
		  $file = $this->db->get_row($sql);
			$this->db->query("DELETE FROM $this->vci_cache_table WHERE id=$file->id");
			
			if (is_file($this->vci_temp_dir.$file->cachfile)){
				unlink($this->vci_temp_dir.$file->cachfile);
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
     * @param String $last_modified Optional unix timestamp of last modified date of original file
     * @return Array|Boolean Returns an array containing the db info and the code, or returns false on failure
     */
		function vci_get_cache_file($last_modified = false){
			$sql = $this->db->prepare("SELECT id,path,UNIX_TIMESTAMP(last_modified) AS last_modified, cachefile FROM $this->vci_cache_table WHERE path = '%s' AND params = '%s'",$this->params['src'],$this->vci_serialize_params());

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

				if ($code = @file_get_contents($this->vci_temp_dir.$file->cachefile)){
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
				// Double check cache file.
				if (file_get_contents($path) == $file){
				  return true;
				} else {
				  unlink($path);
				  return false;
				}
			} else {
				return false;
			}
		}

  	/**
     * Stores a file in the cache database, then filesystem
     * 
     * @param String $file Contents of file to store
     * @param String $last_modified Unix timestamp of last modified time of the original file
     * @return Boolean On success or failure
     */
		function vci_cache_file($file){
			$params = $this->vci_serialize_params();
			$cachefile = 'vci_'.md5($file);
			
			$sql = $this->db->prepare("INSERT INTO $this->vci_cache_table (path,params,last_modified,cachefile) VALUES ('%s','%s',FROM_UNIXTIME(%d),%s)",$this->params['src'],$params,$this->last_modified,$cachefile);
			if ($this->db->query($sql)){
				$insert_id = $this->db->insert_id;
				
				if ($this->vci_write_cache_file($this->vci_temp_dir.$cachefile,$file)){
					return true;
				} else {
					$this->db->query($this->db->prepare("DELETE FROM $this->vci_cache_table WHERE id=%d",$insert_id));
					return false;
				}
			} else {
				return false;
			}
		}

  	/**
  	 * Adds the options to the database when activating the plugin, also adds database table for cache information
  	 */
		function vci_activate(){
			add_option('vci_version', $this->vci_version);
			add_option('vci_use_caching', 'yes', 'Use caching for generated HTML? You can always override inline');
			$which_vim = exec('which vim');
			$vim = (strlen($which_vim) > 0) ? $which_vim : 'vim';
			add_option('vci_vim_path', $vim, 'Path to vim');
			add_option('vci_default_path', ABSPATH);
			
			// Figure out the default download path using ABSPATH and the $_SERVER[DOCUMENT_ROOT]
			$vci_default_download_path = $_SERVER['HTTP_HOST'].str_replace($_SERVER['DOCUMENT_ROOT'],'',ABSPATH);
			
			add_option('vci_default_download_path', $vci_default_download_path);
			add_option('vci_showsyntax', 'no', 'Show syntax by default');
			add_option('vci_scroll','no','Use scrolling by default');
			add_option('vci_scrollheight', '200px', 'Default height of scroll window');
			add_option('vci_html_use_css', 'yes', 'Use CSS by default');
			add_option('vci_link', 'yes', 'Link by default');

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
			delete_option('vci_html_use_css');
			delete_option('vci_link');

			$this->vci_clear_cache();
			$this->vci_remove_cache_table();
		}

  	/**
     * Adds the database table for cache information
     */
		function vci_add_cache_table(){
			$sql = "CREATE TABLE ".$this->vci_cache_table." (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
	              path varchar(255) DEFAULT '' NOT NULL,
	              params varchar(255) NOT NULL,
	  	          last_modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  	          cachefile char(36) NOT NULL,
	              PRIMARY KEY  id (id),
                KEY path_params (path,params),
                KEY mtime (last_modified)
	              )";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
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
  	 * Adds the css file into the head of the blog pages
  	 */
		function vci_add_css(){
			echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/vim-color-improved/css/vci-style.css" />' . "\n";
		}
		
		/**
		 * Adds the css file into the head of the admin page
		 */
		function vci_add_admin_css(){
		  echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/vim-color-improved/css/vci-admin.css" />' . "\n";
		}

  	/**
  	 * Adds the options page to the admin interface
  	 */
		function vci_add_pages(){
			add_options_page('Vim Color Improved Options', 'Vim Color Improved', 8, 'vim-color-improved.php', array(&$this, 'vci_option_page'));
			add_management_page('Vim Color Improved Cache Management', 'Vim Color Improved Cache', 8, 'vim-color-improved.php', array(&$this, 'vci_manage_page'));
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
  	 * @return Array|Boolean Array of objects containing the files in the cache dir or false if the cache dir is empty
  	 */
		function vci_files_cached_list(){
			$sql = 'select id,path,params,last_modified,cachefile from '.$this->vci_cache_table;
			$cached_files = $this->db->get_results($sql);
			
			foreach ($cached_files as $file){
				if (is_file($this->vci_temp_dir.$file->cachefile)){
					$return[] = $file;
				} else {
					$this->vci_remove_cache_file($file->id);
				}
			}
			
			if (count($return) > 0){
				return $return;
			} else {
				return false;
			}
		}

  	/**
  	 * @return Integer Number of files in the cache dir
  	 */
		function vci_files_cached(){
			if (($cache_files = $this->vci_files_cached_list()) !== false){
				return count($cache_files);
			} else {
				return 0;
			}
		}

  	/**
  	 * Clears all files from the cache.
  	 */
		function vci_clear_cache(){
			if (($filelist = $this->vci_files_cached_list()) !== false){
				foreach ($filelist as $file){
					$this->vci_remove_cache_file($file->id);
				}
			}
		}

  	/**
  	 * Gets file modified time. Works on local and remote files
  	 * 
  	 * @param String $path Filesystem path or URI of a file
  	 * @return String Unix timestamp of last modified time
  	 */
		function vci_filemtime($path){
			if(substr($this->params['src'],0,4) == 'http'){
				return filemtime_remote($path);
			} else {
				return  filemtime($path);
			}
		}
	
  	/**
     * Returns a serialized set of parameters for storage in the database. Includes all the parameters that affect the vim output
     *
     * @return String Serialized parameters for storage in the database
     */
		function vci_serialize_params(){
			$return = array();
		  // We only include the parameters here that would affect the vim output.
			$params = array('lines'        => $this->params['lines'],
			                'html_use_css' => $this->params['html_use_css']);
			foreach ($params as $key => $val){
				if (is_array($val)){
					$val = join(",",$val);
				}
				if (strlen($val) > 0){
					$return[] = $key."=".$val;
				}
			}
			return join(';',$return);
		}
	
  	/**
  	 * Defines the options page for the admin interface
  	 */
		function vci_option_page(){
			include('pages/vim-color-improved-options.php'); // Mixed HTML/PHP for page is in an included file
		}
		
		/**
		 * Defines the management page for the admin interface
		 */
		function vci_manage_page(){
		  if (isset($_POST['vci_action'])){
				switch ($_POST['vci_action']){
					case 'vci_clear_cache':
				    $this->vci_clear_cache();
				    break;
					
					case 'vci_clear_one':
					  $this->vci_remove_cache_file((int)$_POST['vci_cache_id']);
					  break;
				}
			}
		  include('pages/vim-color-improved-manage.php');
		}
		
		/**
		 * Adds the button to tinymce
		 */
		function vci_add_mce_button($buttons){
		  array_push($buttons,'vci_box');
		  return $buttons;
		}
		
		/**
		 * Adds the tinymce plugin
		 */
		function vci_add_mce_plugin($plugins){
		  array_push($plugins,'vci');
		  return $plugins;
		}
	}
}

if (class_exists("vim_color_improved")) {
	$vim_color_improved = new vim_color_improved();
}

?>
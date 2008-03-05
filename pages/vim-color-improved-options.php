<div class="wrap">
	<h2>Vim Color Improved</h2>
	<h3 style="border-bottom:1px solid #ccc;">Options</h3>
	<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<p><strong>Path to vim:</strong> <input type="text" name="vci_vim_path" value="<?php echo $this->vci_vim_path; ?>" /></p>
		
		<p><strong>Default Path:</strong> <input type="text" name="vci_default_path" value="<?php echo $this->vci_default_path; ?>" /></p>
		<p>This is default path for code files using a relative src parameter.</p>
		
		<p><strong>Default Download Path:</strong> http://<input type="text" name="vci_default_download_path" value="<?php echo $this->vci_default_download_path; ?>" /></p>
		<p>This is the download path for default files. If this is set incorrectly, your download links will not work properly.</p>
		
		<p><strong>Use caching by default:</strong>
			<?php echo $this->vci_select('vci_use_caching',$this->vci_use_caching); ?>
		</p>
		<p>Override for individual code blocks by passing the cache=yes|no parameter.</p>
		
		<p><strong>Show syntax by default:</strong>
			<?php echo $this->vci_select('vci_showsyntax',$this->vci_showsyntax); ?>
		</p>
		<p>Override for individual code blocks by passing the showsyntax=yes|no parameter.</p>
		
		<p><strong>Scroll by default:</strong>
			<?php echo $this->vci_select('vci_scroll',$this->vci_scroll); ?>
		</p>
		<p>Override for individual code blocks by passing the scroll=yes|no parameter.</p>
		
		<p><strong>Default scroll height:</strong> <input type="text" name="vci_scrollheight" value="<?php echo $this->vci_scrollheight; ?>" /></p>
		<p>Override for individual code blocks by passing the scrollheight=<i>valid css height</i> parameter.</p>
		
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
		</p>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="vci_use_caching,vci_vim_path,vci_default_path,vci_default_download_path,vci_showsyntax,vci_scroll,vci_scrollheight" />
	</form>
	
	<h3 style="border-bottom:1px solid #ccc;">Cache Information</h3>
	</p><?php echo $this->vci_files_cached(); ?> Files are currently cached.</p>
	<?php
	if ((count($cache_files = $this->vci_files_cached_list())) > 0) {
		?>
	<table>
	  <tr style="background:#eee;">
	  	<td><strong>File</strong></td>
	  	<td><strong>With Parameters</strong></td>
	  	<td><strong>Original File Last Modified</strong></td>
	  </tr>
	  <?php
	  $cache_files = $this->vci_files_cached_list();
	  foreach ($cache_files as $file){
	  	echo '<tr><td>'.$file->path.'</td><td>'.$file->params.'</td><td>'.$file->last_modified.'</td></tr>';
	  }
	  ?>
	</table>
	<?php } ?>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<p class="submit"><input type="submit" name="vci_clear_cache" value="Clear Cache" /></p>
	<input type="hidden" name="vci_action" value="vci_clear_cache" />
	</form>
	<pre>
	<?php print_r($this->vci_temp_dir); ?>
	</pre>
</div>
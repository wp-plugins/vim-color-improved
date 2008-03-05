<div class="wrap">
	<h2>Vim Color Improved Options</h2>
	<form method="post" action="options.php" class="vci">
		<?php wp_nonce_field('update-options'); ?>
		<fieldset>
		  <legend>Setup Options</legend>
  		<p><strong>Path to vim:</strong> <input type="text" name="vci_vim_path" value="<?php echo $this->vci_vim_path; ?>" /></p>
  		
  		<p><strong>Default Path:</strong> <input type="text" name="vci_default_path" value="<?php echo $this->vci_default_path; ?>" /></p>
  		<p>This is default path for code files using a relative src parameter.</p>
  		
  		<p><strong>Default Download Path:</strong> http://<input type="text" name="vci_default_download_path" value="<?php echo $this->vci_default_download_path; ?>" /></p>
  		<p>This is the download path for default files. If this is set incorrectly, your download links will not work properly.</p>
		</fieldset>
		
		<p></p>
		
		<fieldset>
		  <legend>Code Display Options</legend>
  		<p><strong>Use caching by default:</strong>
  			<?php echo $this->vci_select('vci_use_caching',$this->vci_use_caching); ?>
  		</p>
  		<p>Override for individual code blocks by passing the cache=yes|no parameter.</p>
  		
  		<p><strong>Use css by default:</strong>
  			<?php echo $this->vci_select('vci_html_use_css',$this->vci_html_use_css); ?>
  		</p>
  		<p>Override for individual code blocks by passing the html_use_css=yes|no parameter.</p>
  		
  		<p><strong>Link by default:</strong>
  			<?php echo $this->vci_select('vci_link',$this->vci_link); ?>
  		</p>
  		<p>Override for individual code blocks by passing the link=yes|no parameter.</p>
  		
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
		</fieldset>
		
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
		</p>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="vci_use_caching,vci_html_use_css,vci_vim_path,vci_default_path,vci_default_download_path,vci_showsyntax,vci_scroll,vci_scrollheight,vci_link" />
	</form>
</div>

<?php
$which_vim = exec('which vim');
var_dump($path_to_vim);
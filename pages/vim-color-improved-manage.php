<script type="text/javascript">
function confirmClearCache(form,text) {
  if (confirm("Remove "+text+" from the cache?")) {
    form.submit;
  } else {
    return false;
  }
}
</script>

<div class="wrap">
 <h2>Vim Color Improved Cache Management</h3>
  </p><?php echo $this->vci_files_cached(); ?> Files are currently cached.</p>
  <?php
  if(($cache_files = $this->vci_files_cached_list()) !== false){
  	?>
    <table class="widefat">
    <thead>
      <tr style="background:#eee;">
        <th>&nbsp;</th>
    	  <th><strong>File</strong></th>
    	  <th><strong>With Parameters</strong></th>
    	  <th><strong>Original File Last Modified</strong></th>
      </tr>
    </thead>
      <?php foreach ($cache_files as $file){ ?>
      <tr>
        <td>
          <form method="post"
          action="<?php echo $_SERVER["REQUEST_URI"]; ?>"
          onsubmit="return confirmClearCache(this.form,'<?php echo $file->path; ?>')">
          <input type="hidden" name="vci_action" value="vci_clear_one" />
          <input type="hidden" name="vci_cache_id" value="<?php echo $file->id; ?>" />
          <input type="submit" value="Clear" />
          </form>
        </td>
        <td><?php echo $file->path; ?></td>
        <td><?php echo $file->params; ?></td>
        <td><?php echo $file->last_modified; ?></td>
      </tr>
      <?php } ?>
  </table>
  <?php } ?>
  <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <p class="submit"><input type="submit" name="vci_clear_cache" value="Clear Cache" /></p>
    <input type="hidden" name="vci_action" value="vci_clear_cache" />
  </form>
</div>
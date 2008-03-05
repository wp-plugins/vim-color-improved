<?php
/**
 *  Additional Functions
 */

/**
 * sys_get_temp_dir
 * 
 * Functional replacement for PHP 5's sys_get_temp_dir function - copied from PHP.net
 */
if ( !function_exists('sys_get_temp_dir') ) {
  // Based on http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/
  function sys_get_temp_dir() {
    if ( !empty($_ENV['TMP']) ) {
      return realpath( $_ENV['TMP'] );
    } else if ( !empty($_ENV['TMPDIR']) ) {
      return realpath( $_ENV['TMPDIR'] );
    } else if ( !empty($_ENV['TEMP']) ) {
      return realpath( $_ENV['TEMP'] );
    } else {
      $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
      if ( $temp_file ) {
        $temp_dir = realpath( dirname($temp_file) );
        unlink( $temp_file );
        return $temp_dir;
      } else {
        return FALSE;
      }
    }
  }
}

/**
 * Gets remote file modified time - taken from php.net, thanks to solarijj at gmail dot com
 * 
 * @param String $uri uri to find mtime
 * @return String Unix timestamp of last modified time
 */
if (!function_exists('filemtime_remote')){
  function filemtime_remote( $uri ){
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
}
?>
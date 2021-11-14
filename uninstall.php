<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'woo-reader';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
 $tables = array(
        'wooreader_settings' ,
        'wooreader_documents'  ,
        'wooreader_woocommerce_link'
    );
// drop a custom database table
global $wpdb;
foreach ($tables as $key => $tableName) {
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . $tableName);
}
//BACKUP UPLOADS FOLDER

$backup = WP_CONTENT_DIR . '/uploads/wooreader_backup/';
if(!is_dir($backup)) {
	mkdir($backup);
}
$zipFile = $backup . "wooreader_". date('YmdHis') .".zip";
$zipArchive = new ZipArchive();

if ($zipArchive->open($zipFile, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== true)
    die("Failed to create archive\n");

if ($zipArchive->status != ZIPARCHIVE::ER_OK)
    echo "Failed to write files to zip\n";

$zipArchive->close();

//DELETE UPLOADS FOLDER

$uploads =  WP_CONTENT_DIR . '/uploads/wooreader/';
$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($uploads),
  RecursiveIteratorIterator::CHILD_FIRST
);

$excludeDirsNames = array();
$excludeFileNames = array();

foreach($it as $entry) {
  if ($entry->isDir()) {
    if (!in_array($entry->getBasename(), $excludeDirsNames)) {
      try {
        rmdir($entry->getPathname());
      }
      catch (Exception $ex) {
        // dir not empty
      }
    }
  }
  elseif (!in_array($entry->getFileName(), $excludeFileNames)) {
    unlink($entry->getPathname());
  }
}

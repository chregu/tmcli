<?php

include_once("settings.php");

$bkdir = $outdir.$bkdir.".inProgress";
if (!isset($argv[1])) {
    die("please specify the directory to delete\n");
}
    
$bkdir = $argv[1];



parseDir($bkdir."/");
DELETE_RECURSIVE_DIRS($bkdir);
function parseDir($dir,$level = 0) {
    global $maxdir;
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if($fileInfo->isDot()) {
            continue;
        }
        if ($fileInfo->isDir()) {
            $oripath = $dir.$fileInfo->getFilename();
            if ($level < 2) {
                print $oripath."\n";
            }
            @unlink($oripath);
            if (file_exists($oripath)) {
            if ($level < $maxdir + 2) {
                parseDir($oripath."/",$level+  1);
            } else {
               // print $oripath ." UNLINKED\n";
            }
            }
            
        }
        
    }
    
}


function DELETE_RECURSIVE_DIRS($dirname,$level = 0)
{ // recursive function to delete 
    // all subdirectories and contents:
    if(is_dir($dirname))$dir_handle=opendir($dirname);
    while(false !== $file=readdir($dir_handle))
    {
        if($file!="." && $file!="..")
        {
            if(!is_dir($dirname."/".$file) || is_link($dirname."/".$file)) {
                unlink ($dirname."/".$file);
            }
            else {
              if ($level < 2) {
                print $dirname."/".$file."\n";
              }
                DELETE_RECURSIVE_DIRS($dirname."/".$file,$level + 1);
            }
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}

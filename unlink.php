<?php

include_once("settings.php");

$bkdir = $bkdir.".inProgress";

$bkdir = "/Volumes/backup/pr_backup.bk/pr_backup.hard";
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
            
            @unlink($oripath);
            if (file_exists($oripath)) {
            if ($level < $maxdir + 2) {
                parseDir($oripath."/",$level+  1);
            } else {
                print $oripath ." UNLINKED\n";
            }
            }
            
        }
        
    }
    
}


function DELETE_RECURSIVE_DIRS($dirname)
{ // recursive function to delete 
    // all subdirectories and contents:
    if(is_dir($dirname))$dir_handle=opendir($dirname);
    while($file=readdir($dir_handle))
    {
        if($file!="." && $file!="..")
        {
            if(!is_dir($dirname."/".$file)) {
                unlink ($dirname."/".$file);
            }
            else {
                DELETE_RECURSIVE_DIRS($dirname."/".$file);
            }
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}

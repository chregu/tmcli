<?php

include_once("settings.php");

//$oldbkdir = '/Volumes/backup/pr_backup.bk/pr_backup.0.full';

parseDir('/',$bkdir,$oldbkdir);

function parseDir($dir,$bkdir,$lastbkdir,$level = 0) {
    global $maxdir;
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if($fileInfo->isDot()) {
            continue;
        }
        if ($fileInfo->isDir()) {
            $oripath = $dir.$fileInfo->getFilename();
            $bkpath = $bkdir.$oripath;
       
            if (file_exists($bkpath)) {
                if ($level < $maxdir + 2) {
                    parseDir($oripath."/",$bkdir,$lastbkdir,$level+  1);
                }
            } else {
                if (!link($lastbkdir.$oripath, $bkdir.$oripath)) {
                print $lastbkdir.$oripath ." => ". $bkdir.$oripath ."\n";
                }
            }
            
        }
        
    }
    
}

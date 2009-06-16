<?php

include_once ("settings.php");

//$oldbkdir = '/Volumes/backup/pr_backup.bk/pr_backup.0.full';
$oldbkdir2 = $outdir . $lastdir;
parseDir('/', $bkdir, $oldbkdir2);

function parseDir($dir, $bkdir, $lastbkdir, $level = 0) {
    global $maxdir;
    if (file_exists($dir) && is_dir($dir)) {
        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            if ($fileInfo->isDir()) {
                $oripath = $dir . $fileInfo->getFilename();
                $bkpath = $bkdir . $oripath;

                if (file_exists($bkpath)) {
                    if ($level < $maxdir + 2) {
                        print " parseDir " . $oripath . "/" . $bkdir ."\n";
                        parseDir($oripath . "/", $bkdir, $lastbkdir, $level + 1);
                    }
                } else {
                    if (!link($lastbkdir . $oripath, $bkdir . $oripath)) {
                            //print "NOT " . $lastbkdir.$oripath ." => ". $bkdir.$oripath ."\n";
                    } else {

                            //print "YES " . $lastbkdir.$oripath ." => ". $bkdir.$oripath ."\n";
                    }
                }

            }

        }
    }

}

<?php

include_once ("settings.php");
include_once ("tmcli.php");

$now = time();
$day = $now - 24 * 3600;
$week = $now - 24 * 3600 * 7 * 2;
$month = $now - 24 * 3600 * 28 * 2;
$dirs = array();

$keep = array();
$del = array();
$tm = new tmcli($outdir);
$inProgressData = $tm->getCurrentInProgressData();
$lastDir = $tm->getLastDir();
$inProgressDir = realpath($outdir."/".$inProgressData['dir']);
foreach (new DirectoryIterator($outdir) as $fileInfo) {
    if ($fileInfo->isDot()) {
        continue;
    }
    if ($fileInfo->isDir()) {
        $name = $fileInfo->getFilename();
        if (strpos($name, ".inProgress") !== false) {
            if ($inProgressDir != realpath($fileInfo->getPathname())) {
              $del[] = $name;
            }
            continue;
        }
       
        $d = new DateTime();
        list($year, $month, $da, $time) = explode("-", $name, 4);
        $d->setDate($year, $month, $da);
        $d->setTime(substr($time, 0, 2), substr($time, 2, 2), substr($time, 4, 2));
        $dirs[$name] = $d;
    }
}

ksort($dirs);
foreach ($dirs as $dir => $date) {
    $t = $date->format("U");
    if ($dir == $lastDir) {
      $m = null;
    } else if ($t < $month) {
        $m = "M" . floor($date->format("W") / 4);

    } else if ($t < $week) {
        $m = "W" . $date->format("W");
    } else if ($t < $day) {
        $m = "D" . $date->format("z");
    } else {
        $m = null;
    }
    if ($m) {
        if (isset($keep[$m])) {
            $del[] = $dir ;
        } else {
            $keep[$m] = $dir;
        }
    }

}

foreach ($del as $dir) {

    $bkdir = $outdir . $dir;

    print "DELETE $bkdir \n";
    parseDir($bkdir . "/");
    DELETE_RECURSIVE_DIRS($bkdir);
}
function parseDir($dir, $level = 0) {
    global $maxdir;
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if ($fileInfo->isDot()) {
            continue;
        }
        if ($fileInfo->isDir()) {
            $oripath = $dir . $fileInfo->getFilename();
            if ($level < 1) {
                print $oripath . "\n";
            }
            @unlink($oripath);
            if (file_exists($oripath)) {
                if ($level < $maxdir + 2) {
                    parseDir($oripath . "/", $level + 1);
                } else {
                    // print $oripath ." UNLINKED\n";
                }
            }

        }

    }

}

function DELETE_RECURSIVE_DIRS($dirname, $level = 0) { // recursive function to delete
    // all subdirectories and contents:
    if (is_dir($dirname))
        $dir_handle = opendir($dirname);
    while (false !== $file = readdir($dir_handle)) {
        if ($file != "." && $file != "..") {
            if (!is_dir($dirname . "/" . $file) || is_link($dirname . "/" . $file)) {
                unlink($dirname . "/" . $file);
            } else {
                if ($level < 2) {
                    print $dirname . "/" . $file . "\n";
                }
                DELETE_RECURSIVE_DIRS($dirname . "/" . $file, $level + 1);
            }
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}

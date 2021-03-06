<?php


if (isset($argv[1]) && $argv[1] == 'cron') {
    $logg = true;
} else {
    $logg = false;
}
$mode = 0;
include_once ("settings.php");
include_once ("tmcli.php");
chdir(dirname(__FILE__));

if (file_exists($outdir)) {
    print "$outdir already mounted\n";
} else if (file_exists("/Volumes/My Book II/backup.sparsebundle")) {
    system('sudo -H -u chregu bash  mountBackup.sh "/Volumes/My Book II/backup.sparsebundle"');
} else if (file_exists("/Volumes/share/backupe.sparsebundle")) {
    system('sudo -H -u chregu bash  mountBackup.sh "/Volumes/share/backupe.sparsebundle" ');
} else {
    print "no backup destination found";
    exit(2);
}

passthru('vsdbutil -a /Volumes/backup/');
$tm = new tmcli($outdir);
if (!$tm->lock()) {
    print "backup locked";
    exit(1);
}

$tm->dryrun = $dryrun;
$tm->logg = $logg;

$lastid = $tm->getLastId();
if (!$lastid) {
    $mode = 1;
    $lastid = 1;
}
$lastdir = $tm->getLastDir();

$rdirs = $tm->getExcludedPathsByApple();
$rdirs2 = array();
//excluded by apple

$modifieds = $tm->getModifiedDirs();



$dirs = $modifieds['dirs'];
$lastid =  $modifieds['newlastid'];
$mode =  $mode | $modifieds['mode'];
$devuuid = $modifieds['devuuid'];

print $lastid ."\n";
ksort($dirs);
$rdiffdat = "";
foreach ($dirs as $d => $q) {
    $ds = explode("/", trim($d, "/"));
    if ($ds[0] == 'Volumes') {
        continue;
    }
    $path = "/";
    foreach ($ds as $k => $p) {
        $path .= $p . "/";
        if (!isset($rdirs[$path]) || (isset($rdirs[$path . "*/"]) && $rdirs[$path . "*/"] === false)) {
            $rdirs[$path] = true;
            if (isset($rdirs[$path . "*/"]) && $rdirs[$path . "*/"] === false) {
                unset($rdirs[$path . "*/"]);

            }
            $rdirs2[$path . "*"] = true;
            $rdirs2 = array_merge(array(
                    $path . "*/" => false
            ), $rdirs2);

        }
        if ($k > $maxdir) {
            break;
        }
    }
    //$rdiffdat .= "- $path**\n";


    if ($k > $maxdir || $q & 1) {
        if (!isset($rdirs["$path**"])) {
            $rdirs["$path**"] = true;
        }
    } else {
        if (!isset($rdirs["$path*/"])) {
            //       $rdirs["$path*/"] = false;
        //     $rdirs["$path*"] = true;
        }
    }
}

$rdiffdat = "";
print "mode : " .$mode ."\n";
if ($mode > 0) {
    print "doing a full backup \n";
    $rdiffdat .= "+ /**\n";

} else {
    foreach ($rdirs as $k => $v) {
        if ($v) {
            $rdiffdat .= "+ ";
        } else {
            $rdiffdat .= "- ";
        }
        $rdiffdat .= "$k\n";
    }
    $rdiffdat .= "#####\n";
    array_reverse($rdirs2);

    foreach ($rdirs2 as $k => $v) {
        if ($v) {
            $rdiffdat .= "+ ";
        } else {
            $rdiffdat .= "- ";
        }
        $rdiffdat .= "$k\n";
    }
    $rdiffdat .= "- /*/\n";
    $rdiffdat .= "+ /*\n";
    $rdiffdat .= "- /**\n";

}
file_put_contents("./rdiff.dat", file_get_contents("./backuprdiff.dat") . $rdiffdat);

$tm->runRsync();

if (!$dryrun) {
    print $lastid . "\n";
    $tm->setLastId($lastid);
    $tm->setLastUUID($devuuid);

    if (!$mode ) {
        print "Create Links...   \n";
        $tm->makeLinks();
    }

    $tm->moveBkDir();

    $tm->writeLastDir();
}

$tm->cleanUp();

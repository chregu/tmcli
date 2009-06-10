<?php



passthru('sudo -u chregu hdiutil attach -noautofsck -noverify /Volumes/My\ Book\ II/backup.sparsebundle');
if (isset($argv[1]) && $argv[1] == 'cron') {
    $logg = true;
} else {
    $logg = false;
}
$full = false;
include_once ("settings.php");
include_once ("tmcli.php");



$tm = new tmcli($outdir);
if (!$tm->lock()) {
    die("backup locked");
}

$tm->dryrun = $dryrun;
$tm->logg = $logg;

$lastid = $tm->getLastId();
$lastuuid = $tm->getLastUUID();
if (!$lastid) {
    $full = true;
    $lastid = 1;
}

$lastdir = $tm->getLastDir();
$orilastid = $lastid;

$dirs = array();
$newlastid = null;
$rdirs = $tm->getExcludedPathsByApple();
$rdirs2 = array();
//excluded by apple


while (!$newlastid) {
    print $lastid . "\n";
    //   print "la ". $lastid ."\n";
    $out = `./fse 1 $lastid`;
    $events = explode("\n", trim($out));

    foreach ($events as $e) {

        $e = trim($e);
        if ($e) {
            $es = explode(":", $e, 3);
            if (isset($es[2])) {
                $es[2] = "/" . $es[2];
            }
            if ($es[1] == 0) {
                $lastid = $es[0];
                $dirs[$es[2]] = 0;
                if (substr($es[2], 0, 9) == "/Volumes/") {
                    continue;
                }
            } else if ($es[1] & 1) {
                $dirs[$es[2]] = $es[1];

            } else if ($es[1] == 16) {

                $newlastid = $es[0];
                // if fsevent reports the same id as initially asked for, there may something be wrong and we should start from the beginning
                if ($orilastid == $newlastid) {
                    $lastid = 1;
                    $newlastid = null;
                }
            } else if ($es[1] == 256) {

                $devuuid = $es[0];
                if ($lastuuid != $devuuid) {
                    print $lastuuid."\n";
                    print $devuuid."\n";
                    $full = true;
                    break;
                }

            }
        }
    }

}

$lastid = $newlastid;
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
if ($full) {
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

    $rdiffdat .= "+ /*\n";
    $rdiffdat .= "- /**\n";

}
file_put_contents("./rdiff.dat", file_get_contents("./backuprdiff.dat") . $rdiffdat);

$tm->runRsync();

if (!$dryrun) {
    print $lastid . "\n";
    $tm->setLastId($lastid);
    $tm->setLastUUID($devuuid);

    $bkdir2 = $bkdir;
    $bkdir = realpath($bkdir . ".inProgress");
    if (!$full) {
        print "Create Links...   \n";
        include ("mklinks.php");
    }

    $tm->moveBkDir();

    $tm->writeLastDir();
}




$tm->cleanUp();

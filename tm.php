<?php
$logg = true;
include_once("settings.php");

$lastid = (int) file_get_contents("$outdir/lastid.dat");
$oldbkdir = basename(trim(file_get_contents("$outdir/lastdir.dat")));
$orilastid = $lastid;
if (!$lastid) {
    $lastid = 1;
}

$dirs = array();
$newlastid = null;
$rdirs = array();
$rdirs2 = array();
//excluded by apple
$out =  `mdfind "com_apple_backup_excludeItem = 'com.apple.backupd'"`;
$apple = explode("\n",trim($out));


// to be implemented
// defaults read /System/Library/CoreServices/backupd.bundle/Contents/Resources/StdExclusions ContentsExcluded

foreach ($apple as $a) {
    $rdirs[$a] = false;
}

while (!$newlastid) {
    print $lastid ."\n";
    //   print "la ". $lastid ."\n";
    $out = `./fse 1 $lastid`;
    $events = explode("\n",trim($out));
    
    foreach($events as $e) {
        $e = trim($e);
        if ($e) {
            $es = explode(":",$e,3);
            if (isset($es[2])) {
                $es[2] = "/" .$es[2];
            }
            if ($es[1] == 0) {
                $lastid = $es[0];
                $dirs[$es[2]] = 0;
                if (substr($es[2],0,9) == "/Volumes/") {
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
                file_put_contents($outdir."/uuid.dat",$devuuid);
             
            }
        }
    }     
    
    
}


$lastid = $newlastid;
ksort($dirs);
$rdiffdat = "";
foreach($dirs as $d => $q) {
    
    
    $ds = explode("/",trim($d,"/"));
    if ($ds[0] == 'Volumes') {
        continue;
    }
    $path = "/";
    foreach ($ds as $k=> $p) {
        $path .= $p ."/";
        if (!isset($rdirs[$path]) || (isset($rdirs[$path."*/"]) && $rdirs[$path ."*/"] === false)) {
            $rdirs[$path] = true;
            if (isset($rdirs[$path."*/"]) && $rdirs[$path ."*/"] === false) {
                unset($rdirs[$path ."*/"]);
                
            }
            $rdirs2[$path."*"] = true;
            $rdirs2 = array_merge(array($path."*/" => false),$rdirs2);
            
            
        }
        if ($k > $maxdir) { break;}
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
foreach ($rdirs as $k => $v) {
    if ($v) {
        $rdiffdat .="+ ";
    } else {
        $rdiffdat .="- ";
    }
    $rdiffdat .="$k\n";
}
$rdiffdat .="#####\n";
array_reverse($rdirs2);
foreach ($rdirs2 as $k => $v) {
    if ($v) {
        $rdiffdat .="+ ";
    } else {
        $rdiffdat .="- ";
    }
    $rdiffdat .="$k\n";
}


$rdiffdat .= "- /**\n";

file_put_contents("./rdiff.dat",file_get_contents("./backuprdiff.dat").$rdiffdat);

$linkdir = "../".basename($oldbkdir);
$cmd = "rsync $dryrun --out-format='%i %n%L' -x --human-readable -H -A -X -E -a  --link-dest=$linkdir --delete   --stats  --include-from=./rdiff.dat / $bkdir".".inProgress";
print "Backing up ...\n";
if ($logg) {
 ob_start();

}
print $cmd ."\n";

passthru($cmd);
if (!$dryrun) {
    print $lastid ."\n";
    file_put_contents("$outdir/lastid.dat",$lastid);
    file_put_contents("$bkdir.inProgress/lastid.dat",$lastid);

    file_put_contents($bkdir.".inProgress/uuid.dat",$devuuid);

    $bkdir2 = $bkdir;
    $bkdir = realpath($bkdir.".inProgress");
    print "Create Links...   \n";
    include("mklinks.php");
    rename($bkdir,$bkdir2);
    file_put_contents("$outdir/lastdir.dat",basename($bkdir2));
    file_put_contents("$bkdir2/lastdir.dat",basename($bkdir2));

}


if ($logg) {
$foo = ob_get_contents();

file_put_contents($bkdir2."/tm.log",$foo);

print $foo;
}

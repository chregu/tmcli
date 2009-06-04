<?php
$outdir = "/Volumes/backup/pr_backup.bk/test/";
$maxdir = 4;
$dryrun = "";
$lastid = (int) file_get_contents("$outdir/lastid.dat");
$orilastid = $lastid;
//make sure we have changed something
touch ("./rdiff.dat");
if (!$lastid) {
    $lastid = 1;
}

$dirs = array();
$newlastid = null;
$rdirs = array();

//excluded by apple
$out =  `mdfind "com_apple_backup_excludeItem = 'com.apple.backupd'"`;
$apple = explode("\n",trim($out));


// to be implemented
// defaults read /System/Library/CoreServices/backupd.bundle/Contents/Resources/StdExclusions ContentsExcluded

foreach ($apple as $a) {
    $rdirs[$a] = false;
}

while (!$newlastid) {
 //   print "la ". $lastid ."\n";
    $out = `./fse 1 $lastid`;
    
    $events = explode("\n",trim($out));
    
    foreach($events as $e) {
        $es = explode(":",trim($e)); 
        if ($es[2] == 0) {
       //     $lastid = $es[0];
            $dirs[$es[1]] = 0;
            
        } else if ($es[2] & 1) {
            $dirs[$es[1]] = $es[2];
            
        } else if ($es[2] == 16) {
           

            $newlastid = $es[0];
            // if fsevent reports the same id as initially asked for, there may something be wrong and we should start from the beginning
             if ($orilastid == $newlastid) {
                $lastid = 1;
                $newlastid = null;
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
            if (isset($rdirs[$path."*/"])) {
                unset($rdirs[$path ."*/"]);
            }
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
            
            $rdirs["$path*/"] = false;
            $rdirs["$path*"] = true;
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


$rdiffdat .= "- /**\n";

file_put_contents("./rdiff.dat",file_get_contents("./backuprdiff.dat").$rdiffdat);
passthru("rsync $dryrun --out-format='%i %n%L' -x --human-readable -H -a  --link-dest=../pr_backup.1.full --delete --partial   --stats  --include-from=./rdiff.dat / $outdir");
if (!$dryrun) {
    print $lastid ."\n";
    file_put_contents("$outdir/lastid.dat",$lastid);
    
    
}

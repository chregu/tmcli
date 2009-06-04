<?php
$outdir = "/Volumes/backup/pr_backup.bk/test/";
$lastid = file_get_contents("./lastid.dat");

$out = `./fse 1 $lastid`;

$events = explode("\n",trim($out));
$dirs = array();

foreach($events as $e) {
    $es = explode(":",trim($e)); 
    if ($es[2] == 0) {
        $dirs[$es[1]] = 0;
    } else if ($es[2] & 1) {
        $dirs[$es[1]] = $es[2];
    } else if ($es[2] == 16) {
        $lastid = $es[0];
    }
}        
ksort($dirs);
$rdiffdat = "";
foreach($dirs as $d => $q) {
    $ds = explode("/",trim($d,"/"));
    $path = "/";
    foreach ($ds as $p) {
        $path .= $p ."/";
        $rdiffdat .= "+ $path\n";
    }
    $rdiffdat .= "- $path*/\n";   
    $rdiffdat .= "+ $path*\n";
       
}

$rdiffdat .= "- /**\n";

file_put_contents("./lastid.dat",$lastid);
print $lastid."\n";
file_put_contents("./rdiff.dat",file_get_contents("./backuprdiff.dat").$rdiffdat);

passthru("rsync  --out-format='%i %n%L' -x --human-readable -H -a  --delete --partial   --stats  --include-from=./rdiff.dat / $outdir");

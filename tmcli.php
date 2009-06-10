<?php

class tmcli {

    protected $bkrootdir = "/Volumes/backup/pr_backup.bk/";
    protected $bkdir = null;
    public $maxdir = 4;
    public $dryrun = "";
    public $logg = false;

    protected $lastDir = null;

    function __construct($bkrootdir) {
        $this->bkrootdir = $bkrootdir;
        $this->bkdir = $bkrootdir . "/" . date("Y-m-d-His") . ".inProgress";
    }

    public function getLastUUID() {
        //TODO read from $this->bkdir
       return  file_get_contents($this->bkrootdir . "/uuid.dat");

    }

    public function setLastUUID($devuuid) {
        file_put_contents($this->bkrootdir . "/uuid.dat", $devuuid);
        file_put_contents($this->bkdir . "/uuid.dat", $devuuid);
    }

    public function getLastId() {
          //TODO read from $this->bkdir
        if (file_exists($this->bkrootdir . "/lastid.dat")) {
            return (int) file_get_contents($this->bkrootdir . "/lastid.dat");
        } else {
            return false;
        }
    }

    public function setLastId($lastid) {
        file_put_contents($this->bkrootdir . "/lastid.dat", $lastid);
        file_put_contents($this->bkdir . "/lastid.dat", $lastid);
    }

    public function getLastDir() {
        if (!$this->lastDir) {
            $this->lastDir = basename(trim(file_get_contents($this->bkrootdir . "/lastdir.dat")));
        }

        return $this->lastDir;
    }

    public function writeLastDir() {
        file_put_contents($this->bkrootdir . "/lastdir.dat", basename($this->bkdir));
        file_put_contents($this->bkdir . "/lastdir.dat", basename($this->bkdir));
    }

    public function getExcludedPathsByApple() {
        $out = `mdfind "com_apple_backup_excludeItem = 'com.apple.backupd'"`;
        $apple = explode("\n", trim($out));

        // to be implemented
        // defaults read /System/Library/CoreServices/backupd.bundle/Contents/Resources/StdExclusions ContentsExcluded


        foreach ($apple as $a) {
            $rdirs[$a] = false;
        }
        return $rdirs;
    }

    public function moveBkDir() {
        $bkdir2 = preg_replace("#.inProgress$#", "", $this->bkdir);
        rename($this->bkdir, $bkdir2);
        $this->bkdir = $bkdir2;

    }

    public function runRsync() {
        $linkdir = "../" . $this->getLastDir();
        $cmd = "rsync " . $this->dryrun . " --out-format='%i %n%L' -x --human-readable -H -A -X -E -a  --link-dest=$linkdir --delete   --stats  --include-from=./rdiff.dat / " . $this->bkdir;
        print "Backing up ...\n";
        if ($this->logg) {
            ob_start();

        }
        print $cmd . "\n";

        passthru($cmd);

    }

    public function cleanUp() {
        if ($this->logg) {
            $foo = ob_get_contents();

            file_put_contents($this->bkdir . "/tm.log", $foo);

            print $foo;
        }
    }

}

<?php

class tmcli {

    protected $bkrootdir = "/Volumes/backup/pr_backup.bk/";
    protected $bkdir = null;
    public $maxdir = 4;
    public $dryrun = "";
    public $logg = false;
    protected $lockfile = null;
    protected $lastDir = null;

    function __construct($bkrootdir) {

        if (!file_exists($bkrootdir) || !is_dir($bkrootdir)) {
            throw new Exception($bkrootdir . " does not exist or is not a directory");
        }

        if (posix_geteuid() !== 0) {
            //throw new Exception("You should be root to make useful backups");
        }
        $this->bkrootdir = $bkrootdir;

        $this->bkdir = $bkrootdir . "/" . date("Y-m-d-His") . ".inProgress";
        $this->lockfile = $this->bkrootdir . "/inProgress.dat";
    }

    function __destruct() {

    }

    public function lock() {
        $data = $this->getCurrentInProgressData();
        if ($data) {

            //check if process exists
            if (posix_kill($data['pid'],0)) {
                  return false;
            }
        }
        $this->writeLockFile();
        return true;
    }

    public function getCurrentInProgressData() {
         if (file_exists($this->lockfile)) {
            list($pid,$dir) = explode(":",trim(file_get_contents($this->lockfile)),2);
            return array("pid" => $pid,"dir" => $dir);
         } else {
             return null;
         }
    }


    protected function writeLockFile() {
        file_put_contents($this->lockfile, getmypid() . ":" . basename($this->getInProgressBkDir()));
    }

    public function unlock() {
        unlink($this->lockfile);
    }

    public function getLastUUID() {
        //TODO read from $this->bkdir
        return file_get_contents($this->bkrootdir . "/uuid.dat");

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
            return "0";
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

    /**
     * Always gets the bkdir name with .inProgress at the end.
     *
     * @return string
     */
    protected function getInProgressBkDir() {
        //of course, you can do that much better efficient, but this should work anyway
        return $this->getFinalBkDir() . ".inProgress";
    }

    /**
     * Always gets the final bkdir name (without .inProgress)
     *
     * @return string
     */
    protected function getFinalBkDir() {
        return preg_replace("#.inProgress$#", "", $this->bkdir);
    }

    public function moveBkDir() {
        $bkdir2 = $this->getFinalBkDir();
        rename($this->bkdir, $bkdir2);
        $this->bkdir = $bkdir2;

    }

    public function runRsync() {
        $linkdir = "../" . $this->getLastDir();
        print "Backing up ...\n";
        if ($this->logg) {
            ob_start();

        }
        $cmd = $this->getRsyncCommand($this->bkdir, $this->dryrun . " --link-dest=" . $linkdir . " ");
        print $cmd . "\n";

        passthru($cmd);

    }

    protected function getRsyncCommand($bkdir, $additionalOptions = '') {
        $cmd = "nice /opt/local/bin/rsync $additionalOptions --out-format='%i %n%L' -x --human-readable -H -A -X -E -a  --delete   --stats  --include-from=./rdiff.dat / " . $bkdir;
        return $cmd;
    }

    public function cleanUp() {
        if ($this->logg) {
            $foo = ob_get_contents();

            file_put_contents($this->bkdir . "/tm.log", $foo);

            print $foo;
        }
        $this->unlock();
    }

    public function getModifiedDirs() {
        //FIXME: too much spaghetti code, maybe do a new class
        $newlastid = null;
        $lastid = $this->getLastId();
        $lastuuid = $this->getLastUUID();
        $orilastid = $lastid;
        $mode = 0;
        $dirs = array();

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
                            print $lastuuid . "\n";
                            print $devuuid . "\n";
                            $mode = 1;
                            $lastid = 1;
                            continue;
                        }

                    }
                }
            }

        }
        return array(
                'dirs' => $dirs,
                'newlastid' => $newlastid,
                'devuuid' => $devuuid,
                'mode' => $mode
        );

    }

    public function makeLinks() {
        $this->makeLinksInDir('/', $this->bkdir, $this->bkrootdir.  $this->getLastDir());
    }

    protected function makeLinksInDir($dir, $bkdir, $lastbkdir, $level = 0) {

        if (file_exists($dir) && is_dir($dir)) {
        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $oripath = $dir . $fileInfo->getFilename();
                $bkpath = $bkdir . $oripath;
                if ($oripath == '/net' ||
                    $oripath == '/Network' ||
                    $oripath == '/Volumes' ||
                    $oripath == '/automount'
                ) {
                    continue;
                }
          //      print $bkpath ."\n";
                if (file_exists($bkpath)) {
                   // print $bkpath . " exists\n";
                    if ($level < $this->maxdir + 2) {
                    //      print " parseDir " . $oripath . "/" . $bkdir ."\n";
                        $this->makeLinksInDir($oripath . "/", $bkdir, $lastbkdir, $level + 1);
                    }
                } else {
                    if (!@link($lastbkdir . $oripath, $bkpath)) {
                        //FIXME
                        // if file can not be found, but exists on $oripath, then it maybe was moved and not gotten by fse
                            print "NOT " . $lastbkdir.$oripath ." => ". $bkdir.$oripath ."\n";
                    } else {

                 //           print "YES " . $lastbkdir.$oripath ." => ". $bkpath ."\n";
                    }
                }

            }

        }
    }

}


}

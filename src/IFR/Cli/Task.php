<?php

namespace IFR\Cli;

define('CTMODE_NORMAL', 1);
define('CTMODE_FINISH', 2);
define('CTMODE_DIE', 3);
define('CTMODE_HALT', 4);

class Task {

    private $name;
    private $pidfile;
    private $masterpid;
    private $pid;
    private $running = false;
    private $daemon = false;
    private $mode = CTMODE_NORMAL;
    private $children = array();
    private $unique = false;
    private $silent = true;
    private $user = false;
    private $uid = false;
    private $group = false;
    private $gid = false;

    public function __construct()
    {

    }

    public function __destruct()
    {
        if($this->pid == $this->masterpid)
        {
            if(@file_exists($this->pidfile) && $this->getPidfilePid() == getmypid())
            {
                unlink($this->pidfile);
            }
        }
    }

    public function setUser($username)
    {
        $this->user = $username;
        if($user = posix_getpwnam($username))
        {
            $this->uid = $user['uid'];
        }
        else
        {
            exit("Invalid username \n");
        }

    }

    public function setGroup($groupname)
    {
        $this->group = $groupname;
        if($group = posix_getgrnam($groupname))
        {
            $this->gid = $group['gid'];
        }
        else
        {
            exit("Invalid groupname \n");
        }
    }

    public function run()
    {
        if($this->unique && $this->isRunning())
        {
            exit;
        }
        if($this->daemon) $this->spawnDaemon();
        $this->ownership();
        $this->masterpid = getmypid();
        $this->pid = $this->masterpid;
        if($this->unique) $this->createPidFile();
        $this->running = true;
        $this->main();
    }

    protected function ownership()
    {
        if($this->uid) posix_setuid($this->uid);
        if($this->gid) posix_setgid($this->gid);
    }

    protected function interrupt()
    {
        $this->checkPidFile();
    }

    public function setDaemon($bool)
    {
        if(!$this->running)
        {
            $this->daemon = $bool;
        }
        else
        {
            throw new Exception('Can\'t switch to daemon mode while running');
        }
    }

    private function spawnDaemon()
    {
        if(!$this->unique || !$this->pidfile)
        {
            exit("Pidfile and unique required for daemon mode\n");
        }
        echo("fork to back \n");
        $pid = pcntl_fork();
        echo("forked ".getmypid()."\n");
        if($pid != 0)
        {
                exit;
        }
        if($this->silent)
        {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            $GLOBALS['STDIN'] = fopen('/dev/null', 'r');
            $GLOBALS['STDOUT'] = fopen('/dev/null', 'a');
            $GLOBALS['STDERR'] = fopen('/dev/null', 'a');
        }
    }

    protected function spawnChild()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            $this->pid = getmypid();
        }
        else
        {
            $this->children[$pid] = $pid;
        }
        return $pid; // return 0 if isParent, >0 if isChild
    }

    public function getChildrenCount()
    {
        return count($this->children);
    }

    public  function setPidFile($pidfile)
    {
        $this->pidfile = $pidfile;
    }

    public  function setUnique($bool)
    {
        $this->unique = $bool;
    }

    public  function setSilent($bool)
    {
        $this->silent = $bool;
    }

    public function isRunning()
    {
        if(@file_exists($this->pidfile))
        {
            $pid = $this->getPidfilePid();
            if( posix_getsid($pid) )
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function waitChild()
    {
        foreach($this->children as $pid => $val)
        {
            if( pcntl_waitpid($pid,$status,WNOHANG) != 0 )
            {
                unset($this->children[$pid]);
                return $pid;
            }
            else
            {
                return 0;
            }
        }
    }

    public function waitChildren()
    {
        $children = 0;
        foreach($this->children as $pid => $val)
        {
            if( pcntl_waitpid($pid,$status,WNOHANG) != 0 )
            {
                unset($this->children[$pid]);
                $children++;
            }
        }
        return $children;
    }

    protected function createPidFile()
    {
        echo("Create pidfile with pid $this->masterpid \n");
        if(!is_dir(dirname($this->pidfile))) @mkdir(dirname($this->pidfile), 0777, true);
        if(!@file_put_contents($this->pidfile, $this->masterpid))
        {
            exit("Can't create PID file $this->pidfile \n");
        }
    }

    protected function getPidfilePid()
    {
        return trim(file_get_contents($this->pidfile));
    }

    public function checkPidFile()
    {
        if(@file_exists($this->pidfile))
        {
            $pid = $this->getPidfilePid();
            // the process of PID is dead = no valid master process operational
            // or the pid in file dose not match master pid of this process set,
            // something broke - DIE
            if( !posix_getsid($pid) || $pid != $this->masterpid )
            {
                echo getmypid()." : $pid : \n";
                exit('invalid pidfile');
            }
        }
        else
        {
            exit("pidfile dose not exist any more \n");
        }
    }

    protected function main()
    {
        exit("Define main() function");
    }

}

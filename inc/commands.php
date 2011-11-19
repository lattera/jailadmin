<?php

abstract class Command {
    protected $name;
    protected $min_number_arguments;
    protected $max_number_arguments;

    abstract protected function Help($retstr);
    abstract protected function Run($args);
    abstract protected function Test($args);

    public function getProperty($prop) {
        switch ($prop) {
            case "name":
                return $this->name;
            case "minArgs":
                return $this->min_number_arguments;
            case "maxArgs":
                return $this->max_number_arguments;
            default:
                throw new Exception(get_class($this) . "->getProperty(): unkown property");
        }
    }

    function __construct() {
        $this->name = "";
        $this->min_number_arguments = 1;
        $this->max_number_arguments = -1;
    }
}

class HelpCommand extends Command {

    function __construct() {
        parent::__construct();

        $this->name = "help";
        $this->min_number_arguments = 0;
    }

    public function Help($retstr=false) {
        global $commands;

        $help = "Jail Admin script. Made by Shawn Webb for Wayfair.\n";
        $help .= "Available commands:\n";
        foreach ($commands as $command)
            if (strcmp("help", $command->getProperty("name")))
                $help .= $command->Help(true);

        echo $help;
        
        return true;
    }

    public function Run($args) {
        return $this->Help(false);
    }

    public function Test($args) {
        if (count($args) == 1)
            return true;

        if (!strcmp($args[1], "help"))
            return true;

        return false;
    }
}

class NewCommand extends Command {
    function __construct() {
        parent::__construct();

        $this->name = "new";
    }

    public function Help($retstr=false) {
        $str = "[*] new - Create new jail\n";

        if ($retstr)
            return $str;

        echo $str;

        return true;
    }

    public function Run($args) {
        echo "Name: ";
        $name = read_stdin();

        echo "ZFS Template: ";
        $template = read_stdin();

        echo "New ZFS Dataset: ";
        $dataset = read_stdin();

        echo "Path: ";
        $path = read_stdin();

        echo "Network Type [EPAIR]: ";
        $nettype = read_stdin();

        echo "Network Interface: ";
        $inet = read_stdin();

        echo "Bridge []: ";
        $bridgename = read_stdin();

        echo "IP: ";
        $ip = read_stdin();

        echo "Default Route: ";
        $route = read_stdin();

        $service = "";
        $services = array();
        do {
            echo "Service (enter blank line when finished): ";
            $service = read_stdin();
            if (strlen($service) > 0)
                array_push($services, $service);
        } while (strlen($service) > 0);

        $j = new Jail("");

        $j->setProperty("name", $name);
        $j->setProperty("dataset", $dataset);
        $j->setProperty("path", $path);
        $j->setProperty("inet", $inet);
        $j->setProperty("bridge", $bridgename);
        $j->setProperty("ip", $ip);
        $j->setProperty("route", $route);

        switch ($nettype) {
            case "":
            case "EPAIR":
                $nettype = NetTypes::EPAIR;
                break;
            default:
                throw new Exception("Unknown Network Type: $nettype");
        }

        $j->setProperty("nettype", $nettype);
        if (count($services) > 0)
            $j->setProperty("services", $services);

        $jails = Jail::findAll();
        foreach ($jails as $jail) {
            if (!strcmp($jail->getProperty("ip"), $ip))
                throw new Exception("$ip already taken");
            if (!strcmp($jail->getProperty("inet"), $inet))
                throw new Exception("$inet already taken");
        }

        if (strlen($bridgename) > 0) {
            $bridges = Bridge::findAll();
            $found = false;
            foreach ($bridges as $b)
                if (!strcmp($b->getProperty("name"), $bridgename))
                    $found = true;

            if ($found == false)
                throw new Exception("$bridge does not exist");
        }

        $j->Persist();

        exec("zfs clone $template $dataset");
        
        $fp = fopen($path . "/etc/ssh/sshd_config", "a");
        if ($fp !== false) {
            fwrite($fp, "ListenAddress $ip\n");
            fclose($fp);
        }

        $fp = fopen($path . "/etc/rc.conf", "a");
        if ($fp !== false) {
            fwrite($fp, "sshd_enable=\"YES\"\n");
            fclose($fp);
        }

        return $j->Start();
    }

    public function Test($args) {
        if (!strcmp($args[1], "new"))
            return true;

        return false;
    }
}

class DeleteCommand extends Command {
    function __construct() {
        parent::__construct();

        $this->name = "delete";
        $this->min_number_arguments = 2;
    }

    public function Help($retstr=false) {
        $str = "[*] delete - Delete jail\n";
        $str .= "[+]    name of jail to delete\n";

        if ($retstr)
            return $str;

        echo $str;
        return true;
    }

    public function Run($args) {
        $j = new Jail($args[2]);

        if ($j->IsOnline())
            $j->Stop();

        $j->Delete();

        return true;
    }

    public function Test($args) {
        if (!strcmp($args[1], "delete"))
            return true;
    }

}

class ListCommand extends Command {
    function __construct() {
        parent::__construct();

        $this->name = "list";
        $this->min_number_arguments = 2;
    }

    public function Help($retstr=false) {
        $str = "[*] list - List bridges, jails, and running jails\n";
        $str .= "[+]    bridges - List configured bridges\n";
        $str .= "[+]    jails - List configured jails\n";
        $str .= "[+]    running - List running configured jails\n";
        if ($retstr)
            return $str;
        echo $str;
        return true;     
    }

    public function Run($args) {
        echo "To be completed. Sorry!\n";
        return true;
        switch ($args[2]) {
            case "bridges":
                list_bridges();
                break;
            case "running":
                list_running();
                break;
            case "jails":
                list_jails();
                break;
            case "help":
                return $this->Help();
            default:
                $this->Help();
                return false;
        }

        return true;
    }

    public function Test($args) {
        if (!strcmp($args[1], "list"))
            return true;

        return false;
    }
}

class StartCommand extends Command {
    function __construct() {
        parent::__construct();

        $this->name = "start";
        $this->min_number_arguments = 2;
    }

    public function Help($retstr=false) {
        $str = "[*] start - Start a jail\n";
        $str .= "[+]    name of jail to start\n";
        if ($retstr)
            return $str;
        echo $str;
        return true;
    }

    public function Run($args) {
        global $jail;

        if (!strcmp($args[2], "help"))
            return $this->Help();

        if (!strcmp($args[2], "[all]")) {
            foreach ($jail as $j) {
                $o = new Jail($j["name"]);
                $o->Start();
            }

            return true;
        }

        $o = new Jail($args[2]);
        return $o->Start();
    }

    public function Test($args) {
        if (!strcmp($args[1], "start"))
            return true;
        return false;
    }

    private static function prep_start($jail) {
        global $config;

        if (is_online($jail)) {
            if ($config["on_start"]["killexisting"] == false) {
                echo "WARNING: " . $jail["name"] . " is either still online or not fully killed. Please manually kill jail.\n";
                return false;
            }

            kill_jail($jail);
        }

        return true;
    }
}

class StopCommand extends Command {
    function __construct() {
        parent::__construct();

        $this->name = "stop";
        $this->min_number_arguments = 2;
    }

    public function Help($retstr=false) {
        $str = "[*] stop - Stop a jail\n";
        $str .= "[+]    name of jail to stop\n";
        if ($retstr)
            return $str;
        echo $str;
        return true;
    }

    public function Run($args) {
        global $jail;

        if (!strcmp($args[2], "help"))
            return $this->Help();

        if (!strcmp($args[2], "[all]")) {
            foreach ($jail as $j) {
                $o = new Jail($j["name"]);
                $o->Stop();
            }

            return true;
        }

        $o = new Jail($args[2]);
        $o->Stop();

        return true;
    }

    public function Test($args) {
        if (!strcmp($args[1], "stop"))
            return true;
        return false;
    }
}

$commands = array();

array_push($commands, new NewCommand);
array_push($commands, new ListCommand);
array_push($commands, new StartCommand);
array_push($commands, new StopCommand);
array_push($commands, new NewCommand);
array_push($commands, new DeleteCommand);
array_push($commands, new HelpCommand);

?>

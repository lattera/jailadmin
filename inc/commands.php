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
        return true;
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
        if (count($args) >= 2 && !strcmp($args[2], "help"))
            return $this->Help();

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
            foreach ($jail as $j)
                if (prep_start($j))
                    start_jail($j);

            return true;
        }

        if (array_key_exists($args[2], $jail) == false) {
            echo "Jail " . $args[2] . " not configured!\n";
            return false;
        }

        if (StartCommand::prep_start($jail[$args[2]]))
            start_jail($jail[$args[2]]);

        return true;
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
            foreach ($jail as $j)
                kill_jail($j);

            return true;
        }

        if (array_key_exists($args[2], $jail) == false) {
            echo "ERROR: Jail " . $args[2] . " is not configured\n";
            return false;
        }

        kill_jail($jail[$args[2]]);

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

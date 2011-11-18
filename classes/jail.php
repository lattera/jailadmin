<?php

class Jail {
    private $name;
    private $nettype;
    private $inet;
    private $bridge;
    private $path;
    private $ip;
    private $route;
    private $dataset;
    private $services;

    function __construct($name) {
        global $jail;

        if (array_key_exists($name, $jail) == false)
            throw new Exception("Jail $jail is not configured!\n");

        $this->dataset = "";
        $this->services = array();
        $this->bridge = "";

        $this->name = $jail[$name]["name"];
        $this->nettype = $jail[$name]["nettype"];
        $this->inet = $jail[$name]["inet"];
        $this->path = $jail[$name]["path"];
        $this->ip = $jail[$name]["ip"];
        $this->route = $jail[$name]["route"];

        if (array_key_exists("dataset", $jail[$name]))
            $this->dataset = $jail[$name]["dataset"];

        if (array_key_exists("services", $jail[$name]))
            $this->services = $jail[$name]["services"];

        if (array_key_exists("bridge", $jail[$name]))
            $this->bridge = $jail[$name]["bridge"];
    }

    public function IsOnline() {
        if (strlen("" . $this->name) == 0)
            return false;

        $o = exec("mount | grep " . $this->path . "/dev");
        return strlen($o) > 0;
    }

    public function Start($force=true) {
        global $bridge;

        if ($this->IsOnline()) {
            if ($force == false) {
                echo "WARNING: $jail is already online. Please manually stop jail.\n";
                return false;
            }

            $this->stop();
        }

        if ($this->nettype == NetTypes::EPAIR) {
            exec("ifconfig " . $this->inet . " create");
        }

        if (strlen($this->bridge) > 0) {
            if (prep_bridge($this->bridge) == false) {
                if ($this->nettype == NetTypes::EPAIR)
                    exec("ifconfig " . $this->inet . "a destroy");

                return false;
            }

            if ($this->nettype == NetTypes::EPAIR)
                exec("ifconfig " . $bridge[$this->bridge]["inet"] . " addm " . $this->inet . "a");
        }

        if ($this->nettype == NetTypes::EPAIR)
            exec("ifconfig " . $this->inet . "a up");

        exec("mount -t devfs devfs " . $this->path . "/dev");
        exec("jail -c vnet name=" . $this->name . " host.hostname=" . $this->name . " path=" . $this->path . " persist");

        if ($this->nettype == NetTypes::EPAIR) {
            exec("ifconfig " . $this->inet . "b vnet " . $this->name);
            exec("jexec " . $this->name . " ifconfig " . $this->inet . "b " . $this->ip);
        }

        exec("jexec " . $this->name . " route add default " . $this->route);

        foreach($this->services as $service)
            exec("jexec " . $this->name . " " . $service . " start");

        return true;
    }

    public function Stop() {
        if ($this->IsOnline() == false)
            return true;

        exec("jail -r " . $this->name);
        exec ("umount " . $this->path . "/dev");

        if ($this->nettype == NetTypes::EPAIR)
            exec("ifconfig " . $this->inet . "a destroy");

        return true;
    }
}

?>

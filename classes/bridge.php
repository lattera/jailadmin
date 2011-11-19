<?php

class Bridge {
    private $name;
    private $inet;
    private $ip;

    function __construct($name) {
        global $bridge;

        if (array_key_exists($name, $bridge) == false)
            throw new Exception("Bridge $bridge is not configured");

        $this->name = $bridge[$name]["name"];
        $this->inet = $bridge[$name]["inet"];
        $this->ip = $bridge[$name]["ip"];
    }

    public function IsOnline() {
        $o = exec("ifconfig " . $this->inet . " | grep -v \"does not exist\"");
        return strlen($o) > 0;
    }

    public function Start() {
        if ($this->IsOnline())
            return true;

        exec("ifconfig " . $this->inet . " create");
        exec("ifconfig " . $this->inet . " " . $this->ip);

        return true;
    }

    public function Stop() {
        if ($this->IsOnline() == false)
            return true;

        exec("ifconfig " . $this->inet . " destroy");

        return true;
    }

    public function getProperty($name) {
        switch ($name) {
            case "inet":
                return $this->inet;
            case "name":
                return $this->name;
            default:
                throw new Exception("Bridge->getProperty(): unknown property: $name");
        }
    }
}

?>

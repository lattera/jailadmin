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
            $bridge = new Bridge($this->bridge);
            $bridge->Start();

            if ($this->nettype == NetTypes::EPAIR)
                exec("ifconfig " . $bridge->getProperty("inet") . " addm " . $this->inet . "a");
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

    public function Delete() {
        exec("cp config.php config.php.tmp");

        $fp = fopen("config.php.tmp", "r");
        if ($fp === false)
            return false;

        $lines = array();
        while (!feof($fp)) {
            $s = rtrim(fgets($fp));
            if (strstr($s, "jail[\"" . $this->name) === false)
                array_push($lines, $s);
        }

        fclose($fp);
        $fp = fopen("config.php", "w");
        if ($fp === false)
            return false;

        $prevblank = false;
        foreach ($lines as $line) {
            if ($prevblank == true && strlen($line) == 0)
                continue;

            fwrite($fp, $line . "\n");

            if (strlen($line) == 0)
                $prevblank = true;
        }

        if (strlen($this->dataset) > 0)
            exec("zfs destroy -r " . $this->dataset);

        exec("rm config.php.tmp");

        return true;
    }

    public function Persist() {
        /* Ensure we're dealing only with what we have */
        $this->Delete();

        $fp = fopen("config.php", "a");

        fwrite($fp, "\$jail[\"" . $this->name . "\"][\"name\"] = \"" . $this->name . "\";\n");
        fwrite($fp, $nettype);
        fwrite($fp, "\$jail[\"" . $this->name . "\"][\"inet\"] = \"" . $this->inet . "\";\n");
        if (strlen($bridgename) > 0)
            fwrite($fp, "\$jail[\"" . $this->name . "\"][\"bridge\"] = \"" . $this->bridge . "\";\n");
        fwrite($fp, "\$jail[\"" . $this->name . "\"][\"path\"] = \"" . $this->path . "\";\n");
        fwrite($fp, "\$jail[\"" . $this->name . "\"][\"ip\"] = \"" . $this->ip . "\";\n");
        fwrite($fp, "\$jail[\"" . $this->name . "\"][\"route\"] = \"" . $this->route . "\";\n");

        if (strlen($this->dataset) > 0)
            fwrite($fp, "\$jail[\"" . $this->name . "\"][\"dataset\"] = \"" . $this->dataset . "\";\n");

        $service = "";
        if (count($this->services) > 0)
            foreach ($this->services as $s)
                $service .= ((strlen($service) > 0) ? ", " : "") . "\"$s\"";

        if (strlen($service) > 0)
            fwrite($fp, "\$jail[\"" . $this->name . "\"][\"services\"] = array($service);\n");

        switch ($this->nettype) {
            case NetTypes::EPAIR:
                fwrite($fp, "\$jail[\"" . $this->name . "\"][\"nettype\"] = NetTypes::EPAIR;\n");
                break;
            default:
                throw new Exception("Jail[" . $this->name . "]->Persist: Unkown nettype\n");
        }

        fwrite($fp, "\n");
        fclose($fp);
    }
}

?>

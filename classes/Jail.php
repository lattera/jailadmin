<?php

class Jail extends fActiveRecord {
    private $network;

    public static function findAll() {
        return Jail::prepData(fRecordSet::build(__CLASS__));
    }

    public static function findByName($name) {
        return Jail::prepData(fRecordSet::build(__CLASS__, array("jail_name=" => $name)));
    }

    public function IsOnline() {
        $o = exec("mount | grep " . $this->getJailName() . "/dev");
        return strlen($o) > 0;
    }

    public function Start() {
        if ($this->IsOnline())
            $this->Stop();

        foreach ($this->network as $n)
            $n->BringHostOnline();

        exec("mount -t devfs devfs " . $this->getPath() . "/dev");
        exec("jail -c vnet name=" . $this->getJailName() . " host.hostname=" . $this->getJailName() . " path=" . $this->getPath() . " persist");

        foreach($this->network as $n)
            $n->BringGuestOnline($this);

        return true;
    }

    public function Stop() {
        if ($this->IsOnline() == false)
            return true;

        exec("jail -r " . $this->getJailName());
        exec("umount " . $this->getPath() . "/dev");

        foreach ($this->network as $n)
            $n->BringOffline();

        return true;
    }

    public function associatedEpairs() {
        return $this->network;
    }

    protected static function prepData($jails) {
        foreach ($jails as $jail)
            $jail->associateEpairs();

        return $jails;
    }

    protected function associateEpairs() {
        $this->network = Epair::findAll($this->getJailId());
    }

    public function View() {
        echo "[" . $this->getJailName() . "] Online => " . ($this->IsOnline() ? "True" : "False") . "\n";
        echo "[" . $this->getJailName() . "] Path => " . $this->getPath() . "\n";
        echo "[" . $this->getJailName() . "] Dataset => " . $this->getDataset() . "\n";
        echo "[" . $this->getJailName() . "] Default Route => " . $this->getDefaultRoute() . "\n";
        foreach ($this->network as $n) {
            $n_name = $n->getEpairDevice();
            $b_name = $n->associatedBridge()->getBridgeDevice();

            echo "[" . $this->getJailName() . "][" . $n_name . "] IP => " . $n->getIp() . "\n";
            echo "[" . $this->getJailName() . "][" . $n_name . "][" . $b_name . "] Name => " . $n->associatedBridge()->getBridgeName() . "\n";
            echo "[" . $this->getJailName() . "][" . $n_name . "][" . $b_name . "] IP => " . $n->associatedBridge()->getBridgeIp() . "\n";
        }
    }
}
?>

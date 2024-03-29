<?php

class Jail extends fActiveRecord {
    private $network;
    private $services;

    public static function findAll() {
        return Jail::prepData(fRecordSet::build(__CLASS__));
    }

    public static function findByName($name) {
        return Jail::prepData(fRecordSet::build(__CLASS__, array("jail_name=" => $name)), true);
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

        exec("jexec " . $this->getJailName() . " route add default " . $this->getDefaultRoute());

        foreach ($this->services as $service)
            exec("jexec " . $this->getJailName() . " " . $service->getServicePath() . " start");

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

    public function associateServices($services=array()) {
        if (count($services) == 0)
            $this->services = Service::findByJailId($this->getJailId());
        else
            $this->services = $services;
    }

    public function associatedServices() {
        return $this->services;
    }

    protected static function prepData($jails, $single=false) {
        foreach ($jails as $jail) {
            $jail->associateEpairs();
            $jail->associateServices();
        }

        if ($single)
            return ($jails->count() == 0) ? false : $jails->getRecord(0);

        return $jails;
    }

    public function associateEpairs($epairs=array()) {
        if (count($epairs) == 0)
            $this->network = Epair::findAll($this->getJailId());
        else
            $this->network = $epairs;
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

        foreach ($this->services as $service)
            echo "[" . $this->getJailName() . "] Service => " . $service->getServicePath() . "\n";
    }

    public function Persist() {
        $this->setJailId($this->store()->getJailId());
        foreach ($this->network as $n) {
            $n->setJailId($this->getJailId());
            $n->Persist();
        }

        foreach ($this->services as $service) {
            $service->setJailId($this->getJailId());
            $service->Persist();
        }
    }

    public function Remove() {
        if ($this->IsOnline())
            $this->Stop();

        foreach ($this->network as $n)
            $n->Remove();

        foreach ($this->services as $service)
            $service->Remove();

        exec("zfs destroy -r " . $this->getDataset());
        $this->delete();
    }
}
?>

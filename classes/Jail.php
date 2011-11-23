<?php

class Jail extends fActiveRecord {
    private $network;

    public static function findAll() {
        return Jail::prepData(fRecordSet::build(__CLASS__));
    }

    public static function findByName($name) {
        return Jail::prepData(fRecordSet::build(__CLASS__, array("name=" => $name)));
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

    protected static function prepData($jails) {
        foreach ($jails as $jail)
            $jail->associateEpairs();

        return $jails;
    }

    protected function associateEpairs() {
        $this->network = Epair::findAll($this->getJailId());
    }
}
?>

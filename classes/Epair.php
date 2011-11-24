<?php

class Epair extends fActiveRecord {
    private $bridge;

    public static function findAll($jail_id) {
        return Epair::prepData(fRecordSet::build(__CLASS__, array("jail_id=" => $jail_id)));
    }

    public static function findAllForUniqueCheck() {
        return Epair::prepData(fRecordSet::build(__CLASS__));
    }

    protected static function prepData($epairs) {
        foreach ($epairs as $epair)
            $epair->associateBridge();

        return $epairs;
    }

    public function associateBridge($bridge=null) {
        if ($bridge != null)
            $this->bridge = $bridge;
        else
            $this->bridge = Bridge::findByBridgeId($this->getBridgeId());
    }

    public function IsOnline() {
        $o = exec("ifconfig " . $this->getEpairDevice() . "a 2>&1 | grep -v \"does not exist\"");
        return strlen($o) > 0;
    }

    public function BringHostOnline() {
        if ($this->IsOnline())
            return true;

        if ($this->bridge->BringOnline() == false)
            return false;

        exec("ifconfig " . $this->getEpairDevice() . " create");
        exec("ifconfig " . $this->bridge->getBridgeDevice() . " addm " . $this->getEpairDevice() . "a");
        exec("ifconfig " . $this->getEpairDevice() . "a up");
    }

    public function BringGuestOnline($jail) {
        if ($jail->IsOnline() == false)
            throw new Error("Epair::BringGuestOnline requires jail " . $jail->getJailName() . " to be staged as online");

        if ($this->IsOnline() == false)
            $this->BringHostOnline();

        exec("ifconfig " . $this->getEpairDevice() . "b vnet " . $jail->getJailName());
        exec("jexec " . $jail->getJailName() . " ifconfig " . $this->getEpairDevice() . "b " . $this->getIp());
        exec("jexec " . $jail->getJailName() . " route add default " . $jail->getDefaultRoute());
    }

    public function BringOffline() {
        if ($this->IsOnline() == false)
            return true;

        exec("ifconfig " . $this->getEpairDevice() . "a destroy");

        return true;
    }

    public function associatedBridge() {
        return $this->bridge;
    }

    public function Persist() {
        $this->setBridgeId($this->bridge->getBridgeId());
        $this->setEpairId($this->store()->getEpairId());
    }

    public function Remove() {
        if ($this->IsOnline())
            $this->BringOffline();

        $this->delete();
    }
}

?>

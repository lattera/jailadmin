<?php

class Bridge extends fActiveRecord {
    public static function findAll() {
        return fRecordSet::build(__CLASS__);
    }

    public static function findByBridgeId($bridge_id) {
        $bridges = fRecordSet::build(__CLASS__, array("bridge_id=" => $bridge_id));
        if ($bridges->count() == 0)
            return false;

        return $bridges->getRecord(0);
    }

    public static function findByName($name) {
        $bridges = fRecordSet::build(__CLASS__, array("bridge_name=" => $name));
        if ($bridges->count() == 0)
            return false;

        return $bridges->getRecord(0);
    }

    public function IsOnline() {
        $o = exec("ifconfig " . $this->getBridgeDevice() . " 2>&1 | grep -v \"does not exist\"");
        return strlen($o) > 0;
    }

    public function BringOnline() {
        if ($this->IsOnline())
            return true;

        exec("ifconfig " . $this->getBridgeDevice() . " create");
        exec("ifconfig " . $this->getBridgeDevice() . " " . $this->getBridgeIp());

        return true;
    }
}

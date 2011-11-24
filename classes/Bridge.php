<?php

class Bridge extends fActiveRecord {
    private $jails = null; /* Lazy loaded for Bridge::relatedJails */

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

    public function relatedJails() {
        if ($this->jails == null) {
            $jails = Jail::findAll();
            $this->jails = array();

            foreach ($jails as $jail)
                foreach ($jail->associatedEpairs() as $n)
                    if (!strcmp($this->getBridgeName(), $n->associatedBridge()->getBridgeName()))
                        array_push($this->jails, $jail);
        }

        return $this->jails;
    }

    public function View() {
        echo "[" . $this->getBridgeName() . "] Online => " . ($this->IsOnline() ? "True" : "False") . "\n";
        echo "[" . $this->getBridgeName() . "] IP => " . $this->getBridgeIp() . "\n";
        echo "[" . $this->getBridgeName() . "] Device => " . $this->getBridgeDevice() . "\n";

        $jails = "";
        foreach ($this->relatedJails() as $jail)
            $jails .= ((strlen($jails) > 0) ? ", " : "") . $jail->getJailName();

        echo "[" . $this->getBridgeName() . "] Assigned Jails => $jails\n";
    }

    public function Persist() {
        $this->store();
    }

    public function Remove() {
        if (count($this->relatedJails()) == 0)
            $this->delete();
    }
}

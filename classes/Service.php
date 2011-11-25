<?php

class Service extends fActiveRecord {
    public static function findByJailId($jail_id) {
        $services = array();
        foreach (fRecordSet::build(__CLASS__, array("jail_id=" => $jail_id)) as $service)
            array_push($services, $service);

        return $services;
    }
}

?>

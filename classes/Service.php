<?php

class Service extends fActiveRecord {
    public static function findByJailId($jail_id) {
        return fRecordSet::build(__CLASS__, array("jail_id=" => $jail_id));
    }
}

?>

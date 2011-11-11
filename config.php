<?php

#################
# Global Config #
#################
$config["on_start"]["killexisting"] = true;
$config["on_start"]["createbridge"] = true;

###########
# Bridges #
###########
$bridge["honeynet"]["name"] = "honeynet";
$bridge["honeynet"]["inet"] = "bridge0";
$bridge["honeynet"]["ip"]   = "192.168.20.1";

###############
# Jail Config #
###############
$jail["honeypot"]["name"]       = "honeypot";
$jail["honeypot"]["nettype"]    = NetTypes::EPAIR;
$jail["honeypot"]["inet"]       = "epair0";
$jail["honeypot"]["bridge"]     = "honeynet";
$jail["honeypot"]["path"]       = "/jails/honeypot";
$jail["honeypot"]["ip"]         = "192.168.20.2";
$jail["honeypot"]["route"]      = "192.168.20.1";

############
# Services #
############
$jail["honeypot"]["services"] = array("/etc/rc.d/sshd");

?>

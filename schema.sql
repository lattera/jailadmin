SET storage_engine=INNODB;

CREATE TABLE bridges (
    bridge_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bridge_name VARCHAR(20) NOT NULL,
    bridge_device VARCHAR(9) NOT NULL,
    bridge_ip VARCHAR(15) NOT NULL
);

CREATE TABLE jails (
    jail_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    jail_name VARCHAR(20) NOT NULL,
    path VARCHAR(500) NOT NULL,
    dataset VARCHAR(500) NOT NULL,
    default_route VARCHAR(15) NOT NULL
);

CREATE TABLE services (
    service_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    service_path VARCHAR(500) NOT NULL,
    jail_id INT NOT NULL,
    FOREIGN KEY (jail_id) REFERENCES jails(jail_id)
);

CREATE TABLE epairs (
    epair_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    jail_id INT NOT NULL,
    bridge_id INT NOT NULL,
    ip VARCHAR(15) NOT NULL,
    epair_device VARCHAR(9) NOT NULL,
    FOREIGN KEY (jail_id) REFERENCES jails(jail_id),
    FOREIGN KEY (bridge_id) REFERENCES bridges(bridge_id)
);

ALTER TABLE `#__vikrentcar_customers` CHANGE `pin` `pin` varchar(16) NOT NULL DEFAULT '0';
ALTER TABLE `#__vikrentcar_cars` ADD COLUMN `max_drivers` tinyint(2) NOT NULL DEFAULT 1;
ALTER TABLE `#__vikrentcar_orders` ADD COLUMN `drivers_count` tinyint(2) NOT NULL DEFAULT 1;
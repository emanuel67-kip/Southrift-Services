-- Add foreign key from drivers to vehicles
ALTER TABLE `drivers`
ADD COLUMN `vehicle_id` INT NULL AFTER `license_number`,
ADD CONSTRAINT `fk_drivers_vehicle` 
    FOREIGN KEY (`vehicle_id`) 
    REFERENCES `vehicles`(`id`) 
    ON DELETE SET NULL;

-- Add foreign key from vehicles to drivers
ALTER TABLE `vehicles`
ADD CONSTRAINT `fk_vehicles_driver`
    FOREIGN KEY (`driver_id`)
    REFERENCES `drivers`(`id`)
    ON DELETE SET NULL;

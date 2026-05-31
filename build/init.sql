CREATE TABLE `account` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `abbonamento` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `descrizione` VARCHAR(255),
  `data_sottoscrizione` DATE NOT NULL,
  `data_scadenza` DATE NOT NULL,
  `costo` DECIMAL(6,2) NOT NULL,
  `id_account` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_account`) REFERENCES `account`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
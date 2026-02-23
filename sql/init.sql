-- MPD MySQL (RoomBooking) - création des tables
-- Recommandé : InnoDB + UTF8MB4
CREATE DATABASE IF NOT EXISTS roombooking
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE roombooking;

-- =====================================================
-- TABLE: utilisateur
-- =====================================================
CREATE TABLE `utilisateur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_utilisateur_email` (`email`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: administrateur
-- =====================================================
CREATE TABLE `administrateur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_administrateur_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_administrateur_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: coordinateur
-- =====================================================
CREATE TABLE `coordinateur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coordinateur_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_coordinateur_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: classe
-- =====================================================
CREATE TABLE `classe` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_classe_name` (`name`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: student
-- =====================================================
CREATE TABLE `student` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `classe_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_utilisateur` (`utilisateur_id`),
  KEY `idx_student_classe` (`classe_id`),
  CONSTRAINT `fk_student_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_student_classe`
    FOREIGN KEY (`classe_id`) REFERENCES `classe`(`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: coordinateur_classe (association N-N)
-- =====================================================
CREATE TABLE `coordinateur_classe` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coordinateur_id` INT UNSIGNED NOT NULL,
  `classe_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coordinateur_classe` (`coordinateur_id`, `classe_id`),
  KEY `idx_cc_classe` (`classe_id`),
  CONSTRAINT `fk_cc_coordinateur`
    FOREIGN KEY (`coordinateur_id`) REFERENCES `coordinateur`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_cc_classe`
    FOREIGN KEY (`classe_id`) REFERENCES `classe`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: salle
-- =====================================================
CREATE TABLE `salle` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_salle_name` (`name`)
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: equipement
-- =====================================================
CREATE TABLE `equipement` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `salle_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipement_salle` (`salle_id`),
  CONSTRAINT `fk_equipement_salle`
    FOREIGN KEY (`salle_id`) REFERENCES `salle`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: reservation
-- =====================================================
CREATE TABLE `reservation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `salle_id` INT UNSIGNED NOT NULL,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `reservation_start` DATETIME NOT NULL,
  `reservation_end` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reservation_salle` (`salle_id`),
  KEY `idx_reservation_utilisateur` (`utilisateur_id`),
  KEY `idx_reservation_salle_time` (`salle_id`, `reservation_start`, `reservation_end`),
  CONSTRAINT `fk_reservation_salle`
    FOREIGN KEY (`salle_id`) REFERENCES `salle`(`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_reservation_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur`(`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `chk_reservation_time` CHECK (`reservation_end` > `reservation_start`)
) ENGINE=InnoDB;
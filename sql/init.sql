-- MPD MySQL (RoomBooking) - création des tables
-- Recommandé : InnoDB + UTF8MB4
CREATE DATABASE IF NOT EXISTS roombooking
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE roombooking;

-- =====================================================
-- TABLE: user
-- =====================================================
CREATE TABLE user (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY UNIQ_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: administrator
-- =====================================================
CREATE TABLE administrator (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY UNIQ_administrator_user (user_id),
  CONSTRAINT FK_administrator_user FOREIGN KEY (user_id) REFERENCES user (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: coordinator
-- =====================================================
CREATE TABLE coordinator (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY UNIQ_coordinator_user (user_id),
  CONSTRAINT FK_coordinator_user FOREIGN KEY (user_id) REFERENCES user (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: classe
-- =====================================================
CREATE TABLE classe (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: student
-- =====================================================
CREATE TABLE student (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  classe_id INT DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY UNIQ_student_user (user_id),
  KEY IDX_student_classe (classe_id),
  CONSTRAINT FK_student_user FOREIGN KEY (user_id) REFERENCES user (id),
  CONSTRAINT FK_student_classe FOREIGN KEY (classe_id) REFERENCES classe (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: coordinateur_classe (association N-N)
-- =====================================================
CREATE TABLE coordinator_classe (
  coordinator_id INT NOT NULL,
  classe_id INT NOT NULL,
  PRIMARY KEY (coordinator_id, classe_id),
  KEY IDX_cc_coordinator (coordinator_id),
  KEY IDX_cc_classe (classe_id),
  CONSTRAINT FK_cc_coordinator FOREIGN KEY (coordinator_id) REFERENCES coordinator (id) ON DELETE CASCADE,
  CONSTRAINT FK_cc_classe FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: room
-- =====================================================
CREATE TABLE room (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  capacity INT NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: equipment
-- =====================================================
CREATE TABLE equipment (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- TABLE: room_equipment
-- =====================================================
CREATE TABLE room_equipment (
  room_id INT NOT NULL,
  equipment_id INT NOT NULL,
  PRIMARY KEY (room_id, equipment_id),
  KEY IDX_room_equipment_room (room_id),
  KEY IDX_room_equipment_equipment (equipment_id),
  CONSTRAINT FK_room_equipment_room FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE,
  CONSTRAINT FK_room_equipment_equipment FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- =====================================================
-- TABLE: reservation
-- =====================================================
CREATE TABLE reservation (
  id INT NOT NULL AUTO_INCREMENT,
  reservation_start DATETIME NOT NULL,
  reservation_end DATETIME NOT NULL,
  room_id INT NOT NULL,
  user_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  PRIMARY KEY (id),
  KEY IDX_reservation_room (room_id),
  KEY IDX_reservation_user (user_id),
  CONSTRAINT FK_reservation_room FOREIGN KEY (room_id) REFERENCES room (id),
  CONSTRAINT FK_reservation_user FOREIGN KEY (user_id) REFERENCES user (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
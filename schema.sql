-- Base de datos y tabla para inventario de oficina (v2)
CREATE DATABASE IF NOT EXISTS oficina_inv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oficina_inv;

DROP TABLE IF EXISTS items;

CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  clase VARCHAR(120),
  cantidad INT NOT NULL DEFAULT 0,       -- Stock total
  condicion VARCHAR(120),                -- Condición
  notas TEXT,
  ubicacion VARCHAR(120),
  min_stock INT NOT NULL DEFAULT 0,      -- Mínimo
  max_stock INT NOT NULL DEFAULT 0,      -- Máximo
  imagen VARCHAR(255),                   -- archivo en /uploads
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_items_nombre ON items (nombre);
CREATE INDEX idx_items_clase ON items (clase);
CREATE INDEX idx_items_ubicacion ON items (ubicacion);
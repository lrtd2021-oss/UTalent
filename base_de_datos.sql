-- Crea la base de UTalent desde cero. Se ejecuta una sola vez en phpMyAdmin.
DROP DATABASE IF EXISTS utalent;
CREATE DATABASE utalent CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE utalent;

-- Cada portal de empleo. "clase" es el nombre del archivo PHP de fuentes/ que sabe leerlo.
CREATE TABLE fuentes (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60)  NOT NULL,
    url    VARCHAR(255) NOT NULL,
    clase  VARCHAR(60)  NOT NULL UNIQUE
);

CREATE TABLE ofertas (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    fuente_id          INT NULL,                -- NULL = oferta cargada a mano desde el panel
    titulo             VARCHAR(255) NOT NULL,
    empresa            VARCHAR(255) NOT NULL,
    departamento       VARCHAR(40)  NOT NULL,
    tipo               ENUM('Público', 'Privado') NOT NULL,
    salario            VARCHAR(60)  NULL,       -- NULL = el aviso no muestra salario
    postulacion_online BOOLEAN NOT NULL DEFAULT 0,
    primer_empleo      BOOLEAN NOT NULL DEFAULT 0,
    enlace             VARCHAR(255) NULL UNIQUE, -- UNIQUE evita guardar dos veces la misma oferta
    fecha              DATE NOT NULL,
    vista_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- última vez que la fuente la trajo
    FOREIGN KEY (fuente_id) REFERENCES fuentes(id) ON DELETE CASCADE
);

INSERT INTO fuentes (nombre, url, clase) VALUES
('Uruguay Concursa', 'https://www.uruguayconcursa.gub.uy', 'UruguayConcursa'),
('BuscoJobs',        'https://www.buscojobs.com.uy',       'BuscoJobs'),
('UTU (DGETP)',      'https://www.utu.edu.uy',             'Utu');

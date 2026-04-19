-- Таблица заявок
CREATE TABLE applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fio VARCHAR(150) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    bio TEXT NOT NULL,
    contract_agreed BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица языков программирования (справочник)
CREATE TABLE programming_languages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Заполнение справочника языков
INSERT INTO programming_languages (name) VALUES 
('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'), 
('Python'), ('Java'), ('Haskel'), ('Clojure'), ('Prolog'), 
('Scala'), ('Go');

-- Таблица связи заявок и языков (один ко многим)
CREATE TABLE application_languages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id) ON DELETE CASCADE,
    UNIQUE KEY (application_id, language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    

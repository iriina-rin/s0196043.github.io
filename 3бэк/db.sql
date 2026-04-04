CREATE TABLE application (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(150),
phone VARCHAR(20),
email VARCHAR(100),
birthdate DATE,
gender VARCHAR(10),
biography TEXT,
contract BOOLEAN
);

CREATE TABLE programming_languages (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(50)
);

CREATE TABLE application_languages (
application_id INT,
language_id INT
);
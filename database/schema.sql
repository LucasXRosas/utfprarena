SET foreign_key_checks = 0;

DROP TABLE IF EXISTS court_images;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS courts;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL,
    encrypted_password VARCHAR(255) NOT NULL,
    avatar_name VARCHAR(65) NULL,
    phone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'student',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    remember_token VARCHAR(100) NULL,
    last_login_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'beach_tennis',
    status VARCHAR(30) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galeria de fotos das quadras (relação 1xN: uma quadra possui N imagens)
-- O arquivo físico fica em public/assets/uploads/courts/{court_id}/{file_name};
-- o banco guarda apenas os metadados e a referência (file_name).
CREATE TABLE court_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    court_id INT NOT NULL,
    file_name VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    size INT UNSIGNED NOT NULL,
    caption VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_court_images_file_name (file_name),
    KEY idx_court_images_court_id (court_id),
    CONSTRAINT fk_court_images_court
        FOREIGN KEY (court_id) REFERENCES courts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- Structure de la table speakers_list
CREATE TABLE IF NOT EXISTS speakers_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    committee_id INT NOT NULL,
    delegate_id INT NOT NULL,
    speaking_time INT NOT NULL,
    status ENUM('waiting', 'speaking', 'done') NOT NULL DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE,
    FOREIGN KEY (delegate_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

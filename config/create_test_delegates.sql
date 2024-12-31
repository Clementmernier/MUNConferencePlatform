-- Récupérer l'ID du comité Security Council
SET @committee_id = (SELECT id FROM committees WHERE name = 'Security Council' LIMIT 1);

-- Créer quelques délégués de test
INSERT INTO users (email, password_hash, firstname, lastname, role, committee_id, country_code) VALUES
('france@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean', 'Dupont', 'delegate', @committee_id, 'FRA'),
('usa@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'delegate', @committee_id, 'USA'),
('uk@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Brown', 'delegate', @committee_id, 'GBR'),
('germany@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hans', 'Mueller', 'delegate', @committee_id, 'DEU');

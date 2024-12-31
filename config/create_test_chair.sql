-- Create a test chair account
INSERT INTO users (email, password_hash, firstname, lastname, role, committee_id)
SELECT 
    'chair@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'Test',
    'Chair',
    'chair',
    id
FROM committees 
WHERE name = 'Security Council'
LIMIT 1;

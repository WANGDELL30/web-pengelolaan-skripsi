ALTER TABLE users
    MODIFY role ENUM('admin', 'operator', 'viewer') DEFAULT 'operator';

INSERT INTO users (username, password, role, full_name, email, notes) VALUES
('viewer', '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW', 'viewer', 'Read Only Viewer', 'viewer@wifiholow.test', 'User viewer read-only')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    full_name = VALUES(full_name),
    email = VALUES(email),
    notes = VALUES(notes);

UPDATE users
SET password = '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW',
    role = 'admin',
    full_name = 'System Admin',
    email = 'admin@wifiholow.test',
    notes = 'Administrator utama'
WHERE username = 'admin';

UPDATE users
SET password = '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW',
    role = 'operator',
    full_name = 'John Operator',
    email = 'john@wifiholow.test',
    notes = 'Operator lapangan'
WHERE username = 'operator1';

UPDATE users
SET password = '$2y$10$nTEDKXvfEWtCbiXSFHmBNOc6kqfr0fibJTTF47Kwtli4RYexCgDTW',
    role = 'viewer',
    full_name = 'Jane Viewer',
    email = 'jane@wifiholow.test',
    notes = 'User pembaca'
WHERE username = 'viewer1';

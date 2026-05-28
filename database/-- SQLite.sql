-- SQLite
ALTER TABLE roles
ADD COLUMN guard_name TEXT DEFAULT 'web';


UPDATE roles
SET guard_name = 'web';


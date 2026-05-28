-- SQLite
ALTER TABLE permissions
ADD COLUMN guard_name TEXT DEFAULT 'web';

UPDATE permissions
SET guard_name = 'web';


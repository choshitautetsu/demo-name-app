-- 新增字段（如果表中已存在字段，可先手工确认避免重复执行）
ALTER TABLE names
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS family_name VARCHAR(255) NOT NULL DEFAULT '';

-- 将旧字段name拆分并迁移
UPDATE names
SET first_name = SUBSTRING_INDEX(name, ' ', 1),
    family_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1))
WHERE first_name = '' OR family_name = '';

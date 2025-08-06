-- 1. 选择数据库
USE namedb;

-- 2. 添加新字段（确保你还没加过）
ALTER TABLE names ADD COLUMN first_name VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE names ADD COLUMN family_name VARCHAR(255) NOT NULL DEFAULT '';

-- 3. 拆分 name 为两个字段（如果是第一次执行）
UPDATE names
SET first_name = SUBSTRING_INDEX(name, ' ', 1),
    family_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1))
WHERE first_name = '' OR family_name = '';

-- Add public flag for address information (controls address, landmark, and pincode visibility)
-- Run this SQL to add the new column to user_user table

ALTER TABLE user_user 
ADD COLUMN is_public_address TINYINT(1) DEFAULT 1 COMMENT 'Controls visibility of all address information (address, landmark, pincode) in public profile view';

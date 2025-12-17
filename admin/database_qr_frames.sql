-- QR Frames Feature - Database Update
-- Run this to add qr_frame column to user_user table

ALTER TABLE user_user ADD COLUMN IF NOT EXISTS qr_frame VARCHAR(100) DEFAULT NULL;

-- The qr_frame column stores the filename of the selected frame (e.g., '6525976-02.png')
-- NULL means use default frame or no frame

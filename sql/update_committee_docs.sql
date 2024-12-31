-- Add description and type columns to committee_docs table
ALTER TABLE committee_docs
ADD COLUMN description TEXT,
ADD COLUMN type VARCHAR(50) DEFAULT 'document' AFTER description;

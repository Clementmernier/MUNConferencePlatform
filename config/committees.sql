-- Drop existing table if it exists
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS committees;
SET FOREIGN_KEY_CHECKS=1;

-- Create committees table
CREATE TABLE committees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    topics TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample committees
INSERT INTO committees (name, description, topics) VALUES
('General Assembly', 'The main deliberative, policymaking and representative organ of the UN', 'International Peace,Security,Development,International Law'),
('Security Council', 'Primary responsibility for maintaining international peace and security', 'Peacekeeping,Conflict Resolution,Sanctions,Military Action'),
('Economic and Social Council', 'Principal body for coordination, policy review, policy dialogue and recommendations on economic, social and environmental issues', 'Sustainable Development,Social Progress,Economic Growth,Environmental Protection'),
('Human Rights Council', 'Responsible for promoting and protecting human rights around the world', 'Human Rights,Civil Liberties,Social Justice,Equality'),
('International Court of Justice', 'Principal judicial organ of the United Nations', 'International Law,Dispute Settlement,Legal Advisory,Justice'),
('World Health Assembly', 'Decision-making body of WHO focused on global health policy', 'Global Health,Disease Prevention,Healthcare Systems,Medical Research');

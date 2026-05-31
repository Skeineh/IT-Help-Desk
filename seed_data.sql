-- =============================================================
-- IT Help Desk & Ticketing Management System
-- Seed Data (Sample Data for Testing)
-- =============================================================

USE ITHelpDesk;

-- Roles
INSERT INTO Role (RoleName, Description) VALUES
('Admin',    'Full system access'),
('Manager',  'Monitor team tickets and reports'),
('Agent',    'IT Support agent who resolves tickets'),
('Employee', 'Standard employee who submits tickets');

-- Categories
INSERT INTO Category (CategoryName, Description) VALUES
('Hardware',      'Computer, printer and physical device issues'),
('Software',      'Application installation and errors'),
('Network',       'Internet, VPN and connectivity problems'),
('Email',         'Outlook and email delivery issues'),
('AccessRequest', 'Permissions and resource access'),
('Other',         'General IT support');

-- Priorities
INSERT INTO Priority (PriorityName, PriorityLevel) VALUES
('Low',      1),
('Medium',   2),
('High',     3),
('Critical', 4);

-- Statuses
INSERT INTO Status (StatusName, Description) VALUES
('Open',       'New ticket awaiting assignment'),
('InProgress', 'Agent is actively working on the ticket'),
('Pending',    'Waiting for user response'),
('Resolved',   'Solution provided'),
('Closed',     'Ticket completed and closed');

-- Sample Users
-- RoleNumber values: 1=Admin, 2=Manager, 3=Agent, 4=Employee (auto-assigned)
INSERT INTO User (RoleNumber, FullName, Email, PasswordHash, Department) VALUES
(1, 'System Administrator', 'admin@company.com',         '$2y$10$REPLACE_WITH_REAL_HASH', 'IT'),
(2, 'Sarah Mansour',        'sarah.manager@company.com', '$2y$10$REPLACE_WITH_REAL_HASH', 'IT'),
(3, 'John Khoury',          'john.agent@company.com',    '$2y$10$REPLACE_WITH_REAL_HASH', 'IT'),
(3, 'Lisa Haddad',          'lisa.agent@company.com',    '$2y$10$REPLACE_WITH_REAL_HASH', 'IT'),
(4, 'Mike Saad',            'mike@company.com',          '$2y$10$REPLACE_WITH_REAL_HASH', 'Sales'),
(4, 'Anna Nassar',          'anna@company.com',          '$2y$10$REPLACE_WITH_REAL_HASH', 'Marketing');

-- Sample Tickets
-- CategoryNumber: 1=Hardware, 2=Software, 3=Network, 4=Email, 5=AccessRequest, 6=Other
-- PriorityNumber: 1=Low, 2=Medium, 3=High, 4=Critical
-- StatusNumber: 1=Open, 2=InProgress, 3=Pending, 4=Resolved, 5=Closed
-- UserNumber: 1=Admin, 2=Sarah, 3=John, 4=Lisa, 5=Mike, 6=Anna
INSERT INTO Ticket (TicketReferenceNumber, Title, Description, CategoryNumber, PriorityNumber, StatusNumber, CreatedByUserNumber, AssignedToUserNumber) VALUES
('TKT-2025-00001', 'Cannot connect to VPN',       'VPN client shows error 619 from home',                   3, 3, 2, 5, 3),
('TKT-2025-00002', 'Outlook keeps crashing',      'Outlook closes immediately after opening',               4, 2, 1, 6, NULL),
('TKT-2025-00003', 'Need access to shared drive', 'Please grant me access to Marketing shared folder',      5, 1, 1, 6, NULL),
('TKT-2025-00004', 'Printer not working',         'The printer on the 3rd floor shows offline',             1, 2, 2, 5, 4);

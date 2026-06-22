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
-- Password for all demo users: Admin@12345
INSERT INTO User (RoleNumber, FullName, Email, PasswordHash, Department, IsActive, MustChangePassword) VALUES
(1, 'System Administrator', 'admin@company.com',         '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'IT',        1, 0),
(2, 'Sarah Mansour',        'sarah.manager@company.com', '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'IT',        1, 0),
(3, 'John Khoury',          'john.agent@company.com',    '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'IT',        1, 0),
(3, 'Lisa Haddad',          'lisa.agent@company.com',    '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'IT',        1, 0),
(4, 'Mike Saad',            'mike@company.com',          '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'Sales',     1, 0),
(4, 'Anna Nassar',          'anna@company.com',          '$2y$12$g6KpL45J1KgyWwWzVtBvKe7sjUKeSVzbumIg1EtgRp/8QHCIwxe22', 'Marketing', 1, 0);

-- Sample Tickets
-- CategoryNumber: 1=Hardware, 2=Software, 3=Network, 4=Email, 5=AccessRequest, 6=Other
-- PriorityNumber: 1=Low, 2=Medium, 3=High, 4=Critical
-- StatusNumber: 1=Open, 2=InProgress, 3=Pending, 4=Resolved, 5=Closed
-- UserNumber: 1=Admin, 2=Sarah, 3=John, 4=Lisa, 5=Mike, 6=Anna
INSERT INTO Ticket (TicketReferenceNumber, Title, Description, CategoryNumber, PriorityNumber, StatusNumber, CreatedByUserNumber, AssignedToUserNumber) VALUES
('TKT-2026-00001', 'Cannot connect to VPN',       'VPN client shows error 619 from home',                   3, 3, 2, 5, 3),
('TKT-2026-00002', 'Outlook keeps crashing',      'Outlook closes immediately after opening',               4, 2, 1, 6, NULL),
('TKT-2026-00003', 'Need access to shared drive', 'Please grant me access to Marketing shared folder',      5, 1, 4, 6, 4),
('TKT-2026-00004', 'Printer not working',         'The printer on the 3rd floor shows offline',             1, 2, 2, 5, 4);

-- Sample history, comments and notifications
INSERT INTO TicketAssignmentHistory (TicketNumber, PreviousAssignedToUserNumber, AssignedToUserNumber, AssignedByUserNumber) VALUES
(1, NULL, 3, 2),
(3, NULL, 4, 2),
(4, NULL, 4, 2);

INSERT INTO TicketStatusHistory (TicketNumber, PreviousStatusNumber, NewStatusNumber, ChangedByUserNumber) VALUES
(1, 1, 2, 3),
(3, 2, 4, 4),
(4, 1, 2, 4);

INSERT INTO TicketComment (TicketNumber, UserNumber, CommentText, IsInternalNote) VALUES
(1, 3, 'Please confirm whether this happens on office Wi-Fi too.', 0),
(1, 2, 'VPN profile may need to be regenerated if the certificate is expired.', 1),
(3, 4, 'Access has been granted and tested.', 0);

INSERT INTO Notification (UserNumber, TicketNumber, NotificationType, Title, Message, IsRead) VALUES
(3, 1, 'ticket_assigned', 'Ticket assigned', 'TKT-2026-00001 was assigned to you.', 0),
(4, 3, 'ticket_assigned', 'Ticket assigned', 'TKT-2026-00003 was assigned to you.', 1),
(6, 3, 'ticket_resolved', 'Ticket resolved', 'TKT-2026-00003 has been resolved.', 0);

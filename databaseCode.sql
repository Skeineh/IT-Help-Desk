-- =============================================================
-- IT Help Desk & Ticketing Management System
-- Database Creation Script
-- Follows SQL Server Standards v1.5 (PascalCase naming rules)
-- Target: MariaDB / MySQL (HeidiSQL)
-- Primary Keys: INT AUTO_INCREMENT named TableName + Number
-- =============================================================

CREATE DATABASE IF NOT EXISTS ITHelpDesk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ITHelpDesk;

-- =============================================================
-- 1. Role
-- =============================================================
CREATE TABLE Role (
    RoleNumber      INT             NOT NULL AUTO_INCREMENT,
    RoleName        VARCHAR(50)     NOT NULL,
    Description     VARCHAR(255)    NULL,
    CreatedDate     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkRole_RoleNumber PRIMARY KEY (RoleNumber),
    CONSTRAINT UnRole_RoleName UNIQUE (RoleName)
);

-- =============================================================
-- 2. User
-- =============================================================
CREATE TABLE User (
    UserNumber      INT             NOT NULL AUTO_INCREMENT,
    RoleNumber      INT             NOT NULL,
    FullName        VARCHAR(100)    NOT NULL,
    Email           VARCHAR(100)    NOT NULL,
    PasswordHash    VARCHAR(255)    NOT NULL,
    PhoneNumber     VARCHAR(20)     NULL,
    Department      VARCHAR(100)    NULL,
    IsActive        TINYINT(1)      NOT NULL DEFAULT 1,
    LastLoginDate   DATETIME        NULL,
    CreatedDate     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkUser_UserNumber PRIMARY KEY (UserNumber),
    CONSTRAINT UnUser_Email UNIQUE (Email),
    CONSTRAINT FkUser_RoleNumber FOREIGN KEY (RoleNumber) REFERENCES Role(RoleNumber)
);

-- =============================================================
-- 3. Category
-- =============================================================
CREATE TABLE Category (
    CategoryNumber  INT             NOT NULL AUTO_INCREMENT,
    CategoryName    VARCHAR(50)     NOT NULL,
    Description     VARCHAR(255)    NULL,
    IsActive        TINYINT(1)      NOT NULL DEFAULT 1,
    CONSTRAINT PkCategory_CategoryNumber PRIMARY KEY (CategoryNumber),
    CONSTRAINT UnCategory_CategoryName UNIQUE (CategoryName)
);

-- =============================================================
-- 4. Priority
-- =============================================================
CREATE TABLE Priority (
    PriorityNumber  INT             NOT NULL AUTO_INCREMENT,
    PriorityName    VARCHAR(50)     NOT NULL,
    PriorityLevel   INT             NOT NULL,
    CONSTRAINT PkPriority_PriorityNumber PRIMARY KEY (PriorityNumber),
    CONSTRAINT UnPriority_PriorityName UNIQUE (PriorityName)
);

-- =============================================================
-- 5. Status
-- =============================================================
CREATE TABLE Status (
    StatusNumber    INT             NOT NULL AUTO_INCREMENT,
    StatusName      VARCHAR(50)     NOT NULL,
    Description     VARCHAR(255)    NULL,
    CONSTRAINT PkStatus_StatusNumber PRIMARY KEY (StatusNumber),
    CONSTRAINT UnStatus_StatusName UNIQUE (StatusName)
);

-- =============================================================
-- 6. Ticket
-- =============================================================
CREATE TABLE Ticket (
    TicketNumber            INT             NOT NULL AUTO_INCREMENT,
    TicketReferenceNumber   VARCHAR(20)     NOT NULL,
    Title                   VARCHAR(200)    NOT NULL,
    Description             TEXT            NOT NULL,
    CategoryNumber          INT             NOT NULL,
    PriorityNumber          INT             NOT NULL,
    StatusNumber            INT             NOT NULL,
    CreatedByUserNumber     INT             NOT NULL,
    AssignedToUserNumber    INT             NULL,
    IsEscalated             TINYINT(1)      NOT NULL DEFAULT 0,
    ResolutionNotes         TEXT            NULL,
    ResolvedDate            DATETIME        NULL,
    CreatedDate             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkTicket_TicketNumber PRIMARY KEY (TicketNumber),
    CONSTRAINT UnTicket_TicketReferenceNumber UNIQUE (TicketReferenceNumber),
    CONSTRAINT FkTicket_CategoryNumber FOREIGN KEY (CategoryNumber) REFERENCES Category(CategoryNumber),
    CONSTRAINT FkTicket_PriorityNumber FOREIGN KEY (PriorityNumber) REFERENCES Priority(PriorityNumber),
    CONSTRAINT FkTicket_StatusNumber FOREIGN KEY (StatusNumber) REFERENCES Status(StatusNumber),
    CONSTRAINT FkTicket_CreatedByUserNumber FOREIGN KEY (CreatedByUserNumber) REFERENCES User(UserNumber),
    CONSTRAINT FkTicket_AssignedToUserNumber FOREIGN KEY (AssignedToUserNumber) REFERENCES User(UserNumber)
);

-- =============================================================
-- 7. TicketComment
-- =============================================================
CREATE TABLE TicketComment (
    TicketCommentNumber INT             NOT NULL AUTO_INCREMENT,
    TicketNumber        INT             NOT NULL,
    UserNumber          INT             NOT NULL,
    CommentText         TEXT            NOT NULL,
    IsInternalNote      TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedDate         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkTicketComment_TicketCommentNumber PRIMARY KEY (TicketCommentNumber),
    CONSTRAINT FkTicketComment_TicketNumber FOREIGN KEY (TicketNumber) REFERENCES Ticket(TicketNumber) ON DELETE CASCADE,
    CONSTRAINT FkTicketComment_UserNumber FOREIGN KEY (UserNumber) REFERENCES User(UserNumber)
);

-- =============================================================
-- 8. TicketAttachment
-- =============================================================
CREATE TABLE TicketAttachment (
    TicketAttachmentNumber  INT             NOT NULL AUTO_INCREMENT,
    TicketNumber            INT             NOT NULL,
    UploadedByUserNumber    INT             NOT NULL,
    FileName                VARCHAR(255)    NOT NULL,
    FilePath                VARCHAR(500)    NOT NULL,
    FileSize                BIGINT          NOT NULL,
    CreatedDate             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkTicketAttachment_TicketAttachmentNumber PRIMARY KEY (TicketAttachmentNumber),
    CONSTRAINT FkTicketAttachment_TicketNumber FOREIGN KEY (TicketNumber) REFERENCES Ticket(TicketNumber) ON DELETE CASCADE,
    CONSTRAINT FkTicketAttachment_UploadedByUserNumber FOREIGN KEY (UploadedByUserNumber) REFERENCES User(UserNumber)
);

-- =============================================================
-- 9. TicketAssignmentHistory
-- =============================================================
CREATE TABLE TicketAssignmentHistory (
    TicketAssignmentHistoryNumber   INT     NOT NULL AUTO_INCREMENT,
    TicketNumber                    INT     NOT NULL,
    AssignedToUserNumber            INT     NULL,
    AssignedByUserNumber            INT     NOT NULL,
    CreatedDate                     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkTicketAssignmentHistory_TicketAssignmentHistoryNumber PRIMARY KEY (TicketAssignmentHistoryNumber),
    CONSTRAINT FkTicketAssignmentHistory_TicketNumber FOREIGN KEY (TicketNumber) REFERENCES Ticket(TicketNumber) ON DELETE CASCADE,
    CONSTRAINT FkTicketAssignmentHistory_AssignedToUserNumber FOREIGN KEY (AssignedToUserNumber) REFERENCES User(UserNumber),
    CONSTRAINT FkTicketAssignmentHistory_AssignedByUserNumber FOREIGN KEY (AssignedByUserNumber) REFERENCES User(UserNumber)
);

-- =============================================================
-- 10. TicketStatusHistory
-- =============================================================
CREATE TABLE TicketStatusHistory (
    TicketStatusHistoryNumber   INT     NOT NULL AUTO_INCREMENT,
    TicketNumber                INT     NOT NULL,
    PreviousStatusNumber        INT     NULL,
    NewStatusNumber             INT     NOT NULL,
    ChangedByUserNumber         INT     NOT NULL,
    CreatedDate                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkTicketStatusHistory_TicketStatusHistoryNumber PRIMARY KEY (TicketStatusHistoryNumber),
    CONSTRAINT FkTicketStatusHistory_TicketNumber FOREIGN KEY (TicketNumber) REFERENCES Ticket(TicketNumber) ON DELETE CASCADE,
    CONSTRAINT FkTicketStatusHistory_PreviousStatusNumber FOREIGN KEY (PreviousStatusNumber) REFERENCES Status(StatusNumber),
    CONSTRAINT FkTicketStatusHistory_NewStatusNumber FOREIGN KEY (NewStatusNumber) REFERENCES Status(StatusNumber),
    CONSTRAINT FkTicketStatusHistory_ChangedByUserNumber FOREIGN KEY (ChangedByUserNumber) REFERENCES User(UserNumber)
);

-- =============================================================
-- 11. Notification
-- =============================================================
CREATE TABLE Notification (
    NotificationNumber  INT             NOT NULL AUTO_INCREMENT,
    UserNumber          INT             NOT NULL,
    TicketNumber        INT             NULL,
    NotificationType    VARCHAR(50)     NOT NULL,
    Title               VARCHAR(200)    NOT NULL,
    Message             VARCHAR(500)    NOT NULL,
    IsRead              TINYINT(1)      NOT NULL DEFAULT 0,
    ReadDate            DATETIME        NULL,
    CreatedDate         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkNotification_NotificationNumber PRIMARY KEY (NotificationNumber),
    CONSTRAINT FkNotification_UserNumber FOREIGN KEY (UserNumber) REFERENCES User(UserNumber) ON DELETE CASCADE,
    CONSTRAINT FkNotification_TicketNumber FOREIGN KEY (TicketNumber) REFERENCES Ticket(TicketNumber) ON DELETE CASCADE
);

-- =============================================================
-- 12. ActivityLog
-- =============================================================
CREATE TABLE ActivityLog (
    ActivityLogNumber       INT             NOT NULL AUTO_INCREMENT,
    UserNumber              INT             NULL,
    ActionType              VARCHAR(100)    NOT NULL,
    EntityType              VARCHAR(100)    NULL,
    EntityReferenceNumber   INT             NULL,
    ActionDescription       VARCHAR(500)    NULL,
    IpAddress               VARCHAR(45)     NULL,
    CreatedDate             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PkActivityLog_ActivityLogNumber PRIMARY KEY (ActivityLogNumber),
    CONSTRAINT FkActivityLog_UserNumber FOREIGN KEY (UserNumber) REFERENCES User(UserNumber) ON DELETE SET NULL
);

-- =============================================================
-- Performance Indexes
-- =============================================================
CREATE INDEX NIX_Ticket_StatusCategory ON Ticket(StatusNumber, CategoryNumber);
CREATE INDEX NIX_Ticket_AssignedToUserNumber ON Ticket(AssignedToUserNumber);
CREATE INDEX NIX_Ticket_CreatedByUserNumber ON Ticket(CreatedByUserNumber);
CREATE INDEX NIX_Notification_UserNumberIsRead ON Notification(UserNumber, IsRead);
CREATE INDEX NIX_ActivityLog_CreatedDate ON ActivityLog(CreatedDate);

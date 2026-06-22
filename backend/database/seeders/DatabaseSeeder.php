<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketAssignmentHistory;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('Admin@12345');

        $roles = collect([
            ['RoleName' => 'Admin', 'Description' => 'Full system access'],
            ['RoleName' => 'Manager', 'Description' => 'Monitor team tickets and reports'],
            ['RoleName' => 'Agent', 'Description' => 'IT support agent who resolves tickets'],
            ['RoleName' => 'Employee', 'Description' => 'Standard employee who submits tickets'],
        ])->mapWithKeys(fn ($role) => [
            $role['RoleName'] => Role::create($role + ['CreatedDate' => $now]),
        ]);

        collect([
            ['CategoryName' => 'Hardware', 'Description' => 'Computer, printer and physical device issues'],
            ['CategoryName' => 'Software', 'Description' => 'Application installation and errors'],
            ['CategoryName' => 'Network', 'Description' => 'Internet, VPN and connectivity problems'],
            ['CategoryName' => 'Email', 'Description' => 'Outlook and email delivery issues'],
            ['CategoryName' => 'AccessRequest', 'Description' => 'Permissions and resource access'],
            ['CategoryName' => 'Other', 'Description' => 'General IT support'],
        ])->each(fn ($category) => Category::create($category + ['IsActive' => true]));

        collect([
            ['PriorityName' => 'Low', 'PriorityLevel' => 1],
            ['PriorityName' => 'Medium', 'PriorityLevel' => 2],
            ['PriorityName' => 'High', 'PriorityLevel' => 3],
            ['PriorityName' => 'Critical', 'PriorityLevel' => 4],
        ])->each(fn ($priority) => Priority::create($priority));

        collect([
            ['StatusName' => 'Open', 'Description' => 'New ticket awaiting assignment'],
            ['StatusName' => 'InProgress', 'Description' => 'Agent is actively working on the ticket'],
            ['StatusName' => 'Pending', 'Description' => 'Waiting for user response'],
            ['StatusName' => 'Resolved', 'Description' => 'Solution provided'],
            ['StatusName' => 'Closed', 'Description' => 'Ticket completed and closed'],
        ])->each(fn ($status) => Status::create($status));

        $admin = User::create([
            'RoleNumber' => $roles['Admin']->RoleNumber,
            'FullName' => 'System Administrator',
            'Email' => 'admin@company.com',
            'PasswordHash' => $password,
            'Department' => 'IT',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $manager = User::create([
            'RoleNumber' => $roles['Manager']->RoleNumber,
            'FullName' => 'Sarah Mansour',
            'Email' => 'sarah.manager@company.com',
            'PasswordHash' => $password,
            'Department' => 'IT',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $agent = User::create([
            'RoleNumber' => $roles['Agent']->RoleNumber,
            'FullName' => 'John Khoury',
            'Email' => 'john.agent@company.com',
            'PasswordHash' => $password,
            'Department' => 'IT',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $agentTwo = User::create([
            'RoleNumber' => $roles['Agent']->RoleNumber,
            'FullName' => 'Lisa Haddad',
            'Email' => 'lisa.agent@company.com',
            'PasswordHash' => $password,
            'Department' => 'IT',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $employee = User::create([
            'RoleNumber' => $roles['Employee']->RoleNumber,
            'FullName' => 'Mike Saad',
            'Email' => 'mike@company.com',
            'PasswordHash' => $password,
            'Department' => 'Sales',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $employeeTwo = User::create([
            'RoleNumber' => $roles['Employee']->RoleNumber,
            'FullName' => 'Anna Nassar',
            'Email' => 'anna@company.com',
            'PasswordHash' => $password,
            'Department' => 'Marketing',
            'IsActive' => true,
            'MustChangePassword' => false,
            'CreatedDate' => $now,
        ]);

        $ticketOne = Ticket::create([
            'TicketReferenceNumber' => 'TKT-2026-00001',
            'Title' => 'Cannot connect to VPN',
            'Description' => 'VPN client shows error 619 from home.',
            'CategoryNumber' => 3,
            'PriorityNumber' => 3,
            'StatusNumber' => 2,
            'CreatedByUserNumber' => $employee->UserNumber,
            'AssignedToUserNumber' => $agent->UserNumber,
            'IsEscalated' => false,
            'CreatedDate' => $now->copy()->subDays(4),
        ]);

        $ticketTwo = Ticket::create([
            'TicketReferenceNumber' => 'TKT-2026-00002',
            'Title' => 'Outlook keeps crashing',
            'Description' => 'Outlook closes immediately after opening.',
            'CategoryNumber' => 4,
            'PriorityNumber' => 2,
            'StatusNumber' => 1,
            'CreatedByUserNumber' => $employeeTwo->UserNumber,
            'AssignedToUserNumber' => null,
            'IsEscalated' => false,
            'CreatedDate' => $now->copy()->subDays(2),
        ]);

        $ticketThree = Ticket::create([
            'TicketReferenceNumber' => 'TKT-2026-00003',
            'Title' => 'Need shared drive access',
            'Description' => 'Please grant access to the Marketing shared folder.',
            'CategoryNumber' => 5,
            'PriorityNumber' => 1,
            'StatusNumber' => 4,
            'CreatedByUserNumber' => $employeeTwo->UserNumber,
            'AssignedToUserNumber' => $agentTwo->UserNumber,
            'IsEscalated' => false,
            'ResolutionNotes' => 'Access was approved by the manager and added to the Marketing group.',
            'ResolvedDate' => $now->copy()->subDay(),
            'CreatedDate' => $now->copy()->subDays(6),
        ]);

        TicketAssignmentHistory::create([
            'TicketNumber' => $ticketOne->TicketNumber,
            'PreviousAssignedToUserNumber' => null,
            'AssignedToUserNumber' => $agent->UserNumber,
            'AssignedByUserNumber' => $manager->UserNumber,
            'CreatedDate' => $now->copy()->subDays(4)->addHours(1),
        ]);

        TicketStatusHistory::create([
            'TicketNumber' => $ticketOne->TicketNumber,
            'PreviousStatusNumber' => 1,
            'NewStatusNumber' => 2,
            'ChangedByUserNumber' => $agent->UserNumber,
            'CreatedDate' => $now->copy()->subDays(3),
        ]);

        TicketStatusHistory::create([
            'TicketNumber' => $ticketThree->TicketNumber,
            'PreviousStatusNumber' => 2,
            'NewStatusNumber' => 4,
            'ChangedByUserNumber' => $agentTwo->UserNumber,
            'CreatedDate' => $now->copy()->subDay(),
        ]);

        TicketComment::create([
            'TicketNumber' => $ticketOne->TicketNumber,
            'UserNumber' => $agent->UserNumber,
            'CommentText' => 'Please confirm whether this happens on office Wi-Fi too.',
            'IsInternalNote' => false,
            'CreatedDate' => $now->copy()->subDays(3)->addHour(),
        ]);

        TicketComment::create([
            'TicketNumber' => $ticketOne->TicketNumber,
            'UserNumber' => $manager->UserNumber,
            'CommentText' => 'VPN profile may need to be regenerated if the certificate is expired.',
            'IsInternalNote' => true,
            'CreatedDate' => $now->copy()->subDays(3)->addHours(2),
        ]);

        Notification::create([
            'UserNumber' => $agent->UserNumber,
            'TicketNumber' => $ticketOne->TicketNumber,
            'NotificationType' => 'ticket_assigned',
            'Title' => 'Ticket assigned',
            'Message' => "{$ticketOne->TicketReferenceNumber} was assigned to you.",
            'IsRead' => false,
            'CreatedDate' => $now->copy()->subDays(4)->addHours(1),
        ]);

        ActivityLog::create([
            'UserNumber' => $employee->UserNumber,
            'ActionType' => 'TicketCreated',
            'EntityType' => 'Ticket',
            'EntityReferenceNumber' => $ticketOne->TicketNumber,
            'ActionDescription' => "Ticket {$ticketOne->TicketReferenceNumber} created.",
            'CreatedDate' => $ticketOne->CreatedDate,
        ]);

        ActivityLog::create([
            'UserNumber' => $employeeTwo->UserNumber,
            'ActionType' => 'TicketCreated',
            'EntityType' => 'Ticket',
            'EntityReferenceNumber' => $ticketTwo->TicketNumber,
            'ActionDescription' => "Ticket {$ticketTwo->TicketReferenceNumber} created.",
            'CreatedDate' => $ticketTwo->CreatedDate,
        ]);

        ActivityLog::create([
            'UserNumber' => $employeeTwo->UserNumber,
            'ActionType' => 'TicketCreated',
            'EntityType' => 'Ticket',
            'EntityReferenceNumber' => $ticketThree->TicketNumber,
            'ActionDescription' => "Ticket {$ticketThree->TicketReferenceNumber} created.",
            'CreatedDate' => $ticketThree->CreatedDate,
        ]);

        ActivityLog::create([
            'UserNumber' => $admin->UserNumber,
            'ActionType' => 'SeederCompleted',
            'EntityType' => 'System',
            'ActionDescription' => 'Week 5 demo seed data created.',
            'CreatedDate' => $now,
        ]);
    }
}

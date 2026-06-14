<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthorizesTickets
{
    protected function resolveUser(): User
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->loadMissing('role');

        return $user;
    }

    protected function roleName(User $user): ?string
    {
        $user->loadMissing('role');

        return $user->role?->RoleName;
    }

    protected function isItRole(User $user): bool
    {
        return in_array($this->roleName($user), ['Admin', 'Manager', 'Agent'], true);
    }

    protected function canViewTicket(User $user, Ticket $ticket): bool
    {
        $roleName = $this->roleName($user);

        if (in_array($roleName, ['Admin', 'Manager'], true)) {
            return true;
        }

        if ($roleName === 'Agent') {
            return (int) $ticket->AssignedToUserNumber === (int) $user->UserNumber;
        }

        if ($roleName === 'Employee') {
            return (int) $ticket->CreatedByUserNumber === (int) $user->UserNumber;
        }

        return false;
    }

    protected function logActivity(
        ?int $userNumber,
        string $actionType,
        ?int $ticketNumber,
        ?string $desc = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        ActivityLog::create([
            'UserNumber'            => $userNumber,
            'ActionType'            => $actionType,
            'EntityType'            => 'Ticket',
            'EntityReferenceNumber' => $ticketNumber,
            'ActionDescription'     => $desc,
            'OldValue'              => $oldValue,
            'NewValue'              => $newValue,
            'IpAddress'             => request()->ip(),
            'CreatedDate'           => now()->toDateTimeString(),
        ]);
    }

    protected function notifyUser(
        ?int $userNumber,
        ?User $actor,
        string $type,
        string $title,
        string $message,
        ?int $ticketNumber = null
    ): void {
        if (!$userNumber) {
            return;
        }

        if ($actor && (int) $actor->UserNumber === (int) $userNumber) {
            return;
        }

        Notification::create([
            'UserNumber'        => $userNumber,
            'TicketNumber'      => $ticketNumber,
            'NotificationType'  => $type,
            'Title'             => $title,
            'Message'           => $message,
            'IsRead'            => false,
            'CreatedDate'       => now()->toDateTimeString(),
        ]);
    }

    protected function notifyMany(
        array $userNumbers,
        ?User $actor,
        string $type,
        string $title,
        string $message,
        ?int $ticketNumber = null
    ): void {
        foreach (array_unique(array_filter($userNumbers)) as $userNumber) {
            $this->notifyUser((int) $userNumber, $actor, $type, $title, $message, $ticketNumber);
        }
    }
}

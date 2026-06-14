<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Role', function (Blueprint $table) {
            $table->increments('RoleNumber');
            $table->string('RoleName', 50)->unique();
            $table->string('Description', 255)->nullable();
            $table->dateTime('CreatedDate')->useCurrent();
        });

        Schema::create('User', function (Blueprint $table) {
            $table->increments('UserNumber');
            $table->unsignedInteger('RoleNumber');
            $table->string('FullName', 100);
            $table->string('Email', 100)->unique();
            $table->string('PasswordHash', 255);
            $table->string('PhoneNumber', 20)->nullable();
            $table->string('Department', 100)->nullable();
            $table->boolean('IsActive')->default(true);
            $table->boolean('MustChangePassword')->default(true);
            $table->dateTime('LastLoginDate')->nullable();
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('RoleNumber')->references('RoleNumber')->on('Role');
        });

        Schema::create('Category', function (Blueprint $table) {
            $table->increments('CategoryNumber');
            $table->string('CategoryName', 50)->unique();
            $table->string('Description', 255)->nullable();
            $table->boolean('IsActive')->default(true);
        });

        Schema::create('Priority', function (Blueprint $table) {
            $table->increments('PriorityNumber');
            $table->string('PriorityName', 50)->unique();
            $table->integer('PriorityLevel');
        });

        Schema::create('Status', function (Blueprint $table) {
            $table->increments('StatusNumber');
            $table->string('StatusName', 50)->unique();
            $table->string('Description', 255)->nullable();
        });

        Schema::create('Ticket', function (Blueprint $table) {
            $table->increments('TicketNumber');
            $table->string('TicketReferenceNumber', 20)->unique();
            $table->string('Title', 200);
            $table->text('Description');
            $table->unsignedInteger('CategoryNumber');
            $table->unsignedInteger('PriorityNumber');
            $table->unsignedInteger('StatusNumber');
            $table->unsignedInteger('CreatedByUserNumber');
            $table->unsignedInteger('AssignedToUserNumber')->nullable();
            $table->boolean('IsEscalated')->default(false);
            $table->text('ResolutionNotes')->nullable();
            $table->dateTime('ResolvedDate')->nullable();
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('CategoryNumber')->references('CategoryNumber')->on('Category');
            $table->foreign('PriorityNumber')->references('PriorityNumber')->on('Priority');
            $table->foreign('StatusNumber')->references('StatusNumber')->on('Status');
            $table->foreign('CreatedByUserNumber')->references('UserNumber')->on('User');
            $table->foreign('AssignedToUserNumber')->references('UserNumber')->on('User');
            $table->index(['StatusNumber', 'CategoryNumber'], 'NIX_Ticket_StatusCategory');
            $table->index('AssignedToUserNumber', 'NIX_Ticket_AssignedToUserNumber');
            $table->index('CreatedByUserNumber', 'NIX_Ticket_CreatedByUserNumber');
        });

        Schema::create('TicketComment', function (Blueprint $table) {
            $table->increments('TicketCommentNumber');
            $table->unsignedInteger('TicketNumber');
            $table->unsignedInteger('UserNumber');
            $table->text('CommentText');
            $table->boolean('IsInternalNote')->default(false);
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('TicketNumber')->references('TicketNumber')->on('Ticket')->cascadeOnDelete();
            $table->foreign('UserNumber')->references('UserNumber')->on('User');
        });

        Schema::create('TicketAttachment', function (Blueprint $table) {
            $table->increments('TicketAttachmentNumber');
            $table->unsignedInteger('TicketNumber');
            $table->unsignedInteger('UploadedByUserNumber');
            $table->string('FileName', 255);
            $table->string('FilePath', 500);
            $table->string('MimeType', 150)->nullable();
            $table->bigInteger('FileSize');
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('TicketNumber')->references('TicketNumber')->on('Ticket')->cascadeOnDelete();
            $table->foreign('UploadedByUserNumber')->references('UserNumber')->on('User');
        });

        Schema::create('TicketAssignmentHistory', function (Blueprint $table) {
            $table->increments('TicketAssignmentHistoryNumber');
            $table->unsignedInteger('TicketNumber');
            $table->unsignedInteger('PreviousAssignedToUserNumber')->nullable();
            $table->unsignedInteger('AssignedToUserNumber')->nullable();
            $table->unsignedInteger('AssignedByUserNumber');
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('TicketNumber')->references('TicketNumber')->on('Ticket')->cascadeOnDelete();
            $table->foreign('PreviousAssignedToUserNumber')->references('UserNumber')->on('User');
            $table->foreign('AssignedToUserNumber')->references('UserNumber')->on('User');
            $table->foreign('AssignedByUserNumber')->references('UserNumber')->on('User');
        });

        Schema::create('TicketStatusHistory', function (Blueprint $table) {
            $table->increments('TicketStatusHistoryNumber');
            $table->unsignedInteger('TicketNumber');
            $table->unsignedInteger('PreviousStatusNumber')->nullable();
            $table->unsignedInteger('NewStatusNumber');
            $table->unsignedInteger('ChangedByUserNumber');
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('TicketNumber')->references('TicketNumber')->on('Ticket')->cascadeOnDelete();
            $table->foreign('PreviousStatusNumber')->references('StatusNumber')->on('Status');
            $table->foreign('NewStatusNumber')->references('StatusNumber')->on('Status');
            $table->foreign('ChangedByUserNumber')->references('UserNumber')->on('User');
        });

        Schema::create('Notification', function (Blueprint $table) {
            $table->increments('NotificationNumber');
            $table->unsignedInteger('UserNumber');
            $table->unsignedInteger('TicketNumber')->nullable();
            $table->string('NotificationType', 50);
            $table->string('Title', 200);
            $table->string('Message', 500);
            $table->boolean('IsRead')->default(false);
            $table->dateTime('ReadDate')->nullable();
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('UserNumber')->references('UserNumber')->on('User')->cascadeOnDelete();
            $table->foreign('TicketNumber')->references('TicketNumber')->on('Ticket')->cascadeOnDelete();
            $table->index(['UserNumber', 'IsRead'], 'NIX_Notification_UserNumberIsRead');
        });

        Schema::create('ActivityLog', function (Blueprint $table) {
            $table->increments('ActivityLogNumber');
            $table->unsignedInteger('UserNumber')->nullable();
            $table->string('ActionType', 100);
            $table->string('EntityType', 100)->nullable();
            $table->integer('EntityReferenceNumber')->nullable();
            $table->string('ActionDescription', 500)->nullable();
            $table->string('OldValue', 255)->nullable();
            $table->string('NewValue', 255)->nullable();
            $table->string('IpAddress', 45)->nullable();
            $table->dateTime('CreatedDate')->useCurrent();

            $table->foreign('UserNumber')->references('UserNumber')->on('User')->nullOnDelete();
            $table->index('CreatedDate', 'NIX_ActivityLog_CreatedDate');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('ActivityLog');
        Schema::dropIfExists('Notification');
        Schema::dropIfExists('TicketStatusHistory');
        Schema::dropIfExists('TicketAssignmentHistory');
        Schema::dropIfExists('TicketAttachment');
        Schema::dropIfExists('TicketComment');
        Schema::dropIfExists('Ticket');
        Schema::dropIfExists('Status');
        Schema::dropIfExists('Priority');
        Schema::dropIfExists('Category');
        Schema::dropIfExists('User');
        Schema::dropIfExists('Role');
    }
};

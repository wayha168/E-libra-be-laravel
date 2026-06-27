<?php



use Illuminate\Support\Facades\Broadcast;



Broadcast::channel('App.Models.User.{id}', function ($user, $id) {

    return (string) $user->id === (string) $id;

});



Broadcast::channel('purchases.{userId}', function ($user, $userId) {

    return (string) $user->id === (string) $userId;

});



Broadcast::channel('notifications.{userId}', function ($user, $userId) {

    return (string) $user->id === (string) $userId;

});



Broadcast::channel('dashboard.overview', function ($user) {

    return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()

        || method_exists($user, 'isAdmin') && $user->isAdmin();

});



Broadcast::channel('dashboard.activities', function ($user) {

    return method_exists($user, 'isStaff') && $user->isStaff();

});



Broadcast::channel('dashboard.presence', function ($user) {

    return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()

        || method_exists($user, 'isAdmin') && $user->isAdmin();

});



// ─── Chat channels ────────────────────────────────────────────────────────────

// Per-conversation channel: the conversation owner (user) OR an admin/super_admin
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {

    // Admin/super_admin can listen to any conversation
    if (method_exists($user, 'isAdmin') && ($user->isAdmin() || $user->isSuperAdmin())) {
        return true;
    }

    // Regular user can only listen to their own conversation
    $conversation = \App\Models\ChatConversation::find($conversationId);

    return $conversation && (string) $conversation->user_id === (string) $user->id;

});



// Admin global channel: notified when any user sends a new message
Broadcast::channel('admin.chats', function ($user) {

    return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()

        || method_exists($user, 'isAdmin') && $user->isAdmin();

});


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


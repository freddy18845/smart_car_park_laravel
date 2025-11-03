<?php

use Illuminate\Support\Facades\Broadcast;

// Default user-specific private channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for subspace updates (all clients can listen)
Broadcast::channel('subspaces', function () {
    return true;
});

// Private channel for individual user notifications
Broadcast::channel('user.notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

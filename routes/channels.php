<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('superadmin.alerts', function ($user) {
    return $user?->isSuperadmin() === true;
});

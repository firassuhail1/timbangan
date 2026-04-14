<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('timbangan.{espId}', function () {
    return true; // public, ESP32 tidak perlu auth
});

Broadcast::channel('perintah.{espId}', function () {
    return true;
});

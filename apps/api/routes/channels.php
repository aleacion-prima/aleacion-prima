<?php

use App\Broadcasting\CanalArea;
use App\Broadcasting\CanalTablero;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tablero', CanalTablero::class);
Broadcast::channel('area.{area}', CanalArea::class);

<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| All order and staff channels require authorization. Clients subscribe with
| Echo.private(), and these callbacks verify identity and role.
|
*/

/*
|--------------------------------------------------------------------------
| Private Channel: customer.{userId}
|--------------------------------------------------------------------------
| Only the authenticated customer who owns the userId may subscribe.
*/
Broadcast::channel('customer.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/*
|--------------------------------------------------------------------------
| Private Channel: admin
|--------------------------------------------------------------------------
| Only users with the admin role may subscribe.
*/
Broadcast::channel('admin', function ($user) {
    return $user->role === 'admin' || $user->hasRole('admin');
});

/*
|--------------------------------------------------------------------------
| Private Staff Channels
|--------------------------------------------------------------------------
| orders -> Admin, Waiter, Chef (new orders, order status updates)
| waiter -> Admin, Waiter (orders ready to be served)
*/
Broadcast::channel('orders', function ($user) {
    return $user->hasAnyRole(['admin', 'waiter', 'chef']);
});

Broadcast::channel('waiter', function ($user) {
    return $user->hasAnyRole(['admin', 'waiter']);
});

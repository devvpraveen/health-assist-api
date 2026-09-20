<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisterUserAction
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'tenant_id' => null,
            'status' => 'active',
        ]);

        event(new Registered($user));

        return $user;
    }
}

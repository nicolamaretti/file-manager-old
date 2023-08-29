<?php

namespace App\Actions\Fortify;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user with his root folder.
     *
     * @param array<string, string> $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

//        $user = User::create([
//            'name' => $input['name'],
//            'email' => $input['email'],
//            'password' => Hash::make($input['password']),
//            'email_verified_at' => now(),
//            'remember_token' => Str::random(10),
//        ]);

        $user = new User();
        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->password = Hash::make($input['password']);
        $user->email_verified_at = now();
        $user->remember_token = Str::random(10);
        $user->can_write_folder = true;
        $user->save();

        $folder = Folder::create([
            'name' => $input['name'] . 'Folder',
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
        ]);

        if ($folder) {
            $user->root_folder_id = $folder->id;
            $user->save();
        }

        return $user;
    }
}

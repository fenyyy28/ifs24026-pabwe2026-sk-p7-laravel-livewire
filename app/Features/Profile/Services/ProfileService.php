<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function updateProfile(User $user, string $name, string $username): User
    {
        $user->update(['name' => $name, 'username' => $username]);

        return $user;
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->update(['password' => $password]);

        return $user;
    }

    public function updatePhoto(User $user, UploadedFile $photo): User
    {
        $this->deletePhotoFile($user);
        $user->update(['profile_photo_path' => $photo->store('profile-photos', 'public')]);

        return $user;
    }

    public function removePhoto(User $user): User
    {
        $this->deletePhotoFile($user);
        $user->update(['profile_photo_path' => null]);

        return $user;
    }

    private function deletePhotoFile(User $user): void
    {
        if ($user->profile_photo_path !== null) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
    }
}

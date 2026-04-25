<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
            'password' => ['nullable', 'sometimes', Password::min(8)],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return $user->refresh()->load('department');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);
        $user = $request->user();
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        $path = $request->file('image')->store('avatars', 'public');
        $user->update(['profile_image' => $path]);
        $url = asset('storage/'.$path);

        return response()->json([
            'profile_image' => $path,
            'url' => $url,
        ]);
    }
}

<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserLocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $user = User::find($request->user_id);
        $user->update([
            'latitude' => $request->lat,
            'longitude' => $request->lng,
        ]);

        return response()->json(['message' => 'Location updated successfully']);
    }
}


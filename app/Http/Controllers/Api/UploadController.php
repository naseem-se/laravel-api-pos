<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function image(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:5120'], // 5MB
        ]);

        $path = $request->file('image')->store('menu-items', 'public');

        return response()->json([
            'success' => true,
            'url' => '/storage/'.$path,
        ]);
    }
}
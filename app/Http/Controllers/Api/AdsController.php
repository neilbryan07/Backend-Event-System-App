<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdsController extends Controller
{
    // ✅ Create Ad (Upload)
    public function storeAd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|mimes:jpg,jpeg,png,gif,pdf|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('uploads/ads', 'public');
        } else {
            return response()->json(['error' => 'File is required'], 400);
        }

        $ad = Ads::create([
            'file_name' => $request->file_name,
            'file' => $filePath,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Ad uploaded successfully', 'data' => $ad], 201);
    }

    // ✅ View All Ads
public function index()
{
    // Fetch ads with user data joined (assuming 'created_by' is foreign key to users.id)
    $ads = Ads::with('user')
        ->orderBy('id', 'desc')
        ->get();

    // Add file URL for each ad
    foreach ($ads as $ad) {
        $ad->file_url = Storage::url($ad->file);
    }

    return response()->json($ads);
}

    // ✅ Edit Ad (optional fields)
    public function updateAd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:ads,id',
            'file_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|mimes:jpg,jpeg,png,gif,pdf|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $ad = Ads::find($request->id);

        if (!$ad) {
            return response()->json(['error' => 'Ad not found'], 404);
        }

        // Handle file update if new file is uploaded
        if ($request->hasFile('file')) {
            if ($ad->file && Storage::exists('public/' . $ad->file)) {
                Storage::delete('public/' . $ad->file);
            }

            $file = $request->file('file');
            $filePath = $file->store('uploads/ads', 'public');
            $ad->file = $filePath;
        }

        // Optional updates
        if ($request->filled('file_name')) {
            $ad->file_name = $request->file_name;
        }

        if ($request->filled('description')) {
            $ad->description = $request->description;
        }

        $ad->save();

        $ad->file_url = Storage::url($ad->file);

        return response()->json(['message' => 'Ad updated successfully', 'data' => $ad]);
    }

    // ✅ Delete Ad
    public function deleteAd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:ads,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $ad = Ads::find($request->id);

        // Delete the stored file
        if ($ad->file && Storage::exists('public/' . $ad->file)) {
            Storage::delete('public/' . $ad->file);
        }

        $ad->delete();

        return response()->json(['message' => 'Ad deleted successfully']);
    }
}

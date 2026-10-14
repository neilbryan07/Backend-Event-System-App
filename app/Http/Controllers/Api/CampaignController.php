<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Branch;

class CampaignController extends Controller
{
    // ✅ CREATE Campaign (must have OM, optional branch)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'om_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role_id', 2)) // Only OM
            ],
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $campaign = Campaign::create([
            'name' => $request->name,
            'om_id' => $request->om_id,
            'branch_id' => $request->branch_id
        ]);

        return response()->json([
            'message' => 'Campaign Created',
            'data' => $campaign
        ]);
    }

    // ✅ UPDATE Campaign (can update branch)
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:campaigns,id',
            'name' => 'required|string|max:255',
            'om_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role_id', 2))
            ],
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $campaign = Campaign::findOrFail($request->id);
        $campaign->update([
            'name' => $request->name,
            'om_id' => $request->om_id,
            'branch_id' => $request->branch_id
        ]);

        return response()->json([
            'message' => 'Campaign Updated',
            'data' => $campaign
        ]);
    }

    // ✅ DELETE Campaign
   public function destroy(Request $request)
{
    $request->validate([
        'id' => 'required|exists:campaigns,id'
    ]);

    $campaign = Campaign::findOrFail($request->id);
    $campaign->delete();

    return response()->json([
        'message' => 'Campaign deleted successfully'
    ]);
}

    // ✅ LIST Campaigns with OM & Branch
    public function index()
    {
        $campaigns = Campaign::with(['om', 'branch'])->get();

        return response()->json([
            'data' => $campaigns
        ]);
    }

    // ✅ ASSIGN USER TO CAMPAIGN

    // ✅ LIST BRANCHES
    public function branchList()
    {
        return response()->json([
            'data' => Branch::all()
        ]);
    }

}
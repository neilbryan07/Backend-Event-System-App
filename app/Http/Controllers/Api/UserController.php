<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use App\Models\Campaign;
use App\Models\Status;
use App\Models\UserEventStatus;

class UserController extends Controller
{
    // Register
public function register(Request $request)
{
    try {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile_no' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'branch_id' => 'nullable|exists:branches,id',
            'gender' => 'nullable|string',

            // ✅ REQUIRED FOR PUBLIC ROLE
            'om_id' => [
                'required_if:role_id,5',
                'exists:users,id',
                function ($attribute, $value, $fail) {

                    $user = User::find($value);

                    if (!$user || $user->role_id != 2) {
                        $fail('Selected OM is invalid.');
                    }
                }
            ],

            // ✅ NEW REQUIRED FIELDS FOR ROLE 5
            'company_information' => 'required_if:role_id,5|string|max:255',
     
        ], [
            'company_information.required_if' => 'Company information is required for Public users.',
            'om_id.required_if' => 'OM is required for Public users.',
        ]);

        $campaignId = $request->campaign_id;
        $branchId = $request->branch_id;

        // ============================================
        // PUBLIC ACCOUNT LOGIC
        // ============================================
        if ($request->role_id == 5) {

            $omUser = User::find($request->om_id);

            if (!$omUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'OM user not found.'
                ], 404);
            }

            if ($omUser->role_id != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not an OM.'
                ], 422);
            }

            // Campaign name
            $campaignName = strtoupper(trim($omUser->name)) . ' PUBLIC';

            $campaign = Campaign::where('om_id', $omUser->id)
                ->where('name', $campaignName)
                ->first();

            if (!$campaign) {
                $campaign = Campaign::create([
                    'name' => $campaignName,
                    'om_id' => $omUser->id,
                    'branch_id' => $omUser->branch_id,
                ]);
            }

            $campaignId = $campaign->id;
            $branchId = $omUser->branch_id;
        }

        // ============================================
        // CREATE USER
        // ============================================
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'company_information' => $request->company_information,
            'school' => $request->school,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'campaign_id' => $campaignId,
            'branch_id' => $branchId,
            'gender' => $request->gender,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    // Login

 public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Eager load role, branch, and campaign with campaign branch
    $user = User::with(['role', 'branch', 'campaign.branch'])
        ->where('email', $request->email)
        ->first();

    // Wrong credentials
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid email or password.',
        ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'Logged in successfully',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'branch' => $user->branch,
            'role' => $user->role,
            'campaign' => [
                'id' => $user->campaign->id ?? null,
                'name' => $user->campaign->name ?? null,
                'om_id' => $user->campaign->om_id ?? null,
                'branch_id' => $user->campaign->branch_id ?? null,
                'branch' => $user->campaign->branch ?? null,
                'created_at' => $user->campaign->created_at ?? null,
                'updated_at' => $user->campaign->updated_at ?? null,
            ],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ],
    ], 200);
}
    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // Dashboard: show all events + status
 public function dashboard(Request $request)
{
    $user = $request->user();

    // -------------np----------------
    // VALIDATION
    // -----------------------------
    $request->validate([
        'status' => 'nullable|in:ongoing_today,upcoming,archived',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'event_id' => 'nullable|exists:events,id'
    ]);

    $query = \App\Models\Event::with([
        'days.userEventStatuses' => function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orderBy('created_at', 'desc');
        },
        'days.userEventStatuses.status'
    ])
    ->orderBy('created_at', 'desc');

    // -----------------------------
    // EVENT ID FILTER
    // -----------------------------
    if ($request->filled('event_id')) {
        $query->where('id', $request->event_id);
    }

    // -----------------------------
    // CLEAN DATE RANGE FILTER (FIXED)
    // -----------------------------
    if ($request->filled('start_date') || $request->filled('end_date')) {

        $startDate = $request->start_date
            ? \Carbon\Carbon::parse($request->start_date)->startOfDay()
            : null;

        $endDate = $request->end_date
            ? \Carbon\Carbon::parse($request->end_date)->endOfDay()
            : null;

        $query->whereHas('days', function ($q) use ($startDate, $endDate) {

            $q->where(function ($day) use ($startDate, $endDate) {

                // TRUE OVERLAP RULE (FIXED NULL cutoff issue)
                if ($startDate && $endDate) {

                    $day->where('event_start', '<=', $endDate)
                        ->where(function ($cutoff) use ($startDate) {

                            $cutoff->where(function ($q) use ($startDate) {
                                $q->whereNull('event_cutoff')
                                  ->where('event_start', '>=', $startDate);
                            })
                            ->orWhere(function ($q) use ($startDate) {
                                $q->whereNotNull('event_cutoff')
                                  ->where('event_cutoff', '>=', $startDate);
                            });

                        });

                } elseif ($startDate) {

                    $day->where('event_start', '>=', $startDate);

                } elseif ($endDate) {

                    $day->where('event_start', '<=', $endDate);
                }
            });
        });
    }

    $events = $query->get();

    // -----------------------------
    // TRANSFORM + STATUS LOGIC
    // -----------------------------
    $events->transform(function ($event) {

        $today = \Carbon\Carbon::today();

        $hasOngoingToday = false;
        $hasUpcoming = false;

        foreach ($event->days as $day) {

            $start = \Carbon\Carbon::parse($day->event_start);
            $end = $day->event_cutoff
                ? \Carbon\Carbon::parse($day->event_cutoff)
                : $start;

            if ($today->between($start->copy()->startOfDay(), $end->copy()->endOfDay())) {
                $hasOngoingToday = true;
            }

            if ($start->isFuture()) {
                $hasUpcoming = true;
            }
        }

        // EVENT STATUS
        if ($hasOngoingToday) {
            $status = 'ongoing_today';
        } elseif ($hasUpcoming) {
            $status = 'upcoming';
        } else {
            $status = 'archived';
        }

        // DAYS TRANSFORM
        $days = collect($event->days)->map(function ($day) {

            $currentStatus = $day->userEventStatuses->first();

            $logs = $day->userEventStatuses->slice(1)->map(function ($status) {
                return [
                    'id' => $status->status_id,
                    'name' => optional($status->status)->name,
                    'scanned_at' => $status->scanned_at
                ];
            });

            $now = \Carbon\Carbon::today();
            $start = \Carbon\Carbon::parse($day->event_start);
            $end = $day->event_cutoff
                ? \Carbon\Carbon::parse($day->event_cutoff)
                : $start;

            if ($now->between($start->copy()->startOfDay(), $end->copy()->endOfDay())) {
                $dayStatus = 'ongoing_today';
            } elseif ($start->isFuture()) {
                $dayStatus = 'upcoming';
            } else {
                $dayStatus = 'archived';
            }

            return [
                'day_id' => $day->id,
                'day_name' => $day->day_name ?? ('Day ' . $day->id),

                'event_start' => $day->event_start,
                'event_cutoff' => $day->event_cutoff,

                'status' => $dayStatus,
                'status_label' => ucfirst(str_replace('_', ' ', $dayStatus)),

                'qr_image' => $day->qr_image ? asset($day->qr_image) : null,

                'current_status' => $currentStatus ? [
                    'id' => $currentStatus->status_id,
                    'name' => optional($currentStatus->status)->name,
                    'scanned_at' => $currentStatus->scanned_at
                ] : null,

                'logs' => $logs
            ];
        });

        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'instruction' => $event->instruction,
            'address' => $event->address,
            'image' => $event->image ? asset($event->image) : null,
            'total_attendees' => $event->total_attendees,
            'registration_cutoff' => $event->registration_cutoff,
            'created_at' => $event->created_at,
            'is_active' => $event->is_active,

            'status' => $status,
            'status_label' => ucfirst(str_replace('_', ' ', $status)),

            'days' => $days
        ];
    });

    // -----------------------------
    // STATUS FILTER
    // -----------------------------
    if ($request->filled('status')) {
        $events = $events->filter(function ($event) use ($request) {
            return $event['status'] === $request->status;
        })->values();
    }

    // -----------------------------
    // RESPONSE
    // -----------------------------
    return response()->json([
        'filters' => [
            'event_id' => $request->event_id,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ],
        'total' => $events->count(),
        'data' => $events
    ]);
}
    public function roleindex()
    {
        $roles = Role::all();
        return response()->json([
            'message' => 'Roles fetched successfully',
            'data' => $roles
        ]);
    }
    public function listOMUsers()
    {
        // Get all OMs
        $omUsers = User::where('role_id', 2)->get()->map(function ($user) {
            // Get the campaign assigned to this OM
            $campaign = Campaign::where('om_id', $user->id)->with('branch')->first();

            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'gender'   => $user->gender,
                'role_id'  => $user->role_id,
                'campaign' => $campaign ? [
                    'id'     => $campaign->id,
                    'name'   => $campaign->name,
                    'branch' => $campaign->branch ? [
                        'id'   => $campaign->branch->id,
                        'name' => $campaign->branch->name
                    ] : null
                ] : null,
            ];
        });

        return response()->json([
            'data' => $omUsers
        ]);
    }
    public function indexStatus()
    {
        $statuses = Status::all(); // fetch all entries

        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }
public function userstats($user_id)
{
    // -----------------------------
    // Get all user event statuses
    // -----------------------------
    $statuses = Status::all();

    $data = $statuses->map(function ($status) use ($user_id) {

        // Get all records for this user + status
        $records = \App\Models\UserEventStatus::with('eventDay')
            ->where('user_id', $user_id)
            ->where('status_id', $status->id)
            ->get();

        // -----------------------------
        // IMPORTANT FIX:
        // Count UNIQUE EVENTS ONLY
        // -----------------------------
        $uniqueEvents = $records->pluck('eventDay.event_id')->unique()->count();

        return [
            'status_id' => $status->id,
            'status' => $status->name,

            // ✅ CLEAN COUNT (NOT DUPLICATED BY DAYS)
            'total_events' => $uniqueEvents,

            // Optional debug info (VERY USEFUL for frontend)
            'total_records' => $records->count(),
        ];
    });

    return response()->json([
        'success' => true,
        'user_id' => $user_id,
        'data' => $data
    ]);
}
public function externalLogin(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    // ---------------------------------------
    // CALL EXTERNAL API
    // ---------------------------------------
    $response = Http::get('https://611systems.com/api/events/index.php', [
        'act' => 'login',
        'ac' => '611',
        'ak' => '546453435asd23asd143asdk23kj4h5y4gh23',
        'email' => $request->email,
        'password' => $request->password,
    ]);

    if (!$response->successful()) {
        return response()->json(['message' => 'External server error'], 500);
    }

    $result = $response->json();

    if (($result['stat'] ?? null) !== 'ok') {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $data = $result['data'];

    // ---------------------------------------
    // ROLE FLAGS
    // ---------------------------------------
    $roleName = strtolower(trim($data['role_name'] ?? ''));

    $isOM = ($roleName === 'operations manager');
    $isHR = ($roleName === 'hr');

    $newRoleId = $isOM ? 2 : 3;

    // ---------------------------------------
    // UPSERT USER
    // ---------------------------------------
    $user = User::where('email', $data['email'])->first();

    $userPayload = [
        'name'       => $data['full_name'] ?? '',
        'email'      => $data['email'],
        'mobile_no'  => $data['mobile_no'] ?? null,
        'status'     => $data['status'] ?? null,
        'age'        => $data['age'] ?? null,
        'gender'     => $data['gender'] ?? null,
        'branch_id'  => data_get($data, 'profile_r.branch_id'),
    ];

    if (!$user) {

        $userPayload['role_id'] = $newRoleId;
        $userPayload['password'] = Hash::make($request->password);

        $user = User::create($userPayload);

    } else {

        // PROTECT ROLE IF ALREADY SYSTEM-LOCKED (role_id = 1)
        if ((int) $user->role_id !== 1) {
            $userPayload['role_id'] = $newRoleId;
        }

        $user->update($userPayload);
    }

    // ---------------------------------------
    // PROJECT NAME
    // ---------------------------------------
    $projectName =
        data_get($data, 'team_r.project_r.name')
        ?? data_get($data, 'team_r.name')
        ?? null;

    $projectName = $projectName
        ? strtoupper(trim(preg_replace('/\s+/', ' ', $projectName)))
        : null;

    // ---------------------------------------
    // FIND OM USER
    // ---------------------------------------
    $omEmail = data_get($data, 'team_r.om_r.email');

    $omUser = null;
    if ($omEmail) {
        $omUser = User::where('email', $omEmail)->first();
    }

    // ---------------------------------------
    // FIND OR CREATE CAMPAIGN
    // ---------------------------------------
    $campaign = null;

    if ($projectName && $omUser) {

        $campaign = Campaign::where('om_id', $omUser->id)
            ->get()
            ->first(function ($c) use ($projectName) {
                return strtoupper(trim(preg_replace('/\s+/', ' ', $c->name))) === $projectName;
            });
    }

    if (!$campaign && $projectName && $omUser) {

        $campaign = Campaign::create([
            'name'      => $projectName,
            'om_id'     => $omUser->id,
            'branch_id' => data_get($data, 'profile_r.branch_id'),
        ]);
    }

    // ---------------------------------------
    // ASSIGN CAMPAIGN
    // ---------------------------------------
    $user->update([
        'campaign_id' => $campaign ? $campaign->id : null,
    ]);

    // ---------------------------------------
    // LOAD RELATIONS
    // ---------------------------------------
    $user->load(['role', 'branch', 'campaign.branch']);

    // ---------------------------------------
    // TOKEN
    // ---------------------------------------
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message'   => 'External login successful',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'is_hr'     => $isHR,
        'user'      => $user
    ]);
}
public function getOm(Request $request)
    {
        // Search keyword
        $search = $request->query('search');

        // Limit per page (default 10)
        $show = $request->query('show', 10);

        // Sort direction (asc or desc)
        $sort = strtolower($request->query('sort', 'desc'));

        // Validate sort
        if (!in_array($sort, ['asc', 'desc'])) {
            $sort = 'desc';
        }

        $query = User::with(['role', 'branch'])
            ->where('role_id', 2);

        // Search by name
        if (!empty($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        // Fast select only needed fields
        $query->select([
            'id',
            'name',
            'email',
            'role_id',
            'branch_id',
            'mobile_no',
            'status',
            'gender',
            'created_at'
        ]);

        // Order by latest
        $query->orderBy('id', $sort);

        // Paginate
        $users = $query->paginate($show);

        return response()->json([
            'status' => true,
            'message' => 'OM list fetched successfully',
            'data' => $users
        ]);
    }
    public function assignRole(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'role_id' => 'required|integer'
    ]);

    $user = User::find($request->user_id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    // OPTIONAL: protect system-locked role (role_id = 1)
    if ((int) $user->role_id === 1) {
        return response()->json([
            'message' => 'This user role is locked and cannot be changed'
        ], 403);
    }

    $user->update([
        'role_id' => $request->role_id
    ]);

    return response()->json([
        'message' => 'Role assigned successfully',
        'user' => $user
    ]);
}
}

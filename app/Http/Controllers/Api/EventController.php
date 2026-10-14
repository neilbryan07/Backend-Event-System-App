<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventDay;
use App\Models\Status;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\UserEventStatus;
use App\Models\UserEventVerified;
use App\Models\UserEventDayVerified;
use Carbon\Carbon;

class EventController extends Controller
{
    // Create Event + Multi-Day QR with image upload
    // Create Event + Multi-Day QR with image upload
    public function createEvent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instruction' => 'nullable|string',
            'address' => 'nullable|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:10240',
            'total_attendees' => 'nullable|integer',
            'registration_cutoff' => 'nullable|date',
            'days' => 'required|string',
        ]);

        $data = $request->only([
            'name',
            'description',
            'instruction',
            'address',
            'total_attendees',
            'registration_cutoff'
        ]);

        // Upload image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'event_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('events', $fileName, 'public');
            $data['image'] = 'storage/events/' . $fileName;
        }

        // Create event
        $event = Event::create($data);

        // Decode days JSON
        $days = json_decode($request->days, true);
        if (!$days || !is_array($days)) {
            return response()->json(['message' => 'Invalid days format'], 422);
        }

        $createdDays = [];

        foreach ($days as $index => $day) {
            $dayName = isset($day['day_name']) && $day['day_name']
                ? $day['day_name']
                : 'Day ' . ($index + 1); // fallback

            $eventStart = $day['event_start'] ?? null;
            $eventCutoff = $day['event_cutoff'] ?? null;

            if (!$eventStart) continue;

            // Generate QR
            $token = Str::random(40);
            $qrFileName = 'qrcodes/event_' . $event->id . '_' . date('Ymd_His', strtotime($eventStart)) . '.svg';

            Storage::disk('public')->put(
                $qrFileName,
                QrCode::format('svg')->size(300)->generate($token)
            );

            $eventDay = EventDay::create([
                'event_id' => $event->id,
                'day_name' => $dayName,
                'event_start' => $eventStart,
                'event_cutoff' => $eventCutoff,
                'qr_token' => $token,
                'qr_image' => 'storage/' . $qrFileName
            ]);

            $createdDays[] = [
                'id' => $eventDay->id,
                'day_name' => $eventDay->day_name,
                'event_start' => $eventDay->event_start,
                'event_cutoff' => $eventDay->event_cutoff,
                'qr_image' => $eventDay->qr_image ? asset($eventDay->qr_image) : null
            ];
        }

        return response()->json([
            'message' => 'Event created successfully',
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'instruction' => $event->instruction,
                'address' => $event->address,
                'image' => $event->image ? asset($event->image) : null,
                'total_attendees' => $event->total_attendees,
                'registration_cutoff' => $event->registration_cutoff,
                'created_at' => $event->created_at
            ],
            'days' => $createdDays
        ]);
    }
    // Update Event + Event Days
   public function updateEvent(Request $request, $eventId)
{
    $event = Event::findOrFail($eventId);

    $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'instruction' => 'nullable|string',
        'address' => 'nullable|string',
        'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
        'total_attendees' => 'nullable|integer',
        'registration_cutoff' => 'nullable|date',
        'is_active' => 'nullable|boolean',
        'days' => 'nullable|string',
    ]);

    $data = $request->only([
        'name',
        'description',
        'instruction',
        'address',
        'total_attendees',
        'registration_cutoff',
        'is_active'
    ]);

    // Update image if exists
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $fileName = 'event_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('events', $fileName, 'public');
        $data['image'] = 'storage/events/' . $fileName;
    }

    $event->update($data);

    $updatedDays = [];

    if ($request->filled('days')) {

        $days = json_decode($request->days, true);

        if (!$days || !is_array($days)) {
            return response()->json(['message' => 'Invalid days format'], 422);
        }

        foreach ($days as $index => $day) {

            $dayId = $day['id'] ?? null;

            /* -----------------------------
               ✅ SOFT DELETE DAY LOGIC
            ------------------------------*/
            if ($dayId && !empty($day['is_deleted']) && $day['is_deleted'] == true) {

                $eventDay = EventDay::find($dayId);

          if ($eventDay) {

    // OPTIONAL: delete QR image file too
    if ($eventDay->qr_image) {
        Storage::disk('public')->delete(str_replace('storage/', '', $eventDay->qr_image));
    }

    $eventDay->delete();
}

                continue;
            }

            $dayName = isset($day['day_name']) && $day['day_name']
                ? $day['day_name']
                : 'Day ' . ($index + 1);

            $eventStart = $day['event_start'] ?? null;
            $eventCutoff = $day['event_cutoff'] ?? null;

            if (!$eventStart) continue;

            if ($dayId) {

                // UPDATE existing
                $eventDay = EventDay::find($dayId);

                if ($eventDay) {
                    $eventDay->update([
                        'day_name' => $dayName,
                        'event_start' => $eventStart,
                        'event_cutoff' => $eventCutoff,
                    ]);

                    $updatedDays[] = [
                        'id' => $eventDay->id,
                        'day_name' => $eventDay->day_name,
                        'event_start' => $eventDay->event_start,
                        'event_cutoff' => $eventDay->event_cutoff,
                        'qr_image' => $eventDay->qr_image ? asset($eventDay->qr_image) : null
                    ];
                }

            } else {

                // CREATE new day
                $token = Str::random(40);

                $qrFileName = 'qrcodes/event_' . $event->id . '_' . date('Ymd_His', strtotime($eventStart)) . '.svg';

                Storage::disk('public')->put(
                    $qrFileName,
                    QrCode::format('svg')->size(300)->generate($token)
                );

                $eventDay = EventDay::create([
                    'event_id' => $event->id,
                    'day_name' => $dayName,
                    'event_start' => $eventStart,
                    'event_cutoff' => $eventCutoff,
                    'qr_token' => $token,
                    'qr_image' => 'storage/' . $qrFileName
                ]);

                $updatedDays[] = [
                    'id' => $eventDay->id,
                    'day_name' => $eventDay->day_name,
                    'event_start' => $eventDay->event_start,
                    'event_cutoff' => $eventDay->event_cutoff,
                    'qr_image' => $eventDay->qr_image ? asset($eventDay->qr_image) : null
                ];
            }
        }
    }

    return response()->json([
        'message' => 'Event updated successfully',
        'event' => [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'instruction' => $event->instruction,
            'address' => $event->address,
            'image' => $event->image ? asset($event->image) : null,
            'total_attendees' => $event->total_attendees,
            'registration_cutoff' => $event->registration_cutoff,
            'is_active' => $event->is_active,
            'updated_at' => $event->updated_at
        ],
        'days' => $updatedDays
    ]);
}

    // Delete Event + cascade delete EventDays
    public function deleteEvent($eventId)
    {
        $event = Event::findOrFail($eventId);

        // Delete associated EventDays and QR images
        foreach ($event->days as $day) {
            if ($day->qr_image && Storage::disk('public')->exists(str_replace('storage/', '', $day->qr_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $day->qr_image));
            }
            $day->delete();
        }

        // Delete main event image
        if ($event->image && Storage::disk('public')->exists(str_replace('storage/', '', $event->image))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $event->image));
        }

        $event->delete();

        return response()->json(['message' => 'Event and its days deleted successfully']);
    }
    // List events with days & QR
    // List events with days & QR
public function listEvents(Request $request)
{
    // -----------------------------
    // ✅ Validate filters
    // -----------------------------
    $request->validate([
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'nullable|in:ongoing_today,upcoming,archived',
        'per_page' => 'nullable|integer|min:1|max:100'
    ]);

    $perPage = $request->get('per_page', 10);

    $query = Event::with('days');

    // -----------------------------
    // ✅ Search filter
    // -----------------------------
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('description', 'like', "%$search%")
              ->orWhere('address', 'like', "%$search%");
        });
    }

    // -----------------------------
    // ✅ Active filter
    // -----------------------------
    if ($request->filled('is_active')) {
        $query->where('is_active', $request->is_active);
    }

    // -----------------------------
    // ✅ DATE RANGE FILTER
    // -----------------------------
    if ($request->filled('start_date') || $request->filled('end_date')) {

        $startDate = $request->start_date
            ? \Carbon\Carbon::parse($request->start_date)->startOfDay()
            : null;

        $endDate = $request->end_date
            ? \Carbon\Carbon::parse($request->end_date)->endOfDay()
            : null;

        $query->whereHas('days', function ($q) use ($startDate, $endDate) {

            if ($startDate && $endDate) {
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->whereBetween('event_start', [$startDate, $endDate])
                        ->orWhereBetween('event_cutoff', [$startDate, $endDate])
                        ->orWhere(function ($inner) use ($startDate, $endDate) {
                            $inner->where('event_start', '<=', $startDate)
                                  ->where(function ($deep) use ($endDate) {
                                      $deep->whereNull('event_cutoff')
                                           ->orWhere('event_cutoff', '>=', $endDate);
                                  });
                        });
                });
            } elseif ($startDate) {
                $q->where('event_start', '>=', $startDate);
            } elseif ($endDate) {
                $q->where('event_start', '<=', $endDate);
            }
        });
    }

    // -----------------------------
    // ✅ PAGINATION (IMPORTANT CHANGE)
    // -----------------------------
    $events = $query->paginate($perPage);

    // -----------------------------
    // ✅ TRANSFORM DATA
    // -----------------------------
    $events->getCollection()->transform(function ($event) {

        $today = \Carbon\Carbon::today();

        $hasOngoingToday = false;
        $hasUpcoming = false;
        $allPast = true;

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
                $allPast = false;
            }

            if ($end->isFuture()) {
                $allPast = false;
            }
        }

        // -----------------------------
        // STATUS
        // -----------------------------
        if ($hasOngoingToday) {
            $status = 'ongoing_today';
        } elseif ($hasUpcoming) {
            $status = 'upcoming';
        } else {
            $status = 'archived';
        }

        // -----------------------------
        // TRANSFORM DAYS
        // -----------------------------
        $event->days = $event->days->map(function ($day) {
            return [
                'id' => $day->id,
                'day_name' => $day->day_name,
                'event_start' => $day->event_start,
                'event_cutoff' => $day->event_cutoff,
                'qr_image' => $day->qr_image ? asset($day->qr_image) : null
            ];
        });

        $event->image = $event->image ? asset($event->image) : null;

        $event->status = $status;
        $event->status_label = ucfirst(str_replace('_', ' ', $status));

        return $event;
    });

    // -----------------------------
    // ✅ STATUS FILTER (AFTER TRANSFORM)
    // -----------------------------
    if ($request->filled('status')) {
        $filtered = $events->getCollection()->filter(function ($event) use ($request) {
            return $event->status === $request->status;
        })->values();

        $events->setCollection($filtered);
    }

    // -----------------------------
    // ✅ RESPONSE (WITH PAGINATION META)
    // -----------------------------
    return response()->json([
        'filters' => [
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'per_page' => $perPage
        ],
        'pagination' => [
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'per_page' => $events->perPage(),
            'total' => $events->total()
        ],
        'events' => $events->items()
    ]);
}

public function scanQr(Request $request)
{
    // 🔥 FORCE PHILIPPINE TIME
    date_default_timezone_set('Asia/Manila');

    $request->validate([
        'qr_token' => 'required'
    ]);

    $user = $request->user();

    $eventDay = EventDay::where('qr_token', $request->qr_token)->first();

    if (!$eventDay) {
        return response()->json([
            'message' => 'Invalid QR',
            'event_id' => null,
            'event' => null,
            'event_name' => null,
            'event_day' => null
        ], 404);
    }

    // -----------------------------
    // Common event info
    // -----------------------------
    $eventInfo = [
        'event_id' => optional($eventDay->event)->id,
        'event' => optional($eventDay->event)->name,
        'event_name' => $eventDay->day_name,
        'event_day' => $eventDay->event_start,
    ];

    // -----------------------------
    // Event start and cutoff
    // -----------------------------
    try {
        $eventStart = new \DateTime($eventDay->event_start);
    } catch (\Exception $e) {
        return response()->json(array_merge([
            'message' => 'Invalid event start datetime'
        ], $eventInfo), 500);
    }

    // Scan starts 3 hours before event
    $scanStart = clone $eventStart;
    $scanStart->modify('-3 hours');

    // ✅ UPDATED SCAN END LOGIC
    if ($eventDay->event_cutoff) {
        $scanEnd = new \DateTime($eventDay->event_cutoff);
    } else {
        $scanEnd = (clone $eventStart)->modify('+12 hours');
    }

    // Current time
    $now = new \DateTime();

    // -----------------------------
    // Before scan window
    // -----------------------------
    if ($now < $scanStart) {
        return response()->json(array_merge([
            'message' => 'Scanning not allowed yet. Scan window starts 3 hours before event.',
            'scan_window' => [
                'starts_at' => $scanStart->format('Y-m-d H:i:s'),
                'ends_at' => $scanEnd->format('Y-m-d H:i:s')
            ],
            'now' => $now->format('Y-m-d H:i:s')
        ], $eventInfo), 400);
    }

    // -----------------------------
    // After cutoff / scan end
    // -----------------------------
    if ($now > $scanEnd) {
        return response()->json(array_merge([
            'message' => 'Scanning is no longer allowed. Event cutoff has passed.',
            'scan_window' => [
                'starts_at' => $scanStart->format('Y-m-d H:i:s'),
                'ends_at' => $scanEnd->format('Y-m-d H:i:s')
            ],
            'now' => $now->format('Y-m-d H:i:s')
        ], $eventInfo), 400);
    }

    // -----------------------------
    // Statuses
    // -----------------------------
    $attendedStatus = Status::where('name', 'Attended')->first();
    $lateStatus = Status::where('name', 'Late Registered')->first();
    $preRegStatus = Status::where('name', 'Pre-Registered')->first();

    // -----------------------------
    // Prevent duplicate scan
    // -----------------------------
    $existingScan = UserEventStatus::where('user_id', $user->id)
        ->where('event_day_id', $eventDay->id)
        ->whereIn('status_id', [
            $attendedStatus->id,
            $lateStatus->id
        ])
        ->first();

    if ($existingScan) {
        return response()->json(array_merge([
            'message' => 'You have already scanned this QR.'
        ], $eventInfo), 400);
    }

    // -----------------------------
    // Check pre-registration
    // -----------------------------
    $preRegistered = UserEventStatus::where('user_id', $user->id)
        ->where('event_day_id', $eventDay->id)
        ->where('status_id', $preRegStatus->id)
        ->exists();

    $scansToCreate = [];

    if ($preRegistered) {

        $scansToCreate[] = [
            'user_id' => $user->id,
            'event_day_id' => $eventDay->id,
            'status_id' => $attendedStatus->id,
            'scanned_at' => $now
        ];

    } else {

        $scansToCreate[] = [
            'user_id' => $user->id,
            'event_day_id' => $eventDay->id,
            'status_id' => $lateStatus->id,
            'scanned_at' => $now
        ];

        $scansToCreate[] = [
            'user_id' => $user->id,
            'event_day_id' => $eventDay->id,
            'status_id' => $attendedStatus->id,
            'scanned_at' => $now
        ];
    }

    // -----------------------------
    // Save scans
    // -----------------------------
    $createdScans = [];

    foreach ($scansToCreate as $scanData) {
        $createdScans[] = UserEventStatus::create($scanData);
    }

    // -----------------------------
    // Response
    // -----------------------------
    $statusResponse = [];

    foreach ($createdScans as $scan) {
        $statusResponse[] = [
            'id' => $scan->status_id,
            'name' => optional($scan->status)->name,
            'scanned_at' => $scan->scanned_at
        ];
    }

    return response()->json([
        'message' => 'Attendance recorded successfully.',
        'event_id' => optional($eventDay->event)->id,
        'event' => optional($eventDay->event)->name,
        'event_name' => $eventDay->day_name,
        'event_day' => $eventDay->event_start,
        'statuses' => $statusResponse,
        'scan_window' => [
            'starts_at' => $scanStart->format('Y-m-d H:i:s'),
            'ends_at' => $scanEnd->format('Y-m-d H:i:s')
        ],
        'now' => $now->format('Y-m-d H:i:s')
    ]);
}

public function getDayAttendanceTable(Request $request)
{
    $request->validate([
        'day_id' => 'required|exists:event_days,id',
        'campaign_id' => 'nullable|exists:campaigns,id',
        'branch_id' => 'nullable|exists:branches,id',
        'status_id' => 'nullable|exists:statuses,id',
        'om_id' => 'nullable|exists:users,id',

        'name' => 'nullable|string',
        'email' => 'nullable|string',
        'contact_number' => 'nullable|string',
        'role' => 'nullable',

        'per_page' => 'nullable|integer|min:1|max:100'
    ]);

    $perPage = $request->get('per_page', 10);

    $eventDay = EventDay::with('event')->findOrFail($request->day_id);
    $event = $eventDay->event;

    $uniqueAttendeesCount = UserEventStatus::where('event_day_id', $eventDay->id)
        ->distinct('user_id')
        ->count('user_id');

    $query = UserEventStatus::with([
        'user.role',
        'user.campaign.branch',
        'user.campaign.omUser',
        'user.branch',
        'status'
    ])
    ->where('event_day_id', $eventDay->id);

    // -----------------------------
    // FILTER: campaign_id
    // -----------------------------
    if ($request->filled('campaign_id')) {
        $query->whereHas('user.campaign', function ($q) use ($request) {
            $q->where('id', $request->campaign_id);
        });
    }

    // -----------------------------
    // FILTER: OM
    // -----------------------------
    if ($request->filled('om_id')) {
        $query->whereHas('user.campaign', function ($q) use ($request) {
            $q->where('om_id', $request->om_id);
        });
    }

    // -----------------------------
    // FILTER: branch
    // -----------------------------
    if ($request->filled('branch_id')) {
        $query->whereHas('user.branch', function ($q) use ($request) {
            $q->where('id', $request->branch_id);
        });
    }

    // -----------------------------
    // FILTER: status
    // -----------------------------
    if ($request->filled('status_id')) {
        $query->whereHas('user', function ($q) use ($request, $eventDay) {
            $q->whereHas('userEventStatuses', function ($q2) use ($request, $eventDay) {
                $q2->where('event_day_id', $eventDay->id)
                   ->where('status_id', $request->status_id);
            });
        });
    }

    // -----------------------------
    // FILTER: name
    // -----------------------------
    if ($request->filled('name')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->name . '%');
        });
    }

    // -----------------------------
    // FILTER: email
    // -----------------------------
    if ($request->filled('email')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('email', 'like', '%' . $request->email . '%');
        });
    }

    // -----------------------------
    // FILTER: contact number
    // -----------------------------
    if ($request->filled('contact_number')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('mobile_no', 'like', '%' . $request->contact_number . '%');
        });
    }

    // -----------------------------
    // FILTER: role (ID OR NAME)
    // -----------------------------
    if ($request->filled('role')) {
        $query->whereHas('user.role', function ($q) use ($request) {

            if (is_numeric($request->role)) {
                $q->where('id', $request->role);
            } else {
                $q->where('name', $request->role);
            }

        });
    }

    // -----------------------------
    // LATEST STATUS PER USER
    // -----------------------------
    $latestStatusIds = UserEventStatus::selectRaw('MAX(id) as latest_id')
        ->where('event_day_id', $eventDay->id)
        ->groupBy('user_id')
        ->pluck('latest_id');

    $query->whereIn('id', $latestStatusIds);

    $attendees = $query->orderBy('scanned_at', 'desc')
        ->paginate($perPage);

    // -----------------------------
    // STATUS COUNTS
    // -----------------------------
    $statusCounts = Status::all()->mapWithKeys(function ($status) use ($eventDay) {
        $count = UserEventStatus::where('event_day_id', $eventDay->id)
            ->where('status_id', $status->id)
            ->count();

        $formattedName = strtolower(str_replace([' ', '-'], '_', $status->name));

        return [$formattedName => $count];
    });

    // -----------------------------
    // TRANSFORM RESULT
    // -----------------------------
    $attendees->getCollection()->transform(function ($item) use ($eventDay) {

        $allStatuses = UserEventStatus::with('status')
            ->where('event_day_id', $eventDay->id)
            ->where('user_id', $item->user->id)
            ->orderBy('scanned_at', 'asc')
            ->get()
            ->map(function ($statusItem) {
                return [
                    'id' => $statusItem->status_id,
                    'name' => optional($statusItem->status)->name,
                    'scanned_at' => $statusItem->scanned_at
                ];
            });

        $isVerified = UserEventDayVerified::where('user_id', $item->user->id)
            ->where('event_day_id', $eventDay->id)
            ->exists();

        return [
            'id' => $item->user->id,
            'name' => $item->user->name,
            'email' => $item->user->email,
            'contact_number' => $item->user->mobile_no,
            'company_information' => $item->user->company_information, 
            'role' => $item->user->role ? [
                'id' => $item->user->role->id,
                'name' => $item->user->role->name
            ] : null,

            'gender' => $item->user->gender,
            'is_verified' => $isVerified,

            'branch' => $item->user->branch ? [
                'id' => $item->user->branch->id,
                'name' => $item->user->branch->name
            ] : null,

            'campaign' => $item->user->campaign ? [
                'id' => $item->user->campaign->id,
                'name' => $item->user->campaign->name,

                'om' => $item->user->campaign->omUser ? [
                    'id' => $item->user->campaign->omUser->id,
                    'name' => $item->user->campaign->omUser->name,
                    'email' => $item->user->campaign->omUser->email,
                    'mobile_no' => $item->user->campaign->omUser->mobile_no,
                ] : null,

                'branch' => $item->user->campaign->branch ? [
                    'id' => $item->user->campaign->branch->id,
                    'name' => $item->user->campaign->branch->name
                ] : null,
            ] : null,

            'status' => [
                'id' => $item->status_id,
                'name' => optional($item->status)->name,
                'scanned_at' => $item->scanned_at
            ],

            'status_history' => $allStatuses
        ];
    });

    return response()->json([
        'event' => [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'instruction' => $event->instruction,
            'address' => $event->address,
            'image' => $event->image ? asset($event->image) : null,
            'total_attendees' => $event->total_attendees,
            'present_attendees' => $uniqueAttendeesCount,
            'registration_cutoff' => $event->registration_cutoff,
            'is_active' => $event->is_active,
            'created_at' => $event->created_at
        ],
        'event_day' => [
            'id' => $eventDay->id,
            'day_name' => $eventDay->day_name,
            'event_start' => $eventDay->event_start,
            'event_cutoff' => $eventDay->event_cutoff,
            'qr_image' => $eventDay->qr_image ? asset($eventDay->qr_image) : null,
        ],
        'status_counts' => $statusCounts,
        'pagination' => [
            'current_page' => $attendees->currentPage(),
            'last_page' => $attendees->lastPage(),
            'per_page' => $attendees->perPage(),
            'total' => $attendees->total()
        ],
        'attendees' => $attendees->items()
    ]);
}

    public function getQREvent($id)
    {

        $event = EventDay::findOrFail($id);
        $dbPath = $event->qr_image;
        $cleanedPath = Str::after($dbPath, 'storage/');
        if (!$dbPath || !Storage::disk('public')->exists($cleanedPath)) {
            abort(404, "File not found. Laravel looked in: storage/app/public/" . $cleanedPath);
        }

        $file = Storage::disk('public')->get($cleanedPath);
        $type = Storage::disk('public')->mimeType($cleanedPath);

        return response($file, 200)
            ->header("Content-Type", $type)
            ->header("Access-Control-Allow-Origin", "http://localhost:5173");
    }
}

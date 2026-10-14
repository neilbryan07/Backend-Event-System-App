<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserEventStatus;
use App\Models\EventDay;
use App\Models\User;
use App\Models\Status;
use App\Models\Event;
use App\Models\UserEventVerified;
use App\Models\UserEventDayVerified;
class UserEventStatusController extends Controller
{
    // User Pre-Register
public function preRegister(Request $request)
{
    $request->validate([
        'event_id' => 'required|exists:events,id'
    ]);

    $user = $request->user();

    // Fetch the event
    $event = Event::find($request->event_id);

    if (!$event) {
        return response()->json(['message' => 'Event not found.'], 404);
    }

    // Check registration cutoff
    if ($event->registration_cutoff && now()->greaterThan($event->registration_cutoff)) {
        return response()->json([
            'message' => 'Pre registration for this event has already closed.'
        ], 403);
    }

    // Fetch all days of the event
    $eventDays = EventDay::where('event_id', $event->id)->get();

    // Get the Pre-Registered status
    $preRegisteredStatus = Status::where('name', 'Pre-Registered')->first();
    if (!$preRegisteredStatus) {
        return response()->json(['message' => 'Pre-Registered status not found.'], 500);
    }

    foreach ($eventDays as $day) {
        // Check if user already pre-registered
        $exists = UserEventStatus::where('user_id', $user->id)
            ->where('event_day_id', $day->id)
            ->where('status_id', $preRegisteredStatus->id)
            ->exists();

        if (!$exists) {
            UserEventStatus::create([
                'user_id' => $user->id,
                'event_day_id' => $day->id,
                'status_id' => $preRegisteredStatus->id,
                'scanned_at' => now()
            ]);
        }
    }

    return response()->json([
        'message' => 'Successfully pre-registered for the event',
        'status' => [
            'id' => $preRegisteredStatus->id,
            'name' => $preRegisteredStatus->name
        ]
    ]);
}
    // Admin cancels registration
public function cancelRegistration(Request $request)
{
    $request->validate([
        'event_id' => 'required|exists:events,id',
        'user_id' => 'required|exists:users,id'
    ]);

    $userId = $request->user_id;

    $eventDays = EventDay::where('event_id', $request->event_id)->get();

    $cancelledStatus = Status::where('name', 'Cancelled')->first();

    if (!$cancelledStatus) {
        return response()->json([
            'message' => 'Cancelled status not found.'
        ], 500);
    }

    foreach ($eventDays as $day) {

        // 1. DELETE ALL EXISTING STATUSES for this user + event day
        UserEventStatus::where('user_id', $userId)
            ->where('event_day_id', $day->id)
            ->delete();

        // 2. INSERT ONLY CANCELLED STATUS
        UserEventStatus::create([
            'user_id' => $userId,
            'event_day_id' => $day->id,
            'status_id' => $cancelledStatus->id,
            'scanned_at' => now()
        ]);
    }

    return response()->json([
        'message' => 'Successfully cancelled registration for the event (all previous statuses removed)',
        'status' => [
            'id' => $cancelledStatus->id,
            'name' => $cancelledStatus->name
        ]
    ]);
}

    // Admin: Event stats
public function stats($event_id)
{
    $event = Event::find($event_id);

    if (!$event) {
        return response()->json(['message' => 'Event not found'], 404);
    }

    // Get all statuses
    $allStatuses = Status::pluck('name', 'id'); 
    // [1 => 'Attended', 2 => 'Late Registered', ...]

    $eventDays = EventDay::where('event_id', $event_id)
        ->with(['userEventStatuses'])
        ->get();

    $days = $eventDays->map(function ($day) use ($allStatuses) {

        // Initialize status counts
        $statusCounts = [];

        foreach ($allStatuses as $statusId => $statusName) {
            $statusCounts[$statusName] = $day->userEventStatuses
                ->where('status_id', $statusId)
                ->count();
        }

        // Total attendees = unique users per day
        $totalAttendees = $day->userEventStatuses
            ->pluck('user_id')
            ->unique()
            ->count();

        return [
            'day_id' => $day->id,
            'date' => $day->day_date,
            'event_start' => $day->event_start ? $day->event_start->toDateTimeString() : null,
            'event_cutoff' => $day->event_cutoff ? $day->event_cutoff->toDateTimeString() : null,
            'qr_image' => $day->qr_image ? asset($day->qr_image) : null,
            'total_attendees' => $totalAttendees,
            'status_counts' => $statusCounts
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
             'is_active' => $event->is_active,
            'registration_cutoff' => $event->registration_cutoff,
            'created_at' => $event->created_at
        ],
        'days' => $days
    ]);
    
}
public function toggleUserEventVerified(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'event_id' => 'required|exists:events,id',
    ]);

    // -----------------------------------
    // CHECK IF USER ATTENDED ANY EVENT DAY
    // -----------------------------------
    $hasAttendance = UserEventStatus::where('user_id', $request->user_id)
        ->whereHas('eventDay', function ($query) use ($request) {
            $query->where('event_id', $request->event_id);
        })
        ->exists();

    if (!$hasAttendance) {
        return response()->json([
            'success' => false,
            'message' => 'User has no attendance record for this event.'
        ], 422);
    }

    // -----------------------------------
    // CHECK VERIFIED RECORD
    // -----------------------------------
    $record = UserEventVerified::where('user_id', $request->user_id)
        ->where('event_id', $request->event_id)
        ->first();

    // CREATE IF NOT EXIST
    if (!$record) {

        $record = UserEventVerified::create([
            'user_id' => $request->user_id,
            'event_id' => $request->event_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User event verified',
            'data' => $record
        ]);
    }

    // HARD DELETE IF EXIST
    $record->delete();

    return response()->json([
        'success' => true,
        'message' => 'User event unverified'
    ]);
}
public function toggleUserEventDayVerified(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'event_day_id' => 'required|exists:event_days,id',
    ]);

    // CHECK IF USER HAS AT LEAST ONE STATUS FOR THIS DAY
    $hasAttendance = UserEventStatus::where('user_id', $request->user_id)
        ->where('event_day_id', $request->event_day_id)
        ->exists();

    if (!$hasAttendance) {
        return response()->json([
            'success' => false,
            'message' => 'User has no attendance/status for this event day.'
        ], 422);
    }

    // FIND EXISTING VERIFIED RECORD
    $record = UserEventDayVerified::where('user_id', $request->user_id)
        ->where('event_day_id', $request->event_day_id)
        ->first();

    // CREATE IF NOT EXIST
    if (!$record) {

        $record = UserEventDayVerified::create([
            'user_id' => $request->user_id,
            'event_day_id' => $request->event_day_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User event day verified',
            'data' => $record
        ]);
    }

    // DELETE IF EXIST
    $record->delete();

    return response()->json([
        'success' => true,
        'message' => 'User event day unverified'
    ]);
}
}
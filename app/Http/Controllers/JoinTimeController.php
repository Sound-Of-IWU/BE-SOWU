<?php

namespace App\Http\Controllers;

use App\Models\JoinTime;
use Illuminate\Http\Request;

class JoinTimeController extends Controller
{
    /**
     * List all join times and include the currently active one.
     */
    public function index()
    {
        $all = JoinTime::orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $active = JoinTime::where('is_active', true)->first();

        // If no record is explicitly marked active, fallback to the latest one
        if (!$active && $all->isNotEmpty()) {
            $active = $all->first();
        }

        return response()->json([
            'data' => $all,
            'active' => $active,
        ]);
    }

    /**
     * Get the active join time directly (public endpoint for client Join page).
     */
    public function getActive()
    {
        $active = JoinTime::where('is_active', true)->first();

        if (!$active) {
            $active = JoinTime::latest()->first();
        }

        return response()->json($active);
    }

    /**
     * Store a new join time schedule.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $isActive = $request->boolean('is_active');

        // If setting this one active, deactivate all others
        if ($isActive) {
            JoinTime::query()->update(['is_active' => false]);
        }

        $data['is_active'] = $isActive;
        $joinTime = JoinTime::create($data);

        return response()->json($joinTime, 201);
    }

    /**
     * Update an existing join time schedule.
     */
    public function update(Request $request, $id)
    {
        $joinTime = JoinTime::findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            JoinTime::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $data['is_active'] = $isActive;
        $joinTime->update($data);

        return response()->json($joinTime);
    }

    /**
     * Toggle the active status of a schedule.
     */
    public function toggleActive($id)
    {
        $joinTime = JoinTime::findOrFail($id);
        $newStatus = !$joinTime->is_active;

        if ($newStatus) {
            JoinTime::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $joinTime->update(['is_active' => $newStatus]);

        return response()->json([
            'message' => $newStatus ? 'Jadwal diaktifkan' : 'Jadwal dinonaktifkan',
            'data' => $joinTime,
        ]);
    }

    /**
     * Delete a join time schedule.
     */
    public function destroy($id)
    {
        $joinTime = JoinTime::findOrFail($id);
        $joinTime->delete();

        return response()->json(['message' => 'Jadwal berhasil dihapus']);
    }
}

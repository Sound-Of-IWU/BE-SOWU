<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        return response()->json(Activity::latest()->paginate(20));
    }

    public function show($id)
    {
        return response()->json(Activity::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required','string','max:255'],
            'deskripsi' => ['nullable','string'],
            'tipe' => ['required','in:activity,event'],
            'image' => ['nullable','image']
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request, $data['judul']);
        }
        $activity = Activity::create($data);
        return response()->json($activity, 201);
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
        $data = $request->validate([
            'judul' => ['sometimes','required','string','max:255'],
            'deskripsi' => ['nullable','string'],
            'tipe' => ['sometimes','required','in:activity,event'],
            'image' => ['nullable','image']
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request, $data['judul'] ?? $activity->judul);
        }
        $activity->update($data);
        return response()->json($activity);
    }

    public function updateImage(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
        $request->validate(['image' => ['required','image']]);
        $path = $this->storeImage($request, $activity->judul);
        $activity->update(['image' => $path]);
        return response()->json($activity);
    }

    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function storeImage(Request $request, string $judul)
    {
        $file = $request->file('image');
        $filename = time().'_'.$file->getClientOriginalName();
        return $file->storeAs('activity/'.str_replace(' ','_',$judul), $filename, 'public');
    }
}

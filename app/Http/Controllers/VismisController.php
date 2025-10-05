<?php

namespace App\Http\Controllers;

use App\Models\Vismis;
use Illuminate\Http\Request;

class VismisController extends Controller
{
    public function index()
    {
        return response()->json(Vismis::all());
    }

    public function show($id)
    {
        return response()->json(Vismis::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $vismis = Vismis::findOrFail($id);
        $data = $request->validate([
            'tipe' => ['sometimes','required','in:visi,misi'],
            'fill' => ['sometimes','required','string'],
            'credit' => ['nullable','string'],
            'image' => ['nullable','image']
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request, $data['tipe'] ?? $vismis->tipe);
        }
        $vismis->update($data);
        return response()->json($vismis);
    }

    public function updateImage(Request $request, $id)
    {
        $vismis = Vismis::findOrFail($id);
        $request->validate(['image' => ['required','image']]);
        $path = $this->storeImage($request, $vismis->tipe);
        $vismis->update(['image' => $path]);
        return response()->json($vismis);
    }

    private function storeImage(Request $request, string $tipe)
    {
        $file = $request->file('image');
        $filename = time().'_'.$file->getClientOriginalName();
        return $file->storeAs('vismis/'.$tipe, $filename, 'public');
    }
}

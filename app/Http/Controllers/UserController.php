<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pengurus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET /api/user
    public function index()
    {
        // Return all users without pagination; frontend will handle paging/filtering
        return response()->json(User::with('pengurus')->get());
    }

    // GET /api/user/{id}
    public function show($id)
    {
        $user = User::with('pengurus')->findOrFail($id);
        return response()->json($user);
    }

    // POST /api/user (admin/pengurus create full user) multiple form-data
    public function store(Request $request)
    {
        $data = $this->validateUser($request, false);
        $user = DB::transaction(function () use ($data, $request) {
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $this->storeProfilePhoto($request, $data['nama_lengkap']);
            }
            $userData = collect($data)->except(['username', 'divisi', 'password'])->toArray();
            $user = User::create($userData);
            $this->syncPengurusRecord($user, $data);
            return $user;
        });
        return response()->json($user->load('pengurus'), 201);
    }

    // POST /api/user/join (public new_member)
    public function join(Request $request)
    {
        $data = $this->validateUserJoin($request);
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $this->storeProfilePhoto($request, $data['nama_lengkap']);
        }
        $user = User::create($data);
        return response()->json($user, 201);
    }

    // PUT /api/user/{id}
    public function update(Request $request, $id)
    {
        $user = User::with('pengurus')->findOrFail($id);
        $data = $this->validateUser($request, true, $user->id);
        DB::transaction(function () use ($user, $data, $request) {
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $this->storeProfilePhoto($request, $data['nama_lengkap'] ?? $user->nama_lengkap);
            }
            $userData = collect($data)->except(['username', 'divisi', 'password'])->toArray();
            $user->update($userData);
            $this->syncPengurusRecord($user, $data);
        });
        return response()->json($user->fresh()->load('pengurus'));
    }

    // PATCH /api/user/{id}/password
    public function updatePassword(Request $request, $id)
    {
        $user = User::with('pengurus')->findOrFail($id);
        $validated = $request->validate([
            'password' => ['required','string','min:6','confirmed']
        ]);
        if ($user->pengurus) {
            $user->pengurus->update(['password' => $validated['password']]);
            return response()->json(['message' => 'Password updated']);
        }
        return response()->json(['message' => 'User bukan pengurus'], 400);
    }

    // PATCH /api/user/{id}/profile-photo
    public function updateProfilePhoto(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate(['profile_photo' => ['required','image']]);
        $path = $this->storeProfilePhoto($request, $user->nama_lengkap);
        $user->update(['profile_photo' => $path]);
        return response()->json($user);
    }

    // DELETE /api/user/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // POST /api/user/bulk-delete
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();
        foreach ($users as $user) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->delete();
        }

        return response()->json([
            'message' => count($validated['ids']) . ' user berhasil dihapus',
            'deleted_count' => count($validated['ids']),
        ]);
    }

    private function validateUser(Request $request, bool $isUpdate = false, ?int $id = null): array
    {
        $currentUser = $id ? User::with('pengurus')->find($id) : null;
        $wasPengurus = $currentUser && $currentUser->role === 'pengurus' && $currentUser->pengurus;
        $pengurusId = $currentUser?->pengurus?->id;

        $rules = [
            'nama_lengkap' => ['sometimes','required','string','max:255'],
            'nim' => ['sometimes','required','string','max:50', Rule::unique('users','nim')->ignore($id)],
            'gender' => ['nullable','in:male,female'],
            'phone_wa' => ['nullable','string','max:30'],
            'username_instagram' => ['nullable','string','max:255'],
            'fakultas' => ['nullable','string','max:255'],
            'prodi' => ['nullable','string','max:255'],
            'angkatan' => ['nullable','string','max:10'],
            'alasan' => ['nullable','string'],
            'range_suara' => ['nullable','in:sopran,alto,tenor,bass,belum_ada'],
            'role' => ['nullable','in:new_member,anggota,pengurus,demisioner'],
            'status' => ['nullable','in:aktif,tidak_aktif'],
            'email' => ['nullable','email', Rule::unique('users','email')->ignore($id)],
            'profile_photo' => ['nullable','image'],
        ];

        // Validasi khusus role pengurus
        if ($request->input('role') === 'pengurus') {
            $rules['username'] = ['required','string','max:100', Rule::unique('pengurus','username')->ignore($pengurusId)];
            $rules['divisi'] = ['required','string','max:100'];
            if ($wasPengurus) {
                $rules['password'] = ['nullable','string','min:6'];
            } else {
                $rules['password'] = ['required','string','min:6'];
            }
        } else {
            $rules['username'] = ['nullable','string','max:100'];
            $rules['divisi'] = ['nullable','string','max:100'];
            $rules['password'] = ['nullable','string'];
        }

        if (!$isUpdate) {
            $rules['nama_lengkap'][0] = 'required';
            $rules['nim'][0] = 'required';
        }
        return $request->validate($rules);
    }

    private function validateUserJoin(Request $request): array
    {
        return $request->validate([
            'nama_lengkap' => ['required','string','max:255'],
            'nim' => ['required','string','max:50','unique:users,nim'],
            'gender' => ['nullable','in:male,female'],
            'phone_wa' => ['nullable','string','max:30'],
            'username_instagram' => ['nullable','string','max:255'],
            'fakultas' => ['nullable','string','max:255'],
            'prodi' => ['nullable','string','max:255'],
            'angkatan' => ['nullable','string','max:10'],
            'alasan' => ['nullable','string'],
            'range_suara' => ['nullable','in:sopran,alto,tenor,bass,belum_ada'],
            'profile_photo' => ['nullable','image']
        ] + ['role' => 'new_member','status' => 'aktif']);
    }

    private function storeProfilePhoto(Request $request, string $nama)
    {
        $file = $request->file('profile_photo');
        $filename = time().'_'.$file->getClientOriginalName();
        return $file->storeAs('profile-photo/'.str_replace(' ','_',$nama), $filename, 'public');
    }

    private function syncPengurusRecord(User $user, array $data = []): void
    {
        if ($user->role === 'pengurus') {
            $pengurusData = [
                'username' => $data['username'] ?? null,
                'divisi' => $data['divisi'] ?? null,
            ];

            if (!empty($data['password'])) {
                $pengurusData['password'] = $data['password'];
            }

            if ($user->pengurus) {
                $user->pengurus->update(array_filter($pengurusData, fn($val) => !is_null($val)));
            } else {
                $pengurusData['user_id'] = $user->id;
                Pengurus::create($pengurusData);
            }
        } else {
            // Jika role diubah menjadi demisioner, anggota, atau new_member:
            // Hapus data kepengurusan (username, password, dan divisi pengurus dihapus)
            if ($user->pengurus) {
                $user->pengurus->delete();
            }
        }
    }
}

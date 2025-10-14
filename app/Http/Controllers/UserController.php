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
        return response()->json(User::with('pengurus')->paginate(20));
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
            $user = User::create($data);
            $this->syncPengurusRecord($user);
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
        $user = User::findOrFail($id);
        $data = $this->validateUser($request, true, $user->id);
        DB::transaction(function () use ($user, $data, $request) {
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $this->storeProfilePhoto($request, $data['nama_lengkap'] ?? $user->nama_lengkap);
            }
            $user->update($data);
            $this->syncPengurusRecord($user);
        });
        return response()->json($user->fresh()->load('pengurus'));
    }

    // PATCH /api/user/{id}/password
    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'password' => ['required','string','min:6','confirmed']
        ]);
        $user->update(['password' => $validated['password']]);
        if ($user->pengurus) {
            $user->pengurus->update(['password' => $validated['password']]);
        }
        return response()->json(['message' => 'Password updated']);
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
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function validateUser(Request $request, bool $isUpdate = false, ?int $id = null): array
    {
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
            'role' => ['nullable','in:new_member,anggota,pengurus'],
            'status' => ['nullable','in:aktif,tidak_aktif'],
            'email' => ['nullable','email', Rule::unique('users','email')->ignore($id)],
            'password' => [$isUpdate ? 'sometimes' : 'nullable','string','min:6'],
            'profile_photo' => ['nullable','image']
        ];
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

    private function syncPengurusRecord(User $user): void
    {
        if ($user->role === 'pengurus') {
            if (!$user->pengurus) {
                Pengurus::create(['user_id' => $user->id]);
            }
        } else {
            if ($user->pengurus) {
                $user->pengurus->delete();
            }
        }
    }
}

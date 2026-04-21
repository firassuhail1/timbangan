<?php

namespace App\Http\Controllers\Update\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $entries = $request->get('entries', 10);
        $search  = $request->get('search', '');

        $users = User::when($search, function ($query, $search) {
            $query->where('username', 'like', "%{$search}%")
                ->orWhere('line', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate($entries);

        return view('admin.master.user.index', compact('users', 'entries', 'search'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.master.user.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'line'     => 'nullable|string|max:255',
            'role'     => 'required|in:admin,user,operator',
            'foto'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'   => 'required|in:Aktif,Nonaktif',
        ], [
            'username.required'  => 'Username wajib diisi.',
            'username.unique'    => 'Username sudah digunakan.',
            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'foto.image'         => 'File harus berupa gambar.',
            'foto.max'           => 'Ukuran foto maksimal 2MB.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('users/foto', 'public');
        }

        User::create([
            'username' => $request->username,
            'line'     => $request->line,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
            'foto'     => $fotoPath,
            'status'   => $request->status,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.master.user.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'line'     => 'nullable|string|max:255',
            'role'     => 'required|in:admin,user,operator',
            'password' => 'nullable|string|min:6|confirmed',
            'foto'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'   => 'required|in:Aktif,Nonaktif',
        ], [
            'username.required'  => 'Username wajib diisi.',
            'username.unique'    => 'Username sudah digunakan.',
            'password.min'       => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'foto.image'         => 'File harus berupa gambar.',
            'foto.max'           => 'Ukuran foto maksimal 2MB.',
        ]);

        $data = [
            'username' => $request->username,
            'line'     => $request->line,
            'role'     => $request->role,
            'status'   => $request->status,
        ];

        // Hanya update password jika diisi
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Update foto jika ada file baru
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $data['foto'] = $request->file('foto')->store('users/foto', 'public');
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Cegah hapus akun sendiri
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        // Hapus foto jika ada
        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}

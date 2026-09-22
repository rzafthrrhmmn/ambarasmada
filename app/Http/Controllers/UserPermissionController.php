<?php

namespace App\Http\Controllers;

use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = UserPermission::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('permission')) {
            $query->where('permission', 'like', '%' . $request->permission . '%');
        }

        $permissions = $query->with(['user'])->paginate(20);

        return inertia('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => $request->only(['user_id', 'permission']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|string|max:255',
        ]);

        UserPermission::create([
            'user_id' => $request->user_id,
            'permission' => $request->permission,
        ]);

        return redirect()->back()->with('success', 'Permission added successfully.');
    }

    public function destroy(UserPermission $permission)
    {
        $permission->delete();

        return redirect()->back()->with('success', 'Permission removed successfully.');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|array',
        ]);

        $user = Auth::user();
        if ($user->id !== $request->user_id && !$user->can('manage_permissions')) {
            abort(403);
        }

        UserPermission::where('user_id', $request->user_id)->delete();

        foreach ($request->permissions as $perm) {
            UserPermission::create([
                'user_id' => $request->user_id,
                'permission' => $perm,
            ]);
        }

        return redirect()->back()->with('success', 'Permissions synced successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Owner only: manage staff accounts and role assignment.
class EmployeeController extends Controller
{
    public function index()
    {
        $showRemoved = request()->query('view') === 'removed';
        $employees = User::query()
            ->when($showRemoved, fn ($q) => $q->onlyTrashed())
            ->when(request('search'), fn($q) => $q->where(function($s) {
                $s->where('name', 'like', '%'.request('search').'%')
                  ->orWhere('username', 'like', '%'.request('search').'%');
            }))
            ->orderBy('name')->paginate(10)->withQueryString();
        return view('employees.index', compact('employees', 'showRemoved'));
    }

    public function create()
    {
        return view('employees.form', ['employee' => new User]);
    }

    public function edit(User $employee)
    {
        return view('employees.form', compact('employee'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'role' => ['required', 'in:owner,store_manager,inventory_staff'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('employees.index')->with('success', 'Employee added.');
    }

    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users','username')->ignore($employee->id)],
            'role' => ['required', 'in:owner,store_manager,inventory_staff'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Never permit the last active Owner to be deactivated or demoted.
        if ($employee->is($request->user()) && ($data['role'] !== 'owner')) {
            return back()->withErrors(['status' => 'You cannot change your own Owner role.']);
        }
        if ($employee->role === 'owner' && ($data['role'] !== 'owner')) {
            $otherOwners = User::where('role', 'owner')->where('status', 'active')
                ->whereKeyNot($employee->id)->exists();
            if (! $otherOwners) {
                return back()->withErrors(['status' => 'At least one active Owner account must remain.']);
            }
        }
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        $data['status'] = 'active';
        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Account updated.');
    }
    public function destroy(Request $request, User $employee)
    {
        // Owner accounts require explicit status/role management, not removal.
        if ($employee->is($request->user()) || $employee->role === 'owner') {
            return back()->withErrors(['employee' => 'Owner accounts cannot be removed from this page.']);
        }

        // Preserve every foreign-key relationship and audit trail.
        $employee->update(['status' => 'inactive']);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee removed. Their history has been preserved.');
    }

    public function restore(Request $request, int $employee)
    {
        $user = User::onlyTrashed()->findOrFail($employee);
        if ($user->role === 'owner') {
            return back()->withErrors(['employee' => 'Owner accounts cannot be restored here.']);
        }
        $user->restore();
        // Restoring an account makes it available for login again.
        $user->update(['status' => 'active']);
        return redirect()->route('employees.index')->with('success', 'Account restored.');
    }
}

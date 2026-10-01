<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Owner only: manage staff accounts and role assignment.
class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::orderBy('name')->get();
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.form', ['employee' => new User]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
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
            'role' => ['required', 'in:owner,store_manager,inventory_staff'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $employee->update($data);

        return back()->with('success', 'Employee updated.');
    }
}

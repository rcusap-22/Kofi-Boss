@extends('layouts.app')
@section('title', 'Add Employee')
@section('content')
    <div class="mb-6">
        <h2 class="page-heading">Add Employee</h2>
        <p class="page-subheading">Create an account for a member of the coffee shop team.</p>
    </div>

    <form method="POST" action="{{ route('employees.store') }}" class="ui-card p-5 sm:p-6 max-w-2xl">
        @csrf
        <div class="space-y-5">
            <div>
                <label class="ui-label">Full Name</label>
                <input type="text" name="name" required class="ui-input" placeholder="e.g. Juan Dela Cruz">
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="ui-label">Username</label>
                    <input type="text" name="username" required class="ui-input" autocomplete="username">
                </div>
                <div>
                    <label class="ui-label">Email</label>
                    <input type="email" name="email" required class="ui-input" autocomplete="email">
                </div>
            </div>

            <div>
                <label class="ui-label">Role</label>
                <select name="role" required class="ui-input">
                    <option value="inventory_staff">Inventory Staff</option>
                    <option value="store_manager">Store Manager</option>
                    <option value="owner">Owner / Manager</option>
                </select>
            </div>

            <div>
                <label class="ui-label">Temporary Password</label>
                <input type="password" name="password" required minlength="8" class="ui-input" autocomplete="new-password">
                <p class="text-xs text-stone-500 mt-2">Use at least 8 characters. The employee can use this to sign in.</p>
            </div>
        </div>

        <div class="mt-7 pt-5 border-t border-stone-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('employees.index') }}" class="ui-btn ui-btn-secondary tap w-full sm:w-auto">Cancel</a>
            <button type="submit" class="ui-btn ui-btn-primary tap w-full sm:w-auto">Create Employee</button>
        </div>
    </form>
@endsection

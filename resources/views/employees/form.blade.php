@extends('layouts.app')
@section('title', $employee->exists ? 'Edit Account' : 'Add Account')
@section('content')
    <div class="mb-6">
        <h2 class="page-heading">{{ $employee->exists ? 'Edit Account' : 'Add Account' }}</h2>
        <p class="page-subheading">{{ $employee->exists ? 'Update this account.' : 'Create a login account.' }}</p>
    </div>

    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" class="ui-card p-5 sm:p-6 max-w-2xl">
        @csrf
        @if($employee->exists) @method('PUT') @endif
        @if($errors->any())<div class='mb-4 text-red-800'>{{ $errors->first() }}</div>@endif
        <div class="space-y-5">
            <div>
                <label class="ui-label">Name</label>
                <input type="text" name="name" value="{{ old('name', $employee->name) }}" required class="ui-input" placeholder="e.g. Juan Dela Cruz">
            </div>

            <div>
                <label class="ui-label">Username</label>
                <input type="text" name="username" value="{{ old('username', $employee->username) }}" required class="ui-input" autocomplete="username">
            </div>

            <div>
                <label class="ui-label">Role</label>
                <select name="role" required class="ui-input">
                    <option value="inventory_staff" @selected(old('role', $employee->role ?? 'inventory_staff') === 'inventory_staff')>Inventory Staff</option>
                    <option value="store_manager" @selected(old('role', $employee->role ?? 'inventory_staff') === 'store_manager')>Store Manager</option>
                    <option value="owner" @selected(old('role', $employee->role ?? 'inventory_staff') === 'owner')>Owner / Manager</option>
                </select>
            </div>

            <div>
                <label class="ui-label">{{ $employee->exists ? 'New Password (optional)' : 'Password' }}</label>
                <input type="password" name="password" @if(!$employee->exists) required @endif minlength="8" class="ui-input" autocomplete="new-password">
                <p class="text-xs text-stone-500 mt-2">{{ $employee->exists ? 'Leave blank to keep the current password.' : 'Use at least 8 characters. The employee can use this to sign in.' }}</p>
            </div>
        </div>

        <div class="mt-7 pt-5 border-t border-stone-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('employees.index') }}" class="ui-btn ui-btn-secondary tap w-full sm:w-auto">Cancel</a>
            <button type="submit" class="ui-btn ui-btn-primary tap w-full sm:w-auto">{{ $employee->exists ? 'Save Changes' : 'Add Account' }}</button>
        </div>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Employees')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="page-heading">Employees</h2>
            <p class="page-subheading">Manage staff access and account status.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="ui-btn ui-btn-primary tap w-full sm:w-auto">＋ Add Employee</a>
    </div>

    <div class="ui-card data-table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Role</th><th>Username</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="font-semibold">{{ $e->name }}</td>
                        <td class="capitalize text-stone-600">{{ str_replace('_', ' ', $e->role) }}</td>
                        <td class="text-stone-600">{{ $e->username }}</td>
                        <td>
                            <form method="POST" action="{{ route('employees.update', $e) }}" class="flex items-center">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $e->name }}">
                                <input type="hidden" name="role" value="{{ $e->role }}">
                                <select name="status" onchange="this.form.submit()" class="min-h-11 rounded-xl border-2 border-stone-200 bg-white px-3 font-semibold text-sm">
                                    <option value="active" @selected($e->status === 'active')>Active</option>
                                    <option value="inactive" @selected($e->status === 'inactive')>Inactive</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

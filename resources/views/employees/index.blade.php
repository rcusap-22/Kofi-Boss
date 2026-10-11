@extends('layouts.app')
@section('title', 'Employees')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="page-heading">Accounts</h2>
            <p class="page-subheading">Manage who can log in to KOFI BOSS.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="ui-btn ui-btn-primary tap w-full sm:w-auto">Add Account</a>
    </div>

    <div class="flex gap-3 mb-4">
        <a href="{{ route('employees.index') }}" class="ui-btn {{ ! $showRemoved ? 'ui-btn-primary' : 'ui-btn-secondary' }}">Accounts</a>
        <a href="{{ route('employees.index', ['view' => 'removed']) }}" class="ui-btn {{ $showRemoved ? 'ui-btn-primary' : 'ui-btn-secondary' }}">Removed Accounts</a>
    </div>

    <form method="GET" action="{{ route('employees.index') }}" class="ui-card p-4 mb-4 flex flex-wrap gap-3 items-end">
      @if($showRemoved)<input type="hidden" name="view" value="removed">@endif
      <label class="flex-1 min-w-48"><span class="ui-label">Search accounts</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Name or username" class="ui-input"></label>
      <button type="submit" class="ui-btn ui-btn-primary">Search</button>
      <a href="{{ route('employees.index', $showRemoved ? ['view'=>'removed'] : []) }}" class="ui-btn ui-btn-secondary">Clear</a>
    </form>
    @if($errors->any())
        <div class="ui-card p-4 mb-4 text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="ui-card data-table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Role</th><th>Username</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="font-semibold">{{ $e->name }}</td>
                        <td class="capitalize text-stone-600">{{ str_replace('_', ' ', $e->role) }}</td>
                        <td class="text-stone-600">{{ $e->username }}</td>
                        <td><span class="font-semibold {{ $showRemoved ? 'text-stone-500' : 'text-emerald-800' }}">{{ $showRemoved ? 'Removed' : 'Active' }}</span></td>
                        <td>
                            @if($showRemoved)
                                <form method="POST" action="{{ route('employees.restore', $e->id) }}" data-confirm="Restore this employee as inactive?">
                                    @csrf
                                    <button type="submit" class="ui-btn ui-btn-secondary">Restore</button>
                                </form>
                            @elseif($e->role !== 'owner')
                                <div class="flex gap-2 flex-wrap">
                                <a href="{{ route('employees.edit', $e) }}" class="ui-btn ui-btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('employees.destroy', $e) }}" data-confirm="Remove this employee? Their login will be disabled, but historical records will remain available.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ui-btn ui-btn-secondary">Remove</button>
                                </form>
                                </div>
                            @else
                                <a href="{{ route('employees.edit', $e) }}" class="ui-btn ui-btn-secondary">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
@endsection

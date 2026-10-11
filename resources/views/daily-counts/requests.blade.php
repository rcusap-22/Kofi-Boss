@extends('layouts.app')
@section('title','Daily Count Requests')
@section('content')
<div class="max-w-6xl mx-auto">
  <div class="mb-5">
    <a class="ui-btn ui-btn-secondary inline-flex items-center justify-center mb-4" href="{{ route('daily-counts.index') }}">Back</a>
    <h2 class="page-heading">Daily Count Requests</h2>
    <p class="page-subheading">Correction and cancellation requests and review history.</p>
  </div>
  <form method="GET" action="{{ route('daily-counts.requests.index') }}" class="ui-card p-4 mb-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <label class="block">
        <span class="block text-sm font-semibold mb-1">Status</span>
        <select name="status" class="ui-input w-full" onchange="this.form.requestSubmit()">
          <option value="all" @selected($status === 'all')>All statuses</option>
          <option value="pending" @selected($status === 'pending')>Pending</option>
          <option value="approved" @selected($status === 'approved')>Approved</option>
          <option value="rejected" @selected($status === 'rejected')>Rejected</option>
          <option value="withdrawn" @selected($status === 'withdrawn')>Withdrawn</option>
        </select>
      </label>
      <label class="block">
        <span class="block text-sm font-semibold mb-1">Request Type</span>
        <select name="type" class="ui-input w-full" onchange="this.form.requestSubmit()">
          <option value="all" @selected($type === 'all')>All types</option>
          <option value="correction" @selected($type === 'correction')>Correction</option>
          <option value="cancellation" @selected($type === 'cancellation')>Cancellation</option>
        </select>
      </label>
      <label class="block">
        <span class="block text-sm font-semibold mb-1">Read Status</span>
        <select name="read" class="ui-input w-full" onchange="this.form.requestSubmit()">
          <option value="all" @selected($read === 'all')>All requests</option>
          <option value="unread" @selected($read === 'unread')>Unread</option>
          <option value="read" @selected($read === 'read')>Read</option>
        </select>
      </label>
    </div>
  </form>
  <div class="ui-card p-4">
    @forelse($requests as $req)
      <a href="{{ route('daily-counts.requests.open',$req) }}"
         class="block rounded-lg border mb-2 p-4 transition-colors {{ !$req->is_read ? 'bg-emerald-100/80 border-emerald-300 hover:bg-emerald-100' : 'bg-white border-slate-200 hover:bg-slate-50' }}">
        <div class="flex justify-between flex-wrap gap-2 items-center">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="{{ !$req->is_read ? 'font-bold text-slate-900' : 'font-medium text-slate-800' }}">{{ ucfirst($req->type) }} · {{ ucfirst($req->status) }}</span>
            @unless($req->is_read)<span class="text-xs font-semibold rounded-full bg-emerald-800 text-white px-2 py-0.5">Unread</span>@endunless
          </div>
          <span class="text-sm {{ !$req->is_read ? 'font-semibold text-slate-800' : 'text-slate-500' }}">{{ $req->created_at->format('M d, Y') }}</span>
        </div>
        <p class="text-sm mt-1 {{ !$req->is_read ? 'font-semibold text-slate-800' : 'text-slate-600' }}">{{ $req->count?->count_date?->format('M d, Y') ?? 'Date unavailable' }} · {{ $req->requester->name ?? 'Staff' }}</p>
        <p class="text-sm mt-1 {{ !$req->is_read ? 'font-semibold text-slate-700' : 'text-slate-600' }}">{{ $req->reason }}</p>
      </a>
    @empty
      <p class="text-slate-600">No requests match the selected filters.</p>
    @endforelse
    <div class="mt-4 flex flex-col gap-2">
      <p class="text-sm text-slate-600">Showing {{ $requests->firstItem() ?? 0 }}–{{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} requests</p>
      {{ $requests->links() }}
    </div>
  </div>
</div>
@endsection

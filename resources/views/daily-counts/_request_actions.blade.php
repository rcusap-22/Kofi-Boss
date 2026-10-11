<style>
.kb-request-toggle { display: block; list-style: none !important; }
.kb-request-toggle::marker { content: "" !important; }
 .kb-request-toggle::-webkit-details-marker { display: none !important; }
.kb-approved-details { border-radius: 12px; transition: background-color .15s ease; }
.kb-approved-details > summary { border-radius: 10px; padding: 12px 14px; background: #f4f7f5; transition: background-color .15s ease; }
.kb-approved-details > summary:hover { background: #e8f2ed; }
.kb-approved-details[open] > summary { background: #e3f3ea; margin-bottom: 18px; }
.kb-approved-details .kb-hide-label { display: none; }
.kb-approved-details[open] .kb-show-label { display: none; }
.kb-approved-details[open] .kb-hide-label { display: inline; }
.kb-approved-details > summary:focus-visible { outline: 3px solid #0b6b50; outline-offset: 3px; }
@media (prefers-reduced-motion: reduce) { .kb-approved-details, .kb-approved-details > summary { transition: none; } }
</style>
<div class="ui-card p-6 mb-6 no-print">
<h3 class="text-xl font-bold mb-2">Submission Requests</h3>
@if(auth()->user()->isInventoryStaff() && auth()->id()===$dailyCount->user_id && $dailyCount->review_status!=='canceled' && !$dailyCount->requests->contains('status','pending'))
<div class="flex flex-wrap items-start gap-3 mt-4 mb-5">
    <a href="{{ route('daily-counts.correction.edit',$dailyCount) }}" class="ui-btn ui-btn-primary">Request Correction</a>
    <details class="min-w-0">
        <summary class="ui-btn ui-btn-secondary cursor-pointer">Request Cancellation</summary>
        <form method="POST" action="{{ route('daily-counts.requests.store',$dailyCount) }}" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 max-w-xl space-y-3">
            @csrf
            <input type="hidden" name="type" value="cancellation">
            <p class="text-sm text-slate-600">Request cancellation of the entire submitted closing inventory. Manager approval is required. The record will not be deleted.</p>
            <label class="ui-label" for="cancellation-reason">Reason (required)</label>
            <textarea id="cancellation-reason" name="reason" class="ui-input" rows="3" required minlength="5" maxlength="1000"></textarea>
            <button class="ui-btn ui-btn-primary">Submit Cancellation Request</button>
        </form>
    </details>
</div>
@endif
<div class="border-t border-slate-200 pt-5 mt-5">
<div class="flex flex-wrap justify-between items-center gap-3 mb-2"><h4 class="text-lg font-bold text-slate-800">Request History</h4><span class="text-sm text-slate-500">{{ $dailyCount->requests->count() }} {{ $dailyCount->requests->count() === 1 ? 'request' : 'requests' }}</span></div>
@if($dailyCount->requests->isEmpty())<p class="text-sm text-slate-500 py-4">No requests for this submission.</p>@endif
@foreach($dailyCount->requests->sortByDesc('id') as $req)
<div class="rounded-xl border border-slate-200 bg-white p-5 md:p-6 mb-5 shadow-sm">
@if($req->status === 'approved')
<details class="group kb-approved-details">
<summary class="kb-request-toggle cursor-pointer">
@endif
    <div class="flex flex-wrap justify-between items-center gap-3 mb-2">
        <div class="flex flex-wrap items-center gap-3">
            <strong class="text-base text-slate-900">{{ ucfirst($req->type) }}</strong>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $req->status === 'approved' ? 'bg-green-100 text-green-800' : ($req->status === 'pending' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($req->status) }}</span>
        </div>
        <span class="text-sm text-slate-500">{{ $req->created_at->format('M d, Y g:i A') }}</span>
    </div>
@if($req->status === 'approved')
<div class="text-sm font-semibold text-emerald-800"><span class="kb-show-label">View details</span><span class="kb-hide-label">Hide details</span></div>
</summary>
@endif
    <div class="space-y-2 mb-4">
        <p class="text-sm text-slate-800"><span class="font-semibold">Reason:</span> {{ $req->reason }}</p>
        <p class="text-sm text-slate-500">Requested by {{ $req->requester->name ?? 'Staff' }}@if($req->reviewer) · Reviewed by {{ $req->reviewer->name }}@endif</p>
    </div>
@if($req->type==='correction')
@php $proposedChanges=$req->items()->with('countItem.item')->get(); @endphp
<div class="mt-5 rounded-xl bg-slate-50 p-4">
 <p class="text-sm font-bold mb-3">{{ $req->status === 'approved' ? 'Approved quantity changes' : 'Proposed quantity changes' }}</p>
 <div class="overflow-x-auto rounded-lg border border-slate-200">
 <table class="w-full text-sm" style="border-collapse:collapse">
  <thead><tr style="background:#f4d48c"><th class="p-3 text-left">Item</th><th class="p-3 text-center">Submitted Count</th><th class="p-3 text-center">{{ $req->status === 'approved' ? 'Approved Count' : 'Proposed Count' }}</th></tr></thead>
  <tbody>
  @forelse($proposedChanges as $change)
  <tr class="border-b"><td class="p-3 font-semibold">{{ $change->countItem?->item?->name ?? 'Item unavailable' }}</td><td class="p-3 text-center">{{ (int)$change->previous_qty }}</td><td class="p-3 text-center font-bold">{{ (int)$change->requested_qty }}</td></tr>
  @empty
  <tr><td colspan="3" class="p-3 text-center">No proposed item changes recorded.</td></tr>
  @endforelse
  </tbody>
 </table>
 </div>
</div>
@endif
@if($req->review_note)<p class="text-sm mt-4 text-slate-700"><strong>Manager note:</strong> {{ $req->review_note }}</p>@endif
@if($req->status==='pending' && auth()->user()->isInventoryStaff() && $req->requested_by===auth()->id())<form method="POST" action="{{ route('daily-counts.requests.withdraw',$req) }}" class="mt-4">@csrf<button class="ui-btn ui-btn-secondary" data-confirm="Withdraw this pending request?">Withdraw Request</button></form>@endif
@if($req->status==='pending' && auth()->user()->isStoreManager())<form method="POST" action="{{ route('daily-counts.requests.review',$req) }}" class="mt-5 border-t border-slate-200 pt-4">@csrf<label class="ui-label">Review note (optional)</label><textarea name="review_note" class="ui-input" rows="2"></textarea><div class="flex flex-wrap gap-3 mt-4"><button name="decision" value="approved" class="ui-btn ui-btn-primary" data-confirm="Approve this correction or cancellation request and apply the approved changes?">Approve</button><button name="decision" value="rejected" class="ui-btn ui-btn-secondary" data-confirm="Reject this correction or cancellation request?">Reject</button></div></form>@endif
@if($req->status === 'approved')
</details>
@endif
</div>
@endforeach
</div>
</div>

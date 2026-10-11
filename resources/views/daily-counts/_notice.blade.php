@if($dailyCount->review_status==='canceled')
<div class="p-3 mb-3 rounded-lg border border-red-200 bg-red-50 text-red-900 text-sm font-semibold">This closing inventory has been canceled with Manager approval.</div>
@elseif($dailyCount->was_corrected)
<div class="p-3 mb-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-sm font-semibold">This closing inventory has been edited following an approved correction.</div>
@endif

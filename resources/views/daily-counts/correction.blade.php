@extends('layouts.app')
@section('title','Request Closing Inventory Correction')
@section('content')
<style>
/* Center correction table headings and their matching values. */
#correction-form .daily-count-head > span:not(:first-child) { text-align: center !important; }
#correction-form .daily-count-row > .daily-count-field {
  text-align: center !important;
  justify-content: center !important;
  align-items: center !important;
}
#correction-form .daily-count-row .daily-actual {
  display: flex;
  flex-direction: column;
  align-items: center !important;
}
#correction-form .daily-count-row .daily-actual input {
  text-align: center !important;
  margin-left: auto;
  margin-right: auto;
  width: 100%;
}
#correction-form .daily-count-row .daily-variance strong,
#correction-form .daily-count-row .daily-expected strong { display: block; text-align: center !important; }
@media (max-width: 767px) {
  #correction-form .daily-count-row > .daily-count-field { align-items: stretch !important; text-align: left !important; }
  #correction-form .daily-count-row .daily-actual input { text-align: center !important; }
}
</style>
<div class="max-w-6xl mx-auto">
  <div class="mb-5">
    <a href="{{ route('daily-counts.show',$dailyCount) }}" class="ui-btn ui-btn-secondary mb-3">← Back to Submitted Inventory</a>
    <h2 class="page-heading">Request Closing Inventory Correction</h2>
    <p class="page-subheading">{{ $dailyCount->count_date->format('F j, Y') }} · Review the submitted quantities below and change only the items that need correction. Your changes require Manager approval.</p>
  </div>
  @if($errors->any())<div class="ui-card p-4 mb-4 text-red-700" role="alert">{{ $errors->first() }}</div>@endif
  <form action="{{ route('daily-counts.requests.store',$dailyCount) }}" method="POST" id="correction-form" novalidate>
    @csrf
    <input type="hidden" name="type" value="correction">
    <div class="ui-card overflow-hidden">
      <div class="daily-count-list">
        <div class="daily-count-head"><span>Item</span><span>Unit</span><span>Start of Day</span><span>Corrected Closing Count</span><span>Variance</span></div>
        @foreach($dailyCount->items as $line)
        <div class="daily-count-row" data-correction-row data-expected="{{ (int)$line->expected_qty }}" data-original="{{ (int)$line->actual_qty }}">
          <div class="daily-count-item"><div class="font-semibold">{{ $line->item->name }}</div><div class="text-xs text-slate-500">{{ $line->item->category }}</div></div>
          <div class="daily-count-field daily-unit"><span class="daily-mobile-label">Unit</span><span>{{ $line->item->unit }}</span></div>
          <div class="daily-count-field daily-expected"><span class="daily-mobile-label">Start of Day</span><strong>{{ number_format($line->expected_qty,0) }}</strong></div>
          <div class="daily-count-field daily-actual"><label class="daily-mobile-label" for="corrected-{{ $line->id }}">Corrected Closing Count</label><input class="actual-input ui-input text-lg font-semibold" id="corrected-{{ $line->id }}" type="number" min="0" step="1" inputmode="numeric" name="corrected[{{ $line->id }}]" required value="{{ old('corrected.'.$line->id,(int)$line->actual_qty) }}"><span class="text-xs text-slate-500">Submitted: {{ (int)$line->actual_qty }}</span></div>
          <div class="daily-count-field daily-variance"><span class="daily-mobile-label">Variance</span><strong class="variance">{{ number_format(abs($line->actual_qty-$line->expected_qty),0) }}</strong></div>
        </div>
        @endforeach
      </div>
      <div id="correction-pagination" class="flex flex-wrap justify-center items-center gap-2 p-3" aria-label="Correction pages"></div>
      <div class="p-5 bg-slate-50">
        <label for="correction-reason" class="ui-label">Reason for correction (required)</label>
        <textarea id="correction-reason" name="reason" rows="3" minlength="5" maxlength="1000" required class="ui-input" placeholder="Explain why the closing count needs correction">{{ old('reason') }}</textarea>
        <p class="text-sm text-slate-600 mt-2">This is a request only. Stock will not change until a Manager approves it.</p>
        <div class="flex flex-wrap gap-3 mt-4"><button type="submit" class="ui-btn ui-btn-primary text-lg">Submit Correction Request</button><a href="{{ route('daily-counts.show',$dailyCount) }}" class="ui-btn ui-btn-secondary">Cancel</a></div>
      </div>
    </div>
  </form>
</div>
<script>
(() => {
  const form=document.getElementById('correction-form');
  const rows=[...form.querySelectorAll('[data-correction-row]')];
  const pager=document.getElementById('correction-pagination');
  const pageCount=Math.ceil(rows.length/10);
  let currentPage=0;
  function render(page){
    currentPage=page;
    rows.forEach((row,index)=>{row.style.display=Math.floor(index/10)===page?'':'none';});
    pager.replaceChildren();
    if(pageCount<=1)return;
    for(let i=0;i<pageCount;i++){
      const btn=document.createElement('button');btn.type='button';btn.textContent=String(i+1);
      btn.className='ui-btn '+(i===page?'ui-btn-primary':'ui-btn-secondary');
      btn.style.cssText='min-width:40px;min-height:36px;padding:6px 10px';
      btn.addEventListener('click',()=>render(i));pager.appendChild(btn);
    }
  }
  rows.forEach(row=>{
    const input=row.querySelector('input[type=number]');const variance=row.querySelector('.variance');
    const expected=Number(row.dataset.expected);
    function update(){const value=Number(input.value);variance.textContent=input.value===''?'—':Math.abs(value-expected).toLocaleString();variance.className='variance '+(value===expected?'text-green-700':'text-red-600');}
    input.addEventListener('input',update);update();
  });
  form.addEventListener('submit',event=>{
    const invalid=rows.find(row=>!row.querySelector('input').checkValidity());
    if(invalid){event.preventDefault();render(Math.floor(rows.indexOf(invalid)/10));invalid.querySelector('input').focus();return;}
    const changed=rows.some(row=>Number(row.querySelector('input').value)!==Number(row.dataset.original));
    if(!changed){event.preventDefault();return;}
    const reason=form.querySelector('[name=reason]');if(!reason.checkValidity()){event.preventDefault();reason.focus();}
  });
  render(0);
})();
</script>
@endsection

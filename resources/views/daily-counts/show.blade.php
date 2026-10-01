@extends('layouts.app')
@section('title','Closing Inventory')
@section('content')<div class="max-w-6xl mx-auto"><div class="mb-5"><h2 class="page-heading">Closing Inventory</h2><p class="page-subheading">Historical daily counts are locked and read-only.</p></div>@include('daily-counts._readonly',['dailyCount'=>$dailyCount])</div>@endsection
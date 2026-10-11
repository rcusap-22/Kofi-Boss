
@if ($paginator->hasPages())
<nav role="navigation"
     aria-label="Report pagination"
     style="display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap;">

    @php
        $buttonStyle = 'display:inline-flex; width:42px; height:42px; min-width:42px; flex:0 0 42px; align-items:center; justify-content:center; box-sizing:border-box; border-radius:10px; text-decoration:none; font-size:16px; line-height:1; border:1px solid #e9c46a; background:#fff4d6; color:#63400a;';
        $activeStyle = 'display:inline-flex; width:42px; height:42px; min-width:42px; flex:0 0 42px; align-items:center; justify-content:center; box-sizing:border-box; border-radius:10px; font-size:16px; line-height:1; font-weight:700; border:1px solid #e9c46a; background:#e9c46a; color:#382600;';
    @endphp

    @if ($paginator->onFirstPage())
        <span style="{{ $buttonStyle }} opacity:0.45; cursor:not-allowed;" aria-disabled="true">&lsaquo;</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
           aria-label="Previous page"
           style="{{ $buttonStyle }}">&lsaquo;</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span style="color:#ffffff; padding:0 4px;">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" style="{{ $activeStyle }}">{{ $page }}</span>
                @else
                    <a href="{{ $url }}"
                       aria-label="Go to page {{ $page }}"
                       style="{{ $buttonStyle }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
           aria-label="Next page"
           style="{{ $buttonStyle }}">&rsaquo;</a>
    @else
        <span style="{{ $buttonStyle }} opacity:0.45; cursor:not-allowed;" aria-disabled="true">&rsaquo;</span>
    @endif
</nav>
@endif

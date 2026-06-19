@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $visiblePages = 5;
    $pages = [];

    if ($lastPage <= $visiblePages + 2) {
        $pages = range(1, $lastPage);
    } elseif ($currentPage <= $visiblePages) {
        $pages = array_merge(range(1, $visiblePages), ['ellipsis-right', $lastPage]);
    } elseif ($currentPage >= $lastPage - ($visiblePages - 2)) {
        $pages = array_merge([1, 'ellipsis-left'], range($lastPage - ($visiblePages - 1), $lastPage));
    } else {
        $pages = [
            1,
            'ellipsis-left',
            $currentPage - 1,
            $currentPage,
            $currentPage + 1,
            'ellipsis-right',
            $lastPage,
        ];
    }
@endphp

<div class="datatable-footer" data-datatable-pagination>
    <p>{{ $summary }}</p>

    @if ($paginator->hasPages())
        <nav class="datatable-pagination" aria-label="{{ $ariaLabel }}">
            @if ($paginator->onFirstPage())
                <span class="disabled">{{ $previousLabel }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ $previousLabel }}</a>
            @endif

            @foreach ($pages as $page)
                @if (is_string($page))
                    <span class="datatable-pagination-ellipsis" aria-hidden="true">...</span>
                @elseif ($page === $currentPage)
                    <span class="active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ $nextLabel }}</a>
            @else
                <span class="disabled">{{ $nextLabel }}</span>
            @endif
        </nav>
    @endif
</div>

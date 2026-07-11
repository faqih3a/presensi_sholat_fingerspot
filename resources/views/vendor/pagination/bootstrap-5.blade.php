@if ($paginator->hasPages())
<nav aria-label="Page navigation" class="app-pagination-nav">
    {{-- Mobile: Previous / Next only --}}
    <div class="d-flex justify-content-between d-sm-none">
        @if ($paginator->onFirstPage())
            <span class="app-page-btn app-page-btn--disabled">
                <i class="bi bi-chevron-left small"></i> Sebelumnya
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="app-page-btn">
                <i class="bi bi-chevron-left small"></i> Sebelumnya
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="app-page-btn">
                Berikutnya <i class="bi bi-chevron-right small"></i>
            </a>
        @else
            <span class="app-page-btn app-page-btn--disabled">
                Berikutnya <i class="bi bi-chevron-right small"></i>
            </span>
        @endif
    </div>

    {{-- Desktop: Full Pagination --}}
    <ul class="app-pagination d-none d-sm-flex">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="app-page-item app-page-item--disabled" aria-disabled="true">
                <span class="app-page-link" aria-hidden="true"><i class="bi bi-chevron-left small"></i></span>
            </li>
        @else
            <li class="app-page-item">
                <a class="app-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">
                    <i class="bi bi-chevron-left small"></i>
                </a>
            </li>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="app-page-item app-page-item--disabled" aria-disabled="true">
                    <span class="app-page-link">{{ $element }}</span>
                </li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="app-page-item app-page-item--active" aria-current="page">
                            <span class="app-page-link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="app-page-item">
                            <a class="app-page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="app-page-item">
                <a class="app-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">
                    <i class="bi bi-chevron-right small"></i>
                </a>
            </li>
        @else
            <li class="app-page-item app-page-item--disabled" aria-disabled="true">
                <span class="app-page-link" aria-hidden="true"><i class="bi bi-chevron-right small"></i></span>
            </li>
        @endif
    </ul>
</nav>
@endif

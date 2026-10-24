@props(['title', 'subtitle' => null, 'breadcrumbs' => null])

<div class="page-header">
    <div>
        @if ($breadcrumbs && count($breadcrumbs))
            <nav aria-label="مسار التصفح" class="mb-1">
                <ol class="breadcrumb mb-0" style="font-size: 0.775rem;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>الرئيسية</a></li>
                    @foreach($breadcrumbs as $key => $item)
                        @php
                            if (is_array($item)) {
                                $bLabel = $item['label'] ?? ($item['name'] ?? '');
                                $bUrl = $item['url'] ?? null;
                            } elseif (is_string($key) && !is_numeric($key)) {
                                $bLabel = $key;
                                $bUrl = $item;
                            } else {
                                $bLabel = (string) $item;
                                $bUrl = null;
                            }
                        @endphp
                        @if(!$loop->last && $bUrl)
                            <li class="breadcrumb-item"><a href="{{ $bUrl }}" class="text-decoration-none text-muted">{{ $bLabel }}</a></li>
                        @else
                            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">{{ $bLabel }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <div class="page-subtitle">{{ $subtitle }}</div>
        @endif
    </div>
    @if (isset($actions))
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>

@php
    $userAgent = strtolower(request()->userAgent() ?? '');
    $isMobile = (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent);
@endphp

@if($isMobile)
    @include('layouts.aluno-mobile')
@else
    @include('layouts.aluno-desktop')
@endif

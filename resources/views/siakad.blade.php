@extends('layouts.app')

@php
    $aplikasi = \App\Models\SiakadApplication::where('aktif', true)
        ->orderBy('urutan')
        ->orderBy('nama')
        ->get();
@endphp

@section('content')
<div class="sk-wrap">
    <div class="sk-head">
        <h1 class="sk-title">SIAKAD &amp; E-Learning</h1>
        <p class="sk-sub">Aplikasi akademik kampus yang terintegrasi dengan TaskMate.</p>
    </div>

    <div class="siakad-grid">
        @forelse($aplikasi as $app)
            <a href="{{ $app->url }}" target="_blank" rel="noopener" class="siakad-card">
                <div class="siakad-top">
                    <span class="siakad-logo">
                        @if($app->logo)
                            <img src="{{ asset($app->logo) }}" alt="Logo {{ $app->nama }}">
                        @else
                            <span class="siakad-logo-fallback">{{ strtoupper(mb_substr($app->nama, 0, 1)) }}</span>
                        @endif
                    </span>
                    <span class="siakad-name">{{ $app->nama }}</span>
                </div>
                <span class="siakad-tag {{ $app->warna === 'navy' ? 'tag-navy' : 'tag-cyan' }}">{{ $app->tag }}</span>
                <p class="siakad-caption">{{ $app->caption }}</p>
                <div class="siakad-bottom">
                    <span class="siakad-url">{{ parse_url($app->url, PHP_URL_HOST) }}</span>
                    <span class="siakad-go"><x-icon name="arrow-right" /></span>
                </div>
            </a>
        @empty
            <div class="sk-kosong">Belum ada aplikasi yang ditambahkan admin.</div>
        @endforelse
    </div>

    <p class="siakad-footnote">Aplikasi di atas dikelola Politeknik Negeri Banjarmasin dan berada di luar aplikasi TaskMate.</p>
</div>
@endsection
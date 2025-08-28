@extends('layouts.app')

@section('title', 'Format Preview | Rirekisho Maker')

@section('content')
@php
  // Resolve safe slug string
  $routeSlug = request()->route('slug') ?? 'basic';
  $slugStr = (string) $routeSlug;

  // Labels
  $titles = [
    'basic'     => 'Basic（基本）',
    'career'    => '職務経歴書（Career）',
    'cover'     => '送付状（Cover）',
    'jobchange' => '転職履歴書',
    'newgrad'   => '新卒履歴書',
    'parttime'  => 'アルバイト履歴書',
    'foreigner' => '外国人向け履歴書',
    'fukugyo'   => '副業履歴書',
    'rirekisho' => '履歴書（JIS規格）',
  ];
  $label = $titles[$slugStr] ?? $slugStr;

  // Image mapping (public/images/Title.jpg)
  $imgMap = [
    'basic'     => 'Basic.jpg',
    'career'    => 'career.jpg',
    'cover'     => 'Cover.jpg',
    'jobchange' => 'jobchange.jpg',
    'newgrad'   => 'newgrad.jpg',
    'parttime'  => 'parttime.jpg',
    'foreigner' => 'foreigner.jpg',
    'fukugyo'   => 'fukugyo.jpg',
    'rirekisho' => 'rirekisho.jpg',
  ];
  $imgPath = asset('images/' . ($imgMap[$slugStr] ?? 'Basic.jpg'));
@endphp

<div class="container py-5">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h4 fw-bold mb-0">プレビュー: {{ $label }}</h1>

    {{-- IMPORTANT: Grid → Preview → Start → SKIP Step1 → go Step2 --}}
    <form method="POST" action="{{ route('rirekisho.start') }}">
      @csrf
      <input type="hidden" name="template" value="{{ $slugStr }}">
      <button type="submit" class="btn btn-primary">この形式で作成する</button>
    </form>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="preview-frame p-3 bg-white border rounded shadow-sm">
        <img src="{{ $imgPath }}" class="img-fluid rounded w-100" alt="{{ $label }}">
      </div>
    </div>
    <div class="col-lg-4">
      <div class="p-3 bg-white border rounded shadow-sm">
        <h2 class="h6 fw-bold mb-2">概要</h2>
        <p class="text-muted small mb-3">
          {{ $label }} のプレビューです。右上のボタンからこのフォーマットで作成を開始できます。
        </p>
        <ul class="small text-muted mb-0">
          <li>PDF出力対応（A4）</li>
          <li>日本語フォント対応</li>
          <li>入力項目はフォーマットに合わせて最適化</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

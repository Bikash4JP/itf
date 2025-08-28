@extends('layouts.app')

@section('title', 'Step 7 | Rirekisho Maker')

@section('content')
@php
  $w = session('wizard', []);
  if (!is_array($w)) { $w = []; }
  $template = (string) ($w['template'] ?? 'basic');
  $titleMap = [
    'basic'     => 'Basic（基本）',
    'career'    => '職務経歴書',
    'cover'     => '送付状',
    'jobchange' => '転職履歴書',
    'newgrad'   => '新卒履歴書',
    'parttime'  => 'アルバイト履歴書',
    'foreigner' => '外国人向け履歴書',
    'fukugyo'   => '副業履歴書',
    'rirekisho' => '履歴書（JIS規格）',
  ];
  $templateLabel = $titleMap[$template] ?? 'Basic（基本）';
@endphp

<div class="container py-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 fw-bold mb-0">Step 7: 確認 & プレビュー</h1>
    <span class="badge bg-primary">選択中: {{ $templateLabel }}</span>
  </div>

  <div class="alert alert-info mb-3">
    下記プレビューは DOCX テンプレートへ入力値を差し込み、HTML として表示しています。<br>
    「PDF をダウンロード」で同じ内容を PDF 出力します。
  </div>

  <div class="ratio ratio-1x1" style="min-height: 70vh; border:1px solid #eaeaea; border-radius:8px; overflow:hidden;">
    <iframe src="{{ route('rirekisho.preview.html') }}" style="width:100%; height:100%; border:0;"></iframe>
  </div>

  <div class="d-flex gap-3 mt-3">
    <a href="{{ route('rirekisho.step6') }}" class="btn btn-outline-secondary">戻る</a>
    <a href="{{ route('rirekisho.preview.pdf') }}" class="btn btn-primary">PDF をダウンロード</a>
    <a href="{{ route('rirekisho.landing') }}" class="btn btn-link">トップへ戻る</a>
  </div>
</div>
@endsection

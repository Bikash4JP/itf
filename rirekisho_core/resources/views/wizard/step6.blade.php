@extends('layouts.app')

@section('title', 'Step 6 | Rirekisho Maker')

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
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 fw-bold mb-0">Step 6: ファイルアップロード</h1>
    <span class="badge bg-primary">選択中: {{ $templateLabel }}</span>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('rirekisho.step6.post') }}" enctype="multipart/form-data">
    @csrf

    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-3">必須</h2>
        <div class="mb-3">
          <label class="form-label">写真（必須）</label>
          <input type="file" class="form-control" name="photo" accept="image/*" required>
          <div class="form-text">JPEG/PNG 推奨。縦横比 3:4（30×40mm 相当）。</div>
        </div>
      </div>
    </div>

    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-3">任意（複数可）</h2>

        <div class="mb-3">
          <label class="form-label">資格証明（Certificates）</label>
          <input type="file" class="form-control" name="certificates[]" multiple>
          <div class="form-text">PDF / 画像 など。</div>
        </div>

        <div class="mb-3">
          <label class="form-label">実績（Achievements）</label>
          <input type="file" class="form-control" name="achievements[]" multiple>
        </div>

        <div class="mb-3">
          <label class="form-label">プロジェクト（Projects）</label>
          <input type="file" class="form-control" name="projects[]" multiple>
        </div>
      </div>
    </div>

    <div class="d-flex gap-3">
      <a href="{{ route('rirekisho.step5') }}" class="btn btn-outline-secondary">戻る</a>
      <button type="submit" class="btn btn-primary">次へ（確認 & プレビュー）</button>
    </div>
  </form>
</div>
@endsection

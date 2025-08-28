@extends('layouts.app')

@section('title', 'Step 5 | Rirekisho Maker')

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

  $motivation = old('motivation', $w['motivation'] ?? '');
  $self_pr    = old('self_pr',    $w['self_pr']    ?? '');
  $preferences= old('preferences',$w['preferences']?? '');
@endphp

<div class="container py-5">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 fw-bold mb-0">Step 5: 志望動機・自己PR・本人希望欄</h1>
    <span class="badge bg-primary">選択中: {{ $templateLabel }}</span>
  </div>

  <form method="POST" action="{{ route('rirekisho.step5.post') }}">
    @csrf

    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">志望動機</label>
          <textarea class="form-control" name="motivation" rows="4" placeholder="例: 貴社の〇〇事業に共感し、これまでの経験を活かして貢献したいと考えています。">{{ $motivation }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">自己PR / 強み</label>
          <textarea class="form-control" name="self_pr" rows="4" placeholder="例: Laravel/Vueでの開発経験、チームリード、課題解決力など">{{ $self_pr }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">本人希望記入欄</label>
          <textarea class="form-control" name="preferences" rows="3" placeholder="例: 勤務地は関東圏、フレックス希望、在宅可など">{{ $preferences }}</textarea>
        </div>
      </div>
    </div>

    <div class="d-flex gap-3">
      <a href="{{ route('rirekisho.step4') }}" class="btn btn-outline-secondary">戻る</a>
      <button type="submit" class="btn btn-primary">次へ（ファイルアップロード）</button>
    </div>
  </form>
</div>
@endsection

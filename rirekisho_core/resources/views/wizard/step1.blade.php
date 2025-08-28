@extends('layouts.app')

@section('title', 'Step 1 | Rirekisho Maker')

@section('content')
@php
  // Template comes from preview POST or query fallback
  $template = old('template', request('template', session('wizard.template', 'basic')));

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
    <h1 class="h3 fw-bold mb-0">Step 1: 職種・雇用形態を選択</h1>
    <span class="badge bg-primary">選択中: {{ $templateLabel }}</span>
  </div>

  <div class="row">
    <div class="col-lg-8">
      {{-- IMPORTANT: Step1 is POST --}}
      <form method="POST" action="{{ route('rirekisho.step1.post') }}">
        @csrf
        <input type="hidden" name="template" value="{{ $template }}">

        <div class="mb-3">
          <label class="form-label">職種 (Job Category)</label>
          <select name="job_category" class="form-select" required>
            <option value="" selected disabled>選択してください</option>
            <option>IT・エンジニア</option>
            <option>介護・看護</option>
            <option>販売・接客</option>
            <option>建設・製造</option>
            <option>その他</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">雇用形態 (Job Type)</label>
          <select name="job_type" class="form-select" required>
            <option value="" selected disabled>選択してください</option>
            <option>正社員</option>
            <option>アルバイト</option>
            <option>契約社員</option>
            <option>派遣</option>
          </select>
        </div>

        <div class="d-flex gap-3">
          <button type="submit" class="btn btn-primary">次へ</button>
          <a href="{{ route('rirekisho.landing') }}" class="btn btn-link">戻る</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Step 2 | Rirekisho Maker')

@section('content')
@php
  $w = session('wizard', []);
  if (!is_array($w)) { $w = []; }

  $tplRaw = $w['template'] ?? request('template', 'basic');
  $template = is_string($tplRaw) ? $tplRaw : 'basic';

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
    <h1 class="h3 fw-bold mb-0">Step 2: 個人情報の入力</h1>
    <span class="badge bg-primary">選択中: {{ $templateLabel }}</span>
  </div>

  <form method="POST" action="{{ route('rirekisho.step2.post') }}">
    @csrf
    <input type="hidden" name="template" value="{{ e($template) }}">

    {{-- Name --}}
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">氏名 (漢字)</label>
        <input type="text" class="form-control" name="name_kanji" value="{{ old('name_kanji') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">氏名 (フリガナ)</label>
        <input type="text" class="form-control" name="name_kana" value="{{ old('name_kana') }}" required>
      </div>
    </div>

    {{-- DOB + Gender --}}
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">生年月日</label>
        <input type="date" class="form-control" name="dob" value="{{ old('dob') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">性別 (任意)</label>
        <select class="form-select" name="gender">
          <option value="">選択しない</option>
          <option value="male"   @selected(old('gender')==='male')>男性</option>
          <option value="female" @selected(old('gender')==='female')>女性</option>
          <option value="other"  @selected(old('gender')==='other')>その他</option>
        </select>
      </div>
    </div>

    {{-- Nationality / Mother tongue --}}
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">国籍</label>
        <input type="text" class="form-control" name="nationality" value="{{ old('nationality') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">母国語</label>
        <input type="text" class="form-control" name="mother_tongue" value="{{ old('mother_tongue') }}" required>
      </div>
    </div>

    {{-- Residence status / Expiry --}}
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">在留資格</label>
        <input type="text" class="form-control" name="residence_status" value="{{ old('residence_status') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">在留期限</label>
        <input type="date" class="form-control" name="residence_expiry" value="{{ old('residence_expiry') }}" required>
      </div>
    </div>

    {{-- Language skill summary --}}
    <div class="mb-3">
      <label class="form-label">言語スキル</label>
      <textarea class="form-control" name="language_skills" rows="3" placeholder="例: 日本語N2、英語ビジネスレベル、ネパール語母国語">{{ old('language_skills') }}</textarea>
    </div>

    <div class="d-flex gap-3">
      <a href="{{ route('rirekisho.step1', ['template' => $template]) }}" class="btn btn-outline-secondary">戻る</a>
      <button type="submit" class="btn btn-primary">次へ</button>
    </div>
  </form>
</div>
@endsection

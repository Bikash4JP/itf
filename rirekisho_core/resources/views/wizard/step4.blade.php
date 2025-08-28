@extends('layouts.app')

@section('title', 'Step 4 | Rirekisho Maker')

@section('content')
@php
  $w = session('wizard', []);
  if (!is_array($w)) { $w = []; }

  $works = old('work', $w['work'] ?? [
    ['from_year'=>'','from_month'=>'','company'=>''],
  ]);
  $licenses = old('licenses', $w['licenses'] ?? [
    ['year'=>'','month'=>'','title'=>''],
  ]);
@endphp

<div class="container py-5">
  <h1 class="h3 fw-bold mb-4">Step 4: 職歴・資格</h1>

  <form method="POST" action="{{ route('rirekisho.step4.post') }}">
    @csrf

    {{-- 職歴 --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <h2 class="h5 fw-bold">職歴</h2>
          <button type="button" id="add-work" class="btn btn-outline-primary btn-sm">＋ 行を追加</button>
        </div>

        <div id="work-rows">
          @foreach($works as $i => $row)
          <div class="row g-2 mb-2 work-row" data-index="{{ $i }}">
            <div class="col-3">
              <input type="number" name="work[{{ $i }}][from_year]" class="form-control" placeholder="年" value="{{ $row['from_year'] ?? '' }}">
            </div>
            <div class="col-2">
              <input type="number" name="work[{{ $i }}][from_month]" class="form-control" placeholder="月" value="{{ $row['from_month'] ?? '' }}">
            </div>
            <div class="col-7">
              <input type="text" name="work[{{ $i }}][company]" class="form-control" placeholder="会社名・職種等" value="{{ $row['company'] ?? '' }}">
            </div>
          </div>
          @endforeach
        </div>

        <template id="work-template">
          <div class="row g-2 mb-2 work-row" data-index="__INDEX__">
            <div class="col-3">
              <input type="number" name="work[__INDEX__][from_year]" class="form-control" placeholder="年">
            </div>
            <div class="col-2">
              <input type="number" name="work[__INDEX__][from_month]" class="form-control" placeholder="月">
            </div>
            <div class="col-7">
              <input type="text" name="work[__INDEX__][company]" class="form-control" placeholder="会社名・職種等">
            </div>
          </div>
        </template>
      </div>
    </div>

    {{-- 資格 --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <h2 class="h5 fw-bold">免許・資格</h2>
          <button type="button" id="add-lic" class="btn btn-outline-primary btn-sm">＋ 行を追加</button>
        </div>

        <div id="lic-rows">
          @foreach($licenses as $i => $row)
          <div class="row g-2 mb-2 lic-row" data-index="{{ $i }}">
            <div class="col-3">
              <input type="number" name="licenses[{{ $i }}][year]" class="form-control" placeholder="年" value="{{ $row['year'] ?? '' }}">
            </div>
            <div class="col-2">
              <input type="number" name="licenses[{{ $i }}][month]" class="form-control" placeholder="月" value="{{ $row['month'] ?? '' }}">
            </div>
            <div class="col-7">
              <input type="text" name="licenses[{{ $i }}][title]" class="form-control" placeholder="資格名" value="{{ $row['title'] ?? '' }}">
            </div>
          </div>
          @endforeach
        </div>

        <template id="lic-template">
          <div class="row g-2 mb-2 lic-row" data-index="__INDEX__">
            <div class="col-3">
              <input type="number" name="licenses[__INDEX__][year]" class="form-control" placeholder="年">
            </div>
            <div class="col-2">
              <input type="number" name="licenses[__INDEX__][month]" class="form-control" placeholder="月">
            </div>
            <div class="col-7">
              <input type="text" name="licenses[__INDEX__][title]" class="form-control" placeholder="資格名">
            </div>
          </div>
        </template>
      </div>
    </div>

    <div class="d-flex gap-3">
      <a href="{{ route('rirekisho.step3') }}" class="btn btn-outline-secondary">戻る</a>
      <button type="submit" class="btn btn-primary">次へ</button>
    </div>
  </form>
</div>

<script>
(function(){
  // Work rows
  const wContainer = document.getElementById('work-rows');
  const wTpl = document.getElementById('work-template').innerHTML;
  document.getElementById('add-work').addEventListener('click', ()=>{
    const i = wContainer.querySelectorAll('.work-row').length;
    const html = wTpl.replace(/__INDEX__/g,i);
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    wContainer.appendChild(div.firstElementChild);
  });

  // License rows
  const lContainer = document.getElementById('lic-rows');
  const lTpl = document.getElementById('lic-template').innerHTML;
  document.getElementById('add-lic').addEventListener('click', ()=>{
    const i = lContainer.querySelectorAll('.lic-row').length;
    const html = lTpl.replace(/__INDEX__/g,i);
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    lContainer.appendChild(div.firstElementChild);
  });
})();
</script>
@endsection

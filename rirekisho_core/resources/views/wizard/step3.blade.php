@extends('layouts.app')

@section('title', 'Step 3 | Rirekisho Maker')

@section('content')
@php
  $w = session('wizard', []);
  if (!is_array($w)) { $w = []; }

  $educations = old('education', $w['education'] ?? [
    ['year'=>'','month'=>'','school'=>''],
  ]);
@endphp

<div class="container py-5">
  <h1 class="h3 fw-bold mb-4">Step 3: 学歴・連絡先</h1>

  <form method="POST" action="{{ route('rirekisho.step3.post') }}">
    @csrf

    {{-- 学歴 --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <h2 class="h5 fw-bold">学歴</h2>
          <button type="button" id="add-edu" class="btn btn-outline-primary btn-sm">＋ 行を追加</button>
        </div>

        <div id="edu-rows">
          @foreach($educations as $i => $row)
          <div class="row g-2 mb-2 edu-row" data-index="{{ $i }}">
            <div class="col-3">
              <input type="number" name="education[{{ $i }}][year]" class="form-control" placeholder="年" value="{{ $row['year'] ?? '' }}">
            </div>
            <div class="col-2">
              <input type="number" name="education[{{ $i }}][month]" class="form-control" placeholder="月" value="{{ $row['month'] ?? '' }}">
            </div>
            <div class="col-7">
              <input type="text" name="education[{{ $i }}][school]" class="form-control" placeholder="学校名・学部等" value="{{ $row['school'] ?? '' }}">
            </div>
          </div>
          @endforeach
        </div>

        <template id="edu-template">
          <div class="row g-2 mb-2 edu-row" data-index="__INDEX__">
            <div class="col-3">
              <input type="number" name="education[__INDEX__][year]" class="form-control" placeholder="年">
            </div>
            <div class="col-2">
              <input type="number" name="education[__INDEX__][month]" class="form-control" placeholder="月">
            </div>
            <div class="col-7">
              <input type="text" name="education[__INDEX__][school]" class="form-control" placeholder="学校名・学部等">
            </div>
          </div>
        </template>
      </div>
    </div>

    {{-- 連絡先 --}}
    <div class="card mb-4">
      <div class="card-body">
        <h2 class="h5 fw-bold">連絡先</h2>
        <div class="mb-2">
          <input type="text" class="form-control" name="postal_code" placeholder="郵便番号" value="{{ old('postal_code', $w['postal_code'] ?? '') }}">
        </div>
        <div class="mb-2">
          <input type="text" class="form-control" name="address_full" placeholder="住所" value="{{ old('address_full', $w['address_full'] ?? '') }}">
        </div>
        <div class="mb-2">
          <input type="text" class="form-control" name="phone" placeholder="電話番号" value="{{ old('phone', $w['phone'] ?? '') }}">
        </div>
        <div class="mb-2">
          <input type="email" class="form-control" name="email" placeholder="メールアドレス" value="{{ old('email', $w['email'] ?? '') }}">
        </div>
      </div>
    </div>

    <div class="d-flex gap-3">
      <a href="{{ route('rirekisho.step2') }}" class="btn btn-outline-secondary">戻る</a>
      <button type="submit" class="btn btn-primary">次へ</button>
    </div>
  </form>
</div>

<script>
(function(){
  const container = document.getElementById('edu-rows');
  const tpl = document.getElementById('edu-template').innerHTML;
  const addBtn = document.getElementById('add-edu');

  function nextIndex(){
    let max = -1;
    container.querySelectorAll('.edu-row').forEach(el=>{
      const idx = parseInt(el.getAttribute('data-index'),10);
      if(!isNaN(idx)) max = Math.max(max, idx);
    });
    return max+1;
  }

  addBtn.addEventListener('click', ()=>{
    const i = nextIndex();
    const html = tpl.replace(/__INDEX__/g, i);
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    container.appendChild(div.firstElementChild);
  });
})();
</script>
@endsection

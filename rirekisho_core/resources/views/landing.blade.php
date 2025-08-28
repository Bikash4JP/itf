@extends('layouts.app')

@section('title', 'Rirekisho Maker | IT-Future')

@section('content')
{{-- HERO --}}
<section class="hero-section">
  <div class="container py-5">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 text-start">
        <h1 class="display-5 fw-bold mb-3">
          日本で働くための <span class="text-primary">履歴書</span> を、かんたん作成
        </h1>
        <p class="lead text-muted mb-4">
          Step-by-step 入力 → プレビュー → PDF ダウンロード。<br>
          JIS風のレイアウトで、企業に提出しやすいPDFを作成できます。
        </p>
        <a href="{{ route('rirekisho.step1', ['template'=>'basic']) }}" class="btn btn-primary btn-lg px-4">
          履歴書作成をはじめる
        </a>
        <div class="text-muted small mt-2">
          ※ 所要時間 5〜10分｜ログイン不要
        </div>
      </div>
      <div class="col-lg-6">
        <div class="preview-card shadow-sm border rounded bg-white p-3">
          <div class="preview-page border rounded p-3">
            <div class="preview-title">履歴書（イメージ）</div>
            <div class="preview-lines mt-3">
              <div class="line"></div>
              <div class="line w-75"></div>
              <div class="line w-50"></div>
              <div class="line"></div>
              <div class="line w-25"></div>
            </div>
          </div>
          <div class="text-center text-muted small mt-2">プレビューサンプル</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- STEPS --}}
<section class="steps-section py-5 bg-white">
  <div class="container">
    <h2 class="h3 fw-bold text-center mb-4">かんたん 3 ステップ</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="step-badge">1</div>
            <h3 class="h5">職種・雇用形態</h3>
            <p class="text-muted small">IT / 介護 / 販売 などの職種と雇用形態を選択。</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="step-badge">2</div>
            <h3 class="h5">必要事項を入力</h3>
            <p class="text-muted small">氏名・住所・学歴・職歴・資格などをフォームに入力。</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <div class="step-badge">3</div>
            <h3 class="h5">プレビュー & PDF</h3>
            <p class="text-muted small">A4サイズPDFを出力して、そのまま提出可能。</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- FORMAT GRID (3x3) --}}
<section class="format-section py-5">
  <div class="container">
    <h2 class="h4 fw-bold text-center mb-4">フォーマットを選択</h2>
    <p class="text-center text-muted mb-5">目的に合った履歴書フォーマットを選んでください</p>

    <div class="row g-4">
      {{-- Row 1 --}}
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'basic']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/Basic.jpg') }}" class="img-fluid rounded mb-2" alt="Basic（基本）">
          <h3 class="h6 fw-bold text-dark">Basic（基本）</h3>
          <p class="text-muted small">標準的な履歴書フォーマット</p>
          <span class="fmt-badge badge text-bg-primary">推奨</span>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'career']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/career.jpg') }}" class="img-fluid rounded mb-2" alt="職務経歴書">
          <h3 class="h6 fw-bold text-dark">職務経歴書（Career）</h3>
          <p class="text-muted small">実績・プロジェクトを詳しく</p>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'cover']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/Cover.jpg') }}" class="img-fluid rounded mb-2" alt="送付状（Cover）">
          <h3 class="h6 fw-bold text-dark">送付状（Cover）</h3>
          <p class="text-muted small">応募書類の添え状</p>
        </a>
      </div>

      {{-- Row 2 --}}
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'jobchange']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/jobchange.jpg') }}" class="img-fluid rounded mb-2" alt="転職履歴書">
          <h3 class="h6 fw-bold text-dark">転職履歴書</h3>
          <p class="text-muted small">JIS規格・転職者向け</p>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'newgrad']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/newgrad.jpg') }}" class="img-fluid rounded mb-2" alt="新卒履歴書">
          <h3 class="h6 fw-bold text-dark">新卒履歴書</h3>
          <p class="text-muted small">研究/活動/インターンをアピール</p>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'parttime']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/parttime.jpg') }}" class="img-fluid rounded mb-2" alt="アルバイト履歴書">
          <h3 class="h6 fw-bold text-dark">アルバイト履歴書</h3>
          <p class="text-muted small">勤務可能日・時間を明記</p>
        </a>
      </div>

      {{-- Row 3 --}}
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'foreigner']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/foreigner.jpg') }}" class="img-fluid rounded mb-2" alt="外国人向け履歴書">
          <h3 class="h6 fw-bold text-dark">外国人向け履歴書</h3>
          <p class="text-muted small">在留/言語/通訳可否など</p>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'fukugyo']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/fukugyo.jpg') }}" class="img-fluid rounded mb-2" alt="副業履歴書">
          <h3 class="h6 fw-bold text-dark">副業履歴書</h3>
          <p class="text-muted small">Wワーク・扶養内の明記</p>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ route('rirekisho.format', ['slug' => 'rirekisho']) }}" class="format-card text-decoration-none">
          <img src="{{ asset('images/rirekisho.jpg') }}" class="img-fluid rounded mb-2" alt="履歴書（JIS規格）">
          <h3 class="h6 fw-bold text-dark">履歴書（JIS規格）</h3>
          <p class="text-muted small">オーソドックスな様式</p>
        </a>
      </div>
    </div>
  </div>
</section>
@endsection

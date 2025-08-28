<div class="container py-5">
  <h2 class="h4 fw-bold text-center mb-4">フォーマットを選択</h2>
  <p class="text-center text-muted mb-4 small">目的に合わせてテンプレートを選んでください。</p>

  <div class="row g-4">
    {{-- 履歴書 --}}
    <div class="col-md-4">
      <a href="{{ route('rirekisho.step1', ['template' => 'rirekisho']) }}" class="text-decoration-none">
        <div class="select-card h-100">
          <div class="select-card-body">
            <div class="select-card-tag">推奨</div>
            <h3 class="h5 mb-2">履歴書</h3>
            <p class="text-muted small mb-3">基本情報・学歴・職歴・資格など。まずはこれ。</p>
            <ul class="text-muted small ps-3 mb-0">
              <li>一般的な応募書類</li>
              <li>コンビニ印刷しやすいA4</li>
            </ul>
          </div>
        </div>
      </a>
    </div>

    {{-- 職務経歴書 --}}
    <div class="col-md-4">
      <a href="{{ route('rirekisho.step1', ['template' => 'shokumu']) }}" class="text-decoration-none">
        <div class="select-card h-100">
          <div class="select-card-body">
            <h3 class="h5 mb-2">職務経歴書</h3>
            <p class="text-muted small mb-3">経験・実績を詳しく書く書類。IT/営業などに有効。</p>
            <ul class="text-muted small ps-3 mb-0">
              <li>プロジェクト/成果を詳細に</li>
              <li>カスタムセクション</li>
            </ul>
          </div>
        </div>
      </a>
    </div>

    {{-- 送付状（将来用） --}}
    <div class="col-md-4">
      <a href="{{ route('rirekisho.step1', ['template' => 'sofujo']) }}" class="text-decoration-none">
        <div class="select-card h-100">
          <div class="select-card-body">
            <h3 class="h5 mb-2">送付状</h3>
            <p class="text-muted small mb-3">書類送付時に添えるカバーレター。後で拡張予定。</p>
            <ul class="text-muted small ps-3 mb-0">
              <li>簡単に作成</li>
              <li>文章テンプレあり</li>
            </ul>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>

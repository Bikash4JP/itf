@extends('layouts.guest')
@section('content')
<h3>新規登録</h3>

@if ($errors->any())
  <article class="contrast">
    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </article>
@endif

<form method="POST" action="{{ route('register') }}">
  @csrf
  <label>名前
    <input type="text" name="name" value="{{ old('name') }}" required>
  </label>
  <label>Email
    <input type="email" name="email" value="{{ old('email') }}" required>
  </label>
  <label>パスワード
    <input type="password" name="password" required>
  </label>
  <label>パスワード(確認)
    <input type="password" name="password_confirmation" required>
  </label>
  <button type="submit">登録する</button>
</form>

<p style="margin-top:1rem">
  既にアカウントがありますか？
  <a href="{{ route('login') }}">ログイン</a>
</p>
@endsection

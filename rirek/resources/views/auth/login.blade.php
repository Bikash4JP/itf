@extends('layouts.guest')
@section('content')
<h3>ログイン</h3>

@if ($errors->any())
  <article class="contrast">
    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </article>
@endif

<form method="POST" action="{{ route('login') }}">
  @csrf
  <label>Email
    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
  </label>
  <label>Password
    <input type="password" name="password" required>
  </label>
  <label>
    <input type="checkbox" name="remember"> Remember me
  </label>
  <button type="submit">ログイン</button>
</form>

<p style="margin-top:1rem">
  アカウントがありませんか？
  <a href="{{ route('register') }}">新規登録</a>
</p>
@endsection

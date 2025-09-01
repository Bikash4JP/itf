<!doctype html><meta charset="utf-8"><title>TEST LOGIN VIEW</title>
<h1>_plain_login</h1>
<form method="POST" action="{{ route('login') }}">
  @csrf
  <input name="email" placeholder="email">
  <input type="password" name="password" placeholder="password">
  <button>login</button>
</form>

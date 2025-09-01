<!doctype html><meta charset="utf-8"><title>TEST REGISTER VIEW</title>
<h1>_plain_register</h1>
<form method="POST" action="{{ route('register') }}">
  @csrf
  <input name="name" placeholder="name">
  <input name="email" placeholder="email">
  <input type="password" name="password" placeholder="password">
  <input type="password" name="password_confirmation" placeholder="confirm">
  <button>register</button>
</form>

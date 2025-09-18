<?php
// /rireki/kaigo/php/validators.php
require_once __DIR__ . '/bootstrap.php';

function is_katakana(string $s): bool {
  return (bool)preg_match('/^[ァ-ヶー・\s]+$/u', $s);
}

function is_yyyy_mm(string $s): bool {
  return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s);
}

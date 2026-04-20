$f = 'c:\Users\pc\OneDrive\Desktop\itf\rireki\kaigo\rireki.php'
$c = Get-Content $f -Raw -Encoding UTF8
$old = '<script src="/rireki/kaigo/js/rireki_form_extra.js?v=20260129"></script>'
$new = $old + "`n  <script src=`"/rireki/kaigo/js/postal_autofill.js?v=1`"></script>"
if ($c.Contains($old)) {
    $c = $c.Replace($old, $new)
    Set-Content $f $c -Encoding UTF8
    Write-Host 'OK: script tag added'
} else {
    Write-Host 'NOT FOUND: check the script tag text'
}

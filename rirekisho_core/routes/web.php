<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\PreviewController;

/*
|--------------------------------------------------------------------------
| Landing & Preview
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('landing'))->name('rirekisho.landing');

Route::get('/format/{slug}', function (string $slug) {
    $allowed = [
        'basic', 'career', 'cover',
        'jobchange', 'newgrad', 'parttime',
        'foreigner', 'fukugyo', 'rirekisho',
    ];
    abort_unless(in_array($slug, $allowed), 404);
    return view('formats.preview', compact('slug'));
})->name('rirekisho.format');

/*
|--------------------------------------------------------------------------
| Wizard Flow (Step1 → … → Step7)
|--------------------------------------------------------------------------
*/

Route::post('/wizard/start', function (Request $request) {
    $template = (string) $request->input('template', 'basic');
    session(['wizard' => array_merge(session('wizard', []), ['template' => $template])]);
    return redirect()->route('rirekisho.step2');
})->name('rirekisho.start');

// Step1
Route::get('/wizard/step1', fn () => view('wizard.step1'))->name('rirekisho.step1');
Route::post('/wizard/step1', function (Request $request) {
    $data = $request->only(['template', 'job_category', 'job_type']);
    session(['wizard' => array_merge(session('wizard', []), $data)]);
    return redirect()->route('rirekisho.step2');
})->name('rirekisho.step1.post');

// Step2
Route::get('/wizard/step2', fn () => view('wizard.step2'))->name('rirekisho.step2');
Route::post('/wizard/step2', function (Request $request) {
    $data = $request->only([
        'name_kanji', 'name_kana', 'dob', 'gender',
        'nationality', 'mother_tongue',
        'residence_status', 'residence_expiry', 'language_skills'
    ]);
    session(['wizard' => array_merge(session('wizard', []), $data)]);
    return redirect()->route('rirekisho.step3');
})->name('rirekisho.step2.post');

// Step3
Route::get('/wizard/step3', fn () => view('wizard.step3'))->name('rirekisho.step3');
Route::post('/wizard/step3', function (Request $request) {
    $contact = $request->only(['postal_code', 'address_full', 'phone', 'email']);
    $education = $request->input('education', []);
    $payload = array_merge($contact, ['education' => $education]);
    session(['wizard' => array_merge(session('wizard', []), $payload)]);
    return redirect()->route('rirekisho.step4');
})->name('rirekisho.step3.post');

// Step4
Route::get('/wizard/step4', fn () => view('wizard.step4'))->name('rirekisho.step4');
Route::post('/wizard/step4', function (Request $request) {
    $work     = $request->input('work', []);
    $licenses = $request->input('licenses', []);
    $payload = ['work' => $work, 'licenses' => $licenses];
    session(['wizard' => array_merge(session('wizard', []), $payload)]);
    return redirect()->route('rirekisho.step5');
})->name('rirekisho.step4.post');

// Step5 (PR)
Route::get('/wizard/step5', fn () => view('wizard.step5'))->name('rirekisho.step5');
Route::post('/wizard/step5', function (Request $request) {
    $pr = $request->only(['motivation', 'self_pr', 'preferences']);
    session(['wizard' => array_merge(session('wizard', []), $pr)]);
    return redirect()->route('rirekisho.step6');
})->name('rirekisho.step5.post');

// Step6 (Uploads)
Route::get('/wizard/step6', fn () => view('wizard.step6'))->name('rirekisho.step6');
Route::post('/wizard/step6', function (Request $request) {
    $saved = [];

    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
        try {
            $path = $request->file('photo')->store('uploads/photos', 'public');
            $saved['photo_path'] = $path;
        } catch (\Throwable $e) {
            return back()->withErrors(['photo' => '写真の保存に失敗しました。'])->withInput();
        }
    } else {
        return back()->withErrors(['photo' => '写真は必須です。'])->withInput();
    }

    foreach (['certificates', 'achievements', 'projects'] as $field) {
        $saved[$field] = [];
        if ($request->hasFile($field)) {
            foreach ($request->file($field) as $file) {
                if ($file && $file->isValid()) {
                    try {
                        $p = $file->store("uploads/{$field}", 'public');
                        $saved[$field][] = $p;
                    } catch (\Throwable $e) {}
                }
            }
        }
    }
    session(['wizard' => array_merge(session('wizard', []), ['uploads' => $saved])]);
    return redirect()->route('rirekisho.step7');
})->name('rirekisho.step6.post');

// Step7 (Preview page with HTML/PDF)
Route::get('/wizard/step7', fn () => view('wizard.step7'))->name('rirekisho.step7');

// Preview renderers
Route::get('/wizard/preview/html', [PreviewController::class, 'html'])->name('rirekisho.preview.html');
Route::get('/wizard/preview/pdf',  [PreviewController::class, 'pdf'])->name('rirekisho.preview.pdf');

// Debug
Route::get('/wizard/debug', fn () => response()->json(session('wizard', [])));

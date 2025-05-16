"use strict";

(function($) {
$(function() {

	/**
	 * メインビジュアル
	 */
	// 表示を更新する間隔（ミリ秒）
	const autoplaySpeed = 6000;

	// 各エレメント
	const $slider = $('#topSlider');
	const $items = $slider.find('li');
	let $controller = $('#topController');

	let current_pos = 0; // 現在位置
	let pause_on_hoevr = false; // マウスオーバー時に一時停止するか
	let pause = false; // 再生状態

	// コントローラが指定されていない場合、追加
	if ( ! $controller.length ) {
		$controller = $('<div></div>');
		$slider.append( $controller );
	}

	// クラスを割り当てる
	$slider.addClass('js-slider');
	$items.addClass('js-slider__item');
	$controller.addClass('js-slider__controller');

	$(window).on('load resize', function() {
	//	$slider.css({ height: $items.outerHeight() + 'px' } );
		$items.find('a').attr('tabindex', '-1');
	});

	for( let i=0,len=$items.length; i<len; i++ ) {
		$controller.append('<a href="#' + (i+1) + '">' + (i+1) + '</a>');
	}

	/**
	 * 表示するスライドを変更
	 * @param next 次に表示するスライド番号
	 * @param prev 切り替える間に表示するスライド番号
	 */
	const change_item = function( next, prev ) {
		if ( next == prev ) {
			return;
		}
		$items.removeClass('js-current');
		$items.removeClass('js-prev');
		$items.eq(next).addClass('js-current');
		if ( prev !== undefined ) {
			$items.eq(prev).addClass('js-prev');
		}

		const $anc = $items.eq(next).find('a');
		if ( $anc.length ) {
			$anc.attr('tabindex', '0');
		}
		current_pos = next;
		update_indicatior();
	}

	/**
	 * インジケータの現在位置を設定
	 */
	const update_indicatior = function() {
		const $anc = $controller.find('a');
		$anc.removeClass('js-current');
		setTimeout( function(){
			$anc.eq(current_pos).addClass('js-current');
		}, 20);
		
	}
	update_indicatior();

	// 初期化
	change_item( 0 );

	// 自動再生
	let timer_id;
	const animation_start = function() {
		if ( timer_id ) {
			return;
		}
		timer_id = setInterval( function() {
			let next = (current_pos +1) % $items.length;
			change_item( next, current_pos );
		}, autoplaySpeed);
	}
	const animation_stop = function() {
		clearInterval( timer_id );
		timer_id = false;
	};

	// 開始
	setTimeout(function() {
		animation_start();
	}, 10 );
	
	$slider.on('mouseover', function() {
		if ( pause_on_hoevr ) {
			pause = true;
			animation_stop();
		}
	});
	$slider.on('mouseout', function() {
		if ( pause_on_hoevr ) {
			pause = false;
			animation_start();
		}
	});
	$controller.on('mouseover', function() {
		if ( pause_on_hoevr ) {
			pause = true;
			animation_stop();
		}
	});
	$controller.on('mouseout', function() {
		if ( pause_on_hoevr ) {
			pause = false;
			animation_start();
		}
	});
	
	$controller.find('a').on('click', function() {
		const next_pos = $(this).attr('href').replace(/^#/,'');
		change_item( next_pos -1, current_pos );
		animation_stop();
		animation_start();
		return false;
	});


	/**
	 * パララックス
	 */
	let current_sp = null;
	let rellax;
	$(window).on('load resize', function() {
		const is_sp = window.matchMedia('(max-width: 480px)').matches;
		if ( current_sp !== is_sp ) {
			if ( rellax ) {
				rellax.destroy();
			}
			if ( is_sp ) {
				rellax = new Rellax('.section_cp__bg', {
					speed: -2,
					center: true
				});
			} else {
				rellax = new Rellax('.section_cp__bg', {
					speed: -9,
					center: true
				});
			}
			current_sp = is_sp;
		}

	});


	});
	})( jQuery );
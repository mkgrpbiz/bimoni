

import Alpine from 'alpinejs';
import { initBankAutocomplete } from './bank-autocomplete';

window.Alpine = Alpine;
Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBankAutocomplete);
} else {
    initBankAutocomplete();
}

// 横スクロール一覧表: マウスホイールで（テーブル上にカーソルを置くだけで）横スクロールできるようにする
function initHorizontalWheelScroll() {
    document.querySelectorAll('.overflow-x-auto').forEach(function (el) {
        el.addEventListener('wheel', function (e) {
            if (el.scrollWidth <= el.clientWidth) return; // 横に伸びていない要素は対象外
            if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return; // 既に横方向の操作（Shift+ホイール等）は素通し
            el.scrollLeft += e.deltaY;
            e.preventDefault();
        }, { passive: false });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHorizontalWheelScroll);
} else {
    initHorizontalWheelScroll();
}

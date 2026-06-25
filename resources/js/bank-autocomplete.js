import banksRaw from './data/banks.json';

const bankList = Object.values(banksRaw);

function createSuggest(input, getItems, getLabel, onSelect) {
    let dropdown = null;

    function hide() {
        if (dropdown) { dropdown.remove(); dropdown = null; }
    }

    function show(items) {
        hide();
        if (!items.length) return;
        dropdown = document.createElement('div');
        dropdown.style.cssText = 'position:absolute;z-index:999;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);width:100%;max-height:200px;overflow-y:auto;top:100%;left:0;margin-top:2px;';
        items.forEach(item => {
            const d = document.createElement('div');
            d.style.cssText = 'padding:10px 12px;font-size:14px;cursor:pointer;';
            d.textContent = getLabel(item);
            d.addEventListener('mouseover', () => d.style.background = '#fdf2f8');
            d.addEventListener('mouseout', () => d.style.background = '');
            d.addEventListener('mousedown', e => {
                e.preventDefault();
                onSelect(item);
                hide();
            });
            dropdown.appendChild(d);
        });
        const wrap = input.parentElement;
        if (wrap.style.position !== 'relative') wrap.style.position = 'relative';
        wrap.appendChild(dropdown);
    }

    input.addEventListener('input', () => {
        const q = input.value.trim();
        if (!q) { hide(); return; }
        const items = getItems(q);
        show(items.slice(0, 15));
    });
    input.addEventListener('blur', () => setTimeout(hide, 200));
}

export function initBankAutocomplete() {
    const bankNameEl   = document.getElementById('bank_name');
    const bankCodeEl   = document.getElementById('bank_code');
    const branchNameEl = document.getElementById('bank_branch_name');
    const branchCodeEl = document.getElementById('bank_branch_code');

    if (!bankNameEl) return;

    let selectedBankCode = bankCodeEl?.value || null;
    let branchCache = {};

    // 銀行名オートコンプリート
    createSuggest(
        bankNameEl,
        q => bankList.filter(b => b.name.includes(q) || (b.hira && b.hira.includes(q)) || (b.kana && b.kana.includes(q))),
        b => b.name,
        b => {
            bankNameEl.value = b.name;
            if (bankCodeEl) bankCodeEl.value = b.code;
            selectedBankCode = b.code;
            if (branchNameEl) branchNameEl.value = '';
            if (branchCodeEl) branchCodeEl.value = '';
        }
    );

    if (!branchNameEl) return;

    async function getBranches(code) {
        if (branchCache[code]) return branchCache[code];
        try {
            const r = await fetch(`/data/zengin/branches/${code}.json`);
            branchCache[code] = await r.json();
        } catch { branchCache[code] = []; }
        return branchCache[code];
    }

    // 支店名オートコンプリート（AJAX）
    branchNameEl.addEventListener('input', async () => {
        const q = branchNameEl.value.trim();
        if (!q || !selectedBankCode) return;
        const branches = await getBranches(selectedBankCode);
        const filtered = branches.filter(b => b.name.includes(q) || (b.hira && b.hira.includes(q)));
        showBranchDropdown(filtered.slice(0, 15));
    });
    branchNameEl.addEventListener('blur', () => setTimeout(hideBranchDropdown, 200));

    let branchDropdown = null;
    function hideBranchDropdown() {
        if (branchDropdown) { branchDropdown.remove(); branchDropdown = null; }
    }
    function showBranchDropdown(items) {
        hideBranchDropdown();
        if (!items.length) return;
        branchDropdown = document.createElement('div');
        branchDropdown.style.cssText = 'position:absolute;z-index:999;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);width:100%;max-height:200px;overflow-y:auto;top:100%;left:0;margin-top:2px;';
        items.forEach(item => {
            const d = document.createElement('div');
            d.style.cssText = 'padding:10px 12px;font-size:14px;cursor:pointer;';
            d.textContent = item.name;
            d.addEventListener('mouseover', () => d.style.background = '#fdf2f8');
            d.addEventListener('mouseout', () => d.style.background = '');
            d.addEventListener('mousedown', e => {
                e.preventDefault();
                branchNameEl.value = item.name;
                if (branchCodeEl) branchCodeEl.value = item.code;
                hideBranchDropdown();
            });
            branchDropdown.appendChild(d);
        });
        const wrap = branchNameEl.parentElement;
        if (wrap.style.position !== 'relative') wrap.style.position = 'relative';
        wrap.appendChild(branchDropdown);
    }
}

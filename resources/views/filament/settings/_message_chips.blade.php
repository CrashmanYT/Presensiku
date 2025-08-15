@php
    /**
     * @var string $helperText
     * @var array<string> $tokens
     * @var array<string> $variantKeys
     * @var string $targetAttr
     * @var string $targetAttrValue
     * @var string $uid
     */
@endphp

<div class="mt-2">
    <div class="rounded-md border border-gray-200 bg-gray-50 p-2 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 overflow-x-auto">
        <div class="flex items-center gap-2 mb-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.75 5.75 12l2.5 3.25m7.5-6.5 2.5 3.25-2.5 3.25M13 4.75 11 19.25"/></svg>
            <span>Helper</span>
        </div>
        <pre class="font-mono text-xs whitespace-pre">{{ $helperText }}</pre>
    </div>

    <div id="{{ $uid }}" class="mt-2 flex flex-wrap gap-2" data-target-attr="{{ $targetAttr }}" data-target-value="{{ $targetAttrValue }}">
        <div class="w-full text-[11px] text-gray-600 dark:text-gray-300">Klik untuk menyisipkan:</div>
        @foreach ($tokens as $t)
            <button type="button" class="px-2 py-0.5 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800" data-token="{{ $t }}">{{ $t }}</button>
        @endforeach
        @foreach ($variantKeys as $vk)
            @php $label = '{v:' . $vk . '}'; @endphp
            <button type="button" class="px-2 py-0.5 text-xs rounded border border-gray-300 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800" data-token="{{ $label }}">{{ $label }}</button>
        @endforeach
    </div>
</div>

<script>
(function(){
  const root = document.getElementById(@json($uid));
  if (!root) return;
  const targetAttr = root.getAttribute('data-target-attr');
  const targetAttrValue = root.getAttribute('data-target-value');

  function findTextarea(startEl){
    let scope = startEl.closest('.fi-fo-field, .fi-forms-field, [data-repeater-item]');
    if (!scope) scope = startEl.parentElement || document;
    let ta = scope.querySelector('textarea['+CSS.escape(targetAttr)+'="'+CSS.escape(targetAttrValue)+'"]');
    if (!ta) {
      // fallback: search globally but prefer within same repeater item if exists
      const repeater = startEl.closest('[data-repeater-item]');
      if (repeater) {
        ta = repeater.querySelector('textarea['+CSS.escape(targetAttr)+'="'+CSS.escape(targetAttrValue)+'"]');
      }
    }
    return ta;
  }

  function insertAtCursor(ta, token){
    const start = (typeof ta.selectionStart === 'number') ? ta.selectionStart : ta.value.length;
    const end = (typeof ta.selectionEnd === 'number') ? ta.selectionEnd : ta.value.length;
    const before = ta.value.slice(0, start);
    const after = ta.value.slice(end);
    ta.value = before + token + after;
    const pos = before.length + token.length;
    if (ta.setSelectionRange) ta.setSelectionRange(pos, pos);
    ta.dispatchEvent(new Event('input', { bubbles: true }));
    ta.dispatchEvent(new Event('change', { bubbles: true }));
    ta.focus();
  }

  root.addEventListener('click', function(e){
    const btn = e.target.closest('button[data-token]');
    if (!btn) return;
    e.preventDefault();
    const token = btn.getAttribute('data-token');
    if (!token) return;
    const ta = findTextarea(root);
    if (!ta) return;
    insertAtCursor(ta, token);
  });
})();
</script>

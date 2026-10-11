<div id="kb-message-dialog" class="logout-dialog hidden no-print" role="alertdialog" aria-modal="true" aria-labelledby="kb-message-title" aria-describedby="kb-message-text">
  <div class="logout-dialog-card" style="max-width:440px">
    <h2 id="kb-message-title" class="text-xl font-bold">Please check your entry</h2>
    <p id="kb-message-text" class="mt-3 text-slate-600" style="white-space:pre-line"></p>
    <div class="mt-5 flex justify-end"><button type="button" id="kb-message-ok" class="ui-btn ui-btn-primary">OK</button></div>
  </div>
</div>
<script>
(() => {
  const dialog = document.getElementById('kb-message-dialog');
  const title = document.getElementById('kb-message-title');
  const text = document.getElementById('kb-message-text');
  const ok = document.getElementById('kb-message-ok');
  if (!dialog || !ok) return;
  let returnFocus = null;
  const close = () => {
    dialog.classList.add('hidden');
    const target = returnFocus;
    returnFocus = null;
    if (target && target.isConnected && target.getClientRects().length) target.focus();
  };
  const show = (message, heading = 'Please check your entry', focusTarget = null) => {
    title.textContent = heading;
    text.textContent = String(message);
    returnFocus = focusTarget || document.activeElement;
    dialog.classList.remove('hidden');
    ok.focus();
  };
  window.KofiDialog = { show };
  ok.addEventListener('click', close);
  dialog.addEventListener('click', e => { if (e.target === dialog) close(); });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !dialog.classList.contains('hidden')) { e.preventDefault(); close(); }
  });
  // Prevent Chrome's built-in validation bubble on every existing and dynamically added form.
  const disableNativeValidation = root => {
    if (root instanceof HTMLFormElement) root.noValidate = true;
    root.querySelectorAll?.('form').forEach(form => { form.noValidate = true; });
  };
  disableNativeValidation(document);
  new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
    if (node.nodeType === 1) disableNativeValidation(node);
  }))).observe(document.documentElement, { childList: true, subtree: true });

  const fieldLabel = field => {
    const id = field.id;
    const label = id ? [...document.querySelectorAll('label[for]')].find(el => el.htmlFor === id) : null;
    const candidate = label?.textContent?.trim() || field.getAttribute('aria-label') || field.closest('label')?.textContent?.trim() || field.name || 'This field';
    return candidate.replace(/\s*\(required\)\s*/ig, '').replace(/\s+/g, ' ').trim();
  };
  const fieldError = field => {
    const label = fieldLabel(field);
    if (field.validity.valueMissing) return `${label} is required.`;
    if (field.validity.typeMismatch) return `Enter a valid ${label.toLowerCase()}.`;
    if (field.validity.rangeUnderflow) return `${label} must be at least ${field.min}.`;
    if (field.validity.rangeOverflow) return `${label} must not exceed ${field.max}.`;
    if (field.validity.stepMismatch) return `${label} must use a valid whole-number increment.`;
    if (field.validity.tooShort) return `${label} must contain at least ${field.minLength} characters.`;
    if (field.validity.tooLong) return `${label} is too long.`;
    if (field.validity.patternMismatch) return `Check the format of ${label.toLowerCase()}.`;
    return field.validationMessage || `Please check ${label.toLowerCase()}.`;
  };
  // Capture before the existing confirmation handler, for both GET and POST forms.
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirmed === 'yes') return;
    form.noValidate = true;
    const invalid = [...form.elements].find(el => el.willValidate && !el.checkValidity());
    if (invalid) {
      event.preventDefault();
      show(fieldError(invalid), 'Please check your entry', invalid);
      return;
    }
    if (form.id === 'correction-form') {
      const rows = [...form.querySelectorAll('[data-correction-row]')];
      const changed = rows.some(row => {
        const input = row.querySelector('input[type="number"]');
        return input && Number(input.value) !== Number(row.dataset.original);
      });
      if (!changed) {
        event.preventDefault();
        show('Change at least one closing count before requesting a correction.', 'No changes detected');
      }
    }
  }, true);
})();
</script>
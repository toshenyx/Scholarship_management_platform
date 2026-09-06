
document.addEventListener('DOMContentLoaded', () => {
  initMultiselects();
  initFileUploads();
  initFilterPills();
  initShortlistHearts();
  initSettingsForms();
  initAuthForms();
});

function initMultiselects() {
  const multiselects = document.querySelectorAll('.multiselect');
  if (multiselects.length === 0) return;

  multiselects.forEach((multiselect) => {
    const trigger = multiselect.querySelector('.multiselect-trigger');
    const placeholder = multiselect.querySelector('.multiselect-placeholder');
    const checkboxes = multiselect.querySelectorAll('input[type="checkbox"]');
    if (!trigger) return;

    trigger.addEventListener('click', (event) => {
      event.stopPropagation(); 
      multiselect.classList.toggle('multiselect--open');
    });

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', () => {
        if (!placeholder) return;
        const checkedLabels = [...checkboxes]
          .filter((c) => c.checked)
          .map((c) => c.closest('.multiselect-option').textContent.trim());

        placeholder.textContent = checkedLabels.length
          ? checkedLabels.join(', ')
          : 'Select preferences';
      });
    });
  });

  
  document.addEventListener('click', (event) => {
    multiselects.forEach((multiselect) => {
      if (!multiselect.contains(event.target)) {
        multiselect.classList.remove('multiselect--open');
      }
    });
  });
}


/* ---------- 2. FILE UPLOAD BOXES ----------
   Used on: profile2.html (Educational Information — "Upload docx")
   Adds a green "has-file" state once a file is chosen, and swaps the
   label text to show the chosen filename instead of the placeholder. */
function initFileUploads() {
  const uploadItems = document.querySelectorAll('.upload-item');
  if (uploadItems.length === 0) return;

  uploadItems.forEach((item) => {
    const input = item.querySelector('input[type="file"]');
    const textEl = item.querySelector('.upload-text');
    if (!input) return;

    const originalText = textEl ? textEl.innerHTML : '';

    input.addEventListener('change', () => {
      const hasFile = input.files.length > 0;
      item.classList.toggle('has-file', hasFile);

      if (textEl) {
        textEl.textContent = hasFile ? input.files[0].name : '';
        if (!hasFile) textEl.innerHTML = originalText;
      }
    });
  });
}


/* ---------- 3. FILTER PILLS (Award type) ----------
   Used on: scholarships.html
   "All" and the specific award-type pills (Full, Partial, Govt,
   Merit, NeedBased, Auto) behave like a small filter: clicking "All"
   clears the rest; clicking any specific pill turns "All" off. */
function initFilterPills() {
  const pillGroups = document.querySelectorAll('.pill-group');
  if (pillGroups.length === 0) return;

  const allPills = document.querySelectorAll('.pill-btn');
  const allButton = [...allPills].find((btn) => btn.textContent.trim() === 'All');

  allPills.forEach((pill) => {
    pill.addEventListener('click', () => {
      if (pill === allButton) {
        // selecting "All" clears every other pill and activates only itself
        allPills.forEach((p) => p.classList.toggle('pill-btn--active', p === allButton));
      } else {
        pill.classList.toggle('pill-btn--active');
        if (allButton) allButton.classList.remove('pill-btn--active');

        // if nothing is left selected, fall back to "All" so the UI is never empty
        const anyActive = [...allPills].some((p) => p.classList.contains('pill-btn--active'));
        if (!anyActive && allButton) allButton.classList.add('pill-btn--active');
      }
    });
  });
}


/* ---------- 4. SHORTLIST HEART TOGGLE ----------
   Used on: updates.html
   Toggles the heart between outline and filled when clicked, as a
   simple "save to shortlist" indicator. */
function initShortlistHearts() {
  const shortlistLinks = document.querySelectorAll('.shortlist-link');
  if (shortlistLinks.length === 0) return;

  shortlistLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault(); // this link doesn't navigate anywhere; it's a toggle button
      const heart = link.querySelector('.heart-icon');
      if (!heart) return;
      const isSaved = heart.textContent.trim() === '♥';
      heart.textContent = isSaved ? '♡' : '♥';
      link.classList.toggle('shortlist-link--active', !isSaved);
    });
  });
}


/* ---------- 5. SETTINGS PAGE (Cancel buttons + password match check) ----------
   Used on: settings.html
   Cancel resets password fields and reverts toggles back to how they
   were when the page loaded. Also gives quick feedback if "Confirm
   new Password" doesn't match "New Password". */
function initSettingsForms() {
  const cards = document.querySelectorAll('.settings-card');
  if (cards.length === 0) return;

  cards.forEach((card) => {
    const cancelBtn = card.querySelector('.btn--ghost');
    const toggles = card.querySelectorAll('.toggle input[type="checkbox"]');
    const passwordInputs = card.querySelectorAll('input[type="password"]');

    // remember each toggle's starting state, so Cancel can restore it
    const initialToggleStates = new Map();
    toggles.forEach((toggle) => initialToggleStates.set(toggle, toggle.checked));

    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        passwordInputs.forEach((input) => (input.value = ''));
        toggles.forEach((toggle) => {
          toggle.checked = initialToggleStates.get(toggle);
        });
      });
    }

    const newPw = card.querySelector('#new-pw');
    const confirmPw = card.querySelector('#confirm-pw');
    if (newPw && confirmPw) {
      const checkMatch = () => {
        const mismatch = confirmPw.value.length > 0 && confirmPw.value !== newPw.value;
        confirmPw.style.borderColor = mismatch ? '#e8503a' : '';
      };
      newPw.addEventListener('input', checkMatch);
      confirmPw.addEventListener('input', checkMatch);
    }
  });
}


/* ---------- 6. SIGNIN / SIGNUP FORM VALIDATION ----------
   Used on: signin.html, signup.html
   Simple client-side check: don't let the form submit with empty
   fields, and show a lightweight inline message instead of the
   browser's default validation popups. */
function initAuthForms() {
  const forms = document.querySelectorAll('body > form');
  if (forms.length === 0) return;

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const inputs = form.querySelectorAll('input[type="text"], input[type="password"]');
      let hasEmpty = false;

      inputs.forEach((input) => {
        input.style.borderColor = '';
        if (input.value.trim() === '') {
          hasEmpty = true;
          input.style.borderColor = '#e8503a';
        }
      });

      if (hasEmpty) {
        event.preventDefault();
        let message = form.querySelector('.form-error-message');
        if (!message) {
          message = document.createElement('p');
          message.className = 'form-error-message';
          message.style.color = '#e8503a';
          message.style.marginTop = '8px';
          form.appendChild(message);
        }
        message.textContent = 'Please fill in all fields.';
      }
    });
  });
}
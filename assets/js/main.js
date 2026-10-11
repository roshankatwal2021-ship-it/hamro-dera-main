document.addEventListener('DOMContentLoaded', function () {

  // ---- Mobile Nav Toggle ----
  const toggle = document.getElementById('navToggle');
  const links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!toggle.contains(e.target) && !links.contains(e.target)) {
        links.classList.remove('open');
      }
    });
  }

  // ---- Image Gallery (listing detail page) ----
  const mainImg = document.getElementById('galleryMain');
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const prevBtn = document.getElementById('galleryPrev');
  const nextBtn = document.getElementById('galleryNext');
  let currentIdx = 0;

  function setActiveThumb(idx) {
    thumbs.forEach(t => t.classList.remove('active'));
    if (thumbs[idx]) thumbs[idx].classList.add('active');
    if (mainImg && thumbs[idx]) {
      mainImg.style.opacity = '0';
      setTimeout(() => {
        mainImg.src = thumbs[idx].querySelector('img').src;
        mainImg.style.opacity = '1';
      }, 150);
      currentIdx = idx;
    }
  }

  thumbs.forEach((thumb, i) => {
    thumb.addEventListener('click', () => setActiveThumb(i));
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      const newIdx = (currentIdx - 1 + thumbs.length) % thumbs.length;
      setActiveThumb(newIdx);
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      const newIdx = (currentIdx + 1) % thumbs.length;
      setActiveThumb(newIdx);
    });
  }

  // Keyboard navigation for gallery
  document.addEventListener('keydown', (e) => {
    if (!mainImg) return;
    if (e.key === 'ArrowLeft' && prevBtn) prevBtn.click();
    if (e.key === 'ArrowRight' && nextBtn) nextBtn.click();
  });

  // Set first thumb active
  if (thumbs.length > 0) thumbs[0].classList.add('active');

  // ---- Image Upload Preview ----
  const uploadZone = document.getElementById('uploadZone');
  const fileInput = document.getElementById('fileInput');
  const previewGrid = document.getElementById('previewGrid');

  if (uploadZone && fileInput) {
    uploadZone.addEventListener('click', () => fileInput.click());

    uploadZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadZone.classList.add('drag-over');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.classList.remove('drag-over');
      handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => handleFiles(fileInput.files));
  }

  let selectedFiles = [];

  function handleFiles(files) {
    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) return;
      selectedFiles.push(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        const item = document.createElement('div');
        item.className = 'preview-item';
        item.innerHTML = `<img src="${e.target.result}" alt="preview">
          <button type="button" class="rm-img" title="Remove"><i class="fas fa-times"></i></button>`;
        item.querySelector('.rm-img').addEventListener('click', () => {
          const idx = Array.from(previewGrid.querySelectorAll('.preview-item')).indexOf(item);
          selectedFiles.splice(idx, 1);
          item.remove();
          updateFileInput();
        });
        previewGrid.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
    updateFileInput();
  }

  function updateFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    if (fileInput) fileInput.files = dt.files;
  }

  // ---- Auto-fade flash messages ----
  const flashes = document.querySelectorAll('.global-flash .alert, .alert-auto-fade');
  flashes.forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // ---- Confirm delete ----
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ---- Animate stat numbers ----
  document.querySelectorAll('.stat-info h3[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    let current = 0;
    const step = Math.max(1, Math.floor(target / 30));
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current >= target) clearInterval(timer);
    }, 30);
  });

});

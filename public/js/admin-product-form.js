(function () {
  'use strict';

  var form = document.getElementById('product-form');
  if (!form) return;

  var d = form.dataset;
  var $ = function (sel, root) { return (root || form).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || form).querySelectorAll(sel)); };
  var fmt = function (template, pct) { return template.replace('{pct}', pct); };

  // ---------------------------------------------------------------- photos
  // Phone photos are often 4–8 MB; the server accepts 24 MB per request, so
  // anything large is resized to 2000px JPEG in the browser before upload.
  var MAX_EDGE = 2000;
  var COMPRESS_OVER_BYTES = 1.2 * 1024 * 1024;
  var photos = $('[data-photos]');
  var fileInput = $('[data-file-input]');
  var drop = $('[data-drop]');
  var photoStatus = $('[data-photo-status]');
  var newFiles = []; // { file, url }
  var busy = 0;

  function setStatus(el, text) {
    el.textContent = text || '';
    el.hidden = !text;
  }

  function compress(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || typeof createImageBitmap !== 'function') {
      return Promise.resolve(file);
    }
    // If decoding stalls, upload the original rather than blocking the form.
    var fallback = new Promise(function (resolve) { setTimeout(function () { resolve(file); }, 8000); });
    return Promise.race([resize(file), fallback]);
  }

  function resize(file) {
    return createImageBitmap(file).then(function (bitmap) {
      var scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
      if (scale === 1 && file.size <= COMPRESS_OVER_BYTES) {
        bitmap.close && bitmap.close();
        return file;
      }
      var canvas = document.createElement('canvas');
      canvas.width = Math.round(bitmap.width * scale);
      canvas.height = Math.round(bitmap.height * scale);
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      bitmap.close && bitmap.close();
      return new Promise(function (resolve) {
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          resolve(new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', 0.85);
      });
    }).catch(function () { return file; });
  }

  function syncFileInput() {
    if (typeof DataTransfer === 'undefined') return;
    var dt = new DataTransfer();
    newFiles.forEach(function (entry) { dt.items.add(entry.file); });
    fileInput.files = dt.files;
  }

  function checkedCover() {
    var radio = $('input[name="cover"]:checked:not(:disabled)');
    return radio ? radio.value : null;
  }

  function renderNewPhotos() {
    var current = checkedCover();
    $$('[data-photo^="new:"]').forEach(function (el) { el.remove(); });
    newFiles.forEach(function (entry, i) {
      var fig = document.createElement('figure');
      fig.className = 'pf-photo is-new';
      fig.dataset.photo = 'new:' + i;
      fig.innerHTML =
        '<img alt="">' +
        '<label class="pf-photo__cover"><input type="radio" name="cover"><span></span></label>' +
        '<button type="button" class="pf-photo__remove" data-remove-photo></button>';
      fig.querySelector('img').src = entry.url;
      var radio = fig.querySelector('input');
      radio.value = 'new:' + i;
      radio.checked = current === radio.value;
      fig.querySelector('span').dataset.on = d.labelCover;
      fig.querySelector('span').dataset.off = d.labelSetCover;
      var btn = fig.querySelector('button');
      btn.textContent = d.labelRemove;
      btn.setAttribute('aria-label', d.labelRemove);
      photos.insertBefore(fig, drop);
    });
    ensureCover();
  }

  function ensureCover() {
    if (!checkedCover()) {
      var first = $('input[name="cover"]:not(:disabled)');
      if (first) first.checked = true;
    }
    updatePreview();
  }

  fileInput.addEventListener('change', function () {
    var picked = Array.prototype.slice.call(fileInput.files || []);
    // Files already tracked are re-added by syncFileInput; only process new picks.
    var fresh = picked.filter(function (f) {
      return !newFiles.some(function (e) { return e.file === f; });
    });
    if (!fresh.length) return;
    busy++;
    setStatus(photoStatus, d.labelCompressing);
    Promise.all(fresh.map(compress)).then(function (files) {
      files.forEach(function (file) { newFiles.push({ file: file, url: URL.createObjectURL(file) }); });
      syncFileInput();
      renderNewPhotos();
      markDirty();
    }).finally(function () {
      busy--;
      if (!busy) setStatus(photoStatus, '');
    });
  });

  ['dragenter', 'dragover'].forEach(function (type) {
    drop.addEventListener(type, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
  });
  ['dragleave', 'drop'].forEach(function (type) {
    drop.addEventListener(type, function () { drop.classList.remove('is-over'); });
  });
  drop.addEventListener('drop', function (e) {
    e.preventDefault();
    if (!e.dataTransfer || !e.dataTransfer.files.length || typeof DataTransfer === 'undefined') return;
    var dt = new DataTransfer();
    newFiles.forEach(function (entry) { dt.items.add(entry.file); });
    Array.prototype.forEach.call(e.dataTransfer.files, function (f) { if (/^image\//.test(f.type)) dt.items.add(f); });
    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change'));
  });

  photos.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-remove-photo]');
    if (!btn) return;
    var fig = btn.closest('[data-photo]');
    var key = fig.dataset.photo;
    if (key.indexOf('new:') === 0) {
      var index = parseInt(key.slice(4), 10);
      URL.revokeObjectURL(newFiles[index].url);
      newFiles.splice(index, 1);
      syncFileInput();
      renderNewPhotos();
    } else {
      var removed = fig.classList.toggle('is-removed');
      $$('input', fig).forEach(function (input) {
        input.disabled = removed;
        if (removed && input.type === 'radio') input.checked = false;
      });
      btn.textContent = removed ? d.labelUndo : d.labelRemove;
      btn.setAttribute('aria-label', btn.textContent);
      ensureCover();
    }
    markDirty();
  });

  photos.addEventListener('change', function (e) {
    if (e.target.name === 'cover') updatePreview();
  });

  // ---------------------------------------------------------------- colors
  var colorList = $('[data-colors]');
  var colorTemplate = $('[data-color-template]');
  var colorStatus = $('[data-color-status]');

  function addColor(preset) {
    if (preset) {
      var exists = $$('input[name="color_ar[]"]', colorList).some(function (input) {
        return input.value.trim() === preset.ar;
      });
      if (exists) {
        setStatus(colorStatus, d.labelColorExists);
        setTimeout(function () { setStatus(colorStatus, ''); }, 2500);
        return;
      }
    }
    var row = colorTemplate.content.firstElementChild.cloneNode(true);
    if (preset) {
      row.querySelector('input[type="color"]').value = preset.hex;
      row.querySelector('input[name="color_ar[]"]').value = preset.ar;
      row.querySelector('input[name="color_en[]"]').value = preset.en;
    }
    colorList.appendChild(row);
    if (!preset) row.querySelector('input[name="color_ar[]"]').focus();
    markDirty();
  }

  $$('[data-color-preset]').forEach(function (btn) {
    btn.addEventListener('click', function () { addColor(JSON.parse(btn.dataset.colorPreset)); });
  });
  $('[data-add-color]').addEventListener('click', function () { addColor(null); });
  colorList.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-remove-color]');
    if (!btn) return;
    btn.closest('[data-color-row]').remove();
    markDirty();
  });

  // ---------------------------------------------------------------- sizes
  var sizes = $('[data-sizes]');
  var customSize = $('[data-custom-size]');

  function addSize() {
    var value = customSize.value.trim().replace(/^eu\s*/i, '');
    if (!value) return;
    var existing = $$('input[name="sizes[]"]', sizes).filter(function (input) { return input.value === value; })[0];
    if (existing) {
      existing.checked = true;
    } else {
      var label = document.createElement('label');
      label.className = 'pf-chip';
      label.innerHTML = '<input type="checkbox" name="sizes[]" checked><span dir="ltr"></span>';
      label.querySelector('input').value = value;
      label.querySelector('span').textContent = 'EU ' + value;
      sizes.appendChild(label);
    }
    customSize.value = '';
    markDirty();
  }

  $('[data-add-size]').addEventListener('click', addSize);
  customSize.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); addSize(); }
  });

  // ---------------------------------------------------------------- price & preview
  var price = $('#f-price');
  var offer = $('#f-offer');
  var offerNote = $('[data-offer-note]');
  var offerNoteDefault = offerNote.textContent;
  var categories = JSON.parse(d.categories || '{}');
  var money = function (value) {
    var n = parseFloat(value);
    if (isNaN(n)) return '—';
    return n.toFixed(3) + ' ' + d.currency;
  };

  function updatePreview() {
    var regular = parseFloat(price.value);
    var sale = parseFloat(offer.value);
    var onSale = !isNaN(regular) && !isNaN(sale) && sale > 0 && sale < regular;
    var pct = onSale ? Math.round((1 - sale / regular) * 100) : 0;

    offerNote.classList.toggle('pf-hint--error', !isNaN(sale) && !isNaN(regular) && sale >= regular);
    offerNote.classList.toggle('pf-hint--good', onSale);
    offerNote.textContent = onSale ? fmt(d.labelSavings, pct)
      : (!isNaN(sale) && !isNaN(regular) && sale >= regular) ? d.labelOfferHigh
      : offerNoteDefault;

    var stock = ($('input[name="stock_status"]:checked') || {}).value;
    var inStock = stock === 'in_stock';
    var name = d.locale === 'en' ? ($('#f-name-en').value.trim() || $('#f-name').value.trim()) : $('#f-name').value.trim();
    var card = $('[data-preview]');
    var cat = ($('input[name="category"]:checked') || {}).value;

    var nameEl = $('[data-preview-name]');
    nameEl.textContent = name || nameEl.dataset.placeholder;
    $('[data-preview-cat]').textContent = categories[cat] || '';
    card.classList.toggle('card--dim', !inStock);

    var priceEl = $('[data-preview-price]');
    priceEl.textContent = '';
    if (onSale && inStock) {
      var s = document.createElement('span'); s.className = 'card__price-sale'; s.textContent = money(sale);
      var r = document.createElement('span'); r.className = 'card__price-reg'; r.textContent = money(regular);
      priceEl.append(s, r);
    } else {
      priceEl.textContent = money(price.value);
    }

    var badge = $('[data-preview-badge]');
    badge.hidden = !(!inStock || onSale);
    badge.classList.toggle('card__badge--unavail', !inStock);
    badge.textContent = !inStock ? d.labelUnavailable : '−' + pct + '%';

    var cover = checkedCover();
    var coverFig = cover ? $('[data-photo="' + cover + '"]') : null;
    var img = $('[data-preview-img]');
    var ph = $('[data-preview-ph]');
    if (coverFig) {
      img.src = coverFig.querySelector('img').src;
      img.hidden = false;
      ph.hidden = true;
    } else {
      img.hidden = true;
      ph.hidden = false;
    }

    var isPublished = $('[data-published]').checked;
    $('[data-preview-hidden]').hidden = isPublished;
    card.classList.toggle('pf-preview__card--hidden', !isPublished);
  }

  var published = $('[data-published]');
  var publishedHint = $('[data-published-hint]');

  ['input', 'change'].forEach(function (type) {
    form.addEventListener(type, function (e) {
      if (e.target.closest('[data-seo-form]')) return updateSeo();
      if (e.target === published) {
        publishedHint.textContent = published.checked ? publishedHint.dataset.on : publishedHint.dataset.off;
      }
      if (e.target.matches('#f-name, #f-name-en, #f-price, #f-offer, [name="stock_status"], [name="category"], [data-published]')) {
        updatePreview();
      }
      if (e.target.matches('#f-name, #f-name-en, #f-description, #f-description-en')) updateSeo();
    });
  });

  // ---------------------------------------------------------------- SEO preview
  var seo = $('[data-seo-form]');
  var slug = $('#f-slug');
  var clip = function (text, max) {
    text = (text || '').replace(/\s+/g, ' ').trim();
    return text.length > max ? text.slice(0, max - 1) + '…' : text;
  };
  var slugify = function (text) {
    return (text || '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };

  function updateSeo() {
    if (!seo) return;
    var currentSlug = slug.value.trim() || seo.dataset.currentSlug || slugify($('#f-name-en').value) || '…';
    ['ar', 'en'].forEach(function (loc) {
      var name = loc === 'en' ? ($('#f-name-en').value.trim() || $('#f-name').value.trim()) : $('#f-name').value.trim();
      var desc = loc === 'en' ? ($('#f-description-en').value.trim() || $('#f-description').value) : $('#f-description').value;
      var title = $('#f-seo-title-' + loc).value.trim() || (name + seo.dataset['suffix' + (loc === 'ar' ? 'Ar' : 'En')]);
      var metaDesc = $('#f-seo-desc-' + loc).value.trim() || clip(desc, 140);
      $('[data-serp-url="' + loc + '"]').textContent = seo.dataset.baseUrl + (loc === 'en' ? '/en' : '') + '/product/' + currentSlug;
      $('[data-serp-title="' + loc + '"]').textContent = clip(title, 60);
      $('[data-serp-desc="' + loc + '"]').textContent = clip(metaDesc, 160);
    });
    $$('[data-count-for]').forEach(function (el) {
      var input = document.getElementById(el.dataset.countFor);
      el.textContent = input.value.length;
      el.classList.toggle('pf-over', input.value.length > parseInt(input.dataset.limit, 10));
    });
  }

  if (slug) {
    slug.addEventListener('input', function () {
      slug.value = slug.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]+/g, '');
    });
  }

  // ---------------------------------------------------------------- dirty state & submit
  var dirty = false;
  var submitting = false;
  function markDirty() { dirty = true; }
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  window.addEventListener('beforeunload', function (e) {
    if (dirty && !submitting) {
      e.preventDefault();
      e.returnValue = d.labelUnsaved;
    }
  });

  form.addEventListener('submit', function (e) {
    if (busy) {
      e.preventDefault();
      setStatus(photoStatus, d.labelCompressing);
      return;
    }
    submitting = true;
    $$('[data-save]').forEach(function (btn) {
      btn.disabled = true;
      btn.textContent = d.labelSaving;
    });
  });

  ensureCover();
  updateSeo();
})();

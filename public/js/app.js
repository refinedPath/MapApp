'use strict';

(function () {
  const API_BASE = '/api';

  const state = { map: null, token: null, addPlaceMode: false, pendingLngLat: null, markers: {}, filter: { tagIds: [], mode: 'any' } };

  const graphemeSegmenter = new Intl.Segmenter(undefined, { granularity: "grapheme" });

  // DOM refs — assigned in init() after the DOM is ready.
  let appConfig = null;
  let authView, loginForm, loginEmail, loginPassword, loginError;
  let mapContainer, mapCustomControls, addPlaceBtn;
  let createPlaceDialog, createPlaceForm, placeName, placeDescription, createPlaceError, cancelCreatePlaceBtn;
  let editPlaceDialog, editPlaceForm, editPlaceName, editPlaceDescription, editPlaceTagsList, editPlaceAllTagsList, editPlaceError, cancelEditPlaceBtn, deleteEditPlaceBtn;
  let manageTagsBtn, manageTagsDialog, tagForm, tagFormName, tagFormColor, tagFormEmoji, tagFormError, tagFormCancelBtn, tagFormSubmit, tagManagerList, closeManageTagsBtn;
  let editPlaceTags = [];
  let editPlaceAllTags = [];
  let manageTags = [];
  let manageTagsDirty = false;
  let openPopup = null;
  let filterBtn, filterDialog, filterTagsList, filterClearBtn, filterCancelBtn, filterApplyBtn;
  let filterTags = [];
  let filterStagedIds = new Set();

  /**
   * Create a DOM element with classes, text, attributes, dataset, and children.
   * @param {string} tag - HTML tag name
   * @param {Object} [options]
   * @param {string[]} [options.classes] - CSS classes to add
   * @param {string} [options.text] - textContent
   * @param {string} [options.title] - title attribute (tooltip)
   * @param {Object<string,string>} [options.dataset] - data-* attributes (camelCase keys)
   * @param {Object<string,string>} [options.attrs] - other attributes (e.g., aria-*)
   * @param {Element[]} [options.children] - children to append in order
   * @returns {HTMLElement}
   */
  function el(tag, { classes = [], text, title, dataset = {}, attrs = {}, children = [] } = {}) {
    const node = document.createElement(tag);
    if (classes.length) node.classList.add(...classes);
    if (text !== undefined) node.textContent = text;
    if (title !== undefined) node.title = title;
    Object.assign(node.dataset, dataset);
    for (const [key, value] of Object.entries(attrs)) node.setAttribute(key, value);
    children.forEach(child => node.appendChild(child));
    return node;
  }

  function init() {
    authView = document.getElementById('authView');
    loginForm = document.getElementById('loginForm');
    loginEmail = document.getElementById('loginEmail');
    loginPassword = document.getElementById('loginPassword');
    loginError = document.getElementById('loginError');

    mapContainer = document.getElementById('mapContainer');
    mapCustomControls = document.getElementById('mapCustomControls');
    addPlaceBtn = document.getElementById('addPlaceBtn');

    createPlaceDialog = document.getElementById('createPlaceDialog');
    createPlaceForm = document.getElementById('createPlaceForm');
    placeName = document.getElementById('placeName');
    placeDescription = document.getElementById('placeDescription');
    createPlaceError = document.getElementById('createPlaceError');
    cancelCreatePlaceBtn = document.getElementById('cancelCreatePlaceBtn');

    editPlaceDialog = document.getElementById('editPlaceDialog');
    editPlaceForm = document.getElementById('editPlaceForm');
    editPlaceName = document.getElementById('editPlaceName');
    editPlaceDescription = document.getElementById('editPlaceDescription');
    editPlaceTagsList = document.getElementById('editPlaceTagsList');
    editPlaceAllTagsList = document.getElementById('editPlaceAllTagsList');
    editPlaceError = document.getElementById('editPlaceError');
    cancelEditPlaceBtn = document.getElementById('cancelEditPlaceBtn');
    deleteEditPlaceBtn = document.getElementById('deleteEditPlaceBtn');

    manageTagsBtn = document.getElementById('manageTagsBtn');
    manageTagsDialog = document.getElementById('manageTagsDialog');
    tagForm = document.getElementById('tagForm');
    tagFormName = document.getElementById('tagFormName');
    tagFormColor = document.getElementById('tagFormColor');
    tagFormEmoji = document.getElementById('tagFormEmoji');
    tagFormError = document.getElementById('tagFormError');
    tagFormCancelBtn = document.getElementById('tagFormCancelBtn');
    tagFormSubmit = document.getElementById('tagFormSubmit');
    tagManagerList = document.getElementById('tagManagerList');
    closeManageTagsBtn = document.getElementById('closeManageTagsBtn');

    filterBtn = document.getElementById('filterBtn');
    filterDialog = document.getElementById('filterDialog');
    filterTagsList = document.getElementById('filterTagsList');
    filterClearBtn = document.getElementById('filterClearBtn');
    filterCancelBtn = document.getElementById('filterCancelBtn');
    filterApplyBtn = document.getElementById('filterApplyBtn');

    loginForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      loginError.textContent = '';

      const payload = {
        email: loginEmail.value,
        password: loginPassword.value,
      };

      try {
        state.token = await login(payload);
        appConfig = await fetchConfig();

        authView.hidden = true;
        mapContainer.hidden = false;
        mapCustomControls.hidden = false;

        if (state.map === null) {
          state.map = new maplibregl.Map({
            style: 'https://tiles.openfreemap.org/styles/liberty',
            center: [-74.0135, 40.7054],
            zoom: 12,
            container: 'mapContainer',
          });

          const places = await fetchPlaces();

          for (const place of places) {
            addPlaceMarker(place);
          }

          state.map.on('click', (e) => {
            if (state.addPlaceMode === true) {
              state.pendingLngLat = e.lngLat;
              createPlaceDialog.showModal();
              disarmAddPlaceMode();
            }
          });
        }
      } catch (err) {
        loginError.textContent = err.message;
        console.error(err);
      }
    });

    createPlaceDialog.addEventListener('submit', async (event) => {
      event.preventDefault();
      createPlaceError.textContent = '';

      const payload = {
        name: placeName.value,
        description: placeDescription.value,
        latitude: state.pendingLngLat.lat,
        longitude: state.pendingLngLat.lng,
      };

      try {
        const newPlace = await authedPostJSON(`${API_BASE}/places`, payload);
        addPlaceMarker(newPlace);
        createPlaceDialog.close();
        createPlaceForm.reset();
      } catch (err) {
        createPlaceError.textContent = err.message;
        console.error(err);
      }
    });

    cancelCreatePlaceBtn.addEventListener('click', () => {
      createPlaceDialog.close();
      disarmAddPlaceMode();
    });

    addPlaceBtn.addEventListener('click', () => {
      if (state.addPlaceMode) {
        disarmAddPlaceMode();
      } else {
        armAddPlaceMode();
      }
    });

    editPlaceForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      editPlaceError.textContent = '';

      const placeId = editPlaceDialog.dataset.placeId;
      const payload = {
        name: editPlaceName.value,
        description: editPlaceDescription.value,
      };

      try {
        await authedFetch(`${API_BASE}/places/${placeId}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        await refreshPlaceMarker(placeId);
        editPlaceDialog.close();
      } catch (err) {
        editPlaceError.textContent = err.message;
        console.error(err);
      }
    });

    cancelEditPlaceBtn.addEventListener('click', () => {
      editPlaceDialog.close();
    });

    deleteEditPlaceBtn.addEventListener('click', async () => {
      const placeId = editPlaceDialog.dataset.placeId;
      if (!confirm('Delete this place? This cannot be undone.')) return;

      editPlaceError.textContent = '';
      try {
        await authedFetch(`${API_BASE}/places/${placeId}`, {
          method: 'DELETE',
        });

        state.markers[placeId]?.remove();
        delete state.markers[placeId];
        if (openPopup && openPopup.placeId === placeId) {
          openPopup = null;
        }
        updateFilterIndicator();

        editPlaceDialog.close();
      } catch (err) {
        editPlaceError.textContent = err.message;
        console.error(err);
      }
    });

    manageTagsBtn.addEventListener('click', () => {
      openManageTags();
    });

    closeManageTagsBtn.addEventListener('click', () => {
      manageTagsDialog.close();
    });

    manageTagsDialog.addEventListener('close', async () => {
      if (!manageTagsDirty) return;
      manageTagsDirty = false;
      try {
        await resyncMarkers();
      } catch (err) {
        console.error(err);
      }
    });

    tagFormCancelBtn.addEventListener('click', () => {
      resetTagForm();
    });

    tagForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      tagFormError.textContent = '';

      const editingId = tagForm.dataset.editingId;
      const payload = {
        name: tagFormName.value,
        color: tagFormColor.value,
        emoji: tagFormEmoji.value,
      };

      try {
        if (editingId) {
          await authedFetch(`${API_BASE}/tags/${editingId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });
          manageTagsDirty = true;
        } else {
          await authedPostJSON(`${API_BASE}/tags`, payload);
        }
        manageTags = await fetchTagsWithCounts();
        renderTagManager();
        resetTagForm();
      } catch (err) {
        tagFormError.textContent = err.message;
        console.error(err);
      }
    });

    filterBtn.addEventListener('click', () => {
      openFilterDialog();
    });

    filterApplyBtn.addEventListener('click', () => {
      applyFilter();
    });

    filterClearBtn.addEventListener('click', () => {
      clearFilter();
    });

    filterCancelBtn.addEventListener('click', () => {
      filterDialog.close();
    });
  }

  function armAddPlaceMode() {
    state.addPlaceMode = true;
    state.map.getCanvas().style.cursor = 'crosshair';
    addPlaceBtn.textContent = 'Cancel';
  }

  function disarmAddPlaceMode() {
    state.addPlaceMode = false;
    state.map.getCanvas().style.cursor = '';
    addPlaceBtn.textContent = 'Add place';
  }

  async function authedPostJSON(url, obj) {
    return authedFetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(obj),
    });
  }

  async function apiFetch(url, options = {}) {
    const response = await fetch(url, options);
    if (response.status === 204) return null;
    const data = await response.json();
    if (!response.ok) throw new Error(data.error ?? `HTTP ${response.status}`);
    return data;
  }

  async function authedFetch(url, options = {}) {
    const headers = {};
    if (options.headers) Object.assign(headers, options.headers);
    headers['Authorization'] = 'Bearer ' + state.token;
    return apiFetch(url, { ...options, headers });
  }

  async function login(payload) {
    const data = await apiFetch(`${API_BASE}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    return data.token;
  }

  async function fetchPlaces() {
    const { tagIds, mode } = state.filter;
    if (tagIds.length === 0) {
      return authedFetch(`${API_BASE}/places`);
    }
    const query = new URLSearchParams({ tags: tagIds.join(','), match: mode });
    return authedFetch(`${API_BASE}/places?${query.toString()}`);
  }

  async function fetchPlace(placeId) {
    return authedFetch(`${API_BASE}/places/${placeId}`);
  }

  async function fetchPlaceTags(placeId) {
    return authedFetch(`${API_BASE}/places/${placeId}/tags`);
  }

  async function fetchTags() {
    return authedFetch(`${API_BASE}/tags`);
  }

  async function fetchTagsWithCounts() {
    return authedFetch(`${API_BASE}/tags/counts`);
  }

  async function fetchConfig() {
    return authedFetch(`${API_BASE}/config`);
  }

  function firstGrapheme(str) {
    if (str === null) return null;
    const seg = graphemeSegmenter.segment(str.trim());
    return seg[Symbol.iterator]().next().value?.segment ?? null;
  }

  async function openEditDialog(place) {
    editPlaceError.textContent = '';
    editPlaceName.value = place.name;
    editPlaceDescription.value = place.description ?? '';
    editPlaceDialog.dataset.placeId = place.id;
    editPlaceDialog.dataset.primaryTagId = place.primary_tag_id ?? '';

    editPlaceTagsList.textContent = 'Loading tags…';
    editPlaceDialog.showModal();

    try {
      const [tags, allTags] = await Promise.all([
        fetchPlaceTags(place.id),
        fetchTags(),
      ]);
      if (!editPlaceDialog.open) return;
      editPlaceTags = tags;
      editPlaceAllTags = allTags;
      renderTags(editPlaceDialog.dataset.primaryTagId || null);
    } catch (err) {
      if (!editPlaceDialog.open) return;
      editPlaceTagsList.textContent = 'Could not load tags.';
      console.error(err);
    }
  }

  function renderTagChip(tag) {
    const chip = el('div', {
      classes: ['place-popup__tag'],
      title: tag.name,
      children: [el('span', { classes: ['place-popup__tag-name'], text: tag.name })],
    });
    chip.style.backgroundColor = tag.color;

    const emoji = firstGrapheme(tag.emoji);
    if (emoji !== null) {
      chip.insertBefore(
        el('span', { classes: ['place-popup__tag-emoji'], text: emoji }),
        chip.firstChild,
      );
    }

    return chip;
  }

  function renderEditableTagChip(tag, controls) {
    const chip = renderTagChip(tag);
    for (const control of controls) {
      chip.appendChild(control);
    }
    return chip;
  }

  function tagControlButton(glyph, label, onClick) {
    const btn = el('button', {
      classes: ['tag-chip__control'],
      text: glyph,
      title: label,
      attrs: { type: 'button', 'aria-label': label },
    });
    btn.addEventListener('click', onClick);
    return btn;
  }

  function renderAssignedTagChip(tag, primaryTagId) {
    const isPrimary = tag.id === primaryTagId;

    const togglePrimary = tagControlButton(
      isPrimary ? '★' : '☆',
      isPrimary ? 'Unset primary' : 'Set as primary',
      async () => {
        const placeId = editPlaceDialog.dataset.placeId;
        const currentPrimary = editPlaceDialog.dataset.primaryTagId || null;
        const makePrimary = tag.id !== currentPrimary;

        try {
          if (makePrimary) {
            await authedFetch(`${API_BASE}/places/${placeId}/primary-tag/${tag.id}`, {
              method: 'PUT',
            });
            editPlaceDialog.dataset.primaryTagId = tag.id;
          } else {
            await authedFetch(`${API_BASE}/places/${placeId}/primary-tag`, {
              method: 'DELETE',
            });
            editPlaceDialog.dataset.primaryTagId = '';
          }
          renderTags(editPlaceDialog.dataset.primaryTagId || null);
          await refreshPlaceMarker(placeId);
        } catch (err) {
          editPlaceError.textContent = err.message;
          console.error(err);
        }
      },
    );

    const unassignTag = tagControlButton('⊖', 'Unassign tag', async () => {
      const placeId = editPlaceDialog.dataset.placeId;
      try {
        await authedFetch(`${API_BASE}/places/${placeId}/tags/${tag.id}`, {
          method: 'DELETE',
        });
        editPlaceTags = editPlaceTags.filter((t) => t.id !== tag.id);
        const wasPrimary = editPlaceDialog.dataset.primaryTagId === tag.id;
        if (wasPrimary) {
          editPlaceDialog.dataset.primaryTagId = '';
        }
        renderTags(editPlaceDialog.dataset.primaryTagId || null);
        if (openPopup && openPopup.placeId === placeId) {
          renderPopupTags(placeId, openPopup.tagsSlot, openPopup.popup);
        }
        await refreshPlaceMarker(placeId);
      } catch (err) {
        editPlaceError.textContent = err.message;
        console.error(err);
      }
    });

    return renderEditableTagChip(tag, [togglePrimary, unassignTag]);
  }

  function renderUnassignedTagChip(tag) {
    const assignTag = tagControlButton('⊕', 'Assign tag', async () => {
      const placeId = editPlaceDialog.dataset.placeId;
      try {
        await authedFetch(`${API_BASE}/places/${placeId}/tags/${tag.id}`, {
          method: 'PUT',
        });
        editPlaceTags = [...editPlaceTags, tag];
        renderTags(editPlaceDialog.dataset.primaryTagId || null);
        if (openPopup && openPopup.placeId === placeId) {
          renderPopupTags(placeId, openPopup.tagsSlot, openPopup.popup);
        }
        await refreshPlaceMarker(placeId);
      } catch (err) {
        editPlaceError.textContent = err.message;
        console.error(err);
      }
    });
    return renderEditableTagChip(tag, [assignTag]);
  }

  function renderTags(primaryTagId) {
    const assignedIds = new Set(editPlaceTags.map((t) => t.id));

    editPlaceTagsList.textContent = '';
    for (const tag of editPlaceTags) {
      editPlaceTagsList.appendChild(renderAssignedTagChip(tag, primaryTagId));
    }

    editPlaceAllTagsList.textContent = '';
    for (const tag of editPlaceAllTags) {
      if (assignedIds.has(tag.id)) continue;
      editPlaceAllTagsList.appendChild(renderUnassignedTagChip(tag));
    }
  }

  function resetTagForm() {
    tagForm.reset();
    delete tagForm.dataset.editingId;
    tagFormColor.value = appConfig.tag.default_color;
    tagFormError.textContent = '';
    tagFormSubmit.value = 'Add tag';
    tagFormCancelBtn.hidden = true;
  }

  async function openManageTags() {
    resetTagForm();
    manageTagsDirty = false;
    tagManagerList.textContent = 'Loading tags…';
    manageTagsDialog.showModal();

    try {
      manageTags = await fetchTagsWithCounts();
      if (!manageTagsDialog.open) return;
      renderTagManager();
    } catch (err) {
      if (!manageTagsDialog.open) return;
      tagManagerList.textContent = 'Could not load tags.';
      console.error(err);
    }
  }

  function renderTagManager() {
    tagManagerList.textContent = '';

    if (manageTags.length === 0) {
      tagManagerList.appendChild(el('p', { classes: ['tag-manager__empty'], text: 'No tags yet.' }));
      return;
    }

    for (const tag of manageTags) {
      tagManagerList.appendChild(renderTagManagerRow(tag));
    }
  }

  function renderTagManagerRow(tag) {
    const chip = renderTagChip(tag);

    const count = el('span', {
      classes: ['tag-manager__count'],
      text: `${tag.assignment_count}`,
      title: `Assigned to ${tag.assignment_count} place${tag.assignment_count === 1 ? '' : 's'}`,
    });

    const editBtn = tagControlButton('✎', 'Edit tag', () => startEditTag(tag));
    const deleteBtn = tagControlButton('✕', 'Delete tag', () => deleteTag(tag));
    deleteBtn.classList.add('tag-manager__delete');

    const row = el('div', {
      classes: ['tag-manager__row'],
      children: [chip, count, editBtn, deleteBtn],
    });
    if (tag.assignment_count === 0) row.classList.add('tag-manager__row--unused');

    return row;
  }

  function startEditTag(tag) {
    tagForm.dataset.editingId = tag.id;
    tagFormName.value = tag.name;
    tagFormColor.value = tag.color;
    tagFormEmoji.value = tag.emoji ?? '';
    tagFormError.textContent = '';
    tagFormSubmit.value = 'Save';
    tagFormCancelBtn.hidden = false;
    tagFormName.focus();
  }

  async function deleteTag(tag) {
    const msg = tag.assignment_count > 0
      ? `Delete "${tag.name}"? It is assigned to ${tag.assignment_count} place${tag.assignment_count === 1 ? '' : 's'}; those tag assignments will be removed. The place${tag.assignment_count === 1 ? '' : 's'} stay${tag.assignment_count === 1 ? 's' : ''} untouched. This cannot be undone.`
      : `Delete "${tag.name}"? This cannot be undone.`;
    if (!confirm(msg)) return;

    tagFormError.textContent = '';
    try {
      await authedFetch(`${API_BASE}/tags/${tag.id}`, { method: 'DELETE' });
      manageTagsDirty = true;
      if (tagForm.dataset.editingId === tag.id) resetTagForm();
      manageTags = await fetchTagsWithCounts();
      renderTagManager();
    } catch (err) {
      tagFormError.textContent = err.message;
      console.error(err);
    }
  }

  async function openFilterDialog() {
    filterStagedIds = new Set(state.filter.tagIds);
    setFilterModeRadio(state.filter.mode);

    filterTagsList.textContent = 'Loading tags…';
    filterDialog.showModal();

    try {
      filterTags = await fetchTags();
      if (!filterDialog.open) return;
      renderFilterTags();
    } catch (err) {
      if (!filterDialog.open) return;
      filterTagsList.textContent = 'Could not load tags.';
      console.error(err);
    }
  }

  function setFilterModeRadio(mode) {
    const input = filterDialog.querySelector(`input[name="filterMode"][value="${mode}"]`);
    if (input) input.checked = true;
  }

  function renderFilterTags() {
    filterTagsList.textContent = '';

    if (filterTags.length === 0) {
      filterTagsList.appendChild(el('p', { classes: ['tag-manager__empty'], text: 'No tags yet.' }));
      return;
    }

    for (const tag of filterTags) {
      filterTagsList.appendChild(renderFilterTagChip(tag));
    }
  }

  function renderFilterTagChip(tag) {
    const selected = filterStagedIds.has(tag.id);
    const children = [el('span', { classes: ['place-popup__tag-name'], text: tag.name })];

    const emoji = firstGrapheme(tag.emoji);
    if (emoji !== null) {
      children.unshift(el('span', { classes: ['place-popup__tag-emoji'], text: emoji }));
    }

    const chip = el('button', {
      classes: ['filter-tag'],
      title: tag.name,
      attrs: { type: 'button', 'aria-pressed': selected ? 'true' : 'false' },
      children,
    });
    chip.style.backgroundColor = tag.color;

    chip.addEventListener('click', () => {
      if (filterStagedIds.has(tag.id)) {
        filterStagedIds.delete(tag.id);
        chip.setAttribute('aria-pressed', 'false');
      } else {
        filterStagedIds.add(tag.id);
        chip.setAttribute('aria-pressed', 'true');
      }
    });

    return chip;
  }

  function readFilterMode() {
    const checked = filterDialog.querySelector('input[name="filterMode"]:checked');
    return checked instanceof HTMLInputElement ? checked.value : 'any';
  }

  async function applyFilter() {
    state.filter = {
      tagIds: [...filterStagedIds],
      mode: readFilterMode(),
    };
    filterDialog.close();
    try {
      await resyncMarkers();
      updateFilterIndicator();
    } catch (err) {
      console.error(err);
    }
  }

  async function clearFilter() {
    state.filter = { tagIds: [], mode: readFilterMode() };
    updateFilterIndicator();
    filterDialog.close();
    try {
      await resyncMarkers();
    } catch (err) {
      console.error(err);
    }
  }

  function updateFilterIndicator() {
    const active = state.filter.tagIds.length > 0;
    filterBtn.textContent = active
      ? `Filter (${Object.keys(state.markers).length})`
      : 'Filter';
    filterBtn.classList.toggle('filter-btn--active', active);
  }

  function buildPlacePopup(place) {
    const rawDescription = place.description ?? null;
    const tagsSlot = el('div', { classes: ['place-popup__tags'] });
    const editBtn = el('button', { classes: ['popup-edit-btn'], text: '✎ Edit' });
    editBtn.addEventListener('click', () => openEditDialog(place));

    const children = [el('div', { text: place.name })];
    if (rawDescription !== null) {
      children.push(el('div', { text: rawDescription }));
    }
    children.push(tagsSlot);
    children.push(editBtn);

    const popupEl = el('div', { children });

    const popup = new maplibregl.Popup(
      {
        offset: {
          'bottom': [0, -48],
          'bottom-left': [0, -48],
          'bottom-right': [0, -48],
          'top': [0, 6],
          'top-left': [0, 6],
          'top-right': [0, 6],
          'left': [20, -27],
          'right': [-20, -27],
        },
      }).setDOMContent(popupEl);

    return { popup, tagsSlot };
  }

  function renderPopupTags(placeId, tagsSlot, popup) {
    tagsSlot.textContent = 'Loading tags…';
    fetchPlaceTags(placeId)
      .then((tags) => {
        if (!popup.isOpen()) return;
        tagsSlot.textContent = '';
        for (const tag of tags) {
          tagsSlot.appendChild(renderTagChip(tag));
        }
      })
      .catch((err) => {
        if (!popup.isOpen()) return;
        tagsSlot.textContent = '';
        tagsSlot.appendChild(el('div', { text: 'Could not load tags.' }));
        console.error(err);
      });
  }

  function addPlaceMarker(place) {
    const SVG_NS = 'http://www.w3.org/2000/svg';

    const color = place.primary_color ?? '#525f7a';

    const emoji = firstGrapheme(place.primary_emoji ?? null);

    const markerEl = el('div', { classes: ['place-marker'] });

    const markerSvg = document.createElementNS(SVG_NS, 'svg');
    markerSvg.setAttribute('class', 'place-marker__pin');
    markerSvg.setAttribute('viewBox', '0 0 30 42');
    markerSvg.setAttribute('width', '30');
    markerSvg.setAttribute('height', '42');

    const path = document.createElementNS(SVG_NS, 'path');
    path.setAttribute(
      'd',
      'M15 0 C6.716 0 0 6.716 0 15 C0 23.5 15 42 15 42 ' +
      'C15 42 30 23.5 30 15 C30 6.716 23.284 0 15 0 Z'
    );
    path.setAttribute('fill', color);
    markerSvg.appendChild(path);

    markerEl.appendChild(markerSvg);

    if (emoji !== null) {
      markerEl.appendChild(el('div', {
        classes: ['place-marker__inner'],
        text: emoji,
      }));
    }

    const { popup, tagsSlot } = buildPlacePopup(place);

    popup.on('open', () => {
      openPopup = { placeId: place.id, tagsSlot, popup };
      renderPopupTags(place.id, tagsSlot, popup);
    });
    popup.on('close', () => {
      if (openPopup && openPopup.popup === popup) openPopup = null;
    });

    const marker = new maplibregl.Marker({ element: markerEl, anchor: 'bottom' })
      .setLngLat([place.longitude, place.latitude])
      .setPopup(popup)
      .addTo(state.map);

    state.markers[place.id] = marker;
    return marker;
  }

  async function refreshPlaceMarker(placeId) {
    if (state.filter.tagIds.length > 0) {
      await resyncMarkers();
      updateFilterIndicator();
      return;
    }

    const freshPlace = await fetchPlace(placeId);

    const old = state.markers[placeId];
    const wasOpen = old.getPopup().isOpen();
    old.remove();

    addPlaceMarker(freshPlace);

    if (wasOpen) state.markers[placeId].togglePopup();
  }

  async function resyncMarkers() {
    const reopenId = openPopup ? openPopup.placeId : null;
    const places = await fetchPlaces();

    for (const id of Object.keys(state.markers)) {
      state.markers[id].remove();
    }
    state.markers = {};
    openPopup = null;

    for (const place of places) {
      addPlaceMarker(place);
    }

    if (reopenId !== null && state.markers[reopenId]) {
      state.markers[reopenId].togglePopup();
    }
  }

  init();
})();
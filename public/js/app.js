'use strict';

(function () {
  const API_BASE = '/api';

  const state = { map: null, token: null, addPlaceMode: false, pendingLngLat: null };

  const graphemeSegmenter = new Intl.Segmenter(undefined, { granularity: "grapheme" });

  // DOM refs — assigned in init() after the DOM is ready.
  let authView, loginForm, loginEmail, loginPassword, loginError;
  let mapContainer, mapCustomControls, addPlaceBtn;
  let createPlaceDialog, createPlaceForm, placeName, placeDescription, createPlaceError, cancelCreatePlaceBtn;
  let editPlaceDialog, editPlaceForm, editPlaceName, editPlaceDescription, editPlaceError, cancelEditPlaceBtn;

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

    loginForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      loginError.textContent = '';

      const payload = {
        email: loginEmail.value,
        password: loginPassword.value,
      };

      try {
        state.token = await login(payload);

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
    return authedFetch(`${API_BASE}/places`);
  }

  async function fetchPlaceTags(placeId) {
    return authedFetch(`${API_BASE}/places/${placeId}/tags`);
  }

  function firstGrapheme(str) {
    if (str === null) return null;
    const seg = graphemeSegmenter.segment(str.trim());
    return seg[Symbol.iterator]().next().value?.segment ?? null;
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

  function buildPlacePopup(place) {
    const rawDescription = place.description ?? null;
    const tagsSlot = el('div', { classes: ['place-popup__tags'] });

    const children = [el('div', { text: place.name })];
    if (rawDescription !== null) {
      children.push(el('div', { text: rawDescription }));
    }
    children.push(tagsSlot);

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
      tagsSlot.textContent = 'Loading tags…';
      fetchPlaceTags(place.id)
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
    });

    return new maplibregl.Marker({ element: markerEl, anchor: 'bottom' })
      .setLngLat([place.longitude, place.latitude])
      .setPopup(popup)
      .addTo(state.map);
  }

  init();
})();
'use strict';

const API_BASE = '/api';

const state = { map: null, token: null, addPlaceMode: false, pendingLngLat: null };

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
  const authView = document.getElementById('authView');
  const loginForm = document.getElementById('loginForm');
  const loginEmail = document.getElementById('loginEmail');
  const loginPassword = document.getElementById('loginPassword');
  const loginError = document.getElementById('loginError');
  const mapContainer = document.getElementById('mapContainer');
  const mapCustomControls = document.getElementById('mapCustomControls');
  const addPlaceBtn = document.getElementById('addPlaceBtn');
  const createPlaceDialog = document.getElementById('createPlaceDialog');
  const createPlaceForm = document.getElementById('createPlaceForm');
  const placeName = document.getElementById('placeName');
  const placeDescription = document.getElementById('placeDescription');
  const createPlaceError = document.getElementById('createPlaceError');
  const cancelCreatePlaceBtn = document.getElementById('cancelCreatePlaceBtn');

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
      const newPlace = await authedFetch(`${API_BASE}/places`, payload);
      addPlaceMarker(newPlace);
      createPlaceDialog.close();
      createPlaceForm.reset();
    } catch (err) {
      createPlaceError.textContent = err.message;
      console.log(err);
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
}

async function login(payload) {
  const response = await fetch(`${API_BASE}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await response.json();

  if (!response.ok) throw new Error(data.error ?? `HTTP ${response.status}`);

  return data.token;
}

async function fetchPlaces() {
  const response = await fetch(`${API_BASE}/places`, {
    method: 'GET',
    headers: {
      'Authorization': 'Bearer ' + state.token,
    },
  });
  const data = await response.json();

  if (!response.ok) throw new Error(data.error ?? `HTTP ${response.status}`);

  return data;
}

async function authedFetch(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + state.token,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await response.json();

  if (!response.ok) throw new Error(data.error ?? `HTTP ${response.status}`);

  return data;
}

function addPlaceMarker(place) {
  const popupDiv = el('div', {
    children: [
      el('div', {
        text: place.name
      }),
    ]
  });

  if (place.description !== null) popupDiv.appendChild(el('div', {
    text: place.description
  }));

  const popup = new maplibregl.Popup().setDOMContent(popupDiv);

  new maplibregl.Marker()
    .setLngLat([place.longitude, place.latitude])
    .setPopup(popup)
    .addTo(state.map);
}

init();
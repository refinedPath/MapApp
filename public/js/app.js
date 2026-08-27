'use strict';

const API_BASE = '/api';

const state = { map: null, token: null };

function init() {
  const authView = document.getElementById('authView');
  const loginForm = document.getElementById('loginForm');
  const loginEmail = document.getElementById('loginEmail');
  const loginPassword = document.getElementById('loginPassword');
  const loginError = document.getElementById('loginError');
  const mapContainer = document.getElementById('mapContainer');

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

      if (state.map === null) {
        state.map = new maplibregl.Map({
          style: 'https://tiles.openfreemap.org/styles/liberty',
          center: [-74.0135, 40.7054],
          zoom: 12,
          container: 'mapContainer',
        });

        const places = await fetchPlaces();

        for (const place of places) {
          new maplibregl.Marker()
            .setLngLat([place.longitude, place.latitude])
            .addTo(state.map);
        }

      }
    } catch (err) {
      loginError.textContent = err.message;
      console.error(err);
    }
  });
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

init();
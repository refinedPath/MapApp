'use strict';

function init() {
  const map = new maplibregl.Map({
    style: 'https://tiles.openfreemap.org/styles/liberty',
    center: [-74.0135, 40.7054],
    zoom: 12,
    container: 'map',
  });
}

init();
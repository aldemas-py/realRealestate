/**
 * FLEXISPACE - Leaflet Map Integration
 * Interactive map showing office space locations in Westlands, Nairobi
 */

function initSpaceMap(elementId, spaces) {
  if (!document.getElementById(elementId)) return;

  // Center on Westlands, Nairobi
  var map = L.map(elementId).setView([-1.2628, 36.8119], 14);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map);

  var bounds = [];

  spaces.forEach(function (space) {
    var statusColors = {
      available: "#2ecc71",
      occupied: "#3498db",
      maintenance: "#f39c12",
      unavailable: "#e74c3c",
    };

    var color = statusColors[space.status] || "#2ecc71";

    var marker = L.circleMarker([space.lat, space.lng], {
      radius: 12,
      fillColor: color,
      color: "#ffffff",
      weight: 3,
      opacity: 1,
      fillOpacity: 0.9,
    }).addTo(map);

    var currency = space.currency || "KES";
    var priceHTML =
      '<div class="map-popup">' +
      "<h4>" +
      space.name +
      "</h4>" +
      '<p class="map-type">' +
      space.space_type
        .replace(/_/g, " ")
        .replace(/\b\w/g, (l) => l.toUpperCase()) +
      "</p>" +
      '<p class="map-price">' +
      currency +
      " " +
      Number(space.price_per_month).toLocaleString() +
      "/mo</p>" +
      '<p class="map-capacity">&#128101; Up to ' +
      space.capacity +
      " people</p>" +
      '<a href="' +
      space.url +
      '" class="map-link">View Details &rarr;</a>' +
      "</div>";

    marker.bindPopup(priceHTML);
    marker.on("mouseover", function () {
      this.openPopup();
    });
    marker.on("mouseout", function () {
      this.closePopup();
    });

    bounds.push([space.lat, space.lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [50, 50] });
  }
}

// Single space map
function initSingleSpaceMap(elementId, space) {
  if (!document.getElementById(elementId)) return;

  var map = L.map(elementId).setView([space.lat, space.lng], 16);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  var marker = L.marker([space.lat, space.lng]).addTo(map);
  marker
    .bindPopup("<strong>" + space.name + "</strong><br>" + space.address)
    .openPopup();
}

import { Controller } from '@hotwired/stimulus';

import L from 'leaflet';

import 'leaflet/dist/leaflet.min.css';

const ARC_POINTS = 48;
const MAX_ARC_LIFT_DEGREES = 6;

export default class extends Controller {
    static values = {
        flows: Array,
        destination: Object,
        labels: Object,
        tileKey: String,
    }

    connect() {
        const destination = this.destinationValue;

        if (!destination || typeof destination.lat !== 'number') {
            return;
        }

        this.map = L.map(this.element, {
            preferCanvas: true,
            scrollWheelZoom: false,
            attributionControl: true,
        });

        L.tileLayer(this.tileUrl(), {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19,
        }).addTo(this.map);

        const flows = this.flowsValue.filter((flow) => typeof flow.lat === 'number' && typeof flow.lng === 'number');
        const maxTravellers = flows.reduce((max, flow) => Math.max(max, flow.travellers), 1);
        const points = [[destination.lat, destination.lng]];

        flows.forEach((flow) => {
            const weight = Math.sqrt(flow.travellers / maxTravellers);

            L.polyline(this.arc(flow, destination), {
                color: this.color('--bs-primary', '#3b76ef'),
                weight: 1 + 4 * weight,
                opacity: 0.35 + 0.45 * weight,
            }).addTo(this.map);

            L.circleMarker([flow.lat, flow.lng], {
                radius: 4 + 10 * weight,
                color: this.color('--bs-primary', '#3b76ef'),
                fillColor: this.color('--bs-primary', '#3b76ef'),
                fillOpacity: 0.5,
                weight: 1,
            }).bindTooltip(this.tooltip(flow), { direction: 'top' }).addTo(this.map);

            points.push([flow.lat, flow.lng]);
        });

        L.circleMarker([destination.lat, destination.lng], {
            radius: 8,
            color: this.color('--bs-danger', '#d5343f'),
            fillColor: this.color('--bs-danger', '#d5343f'),
            fillOpacity: 1,
            weight: 2,
        }).bindTooltip(destination.label, { direction: 'top' }).addTo(this.map);

        this.map.fitBounds(L.latLngBounds(points), { padding: [24, 24], maxZoom: 12 });
        this.map.once('focus', () => this.map.scrollWheelZoom.enable());
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }

    tooltip(flow) {
        const labels = this.labelsValue;
        const rows = [
            `<strong>${this.escape(flow.label)}</strong>`,
            `${labels.travellers}: ${flow.travellers}`,
            `${labels.responses}: ${flow.responses}`,
        ];

        if (flow.km > 0) {
            rows.push(`${labels.distance}: ${flow.km} km`);
        }

        rows.push(`${labels.emissions}: ${flow.co2eq} kg CO₂eq`);

        return rows.join('<br>');
    }

    arc(origin, destination) {
        const from = this.toCartesian(origin.lat, origin.lng);
        const to = this.toCartesian(destination.lat, destination.lng);
        const angle = Math.acos(Math.min(1, Math.max(-1, from.x * to.x + from.y * to.y + from.z * to.z)));

        if (angle === 0) {
            return [[origin.lat, origin.lng], [destination.lat, destination.lng]];
        }

        const points = [];
        const sinAngle = Math.sin(angle);
        const lift = Math.min(MAX_ARC_LIFT_DEGREES, angle * 180 / Math.PI * 0.12);

        for (let step = 0; step <= ARC_POINTS; step++) {
            const fraction = step / ARC_POINTS;
            const a = Math.sin((1 - fraction) * angle) / sinAngle;
            const b = Math.sin(fraction * angle) / sinAngle;
            const x = a * from.x + b * to.x;
            const y = a * from.y + b * to.y;
            const z = a * from.z + b * to.z;
            const latitude = Math.atan2(z, Math.sqrt(x * x + y * y)) * 180 / Math.PI;
            const longitude = Math.atan2(y, x) * 180 / Math.PI;
            const bend = Math.sin(fraction * Math.PI) * lift;

            points.push([latitude + bend, longitude]);
        }

        return points;
    }

    toCartesian(latitude, longitude) {
        const lat = latitude * Math.PI / 180;
        const lng = longitude * Math.PI / 180;

        return {
            x: Math.cos(lat) * Math.cos(lng),
            y: Math.cos(lat) * Math.sin(lng),
            z: Math.sin(lat),
        };
    }

    tileUrl() {
        const style = document.documentElement.dataset.bsTheme === 'dark' ? 'dark_all' : 'light_all';

        const url = `https://{s}.basemaps.cartocdn.com/${style}/{z}/{x}/{y}{r}.png`;

        return this.tileKeyValue.length > 0 ? `${url}?key=${encodeURIComponent(this.tileKeyValue)}` : url;
    }

    color(variable, fallback) {
        const value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();

        return value.length > 0 ? value : fallback;
    }

    escape(value) {
        const element = document.createElement('span');
        element.textContent = value;

        return element.innerHTML;
    }
}

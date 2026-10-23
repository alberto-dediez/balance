import { Controller } from '@hotwired/stimulus';

import L from 'leaflet';

import 'leaflet/dist/leaflet.min.css';

const ARC_POINTS = 48;
const MAX_ARC_LIFT_DEGREES = 6;
const ARROW_LENGTH_PIXELS = 12;
const ARROW_ANGLE_RADIANS = 25 * Math.PI / 180;
const ARROW_MARKER_OFFSET_PIXELS = 10;
const ARROW_HEADING_ARC_POINTS = 4;

export default class extends Controller {
    static values = {
        flows: Array,
        destination: Object,
        labels: Object,
        tileKey: String,
        arrows: { type: Boolean, default: false },
    }

    connect() {
        const globalDestination = this.isPoint(this.destinationValue) ? this.destinationValue : null;
        const flows = this.flowsValue
            .filter((flow) => this.isPoint(flow))
            .map((flow) => ({ flow, destination: this.flowDestination(flow, globalDestination) }))
            .filter((item) => item.destination !== null);

        if (!globalDestination && flows.length === 0) {
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

        const maxTravellers = flows.reduce((max, item) => Math.max(max, item.flow.travellers), 1);
        const destinations = new Map();
        const points = [];
        const color = this.color('--bs-primary', '#3b76ef');

        if (globalDestination) {
            destinations.set(this.pointKey(globalDestination), globalDestination);
        }

        this.arrowHeads = [];

        flows.forEach(({ flow, destination }) => {
            const weight = Math.sqrt(flow.travellers / maxTravellers);
            const arc = this.arc(flow, destination);
            const style = {
                color,
                weight: 1 + 4 * weight,
                opacity: 0.35 + 0.45 * weight,
            };

            L.polyline(arc, style).addTo(this.map);

            if (this.arrowsValue) {
                this.arrowHeads.push({ arc, layer: L.polyline([], style).addTo(this.map) });
            }

            L.circleMarker([flow.lat, flow.lng], {
                radius: 4 + 10 * weight,
                color,
                fillColor: color,
                fillOpacity: 0.5,
                weight: 1,
            }).bindTooltip(this.tooltip(flow), { direction: 'top' }).addTo(this.map);

            destinations.set(this.pointKey(destination), destination);
            points.push([flow.lat, flow.lng]);
        });

        destinations.forEach((destination) => {
            L.circleMarker([destination.lat, destination.lng], {
                radius: 8,
                color: this.color('--bs-danger', '#d5343f'),
                fillColor: this.color('--bs-danger', '#d5343f'),
                fillOpacity: 1,
                weight: 2,
            }).bindTooltip(this.escape(destination.label ?? ''), { direction: 'top' }).addTo(this.map);

            points.unshift([destination.lat, destination.lng]);
        });

        this.map.fitBounds(L.latLngBounds(points), { padding: [24, 24], maxZoom: 12 });

        if (this.arrowsValue) {
            this.refreshArrowHeads();
            this.map.on('zoomend', () => this.refreshArrowHeads());
        }

        this.map.once('focus', () => this.map.scrollWheelZoom.enable());
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }

    isPoint(point) {
        return !!point && typeof point.lat === 'number' && typeof point.lng === 'number';
    }

    flowDestination(flow, globalDestination) {
        if (typeof flow.destLat === 'number' && typeof flow.destLng === 'number') {
            return { lat: flow.destLat, lng: flow.destLng, label: flow.destLabel ?? '' };
        }

        return globalDestination;
    }

    pointKey(point) {
        return `${point.lat.toFixed(4)},${point.lng.toFixed(4)}`;
    }

    refreshArrowHeads() {
        this.arrowHeads.forEach(({ arc, layer }) => layer.setLatLngs(this.arrowHead(arc)));
    }

    arrowHead(arc) {
        const zoom = this.map.getZoom();
        const end = this.map.project(L.latLng(arc[arc.length - 1]), zoom);
        const previous = this.map.project(L.latLng(arc[Math.max(0, arc.length - 1 - ARROW_HEADING_ARC_POINTS)]), zoom);

        if (end.distanceTo(previous) < 1) {
            return [];
        }

        const heading = Math.atan2(end.y - previous.y, end.x - previous.x);
        const tip = L.point(
            end.x - ARROW_MARKER_OFFSET_PIXELS * Math.cos(heading),
            end.y - ARROW_MARKER_OFFSET_PIXELS * Math.sin(heading),
        );
        const side = (offset) => this.map.unproject(L.point(
            tip.x - ARROW_LENGTH_PIXELS * Math.cos(heading + offset),
            tip.y - ARROW_LENGTH_PIXELS * Math.sin(heading + offset),
        ), zoom);

        return [side(ARROW_ANGLE_RADIANS), this.map.unproject(tip, zoom), side(-ARROW_ANGLE_RADIANS)];
    }

    tooltip(flow) {
        const labels = this.labelsValue;
        const title = flow.destLabel
            ? `${this.escape(flow.label)} → ${this.escape(flow.destLabel)}`
            : this.escape(flow.label);
        const rows = [
            `<strong>${title}</strong>`,
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

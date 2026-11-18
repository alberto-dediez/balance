const RECONNECT_DELAY = 30000;

const handlers = new Map();
let source = null;
let reconnect = null;

function hubUrl() {
    const meta = document.querySelector('meta[name="live-hub"]');

    return meta ? meta.content : '';
}

function dispatch(key, data) {
    const callbacks = handlers.get(key);

    if (callbacks) {
        Array.from(callbacks).forEach((callback) => callback(data));
    }
}

function open() {
    const url = hubUrl();

    if (source || !url || handlers.size === 0 || document.visibilityState === 'hidden') {
        return;
    }

    source = new EventSource(url, { withCredentials: true });

    source.onopen = () => {
        Array.from(handlers.keys()).forEach((key) => dispatch(key, { resync: true }));
    };

    source.onmessage = (event) => {
        let data;

        try {
            data = JSON.parse(event.data);
        } catch (error) {
            return;
        }

        if (data && typeof data.key === 'string') {
            dispatch(data.key, data);
        }
    };

    source.onerror = () => {
        if (source && source.readyState === EventSource.CLOSED) {
            close();
            reconnect = setTimeout(open, RECONNECT_DELAY);
        }
    };
}

function close() {
    clearTimeout(reconnect);
    reconnect = null;

    if (source) {
        source.close();
        source = null;
    }
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        close();

        return;
    }

    open();
});

export function subscribe(key, callback) {
    if (!hubUrl()) {
        return () => {};
    }

    if (!handlers.has(key)) {
        handlers.set(key, new Set());
    }

    handlers.get(key).add(callback);
    open();

    return () => {
        const callbacks = handlers.get(key);

        if (!callbacks) {
            return;
        }

        callbacks.delete(callback);

        if (callbacks.size === 0) {
            handlers.delete(key);
        }

        if (handlers.size === 0) {
            close();
        }
    };
}

export function isAvailable() {
    return hubUrl() !== '';
}

export function isLive() {
    return source !== null && source.readyState === EventSource.OPEN;
}

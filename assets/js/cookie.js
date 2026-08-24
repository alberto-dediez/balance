export function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

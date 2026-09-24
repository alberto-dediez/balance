const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

if (timezone) {
  document.cookie = `timezone=${encodeURIComponent(timezone)}; path=/; max-age=31536000; SameSite=Lax`;
}

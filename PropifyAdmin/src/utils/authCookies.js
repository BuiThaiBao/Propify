export const ACCESS_TOKEN_COOKIE =
  import.meta.env.VITE_ADMIN_AUTH_ACCESS_COOKIE || "propify_admin_access_token";

export function getAccessToken() {
  const prefix = `${ACCESS_TOKEN_COOKIE}=`;
  const cookie = document.cookie
    .split("; ")
    .find((item) => item.startsWith(prefix));

  if (!cookie) return null;
  return decodeURIComponent(cookie.slice(prefix.length));
}

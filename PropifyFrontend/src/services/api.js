import axios from "axios";

// 1) Tạo instance với base URL từ .env
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// 2) Request interceptor — tự gắn token vào mọi request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("access_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// 3) Response interceptor — xử lý lỗi 401 (token hết hạn)
api.interceptors.response.use(
  // Nếu response OK → trả về bình thường
  (response) => response,

  // Nếu lỗi
  async (error) => {
    const originalRequest = error.config;

    // Bỏ qua nếu chính request refresh bị lỗi 401 (ngăn vòng lặp vô hạn)
    if (originalRequest.url === "/v1/auth/refresh" || originalRequest.url.includes("/refresh")) {
      localStorage.removeItem("access_token");
      return Promise.reject(error);
    }

    // Nếu 401 và chưa thử refresh → thử refresh token
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        // Gọi refresh bằng token cũ
        const res = await api.post("/v1/auth/refresh");
        const newToken = res.data.data.access_token;

        // Lưu token mới
        localStorage.setItem("access_token", newToken);

        // Retry request cũ với token mới
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        // Refresh cũng fail → xóa token, user tự login lại
        localStorage.removeItem("access_token");
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  },
);

export default api;

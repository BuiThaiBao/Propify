import api from "./api";

/**
 * Cloudinary Service — Option B (Signed Upload)
 *
 * Flow:
 *   1. Lấy signature từ backend (POST /v1/cloudinary/sign?type=avatar|listing)
 *   2. Upload file trực tiếp lên Cloudinary bằng signature đó
 *   3. Trả về secure_url để lưu vào DB
 */
const CLOUDINARY_BASE = "https://api.cloudinary.com/v1_1";

export const CLOUDINARY_RESOURCE_TYPES = {
  IMAGE: "image",
  VIDEO: "video",
  AUTO: "auto",
};

const cloudinaryService = {
  /**
   * Bước 1: Lấy signed signature từ backend.
   * @param {'avatar'|'listing'} uploadType
   */
  async getSignature(uploadType = "avatar") {
    const res = await api.post(`/v1/cloudinary/sign?type=${uploadType}`);
    return res.data.data; // { signature, api_key, cloud_name, timestamp, folder, upload_preset }
  },

  /**
   * Upload file lên Cloudinary bằng signature.
   * @param {File} file - File cần upload
   * @param {'avatar'|'listing'} uploadType
   * @param {object} [options]
   * @param {'image'|'video'|'auto'} [options.resourceType='image'] - Cloudinary resource type
   * @param {Function} [options.onProgress] - Callback nhận % tiến trình (0-100)
   * @param {number} [options.timeout=120000] - Timeout ms, mặc định 120s (video cần lâu hơn)
   * @returns {Promise<{ secure_url: string, public_id: string }>}
   */
  async uploadFile(file, uploadType = "avatar", options = {}) {
    const { resourceType = "image", onProgress = null, timeout = 120000 } = options;
    const sigData = await this.getSignature(uploadType);

    const formData = new FormData();
    formData.append("file", file);
    formData.append("api_key", sigData.api_key);
    formData.append("timestamp", sigData.timestamp);
    formData.append("signature", sigData.signature);
    formData.append("folder", sigData.folder);

    const cloudinaryUrl = `${CLOUDINARY_BASE}/${sigData.cloud_name}/${resourceType}/upload`;

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", cloudinaryUrl, true);
      xhr.timeout = timeout;

      if (onProgress && typeof onProgress === "function") {
        xhr.upload.addEventListener("progress", (e) => {
          if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            onProgress(percent);
          }
        });
      }

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve(JSON.parse(xhr.responseText));
        } else {
          reject(new Error(`Cloudinary upload failed [${xhr.status}]: ${xhr.responseText}`));
        }
      };

      xhr.onerror = () => reject(new Error("Network error during Cloudinary upload"));
      xhr.ontimeout = () => reject(new Error("Upload quá lâu. Vui lòng kiểm tra mạng và thử lại."));
      xhr.send(formData);
    });
  },

  /**
   * Upload ảnh (giữ nguyên API cũ cho tương thích).
   * @param {File} file
   * @param {'avatar'|'listing'} uploadType
   * @param {Function|null} onProgress
   * @returns {Promise<{ secure_url: string, public_id: string }>}
   */
  async uploadImage(file, uploadType = "avatar", onProgress = null) {
    return this.uploadFile(file, uploadType, { resourceType: "image", onProgress });
  },

  /**
   * Upload video.
   * @param {File} file
   * @param {'avatar'|'listing'} uploadType
   * @param {object} [options]
   * @param {Function} [options.onProgress]
   * @param {number} [options.timeout=300000] - Video cần timeout dài hơn (5 phút)
   * @returns {Promise<{ secure_url: string, public_id: string }>}
   */
  async uploadVideo(file, uploadType = "listing", options = {}) {
    return this.uploadFile(file, uploadType, {
      resourceType: "video",
      onProgress: options.onProgress,
      timeout: options.timeout || 300000,
    });
  },
};

export default cloudinaryService;

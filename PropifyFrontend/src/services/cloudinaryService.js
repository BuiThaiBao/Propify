import api from "./api";

/**
 * Cloudinary Service — Option B (Signed Upload)
 *
 * Flow:
 *   1. Lấy signature từ backend (POST /v1/cloudinary/sign?type=avatar|listing)
 *   2. Upload file trực tiếp lên Cloudinary bằng signature đó
 *   3. Trả về secure_url để lưu vào DB
 */
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
   * Bước 2: Upload file lên Cloudinary bằng signature.
   * @param {File} file - File ảnh cần upload
   * @param {'avatar'|'listing'} uploadType
   * @param {Function} [onProgress] - Callback nhận % tiến trình (0-100)
   * @returns {Promise<{ secure_url: string, public_id: string }>}
   */
  async uploadImage(file, uploadType = "avatar", onProgress = null) {
    // Lấy signature từ backend
    const sigData = await this.getSignature(uploadType);

    const formData = new FormData();
    formData.append("file", file);
    formData.append("api_key", sigData.api_key);
    formData.append("timestamp", sigData.timestamp);
    formData.append("signature", sigData.signature);
    formData.append("folder", sigData.folder);
    // Không cần upload_preset cho signed upload

    const cloudinaryUrl = `https://api.cloudinary.com/v1_1/${sigData.cloud_name}/image/upload`;

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", cloudinaryUrl, true);

      // Theo dõi tiến trình upload
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
      xhr.send(formData);
    });
  },
};

export default cloudinaryService;

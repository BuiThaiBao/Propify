import api from './api';

/**
 * Upload file lên R2 thông qua backend.
 * Flow: POST /v1/chat/upload (multipart) → { public_url }
 */
const chatUploadService = {
  /**
   * Upload file lên R2.
   * @param {File} file
   * @param {'image'|'file'} type
   * @param {Function} [onProgress] - callback 0-100
   * @returns {Promise<{ public_url: string, file_key: string, file_name: string, file_size: number, mime_type: string }>}
   */
  async upload(file, type = 'file', onProgress = null) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    const res = await api.post('/v1/chat/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress
        ? (e) => {
            if (e.lengthComputable) {
              onProgress(Math.round((e.loaded / e.total) * 100));
            }
          }
        : undefined,
    });

    return res.data.data;
  },
};

export default chatUploadService;

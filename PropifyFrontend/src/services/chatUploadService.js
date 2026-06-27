import api from './api';

/**
 * Upload file trực tiếp lên R2 qua Presigned URL.
 * Flow:
 *   1. BE: POST /v1/chat/presign → { presigned_url, file_key }
 *   2. FE: PUT file thẳng lên presigned_url (R2)
 *   3. FE: gửi message với file_key
 *   4. BE: GET /v1/chat/file-url?file_key= → { public_url }
 */
const chatUploadService = {
  /**
   * Upload file lên R2 (FE đẩy thẳng, không qua BE).
   */
  async upload(file, type = 'file', onProgress = null) {
    const ext = file.name.split('.').pop() || 'bin';

    // 1. Lấy presigned PUT URL + file_key
    const res = await api.post('/v1/chat/presign', {
      type,
      extension: ext,
      content_type: file.type || 'application/octet-stream',
      file_name: file.name,
      file_size: file.size,
    });

    const { presigned_url, file_key } = res.data.data;

    // 2. PUT thẳng file lên R2
    await this.putFile(presigned_url, file, file.type, onProgress);

    return {
      file_key,
      file_name: file.name,
      file_size: file.size,
      mime_type: file.type || 'application/octet-stream',
    };
  },

  /**
   * Lấy public URL từ file_key (gọi khi render message).
   */
  async getFileUrl(fileKey) {
    const res = await api.get('/v1/chat/file-url', {
      params: { file_key: fileKey },
    });
    return res.data.data.public_url;
  },

  putFile(url, file, contentType, onProgress) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('PUT', url, true);
      xhr.setRequestHeader('Content-Type', contentType);

      if (onProgress && typeof onProgress === 'function') {
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            onProgress(Math.round((e.loaded / e.total) * 100));
          }
        });
      }

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve();
        } else {
          reject(new Error(`Upload failed [${xhr.status}]`));
        }
      };

      xhr.onerror = () => reject(new Error('Upload network error'));
      xhr.send(file);
    });
  },
};

export default chatUploadService;

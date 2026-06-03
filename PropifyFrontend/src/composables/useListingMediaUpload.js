import cloudinaryService from "@/services/cloudinaryService";

export const MAX_LISTING_VIDEO_SIZE_BYTES = 100 * 1024 * 1024;
export const MAX_LISTING_VIDEO_SIZE_LABEL = "100MB";

export function useListingMediaUpload({ onStatus } = {}) {
  function notify(message) {
    if (typeof onStatus === "function") {
      onStatus(message);
    }
  }

  async function uploadSingle(file, mode = "image", { silent = false } = {}) {
    if (!file) return null;
    if (typeof file === "string") return file;

    if (mode === "video" && file.size > MAX_LISTING_VIDEO_SIZE_BYTES) {
      throw new Error(`Dung lượng video vượt quá ${MAX_LISTING_VIDEO_SIZE_LABEL}. Vui lòng chọn video nhỏ hơn.`);
    }

    if (!silent) {
      notify(mode === "video" ? "Đang tải lên video..." : "Đang tải lên hình ảnh...");
    }

    const res = await cloudinaryService.uploadImage(file, "listing");
    return res.secure_url;
  }

  async function uploadMultiple(files, mode = "image") {
    const uploadableFiles = Array.from(files || []).filter(Boolean);
    if (!uploadableFiles.length) return [];

    notify(
      mode === "video"
        ? "Đang tải lên video..."
        : `Đang tải lên ${uploadableFiles.length} hình ảnh...`,
    );

    const urls = await Promise.all(
      uploadableFiles.map((file) => uploadSingle(file, mode, { silent: true })),
    );

    return urls.filter(Boolean);
  }

  async function uploadListingMediaPayload(payload) {
    const [images, video] = await Promise.all([
      uploadMultiple(payload.images, "image"),
      uploadSingle(payload.video, "video"),
    ]);

    payload.images = images;
    payload.video = video;

    if (payload.requestVerification) {
      const [identityCardFront, identityCardBack, legalDocuments] = await Promise.all([
        uploadSingle(payload.identityCardFront, "image"),
        uploadSingle(payload.identityCardBack, "image"),
        uploadMultiple(payload.legalDocuments, "image"),
      ]);

      payload.identityCardFront = identityCardFront;
      payload.identityCardBack = identityCardBack;
      payload.legalDocuments = legalDocuments;
    }

    return payload;
  }

  async function uploadDraftMediaPayload(payload) {
    const [images, video, identityCardFront, identityCardBack, legalDocuments] = await Promise.all([
      uploadMultiple(payload.images, "image"),
      uploadSingle(payload.video, "video"),
      uploadSingle(payload.identityCardFront, "image"),
      uploadSingle(payload.identityCardBack, "image"),
      uploadMultiple(payload.legalDocuments, "image"),
    ]);

    payload.images = images;
    payload.video = video;
    payload.identityCardFront = identityCardFront;
    payload.identityCardBack = identityCardBack;
    payload.legalDocuments = legalDocuments;

    return payload;
  }

  async function uploadVerificationPayload(payload) {
    const [identityCardFront, identityCardBack, legalDocuments] = await Promise.all([
      uploadSingle(payload.identityCardFront, "image"),
      uploadSingle(payload.identityCardBack, "image"),
      uploadMultiple(payload.legalDocuments, "image"),
    ]);

    return {
      identityCardFront,
      identityCardBack,
      legalDocuments,
      publicInfoAgreed: payload.publicInfoAgreed,
    };
  }

  return {
    uploadSingle,
    uploadMultiple,
    uploadListingMediaPayload,
    uploadDraftMediaPayload,
    uploadVerificationPayload,
  };
}

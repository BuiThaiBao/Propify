import cloudinaryService from "@/services/cloudinaryService";

export const MAX_LISTING_VIDEO_SIZE_BYTES = 100 * 1024 * 1024;
export const MAX_LISTING_VIDEO_SIZE_LABEL = "100MB";

export function useListingMediaUpload({ onStatus } = {}) {
  function notify(message) {
    if (typeof onStatus === "function") {
      onStatus(message);
    }
  }

  async function uploadSingle(file, mode = "image") {
    if (!file) return null;
    if (typeof file === "string") return file;

    if (mode === "video" && file.size > MAX_LISTING_VIDEO_SIZE_BYTES) {
      throw new Error(`Dung lượng video vượt quá ${MAX_LISTING_VIDEO_SIZE_LABEL}. Vui lòng chọn video nhỏ hơn.`);
    }

    notify(mode === "video" ? "Đang tải lên video..." : "Đang tải lên hình ảnh...");
    const res = await cloudinaryService.uploadImage(file, "listing");
    return res.secure_url;
  }

  async function uploadMultiple(files, mode = "image") {
    const urls = [];

    for (const file of files || []) {
      const url = await uploadSingle(file, mode);
      if (url) {
        urls.push(url);
      }
    }

    return urls;
  }

  async function uploadListingMediaPayload(payload) {
    payload.images = await uploadMultiple(payload.images, "image");
    payload.video = await uploadSingle(payload.video, "video");

    if (payload.requestVerification) {
      payload.identityCardFront = await uploadSingle(payload.identityCardFront, "image");
      payload.identityCardBack = await uploadSingle(payload.identityCardBack, "image");
      payload.legalDocuments = await uploadMultiple(payload.legalDocuments, "image");
    }

    return payload;
  }

  async function uploadDraftMediaPayload(payload) {
    payload.images = await uploadMultiple(payload.images, "image");
    payload.video = await uploadSingle(payload.video, "video");
    payload.identityCardFront = await uploadSingle(payload.identityCardFront, "image");
    payload.identityCardBack = await uploadSingle(payload.identityCardBack, "image");
    payload.legalDocuments = await uploadMultiple(payload.legalDocuments, "image");

    return payload;
  }

  async function uploadVerificationPayload(payload) {
    return {
      identityCardFront: await uploadSingle(payload.identityCardFront, "image"),
      identityCardBack: await uploadSingle(payload.identityCardBack, "image"),
      legalDocuments: await uploadMultiple(payload.legalDocuments, "image"),
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

import api from "./api";

const packageService = {
  /**
   * Lấy danh sách gói tin đang active.
   */
  getPackages() {
    return api.get("/v1/packages");
  },

  /**
   * Lấy chi tiết gói tin theo ID.
   */
  getPackageById(id) {
    return api.get(`/v1/packages/${id}`);
  },
};

export default packageService;

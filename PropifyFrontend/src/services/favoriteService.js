import api from "./api";

const favoriteService = {
  getFavorites() {
    return api.get("/v1/favorites");
  },

  getFavoriteIds() {
    return api.get("/v1/favorites/ids");
  },

  toggle(listingId) {
    return api.post(`/v1/favorites/${listingId}`);
  },
};

export default favoriteService;

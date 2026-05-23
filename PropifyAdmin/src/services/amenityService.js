import api from './api'

export const amenityService = {
  getAmenities() {
    return api.get('/v1/amenities')
  },

  createAmenity(data) {
    return api.post('/v1/amenities', data)
  },

  updateAmenity(id, data) {
    return api.put(`/v1/amenities/${id}`, data)
  },

  getListingAmenities(listingId) {
    return api.get(`/v1/listings/${listingId}/amenities`)
  },

  updateListingAmenities(listingId, amenities) {
    return api.put(`/v1/listings/${listingId}/amenities`, { amenities })
  },
}

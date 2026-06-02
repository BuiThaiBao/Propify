export const listingKeys = {
  all: ['listings'],
  public: () => [...listingKeys.all, 'public'],
  publicList: (params = {}) => [
    ...listingKeys.public(),
    {
      demand_type: params.demand_type,
      keyword: params.keyword || '',
      page: Number(params.page || 1),
      per_page: Number(params.per_page || 10),
      poster_type: params.poster_type,
      min_price: params.min_price,
      max_price: params.max_price,
      min_area: params.min_area,
      max_area: params.max_area,
      sort: params.sort || '',
    },
  ],
  mapList: (params = {}) => [...listingKeys.all, 'map', params],
};

export const packageKeys = {
  all: ['packages'],
  list: () => [...packageKeys.all, 'list'],
  detail: (id) => [...packageKeys.all, 'detail', Number(id)],
};

export const favoriteKeys = {
  all: ['favorites'],
  ids: () => [...favoriteKeys.all, 'ids'],
  list: () => [...favoriteKeys.all, 'list'],
};

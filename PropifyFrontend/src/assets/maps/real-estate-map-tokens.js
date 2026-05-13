export const realEstateMapTheme = {
  sourceId: "maptiler",
  sourceUrlTemplate: "https://api.maptiler.com/tiles/v3/tiles.json?key={MAPTILER_KEY}",
  glyphsUrlTemplate: "https://api.maptiler.com/fonts/{fontstack}/{range}.pbf?key={MAPTILER_KEY}",
  spriteUrlTemplate: "https://api.maptiler.com/maps/streets-v2/sprite",
  lightStylePath: "/src/assets/maps/real-estate-light.json",
  darkStylePath: "/src/assets/maps/real-estate-dark.json",
  colors: {
    property: "#0ea5e9",
    propertySelected: "#0284c7",
    walkingRadius: "#38bdf8",
    land: "#f6f4ef",
    water: "#b7dfff",
    park: "#d6ead0",
    building: "#ddd8d0",
    school: "#2563eb",
    hospital: "#ef6f6c",
    shopping: "#f59e0b",
    food: "#d97706",
    transport: "#8b5cf6",
    lifestyle: "#22c55e"
  },
  poiClasses: {
    education: ["school", "university", "college", "kindergarten"],
    healthcare: ["hospital", "clinic", "pharmacy", "doctors", "dentist"],
    shopping: ["supermarket", "mall", "convenience", "marketplace", "department_store"],
    food: ["restaurant", "cafe"],
    transport: ["bus_stop", "subway", "railway_station", "train_station", "airport"],
    lifestyle: ["gym", "fitness_centre", "park", "playground", "cinema"]
  },
  recommendedMapLibreOptions: {
    antialias: true,
    pitchWithRotate: true,
    dragRotate: true,
    touchPitch: true,
    cooperativeGestures: true,
    maxPitch: 55,
    minZoom: 5,
    maxZoom: 19
  }
};

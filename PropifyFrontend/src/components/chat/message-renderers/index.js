import FileRenderer from './FileRenderer.vue';
import ImageRenderer from './ImageRenderer.vue';
import SystemRenderer from './SystemRenderer.vue';
import TextRenderer from './TextRenderer.vue';

const renderers = {
  text: TextRenderer,
  image: ImageRenderer,
  file: FileRenderer,
  system: SystemRenderer,
};

export function getMessageRenderer(type) {
  return renderers[type] ?? TextRenderer;
}

export function registerRenderer(type, component) {
  renderers[type] = component;
}

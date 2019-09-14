import { configure } from '@storybook/vue';
import "../../module_v4skin/admin/skins/kajona_v4/less/styles.less"

function requireAll(requireContext) {
  return requireContext.keys().map(requireContext);
}

function loadStories() {
  requireAll(require.context("../../", true, /story\.ts?$/));
}

configure(loadStories, module);

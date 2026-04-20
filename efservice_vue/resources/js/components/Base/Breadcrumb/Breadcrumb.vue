<script setup lang="ts">
import { type HTMLAttributes, useSlots, provide, Fragment, type VNode } from "vue";

export type ProvideBeradcrumb = {
  light?: boolean;
};

export interface BreadcrumbProps extends /* @vue-ignore */ HTMLAttributes {
  light?: boolean;
}

const slots = useSlots();

const { light } = defineProps<BreadcrumbProps>();

provide<ProvideBeradcrumb>("breadcrumb", {
  light: light,
});

function flattenVNodes(vnodes: VNode[]): VNode[] {
  return vnodes.flatMap((vnode) => {
    if (vnode.type === Fragment) {
      return flattenVNodes((vnode.children as VNode[]) || []);
    }
    return [vnode];
  });
}
</script>

<template>
  <nav class="flex" aria-label="breadcrumb">
    <ol
      :class="[
        'flex items-center text-primary dark:text-slate-300',
        { 'text-white/90': light },
      ]"
    >
      <component
        v-for="(item, key) in flattenVNodes(slots.default ? slots.default() : [])"
        :is="item"
        :index="key"
      />
    </ol>
  </nav>
</template>

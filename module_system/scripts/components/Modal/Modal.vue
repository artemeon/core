<template>
  <div
    class="modal fade in"
    :id="modalId"
    tabindex="-1"
    role="dialog"
    data-keyboard="true"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">×</button>
          <h3 class="modal-title">{{title || "&nbsp;"}}</h3>
        </div>
        <slot></slot>
      </div>
    </div>
  </div>
</template>
<script lang="ts" src="./Modal.ts">
</script>
<style lang="less" scoped src="./Modal.less">
</style>

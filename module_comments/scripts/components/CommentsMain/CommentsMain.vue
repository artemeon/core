<template>
  <div :class="heightClass">
    <CommentsBox
      v-if="boxId===openedComment"
      :id="boxId"
      :systemId="systemId"
      :comments="comments"
      @close="closeCommentBox"
      @send="handleSend"
      :isOpen="boxId===openedComment"
    />
  </div>
</template>
<script lang="ts" src="./CommentsMain.ts"></script>

<template>
  <div v-if="show" class="core-component-commentsBox">
    <textarea name="comment" value class="form-control mandatoryFormElement textBox" />
    <a
      class="btn savechanges core-component-formentry-submit textBox"
      name="submitbtn"
      value="add"
      v-on:click.prevent="sendComment"
    >
      <span class="btn-text">add comment</span>
    </a>
    <a
      class="btn savechanges core-component-formentry-submit textBox"
      name="submitbtn"
      value="add"
      v-on:click.prevent="cancel"
    >
      <span class="btn-text">cancel</span>
    </a>
  </div>
</template>
<script lang="ts" src="./CommentsBox.ts"></script>
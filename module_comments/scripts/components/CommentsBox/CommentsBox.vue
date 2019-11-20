<template>
  <div class="core-component-commentsBox">
    {{id}}
    <div @click="close" class="close-commentsBox">
      <i class="fa fa-times" aria-hidden="true"></i>
    </div>
    <div v-if="comments && comments.length>0">
      <div v-for="(comment) in comments" :key="comment.id" class="commentsShow">
        {{comment.author}}
        <br />
        <span>@{{comment.assignee}}</span>
        {{comment.text}}
      </div>
    </div>
    <div class="write-comment">
      <textarea
        name="comment"
        placeholder="Write a comment"
        class="form-control mandatoryFormElement textBox"
        v-model="message"
        @keypress="hasAt($event)"
      />
      <button class="btn sendComment" name="submitbtn" value="add" v-on:click.prevent="sendComment">
        <span class="btn-text">add comment</span>
      </button>
    </div>

    <!--
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
    </a>-->
  </div>
</template>
<script lang="ts" src="./CommentsBox.ts"></script>
<template>
  <div class="form-group has-feedback">
    <label :for="inputId" class="col-sm-3 control-label">{{label}}</label>
    <div class="col-sm-6">
      <input type="text" :id="inputId" class="form-control" v-model="input">

      <span class="form-control-feedback loading-feedback">
          <i class="fa fa-keyboard-o" :id="inputIconId"></i>
      </span>
      <div :id="listId"></div>
    </div>
    <div class="col-sm-2 form-opener">
      <span class="listButton" :id="actionBtnId">
        <a @click="deleteInput">
          <i class="kj-icon fa fa-trash-o"></i>
        </a>
      </span>
    </div>
  </div>
</template>
<script lang="ts" src="./Autocomplete.ts">
</script>

<template>
  <div class="form-group core-component-formentry-datesingle">
    <label :for="id" class="col-sm-3 control-label">{{label}}</label>
    <div class="col-sm-6">
      <div class="input-group">
        <div class="input-group-addon">
          <i class="fa fa-calendar-o"></i>
        </div>
        <input type="text" :id="id" class="form-control" :value="value" />
      </div>
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
<script lang="ts" src="./Datepicker.ts">
</script>

<template>
  <div class="form-group has-feedback core-component-multiselect">
    <label :for="id" class="col-sm-3 control-label">{{label}}</label>
    <div class="col-sm-6">
      <MSelect
        :id="id"
        :multiple="true"
        :options="options"
        v-model="selectedOptions"
        :searchable="true"
        :close-on-select="false"
        :show-labels="false"
      ></MSelect>
    </div>
    <div class="col-sm-2 form-opener">
      <span class="listButton" :id="actionBtnId">
        <a @click="deleteSelection">
          <i class="kj-icon fa fa-trash-o"></i>
        </a>
      </span>
    </div>
  </div>
</template>
<script lang="ts" src="./Multiselect.ts">
</script>

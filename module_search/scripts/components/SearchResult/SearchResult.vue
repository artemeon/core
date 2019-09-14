<template>
  <div class="core-component-searchresult container-fluid">
    <div
      v-for="item in searchResults"
      :key="item.systemid"
      class="searchResultContainer row"
      @click="close(item.link)"
    >
      <div class="row">
      <div class="col-sm-2">
        <small>{{$t("search.module_label")}} : {{item.module}}</small>
      </div>
      <div class="col-sm-10">
        <p>
          <span v-html="item.icon"></span>
          {{item.description}}
        </p>

        <small>{{$t("search.last_modification_date")}} {{item.lastModifiedTime}} {{$t("search.last_modification_user")}} {{item.lastModifiedBy}}</small>
      </div>
    </div>
  </div>
  </div>
</template>

<script lang="ts" src="./SearchResult">
</script>


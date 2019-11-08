<template>
  <div v-if="langFetched" class="pager core-component-pager">
    <ul class="pagination" v-if="totalPages<10">
      <li v-if="current!==1" @click="previous()">
        <a>« {{$t('commons.commons_back')}}</a>
      </li>
      <li
        v-for="(item, index) in totalPages"
        :key="index"
        v-bind:class="{active : current === index+1}"
        @click="changePage(index+1)"
      >
        <a v-bind:class="{active : current === index+1}">{{index+1}}</a>
      </li>
      <li @click="next()" v-if="current!==totalPages">
        <a>{{$t('commons.commons_continue')}} »</a>
      </li>
      <li @click="onTotalElementsClick">
        <a>
          {{$t("system.pageview_total")}}
          {{totalElements}}
        </a>
      </li>
    </ul>
    <ul v-else class="pagination">
      <li v-if="current!==1" @click="previous()">
        <a>« {{$t('commons.commons_back')}}</a>
      </li>
      <li
        v-for="page in items"
        :key="page.label"
        :class="{active : page.active === true}"
        @click="changePage(page.label)"
      >
        <a v-if="page.disable">...</a>
        <a :class="{active : page.active === true}" v-else>{{page.label}}</a>
      </li>
      <li></li>
      <li @click="next()" v-if="current!==totalPages">
        <a>{{$t('commons.commons_continue')}} »</a>
      </li>
      <li @click="onTotalElementsClick">
        <a>
          {{$t("system.pageview_total")}}
          {{totalElements}}
        </a>
      </li>
    </ul>
  </div>
</template>

<script lang="ts" src="./Pagination.ts">
</script>

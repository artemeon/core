<template>
  <div class="core-component-searchbarfilter row container-fluid">
  <form v-if="filterModules" class="form-horizontal">
    <Multiselect
      :options="moduleNames"
      :label="$t('search.search_modules')"
      @select="onModulesChange"
      :tooltip="$t('search.delete_action')"
    ></Multiselect>
    <Autocomplete
      @select="onUserSelect"
      @delete="onUserDelete"
      @input="onAutocompleteInput"
      :loading="fetchingUsers"
      :label="$t('search.search_users')"
      :data="parsedAutoCompleteData"
      :tooltip="$t('search.delete_action')"
    ></Autocomplete>
    <Datepicker
      v-on:change="onStartDateChange"
      :label="$t('search.form_search_changestartdate')"
      :format="datepickerFormat"
      :tooltip="$t('search.delete_action')"
    ></Datepicker>
    <Datepicker
      v-on:change="onEndDateChange"
      :label="$t('search.form_search_changeenddate')"
      :format="datepickerFormat"
      :tooltip="$t('search.delete_action')"
    ></Datepicker>
  </form>
  </div>
</template>
<script lang="ts" src="./SearchbarFilter.ts">
</script>

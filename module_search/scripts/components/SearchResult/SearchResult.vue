<template>
    <div class="core-component-searchresult container-fluid">
        <KeysNavigation :eventsParent="'searchbarInput'" @select="goToSelectedElement">
            <div
                    v-for="(item, index) in searchResults"
                    :key="index"
                    class="searchResultContainer row"
            >
                <div class="row result-clickable" @click="close(item.link)">
                    <div class="col-sm-2 text-center">
                        <div>
                            <div v-html="item.icon" class="result-icon"></div>
                        </div>
                    </div>
                    <div class="col-sm-10">
                        <p>{{item.description}}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2 text-center">
                        <small>{{item.module}}</small>
                    </div>
                    <div class="col-sm-10">
                        <div class="pull-left"><small>{{$t("search.last_modification_date")}} {{item.lastModifiedTime}} {{$t("search.last_modification_user")}} {{item.lastModifiedBy}}</small></div>
                        <div class="pull-right" @click="close()" v-html="item.actions"></div>
                    </div>
                </div>
            </div>
        </KeysNavigation>
    </div>
</template>

<script lang="ts" src="./SearchResult">
</script>

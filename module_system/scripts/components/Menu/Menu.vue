<template>
    <!-- MODULE NAVIGATION -->
    <div v-if="langFetched" class="row-offcanvas row-offcanvas-left">
        <div class="col-md-2 hidden-print mainnavi-container sidebar-offcanvas" id="sidebar">
            <Loader :loading="!isLoaded"></Loader>
            <div v-if="isLoaded" class="sidebar-nav" id="moduleNavigation">
                <div class="nav-header">
                    <span class='dropdown pull-left'>
                        <a href='#' data-toggle='dropdown' role='button'>
                            <i class="kj-icon fa fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu generalContextMenu" role="menu">
                            <ul>
                                <li v-for="item in aspects" :key="item.Aspect_id">
                                    <a @click="switchAspect(item.Aspect_id)">{{ item.Aspect_name }}</a>
                                </li>
                            </ul>
                        </div>
                    </span>
                    {{ $t("system.commons_product_title") }}
                </div>
                <MenuAspect v-for="item in aspects" :key="item.Aspect_id" :aspect="item"></MenuAspect>
            </div>
        </div>
    </div>
</template>

<script lang="ts" src="./Menu">
</script>

<template>
    <!-- MODULE NAVIGATION -->
    <div class="row-offcanvas row-offcanvas-left">
        <div v-if="isLoaded" class="col-md-2 hidden-print mainnavi-container sidebar-offcanvas" id="sidebar">
            <div class="sidebar-nav" id="moduleNavigation">
                <div class="nav-header">
                    <span class='dropdown pull-left'>
                        <a href='#' data-toggle='dropdown' role='button'>
                            <i class="kj-icon fa fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu generalContextMenu" role="menu">
                            <ul>
                                <li v-for="item in aspects" v-bind:key="item.Aspect_id">
                                    <a @click="switchAspect(item.onclick)">{{ item.Aspect_name }}</a>
                                </li>
                            </ul>
                        </div>
                    </span>
                    {{ version }}
                </div>
                <MenuAspect v-for="item in aspects" v-bind:key="item.Aspect_id" :aspect="item"></MenuAspect>
            </div>
        </div>
    </div>
</template>

<script lang="ts" src="./Menu">
</script>

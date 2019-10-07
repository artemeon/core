<template>
    <div class="col-md-2 hidden-print mainnavi-container sidebar-offcanvas" id="sidebar">
        <div class="sidebar-nav" id="moduleNavigation">
            <div class="nav-header">
                <span class='dropdown pull-left'>
                    <a href='#' data-toggle='dropdown' role='button'>
                        <i class="kj-icon fa fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu generalContextMenu" role="menu">
                        <ul>
                            <li v-for="(item) of aspects">
                                <a v-on:click="item.test">{{ item.Aspect_name }}</a>
                            </li>
                        </ul>
                    </div>
                </span>
                Test1
            </div>
        </div>
    </div>
</template>

<script lang="ts" src="./Menu">
</script>

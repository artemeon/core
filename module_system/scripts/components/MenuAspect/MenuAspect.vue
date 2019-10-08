<template>
        <div v-bind:data-kajona-aspectid="aspect.Aspect_id" v-bind:id="aspect.Aspect_id" class="aspect-container panel-group">
            <div class="panel panel-default panel-combined">
                <div class="panel-heading">
                    <span class="linkcontainer collapsed">
                        <a v-for="header in aspect.header" v-bind:key="header.module.systemid" data-toggle="collapse" v-bind:data-parent="'#' + aspect.Aspect_id"
                           v-bind:href="'#menu_' + header.module.systemid + aspect.Aspect_id" rel="tooltip"
                           v-bind:title="header.module.moduleName" v-bind:data-kajona-module="header.module.moduleTitle" @click="combinedActive">
                            <i v-bind:class="'fa ' + header.module.faicon"></i>
                        </a>
                        <a rel="tooltip" data-kajona-module="search" onclick="var event = new Event('agp.search.open'); document.body.dispatchEvent(event);">
                            <i class="fa fa-search"></i>
                        </a>
                    </span>
                </div>
                <div v-for="header in aspect.header" v-bind:key="header.module.systemid" v-bind:id="'menu_' + header.module.systemid + aspect.Aspect_id" class="panel-collapse collapse"
                     v-bind:data-kajona-module="header.module.moduleTitle">
                    <div class="panel-body">
                        <ul >
                            <li v-for="(action, i) in header.module.actions" v-bind:key="i + action.link" v-bind:class="[action.link == '' ? 'divider' : '']">
                                <a v-if="action.name != '' && action.link != ''" v-bind:title="action.name" v-bind:href="action.href" class="adminnavi">{{ action.name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <MenuModule v-for="item in aspect.body" v-bind:key="item.module.systemid" :module="item"></MenuModule>
        </div>
</template>

<script lang="ts" src="./MenuAspect.ts">
</script>

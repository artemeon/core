<template>
        <div :data-kajona-aspectid="aspect.Aspect_id" :id="aspect.Aspect_id" class="aspect-container panel-group">
            <div class="panel panel-default panel-combined">
                <div class="panel-heading">
                    <span class="linkcontainer collapsed">
                        <a v-for="header in aspect.header" :key="header.module.systemid" data-toggle="collapse" :data-parent="'#' + aspect.Aspect_id"
                           :href="'#menu_' + header.module.systemid + aspect.Aspect_id" rel="tooltip"
                           :title="header.module.moduleName" :data-kajona-module="header.module.moduleTitle" @click="combinedActive">
                            <i :class="'fa ' + header.module.faicon"></i>
                        </a>
                        <a rel="tooltip" data-kajona-module="search" onclick="var event = new Event('agp.search.open'); document.body.dispatchEvent(event);">
                            <i class="fa fa-search"></i>
                        </a>
                    </span>
                </div>
                <div v-for="header in aspect.header" :key="header.module.systemid" :id="'menu_' + header.module.systemid + aspect.Aspect_id" class="panel-collapse collapse"
                     :data-kajona-module="header.module.moduleTitle">
                    <div class="panel-body">
                        <ul >
                            <li v-for="(action, i) in header.module.actions" :key="i + action.link" :class="[action.link === '' ? 'divider' : '']">
                                <a v-if="action.name !== '' && action.link !== ''" :title="action.name" :href="action.href" class="adminnavi">{{ action.name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <MenuModule v-for="item in aspect.body" :key="item.module.systemid" :module="item"></MenuModule>
        </div>
</template>

<script lang="ts" src="./MenuAspect.ts">
</script>

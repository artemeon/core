<template>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" v-bind:data-parent="'#' + module.module.aspectId" v-bind:href="'#menu_' + module.module.systemid + module.module.aspectId"
               v-bind:data-kajona-module="module.module.moduleTitle" class="collapsed"
               @click="combinedInactive">
                {{ module.module.moduleName }}
            </a>
        </div>
        <div v-bind:id="'menu_' + module.module.systemid + module.module.aspectId" class="panel-collapse collapse" v-bind:data-kajona-module="module.module.moduleTitle">
            <div class="panel-body">
                <ul>
                    <li v-for="(action, i) in module.module.actions" v-bind:key="i + action.link" v-bind:class="[action.link == '' ? 'divider' : '']">
                        <a v-if="action.name != '' && action.link != ''" v-bind:title="action.name" v-bind:href="action.href" class="adminnavi">{{ action.name }}</a>
                        <div v-if="action.name == '' && action.link != ''">{{ action.link }}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts" src="./MenuModule.ts">
</script>

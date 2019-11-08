<template>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" :data-parent="'#' + module.module.aspectId" :href="'#menu_' + module.module.systemid + module.module.aspectId"
               :data-kajona-module="module.module.moduleTitle" class="collapsed"
               @click="combinedInactive">
                {{ module.module.moduleName }}
            </a>
        </div>
        <div :id="'menu_' + module.module.systemid + module.module.aspectId" class="panel-collapse collapse" :data-kajona-module="module.module.moduleTitle">
            <div class="panel-body">
                <ul>
                    <li v-for="(action, i) in module.module.actions" :key="i + action.name" :class="[action.name === '' ? 'divider' : '']">
                        <a v-if="action.name !== '' && action.link !== ''" :title="action.name" :href="action.href" class="adminnavi">{{ action.name }}</a>
                        <div v-if="action.name !== '' && action.link === ''">{{ action.name }}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts" src="./MenuModule.ts">
</script>

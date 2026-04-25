# catch-table Reference

## Core Props

| Prop | Type | Default | Purpose |
|------|------|---------|---------|
| `api` | `string` | `null` | Backend API path |
| `columns` | `Column[]` | `[]` | Table columns |
| `searchForm` | `SItem[]` | `[]` | Search fields |
| `pagination` | `boolean` | `true` | Show pagination |
| `operation` | `boolean` | `true` | Show the Add button |
| `trash` | `boolean` | `false` | Enable recycle bin mode |
| `exports` | `boolean` | `false` | Show export button |
| `importUrl` | `string` | `''` | Show import button |
| `dialogWidth` | `string` | `''` | Dialog width |
| `dialogHeight` | `string` | `''` | Dialog height |
| `permission` | `string\|null` | `null` | Permission prefix |
| `primaryName` | `string` | `'id'` | Primary key field |
| `defaultParams` | `object` | `{}` | Default query parameters |
| `rowKey` | `string\|Function` | `''` | Row key |
| `destroyConfirm` | `string` | `'确定删除吗'` | Delete confirmation text |

## Common Columns

```ts
{ type: 'selection' }
{ type: 'index', label: '序号', width: 60 }
{ prop: 'name', label: '名称' }
{ type: 'operate', label: '操作', width: 200 }
{ prop: 'status', label: '状态', slot: 'status' }
```

## Named Slots

| Slot | Scope | Use |
|------|-------|-----|
| `dialog` | `row` | Create/edit form inside the built-in dialog |
| `operate` | `{ row, $index }` | Buttons after the default actions |
| `_operate` | `{ row, $index }` | Buttons before the default actions |
| `operation` | `-` | Header actions |
| `middle` | `-` | Content between search and table |
| `csearch` | `-` | Extra search fields |
| `{column.slot}` | `{ row, $index, column }` | Custom cell rendering |

## Exposed Methods

```ts
const tableRef = ref()

tableRef.value.doSearch()
tableRef.value.reset()
tableRef.value.openDialog(row?, title?)
tableRef.value.closeDialog(isReset?)
tableRef.value.del(api, id)
tableRef.value.setDefaultParams(params)
```

## Injected Values

```ts
const closeDialog = inject<(isReset?: boolean) => void>('closeDialog')
const refresh = inject<() => void>('refresh')
```

## Current List Pattern

```vue
<template>
  <catch-table :api="api" :columns="columns" :search-form="searchForm" dialog-width="35%">
    <template #dialog="row">
      <Create :primary="row?.id" :api="api" />
    </template>
  </catch-table>
</template>

<script setup lang="ts">
import Create from './form/create.vue'

const api = 'module/resources'
const columns = []
const searchForm = []
</script>
```

## Current Form Pattern

```vue
<template>
  <el-form ref="form" :model="formData" v-loading="loading">
    <el-button type="primary" @click="submitForm(form)">提交</el-button>
  </el-form>
</template>

<script setup lang="ts">
import { inject, onMounted } from 'vue'
import { useCreate } from '@/composables/curd/useCreate'
import { useShow } from '@/composables/curd/useShow'

const props = defineProps({
  primary: [String, Number],
  api: String
})

const { formData, form, loading, submitForm, close } = useCreate(props.api as string, props.primary)

if (props.primary) {
  useShow(props.api as string, props.primary, formData)
}

const closeDialog = inject('closeDialog') as Function
onMounted(() => {
  close(() => closeDialog())
})
</script>
```

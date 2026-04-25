---
name: catchadmin-frontend
description: Use when creating or editing CatchAdmin Vue pages, catch-table lists, dialog forms, search forms, or view-layer CRUD flows under `web/src/views/`.
---

# CatchAdmin Frontend

## Scope

Follow the current frontend config in this repo and the existing page shape in the target module first.

## Core Rules

- Business pages live in `web/src/views/{module}/`
- Dialog forms use `{resource}/form/create.vue`
- List pages use `catch-table` with a `dialog` slot for create/edit forms
- Create dialogs pass `:primary="row?.id"` and `:api="api"` to `Create`
- Form components use `useCreate(props.api, props.primary)`
- Edit-ready forms call `useShow(props.api, props.primary, formData)`
- Dialog forms close through `inject('closeDialog')`
- Admin components are auto-imported

## Minimal List Page Shape

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

## Minimal Form Shape

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

const props = defineProps<{ primary?: string | number; api: string }>()
const { formData, form, loading, submitForm, close } = useCreate(props.api, props.primary)

if (props.primary) {
  useShow(props.api, props.primary, formData)
}

const closeDialog = inject('closeDialog') as Function
onMounted(() => {
  close(() => closeDialog())
})
</script>
```

## Validation

- Match the nearest page pattern in the same module before introducing a new shape.
- Keep the form path at `{resource}/form/create.vue` for dialog flows.
- Keep `primary` as the form primary-key prop.
- Keep search keys aligned with the backend resource.

## References

- [references/catch-table-api.md](references/catch-table-api.md)

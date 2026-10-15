<script setup>
import { Badge } from '@statamic/cms/ui';

defineProps({ status: { type: String, required: true }, label: { type: String, required: true } });

const colours = { live: 'green', scheduled: 'blue', expired: 'gray', disabled: 'gray' };
</script>

<template>
    <Badge :color="colours[status] ?? 'gray'" :text="label" pill />
</template>

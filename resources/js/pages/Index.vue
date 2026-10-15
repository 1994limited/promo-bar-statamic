<!--
    Content → Promo Bars: every bar, highest priority first. Drag a row (or
    use Move up / Move down) to change which shows first.
-->
<script>
import { Head, router } from '@statamic/cms/inertia';
import { SortableList } from '@statamic/cms';
import { Button, Dropdown, DropdownItem, DropdownMenu, DropdownSeparator, Header, Panel, Switch } from '@statamic/cms/ui';
import StatusBadge from '../components/StatusBadge.vue';
import icon from '../../brand/nav-icon.svg?raw';

export default {
    components: { Button, Dropdown, DropdownItem, DropdownMenu, DropdownSeparator, Head, Header, Panel, SortableList, StatusBadge, Switch },

    props: {
        bars: { type: Array, required: true },
        canEdit: { type: Boolean, default: false },
        multisite: { type: Boolean, default: false },
        urls: { type: Object, required: true },
    },

    data() {
        return { rows: [...this.bars], saving: false };
    },

    computed: {
        icon: () => icon,
    },

    watch: {
        bars(bars) {
            this.rows = [...bars];
        },
    },

    methods: {
        reordered(rows) {
            this.rows = rows;
            this.saveOrder();
        },

        move(index, step) {
            const rows = [...this.rows];
            const [row] = rows.splice(index, 1);
            rows.splice(index + step, 0, row);
            this.rows = rows;
            this.saveOrder();
        },

        saveOrder() {
            this.saving = true;
            this.$axios
                .post(this.urls.reorder, { ids: this.rows.map((row) => row.id) })
                .then(({ data }) => {
                    this.rows = data.bars;
                    Statamic.$toast.success(__('Order saved'));
                })
                .catch(() => Statamic.$toast.error(__('The order couldn’t be saved.')))
                .finally(() => (this.saving = false));
        },

        toggle(row) {
            this.$axios
                .post(row.toggle_url)
                .then(({ data }) => {
                    this.rows = this.rows.map((r) => (r.id === row.id ? data.bar : r));
                    Statamic.$toast.success(data.bar.enabled ? __('Switched on') : __('Switched off'));
                })
                .catch(() => Statamic.$toast.error(__('Something went wrong.')));
        },

        duplicate(row) {
            this.$axios.post(row.duplicate_url).then(({ data }) => router.get(data.redirect));
        },

        destroy(row) {
            if (!confirm(__('Delete “:title”? This can’t be undone.', { title: row.title }))) return;

            this.$axios
                .delete(row.delete_url)
                .then(() => {
                    this.rows = this.rows.filter((r) => r.id !== row.id);
                    Statamic.$toast.success(__('Deleted'));
                })
                .catch(() => Statamic.$toast.error(__('Something went wrong.')));
        },

        schedule(row) {
            if (row.starts && row.ends) return __(':starts – :ends', { starts: row.starts, ends: row.ends });
            if (row.starts) return __('From :date', { date: row.starts });
            if (row.ends) return __('Until :date', { date: row.ends });
            return __('No dates');
        },
    },
};
</script>

<template>
    <Head :title="__('Promo Bars')" />

    <div class="mx-auto max-w-6xl">
        <Header :title="__('Promo Bars')" :icon="icon">
            <Button :href="urls.settings" :text="__('Settings')" icon="cog" />
            <Button v-if="canEdit" :href="urls.create" variant="primary" :text="__('Create Bar')" />
        </Header>

        <Panel>
            <div v-if="rows.length" class="overflow-x-auto">
                <table class="w-full text-sm" :aria-busy="saving">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-600 dark:border-gray-700! dark:text-gray-400!">
                            <th v-if="canEdit" class="w-8 px-2 py-2"><span class="sr-only">{{ __('Order') }}</span></th>
                            <th class="px-3 py-2 font-medium">{{ __('Bar') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Schedule') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Where') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Style') }}</th>
                            <th v-if="canEdit" class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <SortableList :model-value="rows" item-class="promo-bar-row" handle-class="promo-bar-handle" :vertical="true" :disabled="!canEdit" @update:model-value="reordered">
                        <tbody>
                            <tr v-for="(row, index) in rows" :key="row.id" class="promo-bar-row border-b border-gray-100 last:border-0 dark:border-gray-800!">
                                <td v-if="canEdit" class="px-2 py-3 align-top">
                                    <span class="promo-bar-handle block cursor-grab px-1 text-gray-400 select-none" :title="__('Drag to reorder')" aria-hidden="true">⋮⋮</span>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <a :href="row.edit_url" class="font-medium hover:underline!">{{ row.title }}</a>
                                    <div class="mt-0.5 max-w-md truncate text-gray-600 dark:text-gray-400!">{{ row.message }}</div>
                                    <div v-if="multisite" class="mt-0.5 text-xs text-gray-500">{{ row.sites }}</div>
                                </td>
                                <td class="px-3 py-3 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Switch v-if="canEdit" :model-value="row.enabled" size="sm" :aria-label="__('On')" @update:model-value="toggle(row)" />
                                        <StatusBadge :status="row.status" :label="row.status_label" />
                                    </div>
                                </td>
                                <td class="px-3 py-3 align-top text-gray-600 dark:text-gray-400!">{{ schedule(row) }}</td>
                                <td class="px-3 py-3 align-top text-gray-600 dark:text-gray-400!">{{ row.where }}</td>
                                <td class="px-3 py-3 align-top text-gray-600 dark:text-gray-400!">{{ row.style }}</td>
                                <td v-if="canEdit" class="px-3 py-3 text-right align-top">
                                    <Dropdown>
                                        <template #trigger>
                                            <Button icon="dots" variant="ghost" size="sm" :aria-label="__('Actions for :title', { title: row.title })" />
                                        </template>
                                        <DropdownMenu>
                                            <DropdownItem :text="__('Edit')" :href="row.edit_url" />
                                            <DropdownItem :text="__('Duplicate')" @click="duplicate(row)" />
                                            <DropdownItem v-if="index > 0" :text="__('Move up')" @click="move(index, -1)" />
                                            <DropdownItem v-if="index < rows.length - 1" :text="__('Move down')" @click="move(index, 1)" />
                                            <DropdownSeparator />
                                            <DropdownItem :text="__('Delete')" variant="destructive" @click="destroy(row)" />
                                        </DropdownMenu>
                                    </Dropdown>
                                </td>
                            </tr>
                        </tbody>
                    </SortableList>
                </table>
            </div>

            <div v-else class="px-6 py-10 text-center">
                <p class="font-medium">{{ __('No promo bars yet') }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400!">{{ __('Make one, then add the promo_bar tag to your layout where it should appear.') }}</p>
                <div v-if="canEdit" class="mt-4">
                    <Button :href="urls.create" variant="primary" :text="__('Create Bar')" />
                </div>
            </div>
        </Panel>

        <p v-if="rows.length > 1" class="mt-3 text-sm text-gray-600 dark:text-gray-400!">
            {{ __('When several bars are live on a page, the one nearest the top shows first.') }}
        </p>
    </div>
</template>

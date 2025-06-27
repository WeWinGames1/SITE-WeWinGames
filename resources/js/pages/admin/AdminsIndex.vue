<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps<{ admins: Array<any>, users: Array<any> }>();

const addForm = useForm({ user_id: '' });
const removeForm = useForm({ user_id: '' });

function addAdmin() {
    if (addForm.user_id) {
        addForm.post(route('admin.admins.add'), {
            preserveScroll: true,
            onSuccess: () => addForm.reset('user_id'),
        });
    }
}

function removeAdmin(userId: number) {
    removeForm.user_id = userId;
    removeForm.post(route('admin.admins.remove'), {
        preserveScroll: true,
        onSuccess: () => removeForm.reset('user_id'),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="{ title: 'Manage Admins', href: route('admin.admins.index') }">
        
    <Head title="Manage Admins" />
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Admin Users</h1>
        <h2 class="text-xl font-semibold mt-6 mb-2">Current Admins</h2>
        <table class="w-full mb-6">
            <thead>
                <tr>
                    <th class="text-left">Name</th>
                    <th class="text-left">Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="admin in props.admins" :key="admin.id">
                    <td>{{ admin.name }}</td>
                    <td>{{ admin.email }}</td>
                    <td>
                        <button
                            class="text-red-600"
                            @click="removeAdmin(admin.id)"
                            :disabled="admin.id === 1"
                        >
                            Remove
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 class="text-xl font-semibold mt-8 mb-2">Add New Admin</h2>
        <form @submit.prevent="addAdmin" class="flex gap-2 items-center">
            <select v-model="addForm.user_id" class="border rounded px-2 py-1">
                <option value="">Select user</option>
                <option v-for="user in props.users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.email }})
                </option>
            </select>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Add Admin</button>
        </form>
    </div>
    </AppLayout>
</template>
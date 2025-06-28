<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const page = usePage();
const turnstileEnabled = ref(false);
const turnstileSiteKey = ref('');
const turnstileWidget = ref<string | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    website: '', // Honeypot field
    timestamp: Math.floor(Date.now() / 1000), // Current timestamp
    'cf-turnstile-response': '',
});

onMounted(() => {
    // Check if Turnstile is enabled from backend config
    if (window.turnstileConfig) {
        turnstileEnabled.value = window.turnstileConfig.enabled;
        turnstileSiteKey.value = window.turnstileConfig.siteKey;
        
        if (turnstileEnabled.value && window.turnstile) {
            // Render Turnstile widget
            turnstileWidget.value = window.turnstile.render('#cf-turnstile', {
                sitekey: turnstileSiteKey.value,
                callback: function(token: string) {
                    form['cf-turnstile-response'] = token;
                },
                'expired-callback': function() {
                    form['cf-turnstile-response'] = '';
                },
            });
        }
    }
});

const submit = () => {
    // Update timestamp if form was cached
    form.timestamp = Math.floor(Date.now() / 1000);
    
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            // Reset Turnstile if enabled
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Honeypot field - hidden from users -->
                <input 
                    type="text" 
                    name="website" 
                    v-model="form.website" 
                    tabindex="-1"
                    autocomplete="off"
                    style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                    aria-hidden="true"
                />

                <!-- Cloudflare Turnstile -->
                <div v-if="turnstileEnabled" class="flex justify-center">
                    <div id="cf-turnstile"></div>
                </div>
                <InputError :message="form.errors['cf-turnstile-response']" />

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing || (turnstileEnabled && !form['cf-turnstile-response'])">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

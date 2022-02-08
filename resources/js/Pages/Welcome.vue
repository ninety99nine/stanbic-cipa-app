<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center">

        <div>
            <jet-application-logo :scale="2" class="m-auto" />

            <div v-if="canLogin" class="mt-14 text-center">
                <inertia-link v-if="$page.props.user" href="/dashboard" class="text-sm text-gray-700 underline">Dashboard</inertia-link>

                <template v-else>
                    <inertia-link :href="route('login')" class="text-gray-700 underline">Log in</inertia-link>
                </template>
            </div>
        </div>

    </div>
</template>

<script>
    import JetApplicationLogo from '@/Jetstream/ApplicationLogo'
    export default {
        components: {
            JetApplicationLogo
        },
        props: {
            canLogin: Boolean,
            canRegister: Boolean,
            laravelVersion: String,
            phpVersion: String,
        }
    }
</script>

<template>
    <inertia-link :href="'/'">
        <jet-application-logo :scale="1.5" />
    </inertia-link>
</template>

<script>
    import JetApplicationLogo from '@/Jetstream/ApplicationLogo'

    export default {
        components: {
            JetApplicationLogo,
        },
    }
</script>

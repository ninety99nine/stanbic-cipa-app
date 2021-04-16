<template>
    <app-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12 px-20">
            <companies :companies="companies" :progress_totals="progress_totals" />
        </div>

    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout'
    import Companies from '@/Pages/Companies/Main'

    export default {

        props: {
            user: {
                type: Object,
                default: null
            },
            companies: {
                type: Array,
                default: function(){
                    return []
                }
            },
            progress_totals: {
                type: Array,
                default: function(){
                    return []
                }
            },
        },

        components: {
            AppLayout,
            Companies,
        },
    }
</script>

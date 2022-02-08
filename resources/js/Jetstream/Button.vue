<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-sm font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-400 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:shadow-outline-blue disabled:opacity-25 transition"
            :style="style">
        <i v-if="icon" :class="icon+' mr-1'"></i>
        <slot></slot>
    </button>
</template>

<script>
    export default {
        props: {
            icon: {
                type: String,
                default: null,
            },
            type: {
                type: String,
                default: 'submit',
            },
            height: {
                type: [String, Number],
                default: null,
            },
        },
        data() {
            return {
                style: {}
            }
        },
        created(){
            //  If we have a height specified
            if( this.height ){
                //  Set the height property {height: '32px'}
                this.style.height = (this.height).toString() + 'px';
            }
        }
    }
</script>

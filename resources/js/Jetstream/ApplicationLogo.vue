<template>

    <img src="logo/logo.jpeg" :style="'width:'+width+'px'">

</template>

<script>
    export default {
        props: {
            scale: {
                type: Number,
                default: 1
            },
            width: {
                type: Number,
                default: 100
            },
            height: {
                type: Number,
                default: 110
            }
        },
        data() {
            return {
                style: {
                    transform: 'scale('+this.scale+')'
                }
            }
        }
    }
</script>

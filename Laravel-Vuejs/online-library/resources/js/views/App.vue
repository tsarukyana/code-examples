<template>
    <div>
        <menu-component></menu-component>

        <div>
            <transition name="slide-fade" mode="out-in">
                <router-view></router-view>
            </transition>
        </div>
    </div>
</template>
<script>
    import MenuComponent from '../components/MenuComponent'

    export default {
        name: 'OnlineLibrary',
        components: {
            MenuComponent
        },

    }
</script>
<style>
    .slide-fade-enter-active {
        transition: all 0.2s ease;
    }
    .slide-fade-leave-active {
        transition: all 0.2s cubic-bezier(1.0, 0.5, 0.8, 1.0);
    }
    .slide-fade-enter, .slide-fade-leave-to {
        transform: translateY(-10px);
        opacity: 0;
    }
</style>

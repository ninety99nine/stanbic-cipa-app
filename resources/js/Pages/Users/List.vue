<template>

    <div>

        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Users</span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 my-5">

            <!-- Search Bar -->
            <div class="flex items-start">

                <el-input v-model="searchWord" placeholder="Search users" prefix-icon="el-icon-search"
                          size="small" class="mr-2" clearable @keyup.enter="fetchUsers()" @clear="fetchUsers()">
                    <template #prepend>
                        <el-select v-model="searchType" placeholder="Select" :style="{ width: '140px' }" @change="triggerSearch(searchWord, searchType)">
                            <el-option v-for="(searchType, index) in searchTypes" :key="index" :label="searchType.name" :value="searchType.value"></el-option>
                        </el-select>
                    </template>
                </el-input>
                <jet-button :height="32" @click="fetchUsers()">Search</jet-button>
            </div>

            <!-- Select Filter -->
            <div class="flex justify-between items-start">

                <el-select v-model="selectedFilters" multiple placeholder="Filters" size="small"
                           class="w-full mr-20" @change="handleFilter()">
                    <el-option-group v-for="filter in filters" :key="filter.label" :label="filter.label">
                        <el-option v-for="option in filter.options"
                            :key="option.value"
                            :label="option.name"
                            :value="option.value">
                        </el-option>
                    </el-option-group>
                </el-select>

            </div>

            <div class="flex justify-between items-start">

                <jet-button :height="32" icon="el-icon-plus" class="mr-2">
                    <span>Add Role</span>
                </jet-button>

                <jet-button :height="32" icon="el-icon-plus" class="mr-2">
                    <span>Add User</span>
                </jet-button>

            </div>

        </div>

        <div class="grid grid-cols-3 border-b border-t my-3">

            <div class="font-bold text-gray-500 text-sm mt-2">
                <span class="mr-2">Sort By:</span>
                <span class="text-green-500">Created date</span>
                <span class="italic font-light"> - Latest first</span>
            </div>

            <div class="font-bold text-gray-500 text-sm mt-2">
                <template v-if ="searchWord">
                    <span class="mr-2">Search:</span>
                    <span v-if="searchType == 'admin'" class="font-light mx-1">Admin user</span>
                    <span v-if="searchType == 'basic'" class="font-light mx-1">Basic user</span>
                    <span v-if="searchType == 'special'" class="font-light mx-1">Special user</span>
                    <span class="text-green-500">{{ searchWord }}</span>
                </template>
            </div>

            <div class="overflow-auto">
                <div class="float-right font-bold text-gray-500 text-sm">
                    <span class="mr-2">Found</span>
                    <span class="mr-2 text-2xl text-green-500">{{ users.total }}</span>
                    <span>{{ users.total == 1 ? 'result' : 'results' }}</span>
                </div>
            </div>

        </div>

        <div class="border">

            <!-- Table -->
            <el-table :data="tableData">
                <el-table-column width="50" type="selection"></el-table-column>
                <el-table-column min-width="100" prop="name" label="Name"></el-table-column>
                <el-table-column min-width="100" prop="email" label="Email"></el-table-column>
                <el-table-column width="80" label="Action" fixed="right" align="center">
                    <template #default="scope">
                        <el-dropdown trigger="click" placement="bottom-end" class="w-full">
                            <span class="el-dropdown-link block m-auto w-min">
                                <i class="el-icon-more-outline"></i>
                            </span>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item>Reset Password</el-dropdown-item>
                                    <el-dropdown-item class="text-red-500">Delete</el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </template>
                </el-table-column>
            </el-table>

            <!-- Pagination -->
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="pageSize" :page-sizes="[5, 10, 15, 20]"
                            :total="users.total" :page-count="users.total" :current-page="users.current_page"
                            :pager-count="11" background @size-change="changePageSize" @current-change="changePage">
                </el-pagination>
            </div>

        </div>

    </div>

</template>

<script>

    import Dashboard from '@/Pages/Dashboard'
    import JetButton from '@/Jetstream/Button'
    import { Inertia } from '@inertiajs/inertia'

    export default {

        // Use Dashboard Layout
        layout: Dashboard,
        props: {
            users: {
                type: Object,
                default: null
            },
            dynamic_filter_options: {
                type: Object,
                default: null
            }
        },
        components:{ JetButton },
        data() {
            return {
                //  Table attributes
                tableData: [],

                //  Searching attributes
                searchWord: '',
                searchType: 'any',
                searchTypes: [
                    {
                        name: 'Admin user',
                        value: 'admin'
                    },
                    {
                        name: 'Basic user',
                        value: 'basic'
                    },
                    {
                        name: 'Special user',
                        value: 'special'
                    }
                ],

                //  Filter attributes
                minizeFilterSettings: false,
                selectedFilters: [],
                filters: [
                    {
                        label: 'Roles',
                        options: [
                            {
                                name: 'Admin users',
                                value: 'admin'
                            },
                            {
                                name: 'Basic users',
                                value: 'basic'
                            },
                            {
                                name: 'Special users',
                                value: 'special'
                            }
                        ]
                    }
                ],
                filterSettings: {
                    start_percentage: 0,
                    end_percentage: 100
                },

                //  Sorting attributes
                showSortBy: false,
                selectedSortBy: 'updated_at',
                selectedSortByType: 'desc',
                sortByOptions: [
                    {
                        name: 'Created Date',
                        value: 'created_date'
                    }
                ],

                //  Pagination attributes
                currentPage: this.users.current_page,
                perPage: this.users.per_page,


            }
        },
        computed:{
            pageSize(){
                return parseInt(this.users.per_page);
            },
            filterByAdminUsers(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['admin'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByBasicUsers(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['basic'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterBySpecialUsers(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['special'].includes(selectedFilter);
                }).length ? true : false;
            },
            showFilterSettings(){
                return this.filterByAdminUsers || this.filterByBasicUsers || this.filterBySpecialUsers;
            },
            urlQueryParamsAsObject(){

                var url_append = {};

                //  If we have a search word
                if( this.searchWord ){

                    url_append.search = this.searchWord;
                    url_append.search_type = this.searchType;

                }

                //  If we have selected filter
                if( this.selectedFilters.length ){

                    url_append.status = this.selectedFilters.join(',');

                }

                url_append.page = this.currentPage;

                url_append.per_page = this.perPage;

                url_append.sort_by = this.selectedSortBy;

                url_append.sort_by_type = this.selectedSortByType;

                return url_append;
            },
            urlQueryParamsAsString(){

                if( _.isEmpty( this.urlQueryParamsAsObject ) ){

                    return '';

                }else{

                    var string = '?';
                    var field_names = Object.keys(this.urlQueryParamsAsObject);
                    var field_values = Object.values(this.urlQueryParamsAsObject);

                    for (let index = 0; index < field_names.length; index++) {

                        string += field_names[index]+'='+field_values[index];

                        if( (index + 1) != field_names.length ){
                            string += ',';
                        }

                    }

                    return string

                }
            },

        },
        methods:{
            changePage(val) {
                this.currentPage = val;

                this.fetchUsers();
            },
            changePageSize(val) {
                this.perPage = val;

                this.fetchUsers();
            },
            handleFilter(){

                if( this.selectedFilters.length ){

                    //  Clear the search
                    this.searchWord = '';

                }

                this.fetchUsers();
            },
            triggerSearch(searchWord, searchType = 'any'){

                if(searchWord){

                    this.searchWord = searchWord;

                    this.searchType = searchType;

                    this.fetchUsers();

                }

            },
            fetchUsers(){

                var options = { only: ['users'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get(route('users'), this.urlQueryParamsAsObject, options);

                Inertia.on('success', (event) => {

                    this.setTableData(event.detail.page.props.users.data);

                })

            },
            setTableData(users){

                if( users ){
                    this.tableData = users.map(function(ownership_bundle){

                        var data = {

                            //  User information
                            id: ownership_bundle.id,
                            name: ownership_bundle.name,
                            email: ownership_bundle.email,

                        };

                        return data;

                    });
                }
            },
            setSearchFromUrl(){

                if( route().params ){

                    if( route().params.search ){

                        this.searchWord = route().params.search;
                    }

                }
            },
            setSearchTypeFromUrl(){

                if( route().params ){

                    if( route().params.search_type ){

                        this.searchType = route().params.search_type;
                    }

                }
            },
            setFiltersFromUrl(){

                if( route().params ){

                    if( route().params.status ){

                        this.selectedFilters = route().params.status.split(',');
                    }

                }
            },
            setFilterSettingsFromUrl(){

                var properties = Object.keys(this.filterSettings);

                properties.forEach(property => {

                    //  If the date name exists on the url params
                    if( route().params[property] ){

                        this.filterSettings[property] = route().params[property];

                    }
                });
            },
            setSortByFromUrl(){

                if( route().params ){

                    if( route().params.sort_by ){

                        this.selectedSortBy = route().params.sort_by;
                    }

                }
            },
            setSortByTypeFromUrl(){

                if( route().params ){

                    if( route().params.sort_by_type ){

                        this.selectedSortByType = route().params.sort_by_type;
                    }

                }
            }
        },
        created(){
            this.setSearchFromUrl();
            this.setSearchTypeFromUrl();
            this.setFiltersFromUrl();
            this.setFilterSettingsFromUrl();
            this.setSortByFromUrl();
            this.setSortByTypeFromUrl();
            this.setTableData(this.users.data);
        }
    }

</script>

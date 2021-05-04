<template>

    <div>

        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Ownership</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 my-5">

            <!-- Search Bar -->
            <div class="flex items-start">

                <el-input v-model="searchWord" placeholder="Search shareholders or directors" prefix-icon="el-icon-search"
                          size="small" class="mr-2" clearable @clear="fetchOwnershipBundles()">
                    <template #prepend>
                        <el-select v-model="searchType" placeholder="Select" :style="{ width: '140px' }" @change="triggerSearch(searchWord)">
                            <el-option v-for="(searchType, index) in searchTypes" :key="index" :label="searchType.name" :value="searchType.value"></el-option>
                        </el-select>
                    </template>
                </el-input>
                <jet-button :height="32" @click="fetchOwnershipBundles()">Search</jet-button>
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

                <div>
                    <el-dropdown trigger="click" placement="bottom-end">
                        <jet-button :height="32">
                            <span class="el-dropdown-link">
                                <i class="el-icon-more-outline text-white"></i>
                            </span>
                        </jet-button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item icon="el-icon-refresh-right">Refresh</el-dropdown-item>
                                <el-dropdown-item divided>Sort By</el-dropdown-item>
                                <el-dropdown-item>Select Columns</el-dropdown-item>
                                <el-dropdown-item icon="el-icon-download" divided>
                                    <a href="#">Export to Excel</a>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>

            </div>

        </div>

        <div v-if="showFilterSettings" :class="'bg-gray-50 border-b-2 border-blue-100 mb-4 px-5 '+ (minizeFilterSettings ? 'py-2' : 'py-5')">

            <div class="flex justify-between">
                <h4 class="font-bold text-gray-500">Filter Dates</h4>
                <jet-button @click="minizeFilterSettings = !minizeFilterSettings">
                    <i :class="( minizeFilterSettings ? 'el-icon-bottom' : 'el-icon-top') +' text-white'"></i>
                </jet-button>
            </div>

            <template v-if="!minizeFilterSettings">

                <div class="grid grid-cols-4 gap-4 my-5">

                    <div v-if="filterByCustomShareholders">
                        <span class="block py-2 mb-2">Share Allocation</span>
                        <div class="flex items-center">
                            <el-input-number size="mini" v-model="filterSettings.start_percentage" :min="0" :max="100" @change="fetchOwnershipBundles()"></el-input-number>
                            <span class="text-xs mx-4">To</span>
                            <el-input-number size="mini" v-model="filterSettings.end_percentage" :min="filterSettings.start_percentage" :max="100" @change="fetchOwnershipBundles()"></el-input-number>
                        </div>
                    </div>

                </div>

            </template>

        </div>

        <div class="grid grid-cols-3 border-b border-t my-3">

            <div class="font-bold text-gray-500 text-sm mt-2">
                <span class="mr-2">Sort By:</span>
                <span class="text-green-500">Appointment date</span>
                <span class="italic font-light"> - Latest first</span>
            </div>

            <div class="font-bold text-gray-500 text-sm mt-2">
                <template v-if ="searchWord">
                    <span class="mr-2">Search:</span>
                    <span v-if="searchType == 'who_owns'" class="font-light mx-1">Who owns</span>
                    <span v-if="searchType == 'owned_by'" class="font-light mx-1">What does</span>
                    <span class="text-green-500">{{ searchWord }}</span>
                    <span v-if="searchType == 'owned_by'" class="font-light mx-1">own</span>
                </template>
            </div>

            <div class="overflow-auto">
                <div class="float-right font-bold text-gray-500 text-sm">
                    <span class="mr-2">Found</span>
                    <span class="mr-2 text-2xl text-green-500">{{ ownership_bundles.total }}</span>
                    <span>{{ ownership_bundles.total == 1 ? 'result' : 'results' }}</span>
                </div>
            </div>

        </div>

        <div class="border">

            <!-- Table -->
            <el-table :data="tableData">

                <el-table-column min-width="250" prop="company_name" label="Company" fixed>
                    <template #default="scope">
                        <span :style="{ wordBreak: 'break-word' }">
                            <span>{{ scope.row.company_name }}</span><br>
                            <a v-if="scope.row.company_uin" :href="route('companies', {search: scope.row.company_uin, search_type: 'internal'})" class="text-blue-800 text-xs underline cursor-pointer">
                                {{ scope.row.company_uin }}
                            </a>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column min-width="100" prop="company_status" label="Status" fixed>
                    <template #default="scope">
                        <span v-if="scope.row.is_imported_from_cipa">
                            <span class="capitalize">
                                <el-tag v-if="scope.row.company_status == 'Registered'" size="small" type="success">{{ scope.row.company_status }}</el-tag>
                                <el-tag v-else size="small" type="danger">{{ scope.row.company_status }}</el-tag>
                            </span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column min-width="300" prop="name" label="Shareholder" fixed>
                    <template #default="scope">
                        <div :style="{ wordBreak: 'break-word' }">
                            <span class="text-blue-500 text-lg mr-2">
                                <i v-if="scope.row.owner_type == 'individual'" class="el-icon-user"></i>
                                <i v-else-if="scope.row.owner_type == 'company'" class="el-icon-school"></i>
                                <i v-else-if="scope.row.owner_type == 'business'" class="el-icon-suitcase"></i>
                            </span>
                            <span class="no-underline cursor-pointer hover:underline" @click="triggerSearch(scope.row.shareholder_name)">{{ scope.row.shareholder_name }}</span>
                            <template v-if="scope.row.owner_type == 'company' && scope.row.shareholder_uin">
                                <br />
                                <a v-if="scope.row.shareholder_uin" :href="route('companies', {search: scope.row.shareholder_uin, search_type: 'internal'})" class="text-blue-800 text-xs underline cursor-pointer">
                                    {{ scope.row.shareholder_uin }}
                                </a>
                            </template>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column min-width="100" prop="percentage_of_shares" label="% of shares" align="center">
                    <template #default="scope">
                        <span class="font-bold text-2xl text-blue-400">{{ scope.row.percentage_of_shares }}</span>
                        <span class="text-xs text-blue-400">%</span>
                    </template>
                </el-table-column>
                <el-table-column min-width="100" prop="number_of_shares" label="# of shares" align="center"></el-table-column>
                <el-table-column min-width="100" prop="total_shares" label="Total shares" align="center"></el-table-column>
                <el-table-column min-width="100" prop="nominee" label="Nominee" align="center"></el-table-column>

                <el-table-column min-width="250" prop="residential_addresses" label="Residential address">
                    <template #default="scope">
                        <span v-if="scope.row.owner_type == 'individual'" :style="{ wordBreak: 'break-word' }">
                            <span v-if="scope.row.residential_addresses">
                                {{ scope.row.residential_addresses }}
                            </span>
                        </span>
                        <span v-else>N/A</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="250" prop="postal_addresses" label="Postal address">
                    <template #default="scope">
                        <span v-if="scope.row.owner_type == 'individual'" :style="{ wordBreak: 'break-word' }">
                            <span v-if="scope.row.postal_addresses">
                                {{ scope.row.postal_addresses }}
                            </span>
                        </span>
                        <span v-else>N/A</span>
                    </template>
                </el-table-column>

            </el-table>

            <!-- Pagination -->
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="pageSize" :page-sizes="[5, 10, 15, 20]"
                            :total="ownership_bundles.total" :page-count="ownership_bundles.total" :current-page="ownership_bundles.current_page"
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
            ownership_bundles: {
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
                        name: 'Any',
                        value: 'any'
                    },
                    {
                        name: 'Who Owns',
                        value: 'who_owns'
                    },
                    {
                        name: 'Owned By',
                        value: 'owned_by'
                    }
                ],

                //  Filter attributes
                minizeFilterSettings: false,
                selectedFilters: [],
                filterSettings: {
                    start_percentage: 0,
                    end_percentage: 100
                },
                filters: [
                    {
                        label: 'Entity',
                        options: [
                            {
                                name: 'Individuals',
                                value: 'individual'
                            },
                            {
                                name: 'Companies',
                                value: 'company'
                            },
                            {
                                name: 'Businesses',
                                value: 'business'
                            }
                        ]
                    },
                    {
                        label: 'Director Roles',
                        options: [
                            {
                                name: 'Current Director',
                                value: 'current director'
                            },
                            {
                                name: 'Former Director',
                                value: 'former director'
                            },
                            {
                                name: 'Not Director',
                                value: 'not director'
                            }
                        ]
                    },
                    {
                        label: 'Share Allocation',
                        options: [
                            {
                                name: 'Majority shareholders (shares > 50%)',
                                value: 'majority shareholder'
                            },
                            {
                                name: 'Minority shareholders (shares < 50%)',
                                value: 'minority shareholder'
                            },
                            {
                                name: 'Equal shareholders (shares = 50%)',
                                value: 'equal shareholder'
                            },

                            {
                                name: 'Only shareholders (shares = 100%)',
                                value: 'only shareholder'
                            },
                            {
                                name: 'Partial shareholders (shares < 100%)',
                                value: 'partial shareholder'
                            },
                            {
                                name: 'Custom shareholders',
                                value: 'custom shareholder'
                            }
                        ]
                    },
                    {
                        label: 'Ownership',
                        options: [
                            {
                                name: 'Shareholder to many',
                                value: 'shareholder to many'
                            },
                            {
                                name: 'Shareholder to one',
                                value: 'shareholder to one'
                            }
                        ]
                    },
                    /*
                    {
                        label: 'Location',
                        options: [
                            {
                                name: 'Country',
                                value: 'country'
                            },
                            {
                                name: 'Region',
                                value: 'region'
                            }
                        ]
                    },
                    */
                    {
                        label: 'Dates',
                        options: [
                            {
                                name: 'Appointment Date',
                                value: 'appointment date'
                            },
                            {
                                name: 'Ceased Date',
                                value: 'ceased date'
                            },
                            {
                                name: 'Updated Date',
                                value: 'updated date'
                            },
                        ]
                    }
                ],

                //  Sorting attributes
                showSortBy: false,
                selectedSortBy: 'updated_at',
                selectedSortByType: 'desc',
                sortByOptions: [
                    {
                        name: 'Appointment Date',
                        value: 'appointment_date'
                    },
                    {
                        name: 'Ceased Date',
                        value: 'ceased_date'
                    },
                    {
                        name: 'Updated Date',
                        value: 'updated_at'
                    }
                ],

                //  Pagination attributes
                currentPage: this.ownership_bundles.current_page,
                perPage: this.ownership_bundles.per_page,


            }
        },
        computed:{
            pageSize(){
                return parseInt(this.ownership_bundles.per_page);
            },
            filterByCustomShareholders(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['custom shareholder'].includes(selectedFilter);
                }).length ? true : false;
            },
            showFilterSettings(){
                return this.filterByCustomShareholders;
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

                //  Set the filter start percentage (If required)
                if( this.filterByCustomShareholders && this.filterSettings.start_percentage ){
                    url_append.start_percentage = this.filterSettings.start_percentage;
                }

                //  Set the filter end percentage (If required)
                if( this.filterByCustomShareholders && this.filterSettings.end_percentage ){
                    url_append.end_percentage = this.filterSettings.end_percentage;
                }

                url_append.per_page = this.perPage;

                url_append.page = this.currentPage;

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

                this.fetchOwnershipBundles();
            },
            changePageSize(val) {
                this.perPage = val;

                this.fetchOwnershipBundles();
            },
            handleFilter(){
                //  Clear the search
                this.searchWord = '';

                this.fetchOwnershipBundles();
            },
            triggerSearch(searchWord){

                if(searchWord){

                    this.searchWord = searchWord;

                    this.fetchOwnershipBundles();

                }

            },
            fetchOwnershipBundles(){

                var options = { only: ['ownership_bundles'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get(route('ownership-bundles'), this.urlQueryParamsAsObject, options);

                Inertia.on('success', (event) => {
                    this.setTableData(event.detail.page.props.ownership_bundles.data);
                })

            },
            setTableData(ownership_bundles){

                if( ownership_bundles ){
                    this.tableData = ownership_bundles.map(function(ownership_bundle){

                        var data = {

                            //  Ownership bundle information
                            id: ownership_bundle.id,
                            percentage_of_shares: ownership_bundle.percentage_of_shares,
                            number_of_shares: ownership_bundle.number_of_shares,
                            total_shares: ownership_bundle.total_shares,
                            shareholder_name: ownership_bundle.shareholder_name,

                            //  Shareholder information
                            nominee: ownership_bundle.shareholder.nominee.name,
                            appointment_date: ownership_bundle.shareholder.appointment_date,
                            ceased_date: ownership_bundle.shareholder.ceased_date,
                            owner_type: ownership_bundle.shareholder.owner_type,

                            //  Company information
                            company_uin: ownership_bundle.company.uin,
                            company_name: ownership_bundle.company.name,
                            company_status: ownership_bundle.company.company_status,
                            is_imported_from_cipa: ownership_bundle.company.is_imported_from_cipa
                        };

                        //  Additional Shareholder details (If Individual Shareholder)
                        if( ownership_bundle.shareholder.owner_type == 'individual' ){

                            if( ownership_bundle.shareholder.owner ){

                                //  Residential Addresses
                                data.residential_addresses = ownership_bundle.shareholder.owner.addresses.filter((address) => {
                                    return address.type == 'residential_address';
                                }).map((residentialAddress) => {
                                    return residentialAddress.full_address;
                                }).join(' | ');

                                //  Postal Addresses
                                data.postal_addresses = ownership_bundle.shareholder.owner.addresses.filter((address) => {
                                    return address.type == 'postal_address';
                                }).map((postalAddress) => {
                                    return postalAddress.full_address;
                                }).join(' | ');

                            }

                        }else if( ownership_bundle.shareholder.owner_type == 'company' ){

                            if( ownership_bundle.shareholder.owner ){

                                data.shareholder_uin = ownership_bundle.shareholder.owner.uin;

                            }

                        }

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
            this.setTableData(this.ownership_bundles.data);
        }
    }

</script>

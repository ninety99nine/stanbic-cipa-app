<template>

    <div>

        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Shareholders</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 my-5">

            <!-- Search Bar -->
            <div class="flex items-start">

                <el-input v-model="searchWord" placeholder="Search directors" prefix-icon="el-icon-search"
                          size="small" class="mr-2" clearable @keyup.enter="handleFilter()" @clear="handleFilter()">
                    <template #prepend>
                        <el-select v-model="searchType" placeholder="Select" :style="{ width: '140px' }" @change="triggerSearch(searchWord, searchType)">
                            <el-option v-for="(searchType, index) in searchTypes" :key="index" :label="searchType.name" :value="searchType.value"></el-option>
                        </el-select>
                    </template>
                </el-input>
                <jet-button :height="32" @click="handleFilter()">Search</jet-button>
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
                                <el-dropdown-item icon="el-icon-refresh-right" @click="handleFilter()">Refresh</el-dropdown-item>
                                <el-dropdown-item divided @click="showSortBy = true">Sort By</el-dropdown-item>
                                <el-dropdown-item @click="toggleSelectedColumns()">Select Columns</el-dropdown-item>
                                <el-dropdown-item v-if="$page.props.can.includes('export directors')" icon="el-icon-download" divided>
                                    <a :href="exportUrl">Export to Excel</a>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>

            </div>

        </div>

        <div v-if="showFilterSettings" :class="'bg-gray-50 border-b-2 border-blue-100 mb-4 px-5 '+ (minizeFilterSettings ? 'py-2' : 'py-5')">

            <div class="flex justify-between">
                <h4 class="font-bold text-gray-500">Filter Settings</h4>
                <jet-button @click="minizeFilterSettings = !minizeFilterSettings">
                    <i :class="( minizeFilterSettings ? 'el-icon-bottom' : 'el-icon-top') +' text-white'"></i>
                </jet-button>
            </div>

            <template v-if="!minizeFilterSettings">

                <div class="grid grid-cols-4 gap-4 my-5">

                    <div v-if="filterByCustomDirectorShares">
                        <span class="block py-2 mb-2">Share Allocation</span>
                        <div class="flex items-center">
                            <span class="text-xs mr-4">From</span>
                            <el-input-number size="mini" v-model="filterSettings.start_percentage" :min="0" :max="100" @change="handleFilter()"></el-input-number>
                            <span class="text-xs mx-4">To</span>
                            <el-input-number size="mini" v-model="filterSettings.end_percentage" :min="filterSettings.start_percentage" :max="100" @change="handleFilter()"></el-input-number>
                        </div>
                    </div>

                    <div v-if="filterByDirectorToSpecificNumberOfCompanies">

                        <span class="block py-2 mb-2">Director to number of companies</span>

                        <div class="d-flex">
                            <span class="text-xs mr-2">Type:</span>
                            <el-select v-model="filterSettings.director_to_specific_type" size="mini" class="mb-2" placeholder="Select" @change="handleFilter()">
                                <el-option v-for="option in ['Minimum', 'Maximum', 'Exact', 'Range']" :key="option" :label="option" :value="option"></el-option>
                            </el-select>
                        </div>

                        <template v-if="filterSettings.director_to_specific_type == 'Minimum'">
                            <div class="flex items-center">
                            <span class="text-xs mr-4">Mimumim</span>
                                <el-input-number size="mini" v-model="filterSettings.min_companies" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.director_to_specific_type == 'Maximum'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Maximum</span>
                                <el-input-number size="mini" v-model="filterSettings.max_companies" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.director_to_specific_type == 'Exact'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Exactly</span>
                                <el-input-number size="mini" v-model="filterSettings.exact_companies" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.director_to_specific_type == 'Range'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">From</span>
                                <el-input-number size="mini" v-model="filterSettings.min_companies" :min="1" clearable @change="handleFilter()"></el-input-number>
                                <span class="text-xs mx-4">To</span>
                                <el-input-number size="mini" v-model="filterSettings.max_companies" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                    </div>

                    <div v-if="filterByCompanyWithOneOrManyDirectors">

                        <span class="block py-2 mb-2">Has number of directors</span>

                        <div class="d-flex">
                            <span class="text-xs mr-2">Type:</span>
                            <el-select v-model="filterSettings.specific_directors_type" size="mini" class="mb-2" placeholder="Select" @change="handleFilter()">
                                <el-option v-for="option in ['Minimum', 'Maximum', 'Exact', 'Range']" :key="option" :label="option" :value="option"></el-option>
                            </el-select>
                        </div>

                        <template v-if="filterSettings.specific_directors_type == 'Minimum'">
                            <div class="flex items-center">
                            <span class="text-xs mr-4">Mimumim</span>
                                <el-input-number size="mini" v-model="filterSettings.min_directors" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_directors_type == 'Maximum'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Maximum</span>
                                <el-input-number size="mini" v-model="filterSettings.max_directors" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_directors_type == 'Exact'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Exactly</span>
                                <el-input-number size="mini" v-model="filterSettings.equal_directors" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_directors_type == 'Range'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">From</span>
                                <el-input-number size="mini" v-model="filterSettings.min_directors" :min="1" clearable @change="handleFilter()"></el-input-number>
                                <span class="text-xs mx-4">To</span>
                                <el-input-number size="mini" v-model="filterSettings.max_directors" :min="1" clearable @change="handleFilter()"></el-input-number>
                            </div>
                        </template>

                    </div>

                    <div v-if="filterByShareholderAppointedDate">
                        <span class="block py-2 mb-2">Shareholder appointment date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_appointed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="handleFilter()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_appointed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="handleFilter()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByShareholderCeasedDate">
                        <span class="block py-2 mb-2">Shareholder ceased date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_ceased_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="handleFilter()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_ceased_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="handleFilter()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByDirectorAppointedDate">
                        <span class="block py-2 mb-2">Director appointment date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_appointed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="handleFilter()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_appointed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="handleFilter()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByDirectorCeasedDate">
                        <span class="block py-2 mb-2">Director ceased date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_ceased_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="handleFilter()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_ceased_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="handleFilter()"></el-date-picker>
                        </div>
                    </div>

                </div>

            </template>

        </div>

        <div v-if="showSortBy" class="bg-gray-50 p-5 mb-4 border-b-2 border-blue-100">

            <div class="flex justify-between">
                <h4 class="font-bold text-gray-500">Sort By</h4>
                <jet-button @click="showSortBy = false">
                    <i class="el-icon-close text-white"></i>
                </jet-button>
            </div>

            <div class="my-5">

                <el-select v-model="selectedSortBy" placeholder="Select" class="mr-4" @change="handleFilter()">
                    <el-option
                        v-for="sortByOption in sortByOptions"
                        :key="sortByOption.value"
                        :label="sortByOption.name"
                        :value="sortByOption.value"
                        :disabled="sortByOption.value == 'company_name'">
                    </el-option>
                </el-select>

                <el-select v-model="selectedSortByType" placeholder="Select" @change="handleFilter()">
                    <el-option
                        v-for="sortByTypeOption in sortByTypeOptions"
                        :key="sortByTypeOption.value"
                        :label="sortByTypeOption.name"
                        :value="sortByTypeOption.value">
                    </el-option>
                </el-select>

            </div>

        </div>

        <div v-if="showSelectedColumns" class="bg-gray-50 p-5 mb-4 border-b-2 border-blue-100">

            <div class="flex justify-between">
                <h4 class="font-bold text-gray-500">Table Columns</h4>
                <jet-button @click="toggleSelectedColumns()">
                    <i class="el-icon-close text-white"></i>
                </jet-button>
            </div>

            <div class="grid grid-cols-4 gap-4 my-5">

                <div v-for="(tableColumn, index) in tableColumns" :key="index">
                    <el-checkbox v-model="tableColumn.status" class="capitalize">{{ tableColumn.name }}</el-checkbox>
                </div>

            </div>

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
                    <span class="mr-2 text-2xl text-green-500">{{ directors.total }}</span>
                    <span>{{ directors.total == 1 ? 'result' : 'results' }}</span>
                </div>
            </div>

        </div>

        <div class="border">

            <!-- Table -->
            <el-table :data="tableData">

                <el-table-column v-if="selectedColumns.includes('compliant indicator')" min-width="40" fixed>
                    <template #default="scope">
                        <el-popover placement="top-start" :title="scope.row.is_compliant.name" :width="190" trigger="hover">
                            <template #reference>
                                <i v-if="scope.row.is_compliant.status" class="el-icon-circle-check text-green-400"></i>
                                <i v-else class="el-icon-circle-close text-red-400"></i>
                            </template>
                            <div>
                                <span v-if="scope.row.is_cancelled" class="text-red-500">Company is cancelled</span>
                                <span v-else-if="scope.row.is_removed" class="text-red-500">Company is removed</span>
                                <span v-else-if="scope.row.is_not_found" class="text-red-500">Company not found</span>
                            </div>
                        </el-popover>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('company name')" min-width="250" prop="company_name" label="Company" fixed>
                    <template #default="scope">
                        <span :style="{ wordBreak: 'break-word' }">
                            <span class="no-underline cursor-pointer hover:underline" @click="triggerSearch(scope.row.company_name)">{{ scope.row.company_name }}</span>
                            <a v-if="scope.row.company_uin" :href="route('companies', {search: scope.row.company_uin})" class="text-blue-800 text-xs underline cursor-pointer ml-2">
                                {{ scope.row.company_uin }}
                            </a>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('company status')" min-width="100" prop="company_status" label="Status" fixed>
                    <template #default="scope">
                        <span class="capitalize">
                            <el-tag v-if="scope.row.company_status == 'Registered'" size="small" type="success">{{ scope.row.company_status }}</el-tag>
                            <el-tag v-else size="small" type="danger">{{ scope.row.company_status }}</el-tag>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('director')" min-width="300" prop="name" label="Director" fixed>
                    <template #default="scope">
                        <div :style="{ wordBreak: 'break-word' }">
                            <span class="text-blue-500 text-lg mr-2">
                                <i class="el-icon-user"></i>
                            </span>
                            <span class="no-underline cursor-pointer hover:underline" @click="triggerSearch(scope.row.full_name)">{{ scope.row.full_name }}</span>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('appointment date')" min-width="210" prop="appointment_date" label="Appointed date">
                    <template #default="scope">
                        <span>{{ scope.row.appointment_date }}</span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('ceased date')" min-width="200" prop="ceased_date" label="Ceased date">
                    <template #default="scope">
                        <span>{{ scope.row.appointment_date }}</span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('residential addresses')" min-width="250" prop="residential_addresses" label="Residential address">
                    <template #default="scope">
                        <span v-if="scope.row.residential_addresses" :style="{ wordBreak: 'break-word' }">
                            {{ scope.row.residential_addresses }}
                        </span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('postal addresses')" min-width="250" prop="postal_addresses" label="Postal address">
                    <template #default="scope">
                        <span v-if="scope.row.postal_addresses" :style="{ wordBreak: 'break-word' }">
                            {{ scope.row.postal_addresses }}
                        </span>
                    </template>
                </el-table-column>

            </el-table>

            <!-- Pagination -->
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="pageSize" :page-sizes="[5, 10, 15, 20]"
                            :total="directors.total" :page-count="directors.total" :current-page="directors.current_page"
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
            directors: {
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
                filters: [
                    {
                        label: 'Company Statuses',
                        options: this.companyStatusOptions()
                    },
                    {
                        label: 'Business Sectors',
                        options: this.businessSectorsOptions()
                    },
                    {
                        label: 'Company Type',
                        options: this.companyTypesOptions()
                    },
                    {
                        label: 'Sub Type',
                        options: this.companySubTypesOptions()
                    },
                    {
                        label: 'Compliance Status',
                        options: [
                            {
                                value: 'Compliant'
                            },
                            {
                                value: 'Not Compliant'
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
                            }
                        ]
                    },
                    {
                        label: 'Director to one or many companies',
                        options: [
                            {
                                name: 'Director to one company',
                                value: 'director to one'
                            },
                            {
                                name: 'Director to many companies',
                                value: 'director to many'
                            },
                            {
                                name: 'Director to specific # of companies',
                                value: 'director to specific'
                            }
                        ]
                    },
                    {
                        label: 'Company has one or many directors',
                        options: [
                            {
                                name: 'Company has one director',
                                value: 'has one director'
                            },
                            {
                                name: 'Company has many directors',
                                value: 'has many directors'
                            },
                            {
                                name: 'Company has specific # of directors',
                                value: 'has specific directors'
                            }
                        ]
                    },
                    /*
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
                        label: 'Shareholder company',
                        options: [
                            {
                                name: 'Shareholder company has shares',
                                value: 'company owns'
                            },
                            {
                                name: 'Shareholder company doesn\'t have shares',
                                value: 'company does not own'
                            },
                        ]
                    },
                    */
                    {
                        label: 'Dates',
                        options: [
                            {
                                name: 'Appointed date',
                                value: 'director appointed date'
                            },
                            {
                                name: 'Ceased date',
                                value: 'director ceased date'
                            }
                        ]
                    }
                ],
                filterSettings: {
                    start_percentage: 0,
                    end_percentage: 100,

                    min_companies: 1,
                    max_companies: 2,
                    exact_companies: 1,
                    director_to_specific_type: 'Minimum',

                    min_directors: 1,
                    max_directors: 2,
                    equal_directors: 1,
                    specific_directors_type: 'Minimum',

                    director_appointed_start_date: null,
                    director_appointed_end_date: null,

                    director_ceased_start_date: null,
                    director_ceased_end_date: null,
                },

                //  Sorting attributes
                showSortBy: false,
                selectedSortBy: 'created_at',
                selectedSortByType: 'asc',
                sortByOptions: [
                    {
                        name: 'Company Name',
                        value: 'company_name'
                    },
                    {
                        name: 'Director Name',
                        value: 'director_name'
                    },
                    {
                        name: 'Created Date',
                        value: 'created_at'
                    }
                ],
                sortByTypeOptions: [
                    {
                        name: 'Ascending',
                        value: 'asc',
                    },
                    {
                        name: 'Descending',
                        value: 'desc'
                    }
                ],

                //  Columns
                showSelectedColumns: false,
                tableColumns: [
                    {
                        name: 'compliant indicator',
                        status: true
                    },
                    {
                        name: 'company name',
                        status: true
                    },
                    {
                        name: 'company status',
                        status: true
                    },
                    {
                        name: 'director',
                        status: true
                    },
                    {
                        name: 'director appointment date',
                        status: true
                    },
                    {
                        name: 'director ceased date',
                        status: true
                    },
                    {
                        name: 'residential addresses',
                        status: true
                    },
                    {
                        name: 'postal addresses',
                        status: true
                    }
                ],

                //  Pagination attributes
                currentPage: this.directors.current_page,
                perPage: this.directors.per_page,


            }
        },
        computed:{
            selectedColumns(){
                return this.tableColumns.filter(function(tableColumn){
                    return (tableColumn.status == true);
                }).map(function(tableColumn){
                    return tableColumn.name;
                });
            },
            pageSize(){
                return parseInt(this.directors.per_page);
            },
            filterByCustomDirectorShares(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['custom director'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByDirectorToSpecificNumberOfCompanies(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['director to specific'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByCompanyWithOneOrManyDirectors(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['has specific shareholders'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByShareholderAppointedDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['shareholder appointed date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByShareholderCeasedDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['shareholder ceased date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByDirectorAppointedDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['director appointed date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByDirectorCeasedDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['director ceased date'].includes(selectedFilter);
                }).length ? true : false;
            },
            showFilterSettings(){
                return this.filterByCustomDirectorShares || this.filterByDirectorToSpecificNumberOfCompanies ||
                       this.filterByCompanyWithOneOrManyDirectors || this.filterByShareholderAppointedDate ||
                       this.filterByShareholderCeasedDate || this.filterByDirectorAppointedDate ||
                       this.filterByDirectorCeasedDate;
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
                if( this.filterByCustomDirectorShares && this.filterSettings.start_percentage ){
                    url_append.start_percentage = this.filterSettings.start_percentage;
                }

                //  Set the filter end percentage (If required)
                if( this.filterByCustomDirectorShares && this.filterSettings.end_percentage ){
                    url_append.end_percentage = this.filterSettings.end_percentage;
                }

                //  Set the filter source of shares type (If required)
                if( this.filterByDirectorToSpecificNumberOfCompanies && this.filterSettings.director_to_specific_type ){
                    url_append.director_to_specific_type = this.filterSettings.director_to_specific_type;
                }

                //  Set the filter exact source of shares (If required)
                if( this.filterByDirectorToSpecificNumberOfCompanies && this.filterSettings.exact_companies ){
                    if( ['Exact'].includes(this.filterSettings.director_to_specific_type) ){
                        url_append.exact_companies = this.filterSettings.exact_companies;
                    }
                }

                //  Set the filter min source of shares (If required)
                if( this.filterByDirectorToSpecificNumberOfCompanies && this.filterSettings.min_companies ){
                    if( ['Minimum', 'Range'].includes(this.filterSettings.director_to_specific_type) ){
                        url_append.min_companies = this.filterSettings.min_companies;
                    }
                }

                //  Set the filter max source of shares (If required)
                if( this.filterByDirectorToSpecificNumberOfCompanies && this.filterSettings.max_companies ){
                    if( ['Maximum', 'Range'].includes(this.filterSettings.director_to_specific_type) ){
                        url_append.max_companies = this.filterSettings.max_companies;
                    }
                }






                //  Set the filter specific shareholders type (If required)
                if( this.filterByCompanyWithOneOrManyDirectors && this.filterSettings.specific_shareholders_type ){
                    url_append.specific_shareholders_type = this.filterSettings.specific_shareholders_type;
                }

                //  Set the filter equal shareholders (If required)
                if( this.filterByCompanyWithOneOrManyDirectors && this.filterSettings.equal_shareholders ){
                    if( ['Exact'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.equal_shareholders = this.filterSettings.equal_shareholders;
                    }
                }

                //  Set the filter min shareholders (If required)
                if( this.filterByCompanyWithOneOrManyDirectors && this.filterSettings.min_shareholders ){
                    if( ['Minimum', 'Range'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.min_shareholders = this.filterSettings.min_shareholders;
                    }
                }

                //  Set the filter max shareholders (If required)
                if( this.filterByCompanyWithOneOrManyDirectors && this.filterSettings.max_shareholders ){
                    if( ['Maximum', 'Range'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.max_shareholders = this.filterSettings.max_shareholders;
                    }
                }

                //  Set the filter director appointment start date
                if( this.filterByDirectorAppointedDate && this.filterSettings.director_appointed_start_date ){
                    url_append.director_appointed_start_date = this.filterSettings.director_appointed_start_date;
                }

                //  Set the filter director appointment end date
                if( this.filterByDirectorAppointedDate && this.filterSettings.director_appointed_end_date ){
                    url_append.director_appointed_end_date = this.filterSettings.director_appointed_end_date;
                }

                //  Set the filter director ceased start date
                if( this.filterByDirectorCeasedDate && this.filterSettings.director_ceased_start_date ){
                    url_append.director_ceased_start_date = this.filterSettings.director_ceased_start_date;
                }

                //  Set the filter director ceased end date
                if( this.filterByDirectorCeasedDate && this.filterSettings.director_ceased_end_date ){
                    url_append.director_ceased_end_date = this.filterSettings.director_ceased_end_date;
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
            exportUrl(){
                return route('directors-export') + this.urlQueryParamsAsString;
            }
        },
        methods:{
            toggleSelectedColumns(){
                this.showSelectedColumns = !this.showSelectedColumns;
            },
            companyStatusOptions(){
                return ((this.dynamic_filter_options || {}).company_statuses || []).map((status) => {
                    return {
                        value: status
                    }
                });
            },
            companySubTypesOptions(){
                return ((this.dynamic_filter_options || {}).company_sub_types || []).map((type) => {
                    return {
                        value: type
                    }
                });
            },
            companyTypesOptions(){
                return ((this.dynamic_filter_options || {}).company_types || []).map((type) => {
                    return {
                        value: type
                    }
                });
            },
            businessSectorsOptions(){
                return ((this.dynamic_filter_options || {}).business_sectors || []).map((type) => {
                    return {
                        value: type
                    }
                });
            },
            changePage(val) {
                this.currentPage = val;

                this.fetchOwnershipBundles();
            },
            changePageSize(val) {
                this.perPage = val;

                this.fetchOwnershipBundles();
            },
            handleFilter(){

                if( this.selectedFilters.length ){

                    //  Clear the search
                    this.searchWord = '';

                }

                this.fetchOwnershipBundles();
            },
            triggerSearch(searchWord, searchType = 'any'){

                if(searchWord){

                    this.searchWord = searchWord;

                    this.searchType = searchType;

                    this.fetchOwnershipBundles();

                }

            },
            fetchOwnershipBundles(){

                var options = { only: ['directors'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get(route('directors'), this.urlQueryParamsAsObject, options);

                Inertia.on('success', (event) => {
                    this.setTableData(event.detail.page.props.directors.data);
                })

            },
            setTableData(directors){

                if( directors ){
                    this.tableData = directors.map(function(director){

                        var data = {

                            //  Director information
                            id: director.id,
                            ceased_date: director.ceased_date,
                            appointment_date: director.appointment_date

                        };

                        //  If we have an individual
                        if( director.individual ){

                            //  Individual information
                            data.full_name = director.individual.full_name;

                            //  Residential Addresses
                            data.residential_addresses = director.individual.residential_address_lines;

                            //  Postal Addresses
                            data.postal_addresses = director.individual.apostal_address_lines;

                        }

                        //  If we have a company
                        if( director.company ){

                            //  Individual information
                            data.full_name = director.individual.full_name;

                            //  Residential Addresses
                            data.residential_addresses = director.individual.residential_address_lines;

                            //  Postal Addresses
                            data.postal_addresses = director.individual.postal_address_lines;

                            //  Company information
                            data.company_uin = director.company.uin,
                            data.company_name = director.company.name,
                            data.company_status = director.company.company_status,

                            //  Company Attributes information
                            data.is_registered = director.company.is_registered.status;
                            data.is_cancelled = director.company.is_cancelled.status;
                            data.is_removed = director.company.is_removed.status;
                            data.is_not_found = director.company.is_not_found.status;
                            data.is_compliant = director.company.is_compliant;

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
            this.setTableData(this.directors.data);
        }
    }

</script>

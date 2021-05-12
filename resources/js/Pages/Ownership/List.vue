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
                          size="small" class="mr-2" clearable @keyup.enter="fetchOwnershipBundles()" @clear="fetchOwnershipBundles()">
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
                <h4 class="font-bold text-gray-500">Filter Settings</h4>
                <jet-button @click="minizeFilterSettings = !minizeFilterSettings">
                    <i :class="( minizeFilterSettings ? 'el-icon-bottom' : 'el-icon-top') +' text-white'"></i>
                </jet-button>
            </div>

            <template v-if="!minizeFilterSettings">

                <div class="grid grid-cols-4 gap-4 my-5">

                    <div v-if="filterByCustomShareholders">
                        <span class="block py-2 mb-2">Share Allocation</span>
                        <div class="flex items-center">
                            <span class="text-xs mr-4">From</span>
                            <el-input-number size="mini" v-model="filterSettings.start_percentage" :min="0" :max="100" @change="fetchOwnershipBundles()"></el-input-number>
                            <span class="text-xs mx-4">To</span>
                            <el-input-number size="mini" v-model="filterSettings.end_percentage" :min="filterSettings.start_percentage" :max="100" @change="fetchOwnershipBundles()"></el-input-number>
                        </div>
                    </div>

                    <div v-if="filterByShareholderToSpecificNumber">

                        <span class="block py-2 mb-2">Has number of shares</span>

                        <div class="d-flex">
                            <span class="text-xs mr-2">Type:</span>
                            <el-select v-model="filterSettings.source_of_shares_type" size="mini" class="mb-2" placeholder="Select" @change="fetchOwnershipBundles()">
                                <el-option v-for="option in ['Minimum', 'Maximum', 'Exact', 'Range']" :key="option" :label="option" :value="option"></el-option>
                            </el-select>
                        </div>

                        <template v-if="filterSettings.source_of_shares_type == 'Minimum'">
                            <div class="flex items-center">
                            <span class="text-xs mr-4">Mimumim</span>
                                <el-input-number size="mini" v-model="filterSettings.min_source_of_shares" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.source_of_shares_type == 'Maximum'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Maximum</span>
                                <el-input-number size="mini" v-model="filterSettings.max_source_of_shares" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.source_of_shares_type == 'Exact'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Exactly</span>
                                <el-input-number size="mini" v-model="filterSettings.exact_source_of_shares" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.source_of_shares_type == 'Range'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">From</span>
                                <el-input-number size="mini" v-model="filterSettings.min_source_of_shares" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                                <span class="text-xs mx-4">To</span>
                                <el-input-number size="mini" v-model="filterSettings.max_source_of_shares" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                    </div>

                    <div v-if="filterByHasSpecificShareholders">

                        <span class="block py-2 mb-2">Has number of shareholders</span>

                        <div class="d-flex">
                            <span class="text-xs mr-2">Type:</span>
                            <el-select v-model="filterSettings.specific_shareholders_type" size="mini" class="mb-2" placeholder="Select" @change="fetchOwnershipBundles()">
                                <el-option v-for="option in ['Minimum', 'Maximum', 'Exact', 'Range']" :key="option" :label="option" :value="option"></el-option>
                            </el-select>
                        </div>

                        <template v-if="filterSettings.specific_shareholders_type == 'Minimum'">
                            <div class="flex items-center">
                            <span class="text-xs mr-4">Mimumim</span>
                                <el-input-number size="mini" v-model="filterSettings.min_shareholders" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_shareholders_type == 'Maximum'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Maximum</span>
                                <el-input-number size="mini" v-model="filterSettings.max_shareholders" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_shareholders_type == 'Exact'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">Exactly</span>
                                <el-input-number size="mini" v-model="filterSettings.equal_shareholders" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                        <template v-if="filterSettings.specific_shareholders_type == 'Range'">
                            <div class="flex items-center">
                                <span class="text-xs mr-4">From</span>
                                <el-input-number size="mini" v-model="filterSettings.min_shareholders" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                                <span class="text-xs mx-4">To</span>
                                <el-input-number size="mini" v-model="filterSettings.max_shareholders" :min="1" clearable @change="fetchOwnershipBundles()"></el-input-number>
                            </div>
                        </template>

                    </div>

                    <div v-if="filterByShareholderAppointedDate">
                        <span class="block py-2 mb-2">Shareholder appointment date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.shareholder_appointed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchOwnershipBundles()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.shareholder_appointed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchOwnershipBundles()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByShareholderCeasedDate">
                        <span class="block py-2 mb-2">Shareholder ceased date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.shareholder_ceased_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchOwnershipBundles()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.shareholder_ceased_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchOwnershipBundles()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByDirectorAppointedDate">
                        <span class="block py-2 mb-2">Director appointment date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_appointed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchOwnershipBundles()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_appointed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchOwnershipBundles()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByDirectorCeasedDate">
                        <span class="block py-2 mb-2">Director ceased date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.director_ceased_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchOwnershipBundles()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.director_ceased_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchOwnershipBundles()"></el-date-picker>
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
                <el-table-column min-width="40" fixed>
                    <template #default="scope">
                        <el-popover v-if="scope.row.is_imported_from_cipa" placement="top-start" :title="scope.row.is_compliant.name" :width="190" trigger="hover">
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
                <el-table-column min-width="250" prop="company_name" label="Company" fixed>
                    <template #default="scope">
                        <span :style="{ wordBreak: 'break-word' }">
                            <span class="no-underline cursor-pointer hover:underline" @click="triggerSearch(scope.row.company_name)">{{ scope.row.company_name }}</span>
                            <a v-if="scope.row.company_uin" :href="route('companies', {search: scope.row.company_uin, search_type: 'internal'})" class="text-blue-800 text-xs underline cursor-pointer ml-2">
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
                            <el-tag v-if="scope.row.total_shareholder_occurances > 1" size="mini" type="warning" class="ml-2">x{{ scope.row.total_shareholder_occurances }} duplicates</el-tag>
                            <el-tag v-if="scope.row.is_shareholder_to_self" size="mini" type="warning" class="ml-2">shareholder to itself</el-tag>
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
                <el-table-column min-width="100" prop="is_director" label="Director" align="center">
                    <template #default="scope">
                        <span>{{ scope.row.is_director ? 'Yes' : 'No' }}</span>
                    </template>
                </el-table-column>
                <el-table-column min-width="210" prop="shareholder_appointment_date" label="Shareholder appointed date">
                    <template #default="scope">
                        <span v-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.shareholder_appointment_date }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column min-width="200" prop="shareholder_ceased_date" label="Shareholder ceased date">
                    <template #default="scope">
                        <span v-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.shareholder_ceased_date }}</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column min-width="190" prop="director_appointment_date" label="Director appointed date">
                    <template #default="scope">
                        <span v-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.director_appointment_date }}</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column min-width="180" prop="director_ceased_date" label="Director ceased date">
                    <template #default="scope">
                        <span v-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.director_ceased_date }}</span>
                        </span>
                    </template>
                </el-table-column>

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
                                name: 'Organisations',
                                value: 'organisation'
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
                        label: 'Number of shares in several companies',
                        options: [
                            {
                                name: 'Shareholder to one company',
                                value: 'shareholder to one'
                            },
                            {
                                name: 'Shareholder to many companies',
                                value: 'shareholder to many'
                            },
                            {
                                name: 'Shareholder to specific # of companies',
                                value: 'shareholder to specific'
                            }
                        ]
                    },
                    {
                        label: 'Number of shareholders in same company',
                        options: [
                            {
                                name: 'Company has one shareholder',
                                value: 'has one shareholder'
                            },
                            {
                                name: 'Company has many shareholders',
                                value: 'has many shareholders'
                            },
                            {
                                name: 'Company has specific # of shareholders',
                                value: 'has specific shareholders'
                            }
                        ]
                    },
                    {
                        label: 'Duplicates',
                        options: [
                            {
                                name: 'Duplicate shareholder names',
                                value: 'duplicate shareholder names'
                            },
                            {
                                name: 'Shareholder to itself',
                                value: 'shareholder to itself'
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
                                name: 'Shareholder appointed date',
                                value: 'shareholder appointed date'
                            },
                            {
                                name: 'Shareholder ceased date',
                                value: 'shareholder ceased date'
                            },
                            {
                                name: 'Director appointed date',
                                value: 'director appointed date'
                            },
                            {
                                name: 'Director ceased date',
                                value: 'director ceased date'
                            }
                        ]
                    }
                ],
                filterSettings: {
                    start_percentage: 0,
                    end_percentage: 100,

                    min_source_of_shares: 1,
                    max_source_of_shares: 2,
                    exact_source_of_shares: 1,
                    source_of_shares_type: 'Minimum',

                    min_shareholders: 1,
                    max_shareholders: 2,
                    equal_shareholders: 1,
                    specific_shareholders_type: 'Minimum',

                    shareholder_appointed_start_date: null,
                    shareholder_appointed_end_date: null,

                    shareholder_ceased_start_date: null,
                    shareholder_ceased_end_date: null,

                    director_appointed_start_date: null,
                    director_appointed_end_date: null,

                    director_ceased_start_date: null,
                    director_ceased_end_date: null,
                },
                shareholderToSpecificType: 'Minimum',

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
            filterByShareholderToSpecificNumber(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['shareholder to specific'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByHasSpecificShareholders(){
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
                return this.filterByCustomShareholders || this.filterByShareholderToSpecificNumber ||
                       this.filterByHasSpecificShareholders || this.filterByShareholderAppointedDate ||
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
                if( this.filterByCustomShareholders && this.filterSettings.start_percentage ){
                    url_append.start_percentage = this.filterSettings.start_percentage;
                }

                //  Set the filter end percentage (If required)
                if( this.filterByCustomShareholders && this.filterSettings.end_percentage ){
                    url_append.end_percentage = this.filterSettings.end_percentage;
                }

                //  Set the filter source of shares type (If required)
                if( this.filterByShareholderToSpecificNumber && this.filterSettings.source_of_shares_type ){
                    url_append.source_of_shares_type = this.filterSettings.source_of_shares_type;
                }

                //  Set the filter exact source of shares (If required)
                if( this.filterByShareholderToSpecificNumber && this.filterSettings.exact_source_of_shares ){
                    if( ['Exact'].includes(this.filterSettings.source_of_shares_type) ){
                        url_append.exact_source_of_shares = this.filterSettings.exact_source_of_shares;
                    }
                }

                //  Set the filter min source of shares (If required)
                if( this.filterByShareholderToSpecificNumber && this.filterSettings.min_source_of_shares ){
                    if( ['Minimum', 'Range'].includes(this.filterSettings.source_of_shares_type) ){
                        url_append.min_source_of_shares = this.filterSettings.min_source_of_shares;
                    }
                }

                //  Set the filter max source of shares (If required)
                if( this.filterByShareholderToSpecificNumber && this.filterSettings.max_source_of_shares ){
                    if( ['Maximum', 'Range'].includes(this.filterSettings.source_of_shares_type) ){
                        url_append.max_source_of_shares = this.filterSettings.max_source_of_shares;
                    }
                }






                //  Set the filter specific shareholders type (If required)
                if( this.filterByHasSpecificShareholders && this.filterSettings.specific_shareholders_type ){
                    url_append.specific_shareholders_type = this.filterSettings.specific_shareholders_type;
                }

                //  Set the filter equal shareholders (If required)
                if( this.filterByHasSpecificShareholders && this.filterSettings.equal_shareholders ){
                    if( ['Exact'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.equal_shareholders = this.filterSettings.equal_shareholders;
                    }
                }

                //  Set the filter min shareholders (If required)
                if( this.filterByHasSpecificShareholders && this.filterSettings.min_shareholders ){
                    if( ['Minimum', 'Range'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.min_shareholders = this.filterSettings.min_shareholders;
                    }
                }

                //  Set the filter max shareholders (If required)
                if( this.filterByHasSpecificShareholders && this.filterSettings.max_shareholders ){
                    if( ['Maximum', 'Range'].includes(this.filterSettings.specific_shareholders_type) ){
                        url_append.max_shareholders = this.filterSettings.max_shareholders;
                    }
                }

                //  Set the filter shareholder appointment start date
                if( this.filterByShareholderAppointedDate && this.filterSettings.shareholder_appointed_start_date ){
                    url_append.shareholder_appointed_start_date = this.filterSettings.shareholder_appointed_start_date;
                }

                //  Set the filter shareholder appointment end date
                if( this.filterByShareholderAppointedDate && this.filterSettings.shareholder_appointed_end_date ){
                    url_append.shareholder_appointed_end_date = this.filterSettings.shareholder_appointed_end_date;
                }

                //  Set the filter shareholder appointment start date
                if( this.filterByShareholderCeasedDate && this.filterSettings.shareholder_ceased_start_date ){
                    url_append.shareholder_ceased_start_date = this.filterSettings.shareholder_ceased_start_date;
                }

                //  Set the filter shareholder appointment end date
                if( this.filterByShareholderCeasedDate && this.filterSettings.shareholder_ceased_end_date ){
                    url_append.shareholder_ceased_end_date = this.filterSettings.shareholder_ceased_end_date;
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

        },
        methods:{
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
                //  Clear the search
                this.searchWord = '';

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
                            total_shareholder_occurances: ownership_bundle.total_shareholder_occurances,
                            is_shareholder_to_self: ownership_bundle.is_shareholder_to_self,

                            //  Company information
                            company_uin: ownership_bundle.company.uin,
                            company_name: ownership_bundle.company.name,
                            company_status: ownership_bundle.company.company_status,
                            is_imported_from_cipa: ownership_bundle.company.is_imported_from_cipa,

                            //  Company Attributes information
                            is_registered: ownership_bundle.company.is_registered.status,
                            is_cancelled: ownership_bundle.company.is_cancelled.status,
                            is_removed: ownership_bundle.company.is_removed.status,
                            is_not_found: ownership_bundle.company.is_not_found.status,
                            is_compliant: ownership_bundle.company.is_compliant,
                        };

                        //  If we have a shareholder
                        if( ownership_bundle.shareholder ){

                            //  Shareholder information
                            data.nominee = ownership_bundle.shareholder.nominee.name;
                            data.owner_type = ownership_bundle.shareholder.owner_type;
                            data.shareholder_appointment_date = ownership_bundle.shareholder.appointment_date;
                            data.shareholder_ceased_date = ownership_bundle.shareholder.ceased_date;


                            //  Additional Shareholder details (If Individual Shareholder)
                            if( ownership_bundle.shareholder.owner_type == 'individual' ){

                                if( ownership_bundle.shareholder.owner ){

                                    //  Residential Addresses
                                    data.residential_addresses = ownership_bundle.shareholder.owner.addresses.filter((address) => {
                                        return address.type == 'residential_address';
                                    }).map((residentialAddress) => {
                                        return residentialAddress.address_line;
                                    }).join(' | ');

                                    //  Postal Addresses
                                    data.postal_addresses = ownership_bundle.shareholder.owner.addresses.filter((address) => {
                                        return address.type == 'postal_address';
                                    }).map((postalAddress) => {
                                        return postalAddress.address_line;
                                    }).join(' | ');

                                }

                            }else if( ownership_bundle.shareholder.owner_type == 'company' ){

                                if( ownership_bundle.shareholder.owner ){

                                    data.shareholder_uin = ownership_bundle.shareholder.owner.uin;

                                }

                            }

                        }

                        //  If we have a director
                        if( ownership_bundle.director ){

                            //  Director information
                            data.director_appointment_date = ownership_bundle.director.appointment_date;
                            data.director_ceased_date = ownership_bundle.director.ceased_date;

                        }

                        data.is_director = ownership_bundle.director_id ? true : false;

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

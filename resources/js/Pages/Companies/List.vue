<template>

    <div>

        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Companies</span>
            </div>
            <div class="flex">
                <div :style="{ width: '350px' }" class="flex items-center bg-white w-full block px-4 py-2 mt-2 rounded text-gray-300 font-bold mr-5">
                    <span class="border-r inline-block mr-2 pr-2 text-2xl">{{ progress_totals.total_recently_updated_percentage }}%</span>
                    <span class="text-xs text-gray-500">
                        <span>{{ progress_totals.total_recently_updated }}</span>
                        <span class="mx-1">/</span>
                        <span>{{ progress_totals.total }}</span>
                        <span class="ml-1">Recently updated</span>
                        <span class="ml-1">(Last 24hrs)</span>
                    </span>
                </div>
                <div :style="{ width: '350px' }" class="bg-white w-full block px-4 py-2 mt-2 rounded text-green-400 font-bold">
                    <el-progress :percentage="progress_totals.total_imported_percentage" :stroke-width="8" color="#1e40af"></el-progress>
                    <span class="text-xs text-gray-500">
                        <span>{{ progress_totals.total_imported }}</span>
                        <span class="mx-1">/</span>
                        <span>{{ progress_totals.total }}</span>
                        <span class="ml-1">Imported from CIPA</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 my-5">

            <div class="flex items-start">
                <el-input placeholder="Search companies" prefix-icon="el-icon-search" size="small" v-model="searchWord" class="outline-none mr-2" :style="{ outline: 'none' }">
                    <template #prepend>
                        <el-select v-model="selectSearch" placeholder="Select" :style="{ width: '110px' }">
                            <el-option label="Stanbic" value="1"></el-option>
                            <el-option label="CIPA" value="2"></el-option>
                        </el-select>
                    </template>
                </el-input>
                <jet-button :height="32" @click="fetchCompanies()">Search</jet-button>
            </div>

            <div>
                <el-select v-model="selectedFilters" multiple placeholder="Filters" size="small" class="w-full"
                            @change="fetchCompanies()">
                    <el-option-group v-for="filter in filters" :key="filter.label" :label="filter.label">
                        <el-option v-for="option in filter.options"
                            :key="option.value"
                            :label="option.value"
                            :value="option.value">
                        </el-option>
                    </el-option-group>
                </el-select>
            </div>

            <div class="flex justify-end items-start">

                <div>
                    <el-popover placement="top" content="Add one or more clients" :width="200" trigger="hover">
                        <template #reference>
                            <jet-button :height="32" icon="el-icon-plus" class="mr-2">
                                <span>Add Client</span>
                            </jet-button>
                        </template>
                    </el-popover>
                </div>

                <div>
                    <el-popover placement="top" content="Request updated company records from CIPA" :width="200" trigger="hover">
                        <template #reference>
                            <jet-button :height="32" icon="el-icon-refresh" :disabled="multipleSelection.length == 0" class="mr-2">
                                <span>Update Companies</span>
                            </jet-button>
                        </template>
                    </el-popover>
                </div>

                <div>
                    <el-dropdown trigger="click" placement="bottom-end">
                        <jet-button :height="32">
                            <span class="el-dropdown-link">
                                <i class="el-icon-more-outline text-white"></i>
                            </span>
                        </jet-button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item icon="el-icon-refresh-right" @click="fetchCompanies()">Refresh</el-dropdown-item>
                                <el-dropdown-item divided>Sort By</el-dropdown-item>
                                <el-dropdown-item @click="toggleSelectedColumns()">Select Columns</el-dropdown-item>
                                <el-dropdown-item icon="el-icon-download" divided>Export Data</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>

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

        <div class="border">
            <el-table :data="tableData" @selection-change="handleSelectionChange">
                <el-table-column width="50" type="selection" fixed></el-table-column>
                <el-table-column v-if="selectedColumns.includes('compliant indicator')" min-width="40" fixed>
                    <template #default="scope">
                        <i v-if="updatingIndexes.includes(scope.$index)" class="el-icon-loading"></i>
                        <span v-else>
                            <el-popover v-if="scope.row.is_imported_from_cipa" placement="top-start" :title="scope.row.is_compliant.name" :width="190" trigger="hover">
                                <template #reference>
                                    <i v-if="scope.row.is_compliant.status" class="el-icon-circle-check text-green-400"></i>
                                    <i v-else class="el-icon-circle-close text-red-400"></i>
                                </template>
                                <div>
                                    <span v-if="scope.row.is_cancelled" class="text-red-500">Company is cancelled</span>
                                    <span v-if="scope.row.is_removed" class="text-red-500">Company is removed</span>
                                </div>
                            </el-popover>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('name')" min-width="200" prop="name" label="Name" fixed>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span :style="{ wordBreak: 'break-word !important' }">{{ scope.row.name }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('uin')" min-width="145" prop="uin" label="UIN">
                    <template #default="scope">
                        <span>{{ scope.row.uin }}</span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company status')" min-width="100" prop="company_status" label="Status">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span class="capitalize">
                                <el-tag v-if="scope.row.company_status == 'Registered'" size="small" type="success">{{ scope.row.company_status }}</el-tag>
                                <el-tag v-else size="small" type="danger">{{ scope.row.company_status }}</el-tag>
                            </span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('exempt')" min-width="80" prop="exempt" label="Exempt" align="center">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.exempt }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('foreign company')" min-width="150" prop="foreign_company" label="Foreign Company" align="center">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.foreign_company }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company type')" min-width="150" prop="company_type" label="Company Type">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.company_type }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company sub type')" min-width="150" prop="company_sub_type" label="Company SubType">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.company_sub_type }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('annual return filing month')" min-width="120" prop="annual_return_filing_month" label="Return Month" align="center">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <el-popover placement="top-start" :title="scope.row.annual_return_filing_month.long_name" :width="140" trigger="hover">
                                <template #reference>
                                    <span>{{ scope.row.annual_return_filing_month.short_name }}</span>
                                </template>
                            </el-popover>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('last updated')" width="100" prop="last_updated" label="Updated">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span class="italic">{{ scope.row.last_updated }}</span>
                        </span>
                        <span v-else>Never</span>
                    </template>
                </el-table-column>
                <el-table-column width="80" label="Action" fixed="right">
                    <template #default="scope">
                        <el-dropdown trigger="click">
                            <span class="el-dropdown-link">
                                <i class="el-icon-more-outline"></i>
                            </span>
                            <template #dropdown>
                                <el-dropdown-menu>
                                <el-dropdown-item icon="el-icon-view">View</el-dropdown-item>
                                <el-dropdown-item icon="el-icon-refresh" @click="requestCompanyUpdate(scope.row, scope.$index)">Update</el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </template>
                </el-table-column>
            </el-table>
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
            companies: {
                type: Object,
                default: null
            },
            company: {
                type: Object,
                default: null
            },
            progress_totals: {
                type: Array,
                default: function(){
                    return []
                }
            },
        },
        components:{ JetButton },
        data() {
            return {
                tableColumns: [
                    {
                        name: 'compliant indicator',
                        status: true
                    },
                    {
                        name: 'name',
                        status: true
                    },
                    {
                        name: 'uin',
                        status: true
                    },
                    {
                        name: 'company status',
                        status: true
                    },
                    {
                        name: 'exempt',
                        status: true
                    },
                    {
                        name: 'foreign company',
                        status: true
                    },
                    {
                        name: 'company type',
                        status: true
                    },
                    {
                        name: 'company sub type',
                        status: true
                    },
                    {
                        name: 'annual return filing month',
                        status: true
                    },
                    {
                        name: 'last updated',
                        status: true
                    }
                ],
                showSelectedColumns: false,
                selectedFilters: [],
                selectSearch: '1',
                filters: [
                    {
                        label: 'Company Status',
                        options: [
                            {
                                value: 'Registered'
                            },
                            {
                                value: 'Cancelled'
                            },
                            {
                                value: 'Removed'
                            }
                        ]
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
                        label: 'Excempt status',
                        options: [
                            {
                                value: 'Exempt'
                            },
                            {
                                value: 'Not Exempt'
                            }
                        ]
                    },
                    {
                        label: 'Foreign/Local Company',
                        options: [
                            {
                                value: 'Foreign company'
                            },
                            {
                                value: 'Local company'
                            }
                        ]
                    },
                    {
                        label: 'Company Type',
                        options: [
                            {
                                value: 'Private Company'
                            },
                            {
                                value: 'LLC Company'
                            }
                        ]
                    },
                    {
                        label: 'Sub Type',
                        options: [
                            {
                                value: 'Type A'
                            },
                            {
                                value: 'Type B'
                            }
                        ]
                    },
                    {
                        label: 'Importation Status',
                        options: [
                            {
                                value: 'Imported'
                            },
                            {
                                value: 'Not Imported'
                            }
                        ]
                    },
                    {
                        label: 'Update Status',
                        options: [
                            {
                                value: 'Recently Updated'
                            },
                            {
                                value: 'Outdated'
                            }
                        ]
                    }
                ],
                tableData: [],
                searchWord: '',
                multipleSelection: [],
                percentage: 30,
                updatingIndexes: []
            }
        },
        computed: {
            selectedColumns(){
                return this.tableColumns.filter(function(tableColumn){
                    return (tableColumn.status == true);
                }).map(function(tableColumn){
                    return tableColumn.name;
                });
            }
        },
        methods: {
            toggleSelection(rows) {
                if (rows) {
                    rows.forEach(row => {
                        this.$refs.multipleTable.toggleRowSelection(row);
                    });
                } else {
                    this.$refs.multipleTable.clearSelection();
                }
            },
            handleSelectionChange(val) {
                this.multipleSelection = val;
            },
            toggleSelectedColumns(){
                this.showSelectedColumns = !this.showSelectedColumns;
            },
            fetchCompanies(){

                console.log('fetchCompanies');
                var statuses = this.selectedFilters.join(',');

                var data = { search: this.searchWord, status: statuses };
                var options = { only: ['companies'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get('/companies', data , options);

                Inertia.on('success', (event) => {
                    console.log('On Success');
                    console.log(event.detail.page.props.companies.data);
                    this.setTableData(event.detail.page.props.companies.data);
                })


            },
            requestCompanyUpdate(company , index){

                //  Set the record index that is being updated
                this.updatingIndexes = [index];

                console.log('requestCompanyUpdate');

                axios.put(route('company-update', { company_id: company.id })).then(response=>{

                    var company = response.data;

                    this.companies.data[index] = company;

                    this.setTableData(this.companies.data);

                }).catch(error=>{

                    console.log(error.response.data.errors)

                }).finally(() => {

                    this.updatingIndexes = [];

                });

                /*

                console.log(company);
                console.log('id:'+company.id);

                var options = { only: ['company'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.put('/companies/'+company.id, null, options);

                Inertia.on('success', (event) => {
                    event.preventDefault();

                    console.log('On Success');
                    console.log(event);

                    var company = event.detail.page.props.company;

                    console.log('company');
                    console.log(company);

                    this.companies.data[index] = company;

                    this.setTableData(this.companies.data);
                })
                */
            },
            setTableData(companies){
                if( companies ){
                    this.tableData = companies.map(function(company){
                        return {
                            id: company.id,
                            uin: company.uin,
                            name: company.name,
                            company_status: company.company_status,
                            exempt: company.exempt.name,
                            foreign_company: company.foreign_company.name,
                            company_type: company.company_type || '...',
                            company_sub_type: company.company_sub_type || '...',
                            annual_return_filing_month: company.annual_return_filing_month,
                            details: company.details,

                            //  Attributes
                            is_registered: company.is_registered.status,
                            is_cancelled: company.is_cancelled.status,
                            is_removed: company.is_removed.status,
                            is_compliant: company.is_compliant,

                            is_imported_from_cipa: company.is_imported_from_cipa.status,
                            is_recently_updated_with_cipa: company.is_recently_updated_with_cipa.status,

                            last_updated: company.cipa_updated_human_time
                        }
                    });
                }
            }
        },
        created(){
            this.setTableData(this.companies.data);
        }
    }

</script>

<template>

    <div>

        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Companies</span>
            </div>
            <div class="flex">
                <div :style="{ width: '350px' }" class="flex items-center bg-white w-full block px-4 py-2 mt-2 rounded text-blue-400 font-bold mr-5">
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
                <jet-button :height="32">Search</jet-button>
            </div>

            <div>
                <el-select v-model="selectedFilters" multiple placeholder="Filters" size="small" class="w-full">
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
                <jet-button :height="32" icon="el-icon-refresh" :disabled="multipleSelection.length == 0">
                    <span>Update Companies</span>
                </jet-button>
            </div>

        </div>

        <div class="border">

            <el-table :data="tableData" @selection-change="handleSelectionChange">
                <el-table-column width="50" type="selection" fixed></el-table-column>
                <el-table-column width="40" fixed>
                    <template #default="scope">
                        <el-popover placement="top-start" :title="scope.row.is_compliant.name" :width="190" trigger="hover">
                            <template #reference>
                                <i v-if="scope.row.is_compliant.status" class="el-icon-circle-check text-green-400"></i>
                                <i v-else class="el-icon-circle-close text-red-400"></i>
                            </template>
                            <div>
                                <span v-if="scope.row.is_cancelled" class="text-red-500">Company is cancelled</span>
                                <span v-if="scope.row.is_removed" class="text-red-500">Company is removed</span>
                            </div>
                        </el-popover>
                    </template>
                </el-table-column>
                <el-table-column width="200" prop="name" label="Name" fixed>
                    <template #default="scope">
                        <span :style="{ wordBreak: 'break-word !important' }">{{ scope.row.name }}</span>
                    </template>
                </el-table-column>
                <el-table-column width="145" prop="uin" label="UIN"></el-table-column>
                <el-table-column width="100" prop="company_status" label="Status">
                    <template #default="scope">
                        <span class="capitalize">
                            <el-tag v-if="scope.row.company_status == 'Registered'" size="small" type="success">{{ scope.row.company_status }}</el-tag>
                            <el-tag v-else size="small" type="danger">{{ scope.row.company_status }}</el-tag>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column width="80" prop="exempt" label="Exempt" align="center"></el-table-column>
                <el-table-column width="150" prop="foreign_company" label="Foreign Company" align="center"></el-table-column>
                <el-table-column width="150" prop="company_type" label="Company Type"></el-table-column>
                <el-table-column width="150" prop="company_sub_type" label="Company SubType"></el-table-column>
                <el-table-column width="120" prop="return_month" label="Return Month" align="center">
                    <template #default="scope">
                        <el-popover placement="top-start" :title="scope.row.return_month.long_name" :width="140" trigger="hover">
                            <template #reference>
                                <span>{{ scope.row.return_month.short_name }}</span>
                            </template>
                        </el-popover>
                    </template>
                </el-table-column>
                <el-table-column width="100" prop="last_updated" label="Updated">
                    <template #default="scope">
                        <span class="italic">{{ scope.row.last_updated }}</span>
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
                                <el-dropdown-item icon="el-icon-refresh" @click="handleEdit(scope.$index, scope.row)">Update</el-dropdown-item>
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

    import JetButton from '@/Jetstream/Button'

    export default {
        props: {
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
        components:{ JetButton },
        data() {
            return {
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
                    }
                ],
                tableData: [],
                searchWord: '',
                multipleSelection: [],
                percentage: 30
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
            }
        },
        created(){
            this.tableData = this.companies.map(function(company){
                return {
                    id: company.id,
                    uin: company.uin,
                    name: company.name,
                    company_status: company.company_status,
                    exempt: company.exempt.name,
                    foreign_company: company.foreign_company.name,
                    company_type: company.company_type || '...',
                    company_sub_type: company.company_sub_type || '...',
                    return_month: company.return_month,
                    details: company.details,
                    is_registered: company.is_registered.status,
                    is_cancelled: company.is_cancelled.status,
                    is_removed: company.is_removed.status,
                    is_compliant: company.is_compliant,

                    is_imported_from_cipa: company.is_imported_from_cipa.status,
                    is_recently_updated_with_cipa: company.is_recently_updated_with_cipa.status,

                    last_updated: company.cipa_updated_human_time,
                }
            });
        }
    }

</script>

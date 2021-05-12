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
                        <span class="block">
                            <span>{{ progress_totals.total_recently_updated }}</span>
                            <span class="mx-1">/</span>
                            <span>{{ progress_totals.total }}</span>
                            <span class="ml-1">Recently updated</span>
                        </span>
                        <span class="block">(Last 24hrs)</span>
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
                <el-input v-model="searchWord" :disabled="isBulkUpdating" placeholder="Search companies" prefix-icon="el-icon-search"
                          size="small" class="outline-none mr-2" :style="{ outline: 'none' }" clearable @keyup.enter="fetchCompanies()"
                          @clear="fetchCompanies()">
                    <template #prepend>
                        <el-select v-model="searchType" placeholder="Select" :style="{ width: '110px' }">
                            <el-option v-for="(searchType, index) in searchTypes" :key="index" :label="searchType.name" :value="searchType.value"></el-option>
                        </el-select>
                    </template>
                </el-input>
                <jet-button :height="32" @click="fetchCompanies()" :disabled="isBulkUpdating">Search</jet-button>
            </div>

            <div>
                <el-select v-model="selectedFilters" multiple placeholder="Filters" size="small" class="w-full"
                            @change="handleFilter()" :disabled="isBulkUpdating">
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
                    <el-popover placement="top" content="Add one or more clients" :width="200"
                                trigger="hover" :key="addClientPopoverKey">
                        <template #reference>
                            <jet-button :height="32" icon="el-icon-plus" class="mr-2" :disabled="isBulkUpdating || addClient"
                                        @click="openAddClient">
                                <span>Add Client</span>
                            </jet-button>
                        </template>
                    </el-popover>
                </div>

                <div class="flex">
                    <el-popover placement="top" content="Request updated company records from CIPA"
                                :width="200" trigger="hover" :key="updateCompaniesPopoverKey">
                        <template #reference>

                            <div>

                                <jet-button @click="bulkRequestCompanyUpdate()" :disabled="multipleSelection.length == 0 || isBulkUpdating"
                                            :height="32" icon="el-icon-refresh" class="rounded-r-none">
                                    <span>Update Companies</span>
                                </jet-button>

                            </div>

                        </template>
                    </el-popover>

                    <el-dropdown trigger="click" :class="['inline-flex items-center py-2 bg-blue-800 border border-transparent rounded-r-sm font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:shadow-outline-blue disabled:opacity-25 transition pr-1 mr-2', multipleSelection.length == 0 || isBulkUpdating ? 'opacity-25' : '']" placement="bottom-end">

                        <span class="el-dropdown-link">
                            <i class="el-icon-arrow-down el-icon--right text-white"></i>
                        </span>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item :disabled="multipleSelection.length == 0 || isBulkUpdating"  @click="bulkRequestCompanyUpdate(true)">One by one</el-dropdown-item>
                                <el-dropdown-item :disabled="multipleSelection.length == 0 || isBulkUpdating"  @click="bulkRequestCompanyUpdate(false)">Same time</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
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
                                <el-dropdown-item divided @click="showSortBy = true">Sort By</el-dropdown-item>
                                <el-dropdown-item @click="toggleSelectedColumns()">Select Columns</el-dropdown-item>
                                <el-dropdown-item icon="el-icon-download" divided>
                                    <a :href="exportCompaniesUrl">Export to Excel</a>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>

            </div>

        </div>

        <div v-if="addClient" class="bg-gray-50 border-b-2 border-blue-100 mb-4 px-5 py-5">

            <div class="flex justify-between">
                <h4 class="font-bold text-gray-500">Add Client</h4>
                <jet-button @click="addClient = false">
                    <i class="el-icon-close text-white"></i>
                </jet-button>
            </div>

            <form @submit.prevent="submit" class="my-5">

                <input type="file" :disabled="form.processing" @input="form.excelFile = $event.target.files[0]" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="hover:bg-gray-100 active:bg-gray-200 text-blue-900 text-xs border border-dashed cursor-pointer p-2 mr-2" />

                <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-blue-800 border border-transparent rounded-sm font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:shadow-outline-blue disabled:opacity-25 transition">
                    <i v-if="form.processing" class="el-icon-loading mr-1"></i>
                    <span>Submit</span>
                </button>

            </form>

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

                    <div v-if="filterByDissolutionDate">
                        <span class="block py-2 mb-2">Dissolution Date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.dissolution_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchCompanies()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.dissolution_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchCompanies()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByIncorporationDate">
                        <span class="block py-2 mb-2">Incorporation Date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.incorporation_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchCompanies()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.incorporation_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchCompanies()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByReRegistrationDate">
                        <span class="block py-2 mb-2">Re-registration Date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.re_registration_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchCompanies()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.re_registration_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchCompanies()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByARLastFiledDate">
                        <span class="block py-2 mb-2">Annual Return Last Filed Date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.annual_return_last_filed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchCompanies()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.annual_return_last_filed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchCompanies()"></el-date-picker>
                        </div>
                    </div>

                    <div v-if="filterByARLastFiledDate">
                        <span class="block py-2 mb-2">Annual Return Last Filed Date</span>
                        <div class="flex items-center">
                            <el-date-picker v-model="filterSettings.annual_return_last_filed_start_date" type="date" size="small" format="DD MMM YYYY" placeholder="Start date" @change="fetchCompanies()"></el-date-picker>
                            <span>-</span>
                            <el-date-picker v-model="filterSettings.annual_return_last_filed_end_date" type="date" size="small" format="DD MMM YYYY" placeholder="End date" @change="fetchCompanies()"></el-date-picker>
                        </div>
                    </div>

                <div v-if="filterByARFillingMonth">
                    <span class="block py-2 mb-2">Annual Return Filling Month</span>
                    <el-select v-model="filterSettings.annual_return_filing_month" placeholder="Select" @change="fetchCompanies()">
                        <el-option
                            v-for="fillingMonthOption in fillingMonthOptions"
                            :key="fillingMonthOption.value"
                            :label="fillingMonthOption.name"
                            :value="fillingMonthOption.value">
                        </el-option>
                    </el-select>

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

                <el-select v-model="selectedSortBy" placeholder="Select" class="mr-4" @change="fetchCompanies()">
                    <el-option
                        v-for="sortByOption in sortByOptions"
                        :key="sortByOption.value"
                        :label="sortByOption.name"
                        :value="sortByOption.value">
                    </el-option>
                </el-select>

                <el-select v-model="selectedSortByType" placeholder="Select" @change="fetchCompanies()">
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

        <div v-if="isBulkUpdating" class="overflow-auto mb-2">

            <div :style="{ width: '380px' }" class="bg-white w-full block px-4 py-2 mt-2 rounded text-green-400 font-bold float-right">
                <el-progress :percentage="bulkUpdateProgress.percentage" :stroke-width="8" color="#1e40af"></el-progress>
                <span class="text-xs text-gray-500">
                    <i class="el-icon-loading mr-1"></i>
                    <span class="mr-1">Updating</span>
                    <span>{{ bulkUpdateProgress.current }}</span>
                    <span class="mx-1">/</span>
                    <span>{{ bulkUpdateProgress.total }}</span>
                </span>
            </div>

        </div>

        <div class="grid grid-cols-3 border-b border-t my-3">

            <div class="font-bold text-gray-500 text-sm mt-2">
                <span class="mr-2">Sort By:</span>
                <span class="text-green-500">{{ selectedSortByName }}</span>
                <span class="italic font-light"> - {{ selectedSortByTypeName }}</span>
            </div>

            <div class="font-bold text-gray-500 text-sm mt-2">
                <template v-if ="searchWord">
                    <span class="mr-2">Search:</span>
                    <span class="text-green-500">{{ searchWord }}</span>
                    <span class="font-light mx-1">within</span>
                    <span class="text-green-500">{{ selectedSearchTypeName }}</span>
                </template>
            </div>

            <div class="overflow-auto">
                <div class="float-right font-bold text-gray-500 text-sm">
                    <span class="mr-2">Found</span>
                    <span class="mr-2 text-2xl text-green-500">{{ companies.total }}</span>
                    <span>{{ companies.total == 1 ? 'company' : 'companies' }}</span>
                </div>
            </div>

        </div>

        <div class="border">
            <el-table ref="companiesTable" :data="tableData" @selection-change="handleSelectionChange">
                <el-table-column width="50" type="selection" fixed :selectable="checkIfselectableRow"></el-table-column>
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
                                    <span v-else-if="scope.row.is_removed" class="text-red-500">Company is removed</span>
                                    <span v-else-if="scope.row.is_not_found" class="text-red-500">Company not found</span>
                                </div>
                            </el-popover>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('name')" min-width="200" prop="name" label="Name" sortable fixed>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span :style="{ wordBreak: 'break-word !important' }">{{ scope.row.name }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column type="expand">
                    <template #default="scope">

                        <div :style="{ maxWidth: '1200px' }">

                            <div class="grid grid-cols-3 gap-4 my-2">

                                <div>
                                    <span class="block font-bold text-gray-500 py-2 mb-2">Directors</span>

                                    <div v-for="(director, index) in scope.row.directors" :key="index"
                                         class="bg-white rounded-sm shadow-md border p-4 mb-2">
                                        <div class="flex justify-between mb-2 pb-2 border-dotted border-b">
                                            <a :href="route('ownership-bundles', {search: director.individual.full_name, search_type: 'any'})" class="font-bold text-blue-800 underline cursor-pointer">
                                                <span class="mr-1">{{ director.individual.full_name }}</span>
                                            </a>
                                            <span class="text-blue-800 text-xs underline cursor-pointer" :style="{ minWidth: '65px' }">Show More</span>
                                        </div>
                                        <span v-if="director.individual.residential_address_lines">{{ director.individual.residential_address_lines }}</span>
                                    </div>

                                </div>

                                <div>
                                    <span class="block font-bold text-gray-500 py-2 mb-2">Shareholders</span>
                                    <div v-for="(shareholder, index) in scope.row.shareholders" :key="index"
                                         class="bg-white rounded-sm shadow-md border p-4 mb-2">

                                        <template v-if="shareholder.owner.resource_type == 'individual'">
                                            <div class="flex justify-between mb-2 pb-2 border-dotted border-b">
                                                <a :href="route('ownership-bundles', {search: shareholder.owner.full_name, search_type: 'any'})" class="font-bold text-blue-800 underline cursor-pointer">
                                                    <span class="mr-1">{{ shareholder.owner.full_name }}</span>
                                                </a>
                                                <span class="text-blue-800 text-xs underline cursor-pointer" :style="{ minWidth: '65px' }">Show More</span>
                                            </div>
                                            <span v-if="shareholder.owner.residential_address_lines">{{ shareholder.owner.residential_address_lines }}</span>
                                        </template>

                                        <template v-if="shareholder.owner.resource_type == 'company'">
                                            <div class="flex justify-between mb-2 pb-2 border-dotted border-b">
                                                <span class="font-bold">
                                                    <span v-if="shareholder.owner.uin" class="mr-1">{{ shareholder.owner.name }}</span>
                                                    <a v-else :href="route('ownership-bundles', {search: shareholder.owner.name, search_type: 'any'})" class="text-blue-800 underline cursor-pointer">{{ shareholder.owner.name }}</a>
                                                    <span v-if="shareholder.owner.uin">
                                                        (<a :href="route('ownership-bundles', {search: shareholder.owner.uin, search_type: 'any'})" class="text-blue-800 text-xs underline cursor-pointer">{{ shareholder.owner.uin }}</a>)
                                                    </span>
                                                </span>
                                                <span class="text-blue-800 text-xs underline cursor-pointer" :style="{ minWidth: '65px' }">Show More</span>
                                            </div>
                                            <span>Tlokweng Masetlheng Ward, Tlokweng, Botswana</span>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <span class="block font-bold text-gray-500 py-2 mb-2">Share Allocation</span>

                                    <div class="bg-white rounded-sm shadow-md border mb-2">

                                        <div class="bg-gray-100 grid grid-cols-2 py-2 px-4 mb-2">

                                            <div>
                                                <span class="font-bold">Shareholder Name</span>
                                            </div>

                                            <div>
                                                <span class="font-bold">Number of Shares</span>
                                            </div>

                                        </div>

                                        <div v-for="(ownership_bundle, index) in scope.row.ownership_bundles" :key="index" class="grid grid-cols-2 px-2 px-4 mb-2">
                                            <span class="block">{{ ownership_bundle.owners.owner.shareholder_name }}</span>
                                            <span class="block">{{ ownership_bundle.number_of_shares }}</span>
                                        </div>

                                    </div>

                                </div>

                                <div>
                                    <span class="block font-bold text-gray-500 py-2 mb-2">Secretaries</span>
                                    <div v-for="(secretary, index) in scope.row.secretaries" :key="index"
                                         class="bg-white rounded-sm shadow-md border p-4 mb-2">

                                        <template v-if="secretary.individual_secretary">
                                            <div class="flex justify-between mb-2 pb-2 border-dotted border-b">
                                                <a :href="route('ownership-bundles', {search: secretary.individual_secretary.individual_name.first_name, search_type: 'any'})" class="text-blue-800 text-xs underline cursor-pointer">
                                                    <span class="mr-1">{{ secretary.individual_secretary.individual_name.first_name }}</span>
                                                    <span v-if="secretary.individual_secretary.individual_name.middle_names" class="mr-1">{{ secretary.individual_secretary.individual_name.middle_names }}</span>
                                                    <span>{{ secretary.individual_secretary.individual_name.last_name }}</span>
                                                </a>
                                                <span class="text-blue-800 text-xs underline cursor-pointer" :style="{ minWidth: '65px' }">Show More</span>
                                            </div>
                                            <span>Tlokweng Masetlheng Ward, Tlokweng, Botswana</span>

                                        </template>

                                        <template v-else-if="secretary.entity_secretary">
                                            <div class="flex justify-between mb-2 pb-2 border-dotted border-b">
                                                <span class="font-bold">
                                                    <span v-if="secretary.entity_secretary.uin" class="mr-1">{{ secretary.entity_secretary.company_name }}</span>
                                                    <a v-else href="/directors-and-secretarys" class="text-blue-800 underline cursor-pointer">{{ secretary.entity_secretary.company_name }}</a>
                                                    <span v-if="secretary.entity_secretary.uin">
                                                        (<a href="/directors-and-secretarys" class="text-blue-800 text-xs underline cursor-pointer">{{ secretary.entity_secretary.uin }}</a>)
                                                    </span>
                                                </span>
                                                <span class="text-blue-800 text-xs underline cursor-pointer" :style="{ minWidth: '65px' }">Show More</span>
                                            </div>
                                            <span>Tlokweng Masetlheng Ward, Tlokweng, Botswana</span>
                                        </template>

                                    </div>
                                </div>

                            </div>
                        </div>

                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('uin')" min-width="145" prop="uin" label="UIN">
                    <template #default="scope">
                        <span>{{ scope.row.uin }}</span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company status')" min-width="100" prop="company_status" label="Status" sortable>
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
                <el-table-column v-if="selectedColumns.includes('exempt')" min-width="110" prop="exempt" label="Exempt" align="center" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.exempt }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('foreign company')" min-width="160" prop="foreign_company" label="Foreign company" align="center" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.foreign_company }}</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('old company number')" min-width="160" prop="old_company_number" label="Old company #" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.old_company_number }}</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column v-if="selectedColumns.includes('registered office address')" min-width="250" prop="registered_office_address" label="Registered office address" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.registered_office_address }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('postal address')" min-width="250" prop="postal_address" label="Postal address" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.registered_office_address }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('principal place of business')" min-width="250" prop="principal_place_of_business" label="Principal place of business" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.principal_place_of_business }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('incorporation date')" min-width="170" prop="incorporation_date" label="Incorporation date" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.incorporation_date }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('re-registration date')" min-width="180" prop="re_registration_date" label="Re-registration date" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.re_registration_date }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('dissolution date')" min-width="160" prop="dissolution_date" label="Dissolution date" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.dissolution_date }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('own constitution')" min-width="160" prop="own_constitution_yn" label="Own constitution" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.own_constitution_yn }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('business sector')" min-width="150" prop="business_sector" label="Business sector">
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.business_sector }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company type')" min-width="150" prop="company_type" label="Company type" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.company_type }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('company sub type')" min-width="170" prop="company_sub_type" label="Company sub-type" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.company_sub_type }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('annual return filing month')" min-width="140" prop="annual_return_filing_month" label="Return month" align="center" sortable>
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
                <el-table-column v-if="selectedColumns.includes('annual return last filed date')" min-width="170" prop="annual_return_last_filed_date" label="A-R last filed date" align="center" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span>{{ scope.row.annual_return_last_filed_date }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column v-if="selectedColumns.includes('last updated')" width="110" prop="last_updated" label="Updated" align="center" sortable>
                    <template #default="scope">
                        <el-skeleton-item v-if="updatingIndexes.includes(scope.$index)" variant="text" />
                        <span v-else-if="scope.row.is_imported_from_cipa">
                            <span class="italic">{{ scope.row.last_updated }}</span>
                        </span>
                        <span v-else>Never</span>
                    </template>
                </el-table-column>
                <el-table-column width="80" label="Action" fixed="right" align="center">
                    <template #default="scope">
                        <el-dropdown trigger="click" placement="bottom-end" class="w-full">
                            <span class="el-dropdown-link block m-auto w-min">
                                <i class="el-icon-more-outline"></i>
                            </span>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item icon="el-icon-view" :disabled="isBulkUpdating">View</el-dropdown-item>
                                    <el-dropdown-item icon="el-icon-refresh" :disabled="isBulkUpdating" @click="requestCompanyUpdate(scope.row, scope.$index)">Update</el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </template>
                </el-table-column>
            </el-table>
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="companies.per_page" :page-sizes="[5, 10, 15, 20]"
                            :total="companies.total" :page-count="companies.total" :current-page="companies.current_page"
                            :pager-count="11" background @size-change="changePageSize" @current-change="changePage">
                </el-pagination>
            </div>
        </div>

    </div>

</template>

<script>

    import moment from 'moment'
    import Dashboard from '@/Pages/Dashboard'
    import JetButton from '@/Jetstream/Button'
    import { Inertia } from '@inertiajs/inertia'

    import { useForm } from '@inertiajs/inertia-vue3'
    import { forEach, round } from 'lodash'

    export default {
        // Inertia Form functionality
        setup () {
            const form = useForm({
                excelFile: null,
            })

            function submit() {
                form.post(route('companies-import'));
            }

            return { form, submit }
        },

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
                type: Object,
                default: null
            },
            dynamic_filter_options: {
                type: Object,
                default: null
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
                        name: 'incorporation date',
                        status: true
                    },
                    {
                        name: 're-registration date',
                        status: true
                    },
                    {
                        name: 'old company number',
                        status: true
                    },
                    {
                        name: 'registered office address',
                        status: true
                    },
                    {
                        name: 'postal address',
                        status: true
                    },
                    {
                        name: 'principal place of business',
                        status: true
                    },
                    {
                        name: 'dissolution date',
                        status: true
                    },
                    {
                        name: 'own constitution',
                        status: true
                    },
                    {
                        name: 'business sector',
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
                        name: 'annual return last filed date',
                        status: true
                    },
                    {
                        name: 'last updated',
                        status: true
                    }
                ],
                addClient: false,
                showSelectedColumns: false,
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
                    },
                    {
                        label: 'Dates',
                        options: [
                            {
                                value: 'Dissolution Date'
                            },
                            {
                                value: 'Incorporation Date'
                            },
                            {
                                value: 'Re-registration Date'
                            },
                            {
                                value: 'A-R Last Filed Date'
                            },
                            {
                                value: 'A-R Filling Month'
                            },
                            {
                                value: 'Imported Date'
                            },
                            {
                                value: 'Updated Date'
                            },
                        ]
                    }
                ],
                filterSettings: {
                    dissolution_start_date: null,
                    dissolution_end_date: null,

                    incorporation_start_date: null,
                    incorporation_end_date: null,

                    re_registration_start_date: null,
                    re_registration_end_date: null,

                    annual_return_last_filed_start_date: null,
                    annual_return_last_filed_end_date: null,

                    annual_return_filing_month: null
                },
                showSortBy: false,
                selectedSortBy: 'incorporation_date',
                selectedSortByType: 'desc',
                sortByOptions: [
                    {
                        name: 'Updated Date',
                        value: 'cipa_updated_at'
                    },
                    {
                        name: 'Incorporation Date',
                        value: 'incorporation_date'
                    },
                    {
                        name: 'Re-registration Date',
                        value: 're_registration_date'
                    },
                    {
                        name: 'Annual Return Last Filed Date',
                        value: 'annual_return_last_filed_date'
                    },
                    {
                        name: 'Dissolution Date',
                        value: 'dissolution_date'
                    }
                ],
                sortByTypeOptions: [
                    {
                        name: 'Latest first',
                        value: 'desc',
                    },
                    {
                        name: 'Oldest first',
                        value: 'asc'
                    }
                ],
                minizeFilterSettings: false,
                tableData: [],
                searchWord: '',
                searchType: 'internal',
                searchTypes: [
                    {
                        name: 'Stanbic',
                        value: 'internal'
                    },
                    {
                        name: 'CIPA',
                        value: 'external'
                    }
                ],
                multipleSelection: [],
                percentage: 30,
                updatingIndexes: [],
                stopBulkUpdateStatus: false,
                bulkUpdateProgress: null,
                addClientPopoverKey: 1,
                updateCompaniesPopoverKey: 1,
                currentPage: this.companies.current_page,
                perPage: this.companies.per_page
            }
        },
        computed: {
            selectedColumns(){
                return this.tableColumns.filter(function(tableColumn){
                    return (tableColumn.status == true);
                }).map(function(tableColumn){
                    return tableColumn.name;
                });
            },
            isBulkUpdating(){
                return (this.bulkUpdateProgress != null);
            },
            selectedSortByName(){
                return this.sortByOptions.find(sortByOption => sortByOption.value == this.selectedSortBy).name;
            },
            selectedSortByTypeName(){
                return this.sortByTypeOptions.find(sortByTypeOption => sortByTypeOption.value == this.selectedSortByType).name;
            },
            selectedSearchTypeName(){
                return this.searchTypes.find(searchType => searchType.value == this.searchType).name;
            },
            fillingMonthOptions(){

                var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

                return months.map((month, index) => {
                    return {
                        name: month,
                        value: (index + 1)
                    }
                });
            },
            showFilterSettings(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return [
                            'Imported Date', 'Updated Date', 'Dissolution Date', 'Incorporation Date',
                            'Re-registration Date', 'A-R Last Filed Date', 'A-R Filling Month',
                        ].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByDissolutionDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['Dissolution Date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByIncorporationDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['Incorporation Date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByReRegistrationDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['Re-registration Date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByARLastFiledDate(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['A-R Last Filed Date'].includes(selectedFilter);
                }).length ? true : false;
            },
            filterByARFillingMonth(){
                return this.selectedFilters.filter((selectedFilter) => {
                    return ['A-R Filling Month'].includes(selectedFilter);
                }).length ? true : false;
            },
            companiesUrlQueryParamsAsObject(){

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

                //  Set the filter dissolution start date (If required)
                if( this.filterByDissolutionDate && this.filterSettings.dissolution_start_date ){
                    url_append.dissolution_start_date = moment(this.filterSettings.dissolution_start_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter dissolution end date (If required)
                if( this.filterByDissolutionDate && this.filterSettings.dissolution_end_date ){
                    url_append.dissolution_end_date = moment(this.filterSettings.dissolution_end_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter incorporation start date (If required)
                if( this.filterByIncorporationDate && this.filterSettings.incorporation_start_date ){
                    url_append.incorporation_start_date = moment(this.filterSettings.incorporation_start_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter incorporation end date (If required)
                if( this.filterByIncorporationDate && this.filterSettings.incorporation_end_date ){
                    url_append.incorporation_end_date = moment(this.filterSettings.incorporation_end_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter re_registration start date (If required)
                if( this.filterByReRegistrationDate && this.filterSettings.re_registration_start_date ){
                    url_append.re_registration_start_date = moment(this.filterSettings.re_registration_start_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter re_registration end date (If required)
                if( this.filterByReRegistrationDate && this.filterSettings.re_registration_end_date ){
                    url_append.re_registration_end_date = moment(this.filterSettings.re_registration_end_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter annual return last filed start date (If required)
                if( this.filterByARLastFiledDate && this.filterSettings.annual_return_last_filed_start_date ){
                    url_append.annual_return_last_filed_start_date = moment(this.filterSettings.annual_return_last_filed_start_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter annual return last filed end date (If required)
                if( this.filterByARLastFiledDate && this.filterSettings.annual_return_last_filed_end_date ){
                    url_append.annual_return_last_filed_end_date = moment(this.filterSettings.annual_return_last_filed_end_date).format('YYYY-MM-DD 00:00:00');
                }

                //  Set the filter annual return filled month (If required)
                if( this.filterByARFillingMonth && this.filterSettings.annual_return_filing_month ){
                    url_append.annual_return_filing_month = this.filterSettings.annual_return_filing_month;
                }

                url_append.per_page = this.perPage;

                url_append.page = this.currentPage;

                url_append.sort_by = this.selectedSortBy;

                url_append.sort_by_type = this.selectedSortByType;

                return url_append;
            },
            companiesUrlQueryParamsAsString(){

                if( _.isEmpty( this.companiesUrlQueryParamsAsObject ) ){

                    return '';

                }else{

                    var string = '?';
                    var field_names = Object.keys(this.companiesUrlQueryParamsAsObject);
                    var field_values = Object.values(this.companiesUrlQueryParamsAsObject);

                    for (let index = 0; index < field_names.length; index++) {

                        string += field_names[index]+'='+field_values[index];

                        if( (index + 1) != field_names.length ){
                            string += ',';
                        }

                    }

                    return string

                }
            },
            exportCompaniesUrl(){
                return route('companies-export') + this.companiesUrlQueryParamsAsString;
            }
        },
        methods: {
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
            handleSelectionChange(val) {
                this.multipleSelection = val;
            },
            toggleSelectedColumns(){
                this.showSelectedColumns = !this.showSelectedColumns;
            },
            checkIfselectableRow(row, index){
                return !this.isBulkUpdating;
            },
            handleFilter(){
                //  Clear the search
                this.searchWord = '';

                this.fetchCompanies();
            },
            fetchCompanies(){

                var options = { only: ['companies', 'progress_totals'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get(route('companies'), this.companiesUrlQueryParamsAsObject, options);

                Inertia.on('success', (event) => {
                    this.setTableData(event.detail.page.props.companies.data);
                })

            },
            openAddClient(){
                ++this.addClientPopoverKey;
                this.addClient = true;
            },
            changePage(val) {
                this.currentPage = val;

                this.fetchCompanies();
            },
            changePageSize(val) {
                this.perPage = val;

                this.fetchCompanies();
            },
            async bulkRequestCompanyUpdate(waitForEachCall = true){

                /** Force close incase the popover is still showing. When we hover over the
                 *  button while its still not disabled, this triggers the the popover to
                 *  show. However when we click the button it will instantly disable and
                 *  the popover remains showing instead of hiding. We must force the
                 *  close by re-rendering the popover.
                 */
                ++this.updateCompaniesPopoverKey;

                var selectedRows = this.multipleSelection;

                //  Foreach selected company
                for (let index = 0; index < selectedRows.length; index++) {

                    var company = selectedRows[index];

                    //  If we would like to stop the bulk update
                    if( this.stopBulkUpdateStatus == true ){

                        //  Reset the stop bulk update status
                        this.stopBulkUpdateStatus = false;

                        //  Stop loop
                        break;

                    }else{

                        this.bulkUpdateProgress = {
                            current: (index + 1),
                            total:  selectedRows.length,
                            percentage: round((((index + 1) / selectedRows.length) * 100), 0)
                        }

                        var matching_index = this.companies.data.findIndex(function(currentValue){
                            return (currentValue.id == company.id);
                        });

                        if(matching_index >= 0){

                            //  If we should wait for this call before we can run the next call
                            if( waitForEachCall == true ){

                                //  Request company update (Wait for Call to complete)
                                await this.requestCompanyUpdate(company , matching_index);

                            }else{

                                //  Request company update (Don't wait for Call to complete)
                                this.requestCompanyUpdate(company , matching_index);
                            }

                            //  Unselect the current row
                            this.$refs.companiesTable.toggleRowSelection(company, false);

                        }

                    }

                }

                //  Reset multiple selection
                this.multipleSelection = [];

                //  Reset bulk update progress
                this.bulkUpdateProgress = null;

            },
            async requestCompanyUpdate(company , index){

                //  Set the record index that is being updated
                this.updatingIndexes.push(index);

                await axios.put(route('company-update', { company_id: company.id })).then(response=>{

                    var company = response.data;

                    this.companies.data[index] = company;

                    this.setTableData(this.companies.data);

                }).catch(error=>{

                    console.log(error.response.data.errors)

                }).finally(() => {

                    //  Remove the record index that is being updated
                    this.updatingIndexes = this.updatingIndexes.filter(function(currentValue){
                        return (currentValue != index);
                    });

                });
            },
            setTableData(companies){
                if( companies ){
                    this.tableData = companies.map(function(company){
                        return {
                            id: company.id,
                            uin: company.uin,
                            name: company.name,
                            info: company.info,
                            company_status: company.company_status,
                            exempt: company.exempt.name,
                            foreign_company: company.foreign_company.name,
                            company_type: company.company_type,
                            company_sub_type: company.company_sub_type,
                            incorporation_date: company.incorporation_date,
                            re_registration_date: company.re_registration_date,
                            old_company_number: company.old_company_number,
                            dissolution_date: company.dissolution_date,
                            own_constitution_yn: company.own_constitution_yn.name,
                            business_sector: company.business_sector,
                            annual_return_filing_month: company.annual_return_filing_month,
                            annual_return_last_filed_date: company.annual_return_last_filed_date,
                            details: company.details,

                            registered_office_address: company.registered_office_address,
                            postal_address: company.postal_address,
                            principal_place_of_business: company.principal_place_of_business,

                            ownership_bundles: company.ownership_bundles,
                            shareholders: company.shareholders,
                            secretaries: company.secretaries,
                            directors: company.directors,


                            //  'registered_office_address', 'postal_address', 'principal_place_of_business',
                            //  'ownership_bundles', 'directors', 'shareholders', 'secretary'

                            //  Attributes
                            is_registered: company.is_registered.status,
                            is_cancelled: company.is_cancelled.status,
                            is_removed: company.is_removed.status,
                            is_not_found: company.is_not_found.status,
                            is_compliant: company.is_compliant,

                            is_imported_from_cipa: company.is_imported_from_cipa.status,
                            is_recently_updated_with_cipa: company.is_recently_updated_with_cipa.status,

                            last_updated: company.cipa_updated_human_time
                        }
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
            },
        },
        created(){
            this.setSearchFromUrl();
            this.setSearchTypeFromUrl();
            this.setFiltersFromUrl();
            this.setFilterSettingsFromUrl();
            this.setSortByFromUrl();
            this.setSortByTypeFromUrl();
            this.setTableData(this.companies.data);
        }
    }

</script>

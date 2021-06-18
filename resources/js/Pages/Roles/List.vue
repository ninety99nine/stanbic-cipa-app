<template>

    <div>

        <!-- Title -->
        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Roles</span>
            </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="grid grid-cols-3 gap-4 my-5">

            <!-- Search Bar -->
            <div class="flex items-start">
                <el-input v-model="searchWord" placeholder="Search roles by name" prefix-icon="el-icon-search"
                          size="small" class="mr-2" clearable @keyup.enter="fetchRoles()" @clear="fetchRoles()">
                </el-input>
                <jet-button :height="32" @click="fetchRoles()">Search</jet-button>
            </div>

            <!-- Select Filter -->
            <div>

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

            <div v-if="$page.props.can.includes('create users')" class="overflow-auto">

                <!-- Create Role Button -->
                <jet-button :height="32" icon="el-icon-plus" class="float-right" @click.prevent="openModal()">
                    <span>Create Role</span>
                </jet-button>

            </div>

        </div>

        <!-- Sort, Search & Results Summary -->
        <div class="grid grid-cols-3 border-b border-t my-3">

            <div class="font-bold text-gray-500 text-sm mt-2">
                <span class="mr-2">Sort By:</span>
                <span class="text-green-500">Created date</span>
                <span class="italic font-light"> - Latest first</span>
            </div>

            <div class="font-bold text-gray-500 text-sm mt-2">
                <template v-if ="searchWord">
                    <span class="mr-2">Search:</span>
                    <span class="text-green-500">{{ searchWord }}</span>
                </template>
            </div>

            <div class="overflow-auto">
                <div class="float-right font-bold text-gray-500 text-sm">
                    <span class="mr-2">Found</span>
                    <span class="mr-2 text-2xl text-green-500">{{ roles.total }}</span>
                    <span>{{ roles.total == 1 ? 'result' : 'results' }}</span>
                </div>
            </div>

        </div>

        <!-- Table -->
        <div class="border">

            <el-table :data="tableData">
                <el-table-column min-width="100" prop="name" label="Role">
                    <template #default="scope">
                        <el-tag v-if="scope.row.name == 'admin'" size="small" class="capitalize">{{ scope.row.name }}</el-tag>
                        <el-tag v-else-if="scope.row.name == 'user'" size="small" type="success" class="capitalize">{{ scope.row.name }}</el-tag>
                        <el-tag v-else size="small" type="info" class="capitalize">{{ scope.row.name }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column min-width="100" prop="role" label="Permissions">
                    <template #default="scope">
                        <el-popover v-if="scope.row.permissions.length" placement="top" trigger="hover" :width="200">
                            <template #reference>
                                <div class="flex items-end" :style="{ maxWidth: '75px' }">
                                    <el-tag size="small" effect="plain">{{ scope.row.permissions.length }}</el-tag>
                                    <span class="text-gray-300 ml-2 text-xs">/ {{ $page.props.permissions.length }}</span>
                                </div>
                            </template>
                            <h4 class="text-2xl text-blue-900 border-b mb-2 pb-2">Permissions</h4>
                            <ul class="list-disc list-inside">
                                <li v-for="(permission, index) in scope.row.permissions" :key="index" class="capitalize mb-1">{{ permission.name }}</li>
                            </ul>
                        </el-popover>
                    </template>
                </el-table-column>
                <el-table-column v-if="$page.props.can.includes('update roles') || $page.props.can.includes('delete roles')" width="200" label="Action" fixed="right" align="center">
                    <template #default="scope">
                        <div v-if="['admin', 'user'].includes(scope.row.name) == false" class="overflow-auto">

                            <!-- Delete Button -->
                            <jet-danger-button v-if="$page.props.can.includes('delete roles')" @click="confirmDestroy(scope.row)" :disabled="form.processing" class="float-right mr-2"><i class="el-icon-delete my-0.5"></i></jet-danger-button>

                            <!-- Edit Button -->
                            <jet-secondary-button v-if="$page.props.can.includes('update roles')" @click.prevent="edit(scope.row)" :disabled="form.processing" class="float-right mr-1">Edit</jet-secondary-button>

                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <!-- Pagination -->
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="pageSize" :page-sizes="[5, 10, 15, 20]"
                            :total="roles.total" :page-count="roles.total" :current-page="roles.current_page" :pager-count="11"
                            background @size-change="changePageSize" @current-change="changePage">
                </el-pagination>
            </div>

        </div>

        <el-dialog v-model="openDeleteDialog" title="Delete Role" width="30%">
            <span class="break-normal text-base mb-2">Select a <span class="text-blue-900 font-bold capitalize">replacement role</span> before you can delete the <span class="text-blue-900 font-bold capitalize">{{ capitalizeFirstWord(role.name) }}</span> role</span>
            <form>

                <div class="bg-white pt-5">
                    <div>

                        <!-- Role -->
                        <div class="mb-4">
                            <jet-label for="role" value="Select Role" class="mb-1" />
                            <el-select id="role" v-model="form.role" placeholder="Select" class="w-full">
                                <el-option v-for="available_role in roles.data" :key="available_role.name" :label="capitalizeFirstWord(available_role.name)" :value="available_role.name" class="capitalize" :disabled="available_role.name == role.name"></el-option>
                            </el-select>
                            <jet-input-error :message="form.errors.role" class="mt-2" />
                        </div>

                    </div>
                </div>
            </form>

            <template #footer>
                <span class="dialog-footer">

                    <jet-secondary-button @click="openDeleteDialog = false; reset()" class="mr-2">
                        Cancel
                    </jet-secondary-button>

                    <jet-danger-button @click="destroy()" :class="{ 'opacity-25': form.processing }" :disabled="form.processing || !form.role">
                        Delete
                    </jet-danger-button>

                </span>
            </template>
        </el-dialog>

        <!-- Modal -->
        <div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400" v-if="isOpen">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>​
                <div class="inline-block align-bottom bg-white text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">

                <form>

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="">

                            <!-- Title -->
                            <jet-section-title class="mb-4">
                                <template #title>{{ editMode ? 'Edit Role' : 'Create Role' }}</template>
                            </jet-section-title>

                            <!-- Name -->
                            <div class="mb-4">
                                <jet-label for="name" value="Name" />
                                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                                <jet-input-error :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Permissions -->
                            <div class="mb-4">
                                <jet-label for="permissions" value="Permissions" class="border-b pb-2 mb-4" />
                                <el-checkbox-group id="permissions" v-model="form.permissions" class="grid grid-cols-3 gap-4">
                                    <el-checkbox v-for="(permission, index) in permissions" :key="index" :label="permission.name" :checked="form.permissions.includes(permission.name)" :disabled="['view companies'].includes(permission.name)"></el-checkbox>
                                </el-checkbox-group>
                                <jet-input-error :message="form.errors.permissions" class="mt-2" />
                            </div>

                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">

                        <jet-button v-if="!editMode" @click.prevent="create()" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Create
                        </jet-button>

                        <jet-button v-if="editMode" @click.prevent="update()" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Update
                        </jet-button>

                        <jet-secondary-button @click="closeModal()" class="mr-2">
                            Cancel
                        </jet-secondary-button>

                    </div>

                </form>

                </div>
            </div>
        </div>

    </div>

</template>

<script>

    import Dashboard from '@/Pages/Dashboard'
    import { Inertia } from '@inertiajs/inertia'

    import JetLabel from '@/Jetstream/Label'
    import JetInput from '@/Jetstream/Input'
    import JetButton from '@/Jetstream/Button'
    import JetCheckbox from '@/Jetstream/Checkbox'
    import JetInputError from '@/Jetstream/InputError'
    import JetSectionTitle from '@/Jetstream/SectionTitle'
    import JetDangerButton from '@/Jetstream/DangerButton'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton'

    export default {
        layout: Dashboard,
        components: {
            JetLabel, JetInput, JetButton, JetCheckbox, JetInputError, JetSectionTitle, JetDangerButton,
            JetSecondaryButton
        },
        props: ['roles', 'permissions', 'errors'],
        data() {
            return {
                //  Form attributes
                role: null,
                form: null,
                isOpen: false,
                editMode: false,
                openDeleteDialog: false,

                //  Table attributes
                tableData: [],

                //  Searching attributes
                searchWord: '',

                //  Filter attributes
                selectedFilters: [],
                filters: [
                    {
                        label: 'Permissions',
                        options: this.getFilterPermissions()
                    }
                ],

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
                currentPage: this.roles.current_page,
                perPage: this.roles.per_page
            }
        },
        computed:{
            pageSize(){
                return parseInt(this.roles.per_page);
            },
            urlQueryParamsAsObject(){

                var url_append = {};

                //  If we have a search word
                if( this.searchWord ){

                    url_append.search = this.searchWord;

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
        methods: {
            /**
             *  FORM METHODS
             */
            openModal: function () {
                this.isOpen = true;
            },
            closeModal: function () {
                this.isOpen = false;
                this.editMode=false;
                this.reset();
            },
            reset: function () {
                this.form = this.$inertia.form({
                    name: null,
                    role: null,
                    permissions: ['view companies']
                });
            },
            create: function () {
                var options = {
                    preserveState: true, preserveScroll: true, replace: true,
                    onSuccess: (response) => {
                        this.reset();
                        this.closeModal();
                        this.editMode = false;
                        this.setTableData(response.props.roles.data);
                        this.$message({
                            message: 'Role created successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not create role',
                            type: 'warning'
                        });
                    },
                };

                this.form.post(route('role-create'), options);
            },
            edit: function (role) {
                this.form.name = role.name;
                this.form.permissions = role.permissions.map((permission) => {
                    return permission.name;
                });

                this.role = role;

                this.editMode = true;
                this.openModal();
            },
            update: function () {
                var options = {
                    preserveState: true, preserveScroll: true, replace: true,
                    onSuccess: (response) => {
                        this.reset();
                        this.closeModal();
                        this.editMode = false;
                        this.setTableData(response.props.roles.data);
                        this.$message({
                            message: 'Role updated successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not update role',
                            type: 'warning'
                        });
                    },
                };

                this.form.put(route('role-update', this.role.id), options);
            },
            confirmDestroy(role){
                this.role = role;
                this.openDeleteDialog = true;
            },
            destroy: function () {

                var options = {
                    preserveState: true, preserveScroll: true, replace: true,
                    onSuccess: (response) => {
                        this.openDeleteDialog = false;
                        this.setTableData(response.props.roles.data);
                        this.$message({
                            message: 'Role deleted successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not delete role',
                            type: 'warning'
                        });
                    },
                };

                this.form.delete(route('role-delete', this.role.id), options);
            },

            /**
             *  TABLE METHODS
             */
            capitalizeFirstWord: function (string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            },
            getFilterPermissions(){
                return this.permissions.map((permissions) => {
                    return {
                        name: this.capitalizeFirstWord(permissions.name),
                        value: permissions.name
                    }
                });
            },
            changePage(val) {
                this.currentPage = val;

                this.fetchRoles();
            },
            changePageSize(val) {
                this.perPage = val;

                this.fetchRoles();
            },
            handleFilter(){

                if( this.selectedFilters.length ){

                    //  Clear the search
                    this.searchWord = '';

                }

                this.fetchRoles();
            },
            triggerSearch(searchWord){

                if(searchWord){

                    this.searchWord = searchWord;

                    this.fetchRoles();

                }

            },
            fetchRoles(){

                var options = { only: ['roles'], preserveState: true, preserveScroll: true, replace: true };

                var response = this.$inertia.get(route('roles'), this.urlQueryParamsAsObject, options);

                Inertia.on('success', (event) => {

                    this.setTableData(event.detail.page.props.roles.data);

                })

            },
            setTableData(roles){

                if( roles ){
                    this.tableData = roles.map(function(role){

                        let hasPermissions = role.permissions.length;

                        let data = {

                            //  Role information
                            id: role.id,
                            name: role.name,
                            permissions: hasPermissions ? role.permissions : []

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
            setFiltersFromUrl(){

                if( route().params ){

                    if( route().params.status ){

                        this.selectedFilters = route().params.status.split(',');
                    }

                }
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
            this.reset();
            this.setSearchFromUrl();
            this.setFiltersFromUrl();
            this.setSortByFromUrl();
            this.setSortByTypeFromUrl();
            this.setTableData(this.roles.data);
        }
    }

</script>

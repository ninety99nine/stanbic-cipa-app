<template>

    <div>

        <!-- Title -->
        <div class="flex justify-between border-b pb-4">
            <div class="flex items-center">
                <span class="text-2xl items-center font-bold text-gray-500">Users</span>
            </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="grid grid-cols-3 gap-4 my-5">

            <!-- Search Bar -->
            <div class="flex items-start">
                <el-input v-model="searchWord" placeholder="Search users by name or email" prefix-icon="el-icon-search"
                          size="small" class="mr-2" clearable @keyup.enter="fetchUsers()"
                          @clear="fetchUsers()">
                </el-input>
                <jet-button :height="32" @click="fetchUsers()">Search</jet-button>
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

                <!-- Create User Button -->
                <jet-button :height="32" icon="el-icon-plus" class="float-right" @click.prevent="openModal()">
                    <span>Create User</span>
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
                    <span class="mr-2 text-2xl text-green-500">{{ users.total }}</span>
                    <span>{{ users.total == 1 ? 'result' : 'results' }}</span>
                </div>
            </div>

        </div>

        <!-- Table -->
        <div class="border">

            <el-table :data="tableData">
                <el-table-column min-width="100" prop="name" label="Name"></el-table-column>
                <el-table-column min-width="100" prop="email" label="Email"></el-table-column>
                <el-table-column min-width="100" prop="role" label="Role">
                    <template #default="scope">
                        <el-tag v-if="scope.row.role == 'admin'" size="small" class="capitalize">{{ scope.row.role }}</el-tag>
                        <el-tag v-else-if="scope.row.role == 'user'" size="small" type="success" class="capitalize">{{ scope.row.role }}</el-tag>
                        <el-tag v-else size="small" type="info" class="capitalize">{{ scope.row.role }}</el-tag>
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
                <el-table-column v-if="$page.props.can.includes('update users') || $page.props.can.includes('delete users')" width="200" label="Action" fixed="right" align="center">
                    <template #default="scope">
                        <div v-if="$page.props.user.id != scope.row.id" class="overflow-auto">

                            <!-- Delete Button -->
                            <jet-danger-button v-if="$page.props.can.includes('delete users')" @click="confirmDestroy(scope.row)" :disabled="form.processing" class="float-right mr-2"><i class="el-icon-delete my-0.5"></i></jet-danger-button>

                            <!-- Edit Button -->
                            <jet-secondary-button v-if="$page.props.can.includes('update users')" @click.prevent="edit(scope.row)" :disabled="form.processing" class="float-right mr-1">Edit</jet-secondary-button>

                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <!-- Pagination -->
            <div class="overflow-auto py-4">
                <el-pagination class="float-right" layout="sizes, prev, pager, next" :page-size="pageSize" :page-sizes="[5, 10, 15, 20]"
                            :total="users.total" :page-count="users.total" :current-page="users.current_page" :pager-count="11"
                            background @size-change="changePageSize" @current-change="changePage">
                </el-pagination>
            </div>

        </div>

        <el-dialog v-model="openDeleteDialog" title="Delete User" width="30%">
            <span class="text-base">Are you sure you want to delete <span class="text-blue-900 font-bold capitalize">{{ user.name }}</span>?</span>
            <template #footer>
                <span class="dialog-footer">

                    <jet-secondary-button @click="openDeleteDialog = false" class="mr-2">
                        Cancel
                    </jet-secondary-button>

                    <jet-danger-button @click="destroy()" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
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
                            <template #title>{{ editMode ? 'Edit User' : 'Create User' }}</template>
                        </jet-section-title>

                        <!-- Name -->
                        <div class="mb-4">
                            <jet-label for="name" value="Name" />
                            <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                            <jet-input-error :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <jet-label for="email" value="Email" />
                            <jet-input id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                            <jet-input-error :message="form.errors.email" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div class="mb-4">
                            <jet-label for="role" value="Role" class="mb-1" />
                            <el-select id="role" v-model="form.role" placeholder="Select">
                                <el-option v-for="role in roles" :key="role.name" :label="capitalizeFirstWord(role.name)" :value="role.name" class="capitalize"></el-option>
                            </el-select>
                            <jet-input-error :message="form.errors.role" class="mt-2" />
                        </div>

                        <div v-if="editMode">
                            <jet-checkbox v-model="form.reset_password" :disabled="form.processing" class="mr-2"></jet-checkbox>
                            <span>Reset Password</span>
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
        props: ['users', 'roles', 'permissions', 'errors'],
        data() {
            return {
                //  Form attributes
                user: null,
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
                        label: 'Roles',
                        options: this.getFilterRoles()
                    },
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
                currentPage: this.users.current_page,
                perPage: this.users.per_page
            }
        },
        computed:{
            pageSize(){
                return parseInt(this.users.per_page);
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
                    email: null,
                    reset_password: false,
                    role: this.roles[0].name
                });
            },
            create: function () {
                var options = {
                    preserveState: true, preserveScroll: true, replace: true,
                    onSuccess: (response) => {
                        this.reset();
                        this.closeModal();
                        this.editMode = false;
                        this.setTableData(response.props.users.data);
                        this.$message({
                            message: 'User created successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not create user',
                            type: 'warning'
                        });
                    },
                };

                this.form.post(route('user-create'), options);
            },
            edit: function (user) {
                this.form.email = user.email;
                this.form.name = user.name;
                this.form.role = user.role;
                this.user = user;

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
                        this.setTableData(response.props.users.data);
                        this.$message({
                            message: 'User updated successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not update user',
                            type: 'warning'
                        });
                    },
                };

                this.form.put(route('user-update', this.user.id), options);
            },
            confirmDestroy(user){
                this.user = user;
                this.openDeleteDialog = true;
            },
            destroy: function () {

                var options = {
                    preserveState: true, preserveScroll: true, replace: true,
                    onSuccess: (response) => {
                        this.openDeleteDialog = false;
                        this.setTableData(response.props.users.data);
                        this.$message({
                            message: 'User deleted successfully',
                            type: 'success'
                        });
                    },
                    onError: errors => {
                        this.$message({
                            message: 'Sorry, could not delete user',
                            type: 'warning'
                        });
                    },
                };

                this.form.delete(route('user-delete', this.user.id), options);
            },

            /**
             *  TABLE METHODS
             */
            capitalizeFirstWord: function (string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            },
            getFilterRoles(){
                return this.roles.map((role) => {
                    return {
                        name: this.capitalizeFirstWord(role.name),
                        value: role.name
                    }
                });
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
            triggerSearch(searchWord){

                if(searchWord){

                    this.searchWord = searchWord;

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
                    this.tableData = users.map(function(user){

                        let hasRoles = user.roles.length;
                        let hasPermissions = hasRoles ? user.roles[0].permissions.length : 0;

                        let data = {

                            //  User information
                            id: user.id,
                            name: user.name,
                            email: user.email,
                            role: hasRoles ? user.roles[0].name : null,
                            permissions: hasPermissions ? user.roles[0].permissions : []

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
            this.setTableData(this.users.data);
        }
    }

</script>

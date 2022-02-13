<template>
    <div class="crud">
        <div class="content">
            <div id="data" class="tab">
                <h3>Settings <b-button variant="success" @click="show_update_modal('post', newObject)" size="sm">Create New</b-button> </h3>

                <!-- ======================= SETTINGS LISTING ================== -->
                <template>
                    <b-form @submit="submitSearch">
                        <b-table striped show-empty :items="items" :fields="fields" empty-text="No records found!" @row-clicked="row_click_handler" no-local-sorting @sort-changed="sortingChanged" head-variant="dark" table-hover>

                            <template slot="top-row" slot-scope="{ fields }">
                                <td v-for="field in fields">
                                    <!-- <template v-if="field.key=='meta_object_uuid'"> -->
                                    <template v-if="field.key == 'action'">
                                        <b-button size="sm" variant="outline-primary" type="submit" @click="search()">Search</b-button>
                                    </template>
                                    <template v-else>
                                        <b-form-input v-model="searchValues[field.key]" type="search" :placeholder="field.label"></b-form-input>
                                    </template>
                                </td>
                            </template>

                            <!-- <template v-slot:cell(meta_object_uuid)="row"> -->
                            <template v-slot:cell(action)="row">
                                <b-button size="sm" variant="outline-danger" v-on:click.stop="" @click="show_update_modal('delete', row.item)">Delete</b-button>
                            </template>

                        </b-table>
                    </b-form>
                </template>

                <!-- <b-pagination v-if="totalItems > limit" size="md" :total-rows="totalItems" v-model="currentPage" :per-page="limit"  align="center"></b-pagination> -->
                <b-pagination-nav :link-gen="linkGen" :number-of-pages="numberOfPages" use-router></b-pagination-nav>

                <!-- ======================= MODAL UPDATE & DELETE ================== -->
                <b-modal
                        id="crud-modal"
                        :title="modalTitle"
                        :header-bg-variant="modalVariant"
                        header-text-variant="light"
                        body-bg-variant="light"
                        body-text-variant="dark"
                        :ok-title="ButtonTitle"
                        :ok-variant="ButtonVariant"
                        centered
                        @ok="update_modal_ok_handler"
                        :cancel-disabled="actionState"
                        :ok-disabled="loadingState"
                        :ok-only="actionState && !loadingState"
                        size="lg"
                >
                    <template v-if="!actionState">
                        <!-- <p>{{actionTitle}}</p> -->
                        <!-- apply filter "humanize" on the label -->
                        <b-form-group class="form-group" v-for="(value, index) in putValues" v-if="index!='meta_object_uuid'" v-bind:key="index" :label="index + ':' | humanize" label-align="right" label-cols="3">
                            <template v-if="action === 'delete'">
                                <b-form-input :value="value" disabled></b-form-input>
                            </template>
                            <template v-else>
                                <b-form-input v-model="putValues[index]" :disabled="!editable_record_properties.includes(index)"></b-form-input>
                            </template>
                        </b-form-group>
                    </template>

                    <template v-else>
                        <p v-if="loadingState">
                            {{loadingMessage}}
                            ...
                        </p>
                        <p v-else>
                            <template v-if="requestError == ''">
                                {{successfulMessage}}
                            </template>
                            <template v-else>
                                The operation can not be performed due to an error:<br />
                                {{requestError}}
                            </template>
                        </p>
                    </template>
                </b-modal>


                <!-- ======================= MODAL PERMISSIONS ================== -->
                <b-modal
                        id="crud-permissions"
                        :title="title_permissions"
                        header-bg-variant="success"
                        header-text-variant="light"
                        body-bg-variant="light"
                        body-text-variant="dark"
                        hide-footer
                        size="lg"
                >
                    <b-table
                            striped
                            show-empty
                            :items="items_permissions"
                            :fields="fields_permissions"
                            empty-text="No records found!"
                            head-variant="dark"
                            table-hover
                            :busy.sync="isBusy_permissions"
                    >

                        <!-- permision_uuid is just a value that can not be used here as it is only for the first row/setting -->
                        <template v-slot:[setSlotCell(action_name)]="row" v-for="(permission_uuid, action_name) in items_permissions[0].permissions">
                            <b-form-checkbox :value="row.item.permissions[action_name] ? row.item.permissions[action_name] : 0" unchecked-value="" @change="toggle_permission(row.item, action_name, row.item.permissions[action_name] ? 1 : 0)" v-model="row.item.permissions[action_name]"></b-form-checkbox>
                        </template>

                    </b-table>
                </b-modal>

            </div>
        </div>
    </div>

</template>

<script>
    import Hook from '@GuzabaPlatform.Platform/components/hooks/Hooks.vue'
    import ToastMixin from '@GuzabaPlatform.Platform/ToastMixin.js'

    import vSelect from 'vue-select'
    import 'vue-select/dist/vue-select.css'

    export default {
        name: "SettingsAdmin",
        mixins: [
            ToastMixin,
        ],
        components: {
            Hook,
            vSelect,
        },
        data() {
            return {
                checkbox_test: '0',
                limit: 10,
                currentPage: 1,
                totalItems: 0,

                numberOfPages: 1,

                //selectedClassName: '',
                //selectedClassNameShort: '',
                sortBy: 'none',
                sortDesc: false,

                searchValues: {},
                putValues: {},

                requestError: '',

                action: '',
                actionTitle: '',
                modalTitle: '',
                modalVariant: '',
                ButtonTitle: '',
                ButtonVariant: '',

                crudObjectUuid: '',

                actionState: false,
                loadingState: false,

                loadingMessage: '',
                successfulMessage: '',

                items: [],
                fields: [],//these are the columns
                record_properties: [],
                editable_record_properties: [],

                items_permissions: [
                    //must have a default even empty value to avoid the error on template load
                    {
                        permissions: [],
                    }
                ],
                fields_permissions: [],
                fields_permissions_base: [
                    {
                        key: 'setting_id',
                        label: 'Setting ID',
                        sortable: true
                    },
                    {
                        key: 'setting_name',
                        label: 'Setting Name',
                        sortable: true
                    },
                ],

                title_permissions: "Permissions",
                isBusy_permissions: false,
                selectedObject: {},

                newObject: {},

                settings: [],
            }
        },
        methods: {

            /**
             * @param {int} pageNum
             * @return {string}
             */
            linkGen(pageNum) {
                return pageNum === 1 ? '?' : `?page=${pageNum}`
            },

            // https://stackoverflow.com/questions/58140842/vue-and-bootstrap-vue-dynamically-use-slots
            setSlotCell(action_name) {
                return `cell(${action_name})`;
            },

            submitSearch(evt){
                evt.preventDefault()
                this.search()
            },

            get_settings() {

                if (typeof this.$route.query.page !== 'undefined') {
                    this.currentPage = this.$route.query.page;
                } else {
                    this.currentPage = 1;
                }

                this.fields = [];
                this.newObject = {};

                for (let key in this.searchValues) {
                    if (this.searchValues[key] == '') {
                        delete this.searchValues[key];
                    }
                }

                let objJsonStr = JSON.stringify(this.searchValues);//this is passed as GET so needs to be stringified
                let searchValuesToPass = encodeURIComponent(window.btoa(objJsonStr));

                let url = '/admin/settings/' + this.currentPage + '/' + this.limit + '/'+ searchValuesToPass + '/' + this.sortBy + '/' + this.sortDesc;
                this.$http.get(url)
                    .then(resp => {
                        // self.fields.push({
                        //     label: 'UUID',
                        //     key: key,
                        //     sortable: true
                        // });
                        for (let i in resp.data.listing_columns) {
                            let key = resp.data.listing_columns[i];
                            this.fields.push({
                                key: key,
                                sortable: true
                            });
                            this.newObject[key] = '';
                        }
                        this.fields.push({
                            label: 'Action',
                            key: 'action',
                            sortable: true
                        });

                        this.items = resp.data.data;

                        this.totalItems = resp.data.totalItems;
                        this.numberOfPages = Math.ceil (this.totalItems / this.limit );

                        this.record_properties = resp.data.record_properties;
                        this.editable_record_properties = resp.data.editable_record_properties;
                    })
                    .catch(err => {
                        this.$bvToast.toast('Settings data could not be loaded due to server error.' + '\n' + err.response.data.message)
                    });
            },

            search() {
                this.reset_params();
                this.get_settings();
            },

            //reset_params(className){
            reset_params() {
                this.currentPage = 1;
                this.totalItems = 0;
                this.sortBy = 'setting_name';
            },

            row_click_handler(record, index) {
                this.show_update_modal('put', record);
            },

            /**
             * Shows the modal dialog for updating/creating/deleting a user record
             * @param {string} action The actual HTTP method to be executed
             * @param {array} row
             */
            show_update_modal(action, row) {
                this.action = action;
                this.crudObjectUuid = null;
                this.putValues = {};

                for (let key in row) {
                    if (key == "meta_object_uuid") {
                        this.crudObjectUuid = row[key];
                        //} else if (!key.includes("meta_")){
                    } else if (!key.includes("meta_") && this.record_properties.includes(key)) { // show only the properties listed in record_properties
                        this.putValues[key] = row[key];
                    }
                }

                switch (this.action) {
                    case 'delete' :
                        this.modalTitle = 'Deleting setting';
                        this.modalVariant = 'danger';
                        this.ButtonVariant = 'danger';
                        //this.actionTitle = 'Are you sure, you want to delete object:';
                        this.ButtonTitle = 'Delete';
                        break;

                    case 'put' :
                        this.modalTitle = 'Edit setting';
                        this.modalVariant = 'success';
                        this.ButtonVariant = 'success';
                        this.ButtonTitle = 'Save';
                        break;

                    case 'post' :
                        this.modalTitle = 'Create new setting';
                        this.modalVariant = 'success';
                        this.ButtonVariant = 'success';
                        this.ButtonTitle = 'Save';
                        break;
                }

                if (!this.crudObjectUuid && this.action != "post") {
                    this.requestError = "This setting has no meta data!";
                    this.actionState = true;
                    this.loadingState = false;
                    this.ButtonTitle = 'Ok';
                } else {
                    this.actionState = false
                    this.loadingState = false
                }

                this.$bvModal.show('crud-modal');

            },

            update_modal_ok_handler(bvEvt) {
                if(!this.actionState) {
                    bvEvt.preventDefault() //if actionState is false, doesn't close the modal
                    this.actionState = true
                    this.loadingState = true

                    let self = this;
                    let sendValues = {};

                    let url = '/admin/settings/setting';

                    switch(this.action) {
                        case 'delete' :
                            self.loadingMessage = 'Deleting setting with uuid: ' + this.crudObjectUuid;
                            url += '/' + this.crudObjectUuid;
                            break;

                        case 'put' :
                            self.loadingMessage = 'Saving setting with uuid: ' + this.crudObjectUuid;
                            url += '/' + this.crudObjectUuid;
                            sendValues = this.putValues;
                            delete sendValues['meta_object_uuid'];
                            break;

                        case 'post' :
                            self.loadingMessage = 'Saving new setting';
                            sendValues = this.putValues;
                            delete sendValues['meta_object_uuid'];
                            break;
                    }

                    this.$http({
                        method: this.action,
                        url: url,
                        data: sendValues
                    })
                        .then(resp => {
                            this.requestError = '';
                            this.successfulMessage = resp.data.message;
                            this.get_settings()
                        })
                        .catch(err => {
                            if (err.response.data.message) {
                                this.requestError = err.response.data.message;
                            } else {
                                this.requestError = err;
                            }
                        })
                        .finally( () => {
                            this.loadingState = false
                            this.actionState = true
                            this.ButtonTitle = 'OK';
                            this.ButtonVariant = 'success';
                        });
                }
            },

            sortingChanged(ctx) {
                this.sortBy = ctx.sortBy;
                this.sortDesc = ctx.sortDesc ? 1 : 0;

                this.get_settings();
            }
        },
        props: {
            contentArgs: {}
        },
        watch: {
            $route (to, from) { // needed because by default no class is loaded and when it is loaded the component for the two routes is the same.
                this.get_settings();
            }
        },
        mounted() {
            this.get_settings();

        },
    };

</script>

<style>
    .content {
        height: 100vh;
        top: 64px;
    }

    .tab {
        float: left;
        height: 100%;
        overflow: none;
        padding: 20px;
    }

    #sidebar{
        font-size: 10pt;
        border-width: 0 5px 0 0;
        border-style: solid;
        width: 30%;
        text-align: left;
    }

    #data {
        width: 100%;
        font-size: 10pt;
    }

    li {
        cursor: pointer;
    }

    .btn {
        width: 100%;
    }

    tr:hover{
        background-color: #ddd !important;
    }

    th:hover{
        background-color: #000 !important;
    }

    tr {
        cursor: pointer;
    }
</style>

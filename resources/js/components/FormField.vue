<template>
    <default-field :field="field" :full-width-content="field.fullWidth" :show-help-text="false">
        <template slot="field" :class="{'border-danger border': hasErrors}">
            <div :class="{'border-danger border': hasErrors}">
                <div v-if="field.showToolbar" class="flex border border-40">
                    <div @click="selectAll" class="w-16 text-center flex justify-center items-center">
                        <fake-checkbox :checked="selectingAll" class="cursor-pointer"></fake-checkbox>
                    </div>
                    <div class="flex-1 flex items-center relative">
                        <input v-model="search" type="text" placeholder="Search" class="form-control form-input form-input-bordered w-full ml-0 m-4">
                        <span v-if="search" @click="clearSearch" class="pin-r font-sans font-bolder absolute pr-8 cursor-pointer text-black hover:text-80">x</span>
                    </div>
                </div>
                <div class="border-t-0 border border-40 relative overflow-auto" :style="{ height: field.height }">
                    <div v-for="resource in resources" :key="resource.value" @click="toggle($event, resource.value)" class="flex py-3 cursor-pointer select-none hover:bg-30">
                        <div class="w-16 flex justify-center">
                            <fake-checkbox :checked="selected.includes(resource.value)" />
                        </div>
                        <span>{{ resource.display }}</span>
                    </div>
                </div>
            </div>

            <help-text class="error-text mt-2 text-danger" v-if="hasErrors">
                {{ firstError }}
            </help-text>

            <div class="help-text mt-3">
                <span v-if="field.showCounts" class="pr-2">
                    {{ selected.length  }} / {{ available.length }}
                </span>
                <span v-if="field.helpText">
                    {{ field.helpText }}
                </span>
            </div>

        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            search: null,
            selected: [],
            selectingAll: false,
            available: []
        }
    },
    methods: {
        setInitialValue() {

            let baseUrl = '/nova-vendor/nova-attach-many/';

            if(this.resourceId) {
                Nova.request(baseUrl + this.resourceName + '/' + this.resourceId + '/attachable/' + this.field.attribute)
                    .then((data) => {
                        this.selected = data.data.selected || [];
                        this.available = data.data.available || [];
                    });
            }
            else {
                Nova.request(baseUrl + this.resourceName + '/attachable/' + this.field.attribute)
                    .then((data) => {
                        this.available = data.data.available || [];
                    });
            }
        },

        fill(formData) {
            formData.append(this.field.attribute, this.value || [])
        },

        toggle(event, id){
            if(this.selected.includes(id)) {
                this.selected = this.selected.filter(selectedId => selectedId != id);
            }
            else {
                this.selected.push(id)
            }
        },
        selectAll() {
            var selected = this.selected;

            this.selectingAll = ! this.selectingAll;

            // search can return 0 results
            if(this.resources.length == 0) {
                return;
            }

            if(this.resources.length == 1 && this.selected == 1)
            {
                this.selected = [];
            }

            // add all resources
            if(! this.search && this.selectingAll) {
                this.resources.forEach(resource => {
                    selected.push(resource.value)
                })
            }

            // remove all resources
            if(! this.search && ! this.selectingAll) {
                selected = [];
            }

            // append searched resources
            if(this.search && this.selectingAll) {
                this.resources.forEach(resource => {
                    selected.push(resource.value)
                })
            }

            // remove only searched items
            if(this.search && ! this.selectingAll) {

                let exludingIds = [];

                this.resources.forEach(resource => {
                    exludingIds.push(resource.value);
                })

                selected = selected.filter(id => exludingIds.includes(id) == false);
            }

            this.selected = selected;
        },
        clearSearch()
        {
            this.selectingAll = false;
            this.search = null;
        },
        checkIfSelectAllIsActive() {

            if(this.resources.length === 0) {
                this.selectingAll = false; return;
            }

            let visibleAndSelected = [];

            this.resources.forEach(resource => {
                if(this.selected.includes(resource.value)) {
                    visibleAndSelected.push(resource.value);
                }
            })

            this.selectingAll = visibleAndSelected.length == this.resources.length;
        }
    },
    computed: {
        resources: function() {
            if(this.search == null) {
                return this.available;
            }

            return this.available.filter((resource) => {
                return resource.display.toLowerCase().includes(this.search.toLowerCase())
            });
        },
        hasErrors: function() {
            return this.errors.errors.hasOwnProperty(this.field.attribute);
        },
        firstError: function() {
            return this.errors.errors[this.field.attribute][0]
        }
    },
    watch: {
        'search': {
            handler: function(search) {
                this.checkIfSelectAllIsActive();
            }
        },
        'selected': {
            handler: function (selected) {
                this.value = JSON.stringify(selected);
                this.checkIfSelectAllIsActive();
            },
            deep: true
        }
    }
}
</script>
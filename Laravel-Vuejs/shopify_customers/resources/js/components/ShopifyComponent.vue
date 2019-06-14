<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default mb-md-3">
                    <div class="card-header text-center"><h3>Please fill out the form</h3></div>
                    <div class="card-body customer_item" v-for="(item, key) in items" :key="key">
                        <div class="row">
                            <div class="col-md-12">
                                <button @click="removeItem(key)" class="remove_customer"> x </button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label  class="col-sm-2 col-form-label">Name</label>
                            <div class="col-sm-6">
                                <v-select v-model="item.selected" :options="shopifyCustomers"></v-select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Phone</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" v-model="item.selected.phone" :class="item.selected.invalidPhone" @change="checkPhoneNumber(item)">
                            </div>
                        </div>
                    </div>

                </div>
                <button type="button" class="btn btn-primary add_new_item" @click="addNewItem">+ Add new</button>
                <button class="btn btn-primary" @click="saveCustomers">Save Customers</button>
            </div>
        </div>
    </div>
</template>

<script>
    function phoneNumber(inputtxt)
    {
        if (inputtxt === undefined || inputtxt === null) {
            return false;
        }
        let phoneNo = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im; // todo we can change this condition as we want
        return !!(inputtxt.match(phoneNo));
    }
    export default {
        data:  function () {
            return {
                items: [],
                shopifyCustomers: [],
                customers: [],
            }
        },
        mounted() {
            console.log('Component mounted.');

            axios.get('/getCustomers')
                .then(res => {
                    if (res.data.customers.length > 0) {
                        res.data.customers.forEach((item, i) => {
                            item.phone = item.phone.replace(/ /g, '');
                            const invalidPhoneClass = phoneNumber(item.phone) === true ? '' : 'invalid-phone-number';
                            this.items.push({selected: {label: item.name, value: item.customer_id, phone: item.phone, country: item.country, email: item.email, invalidPhone: invalidPhoneClass}});
                        });
                    }
                });

            axios.get('/getShopifyCustomers')
                .then(response => {
                    if (response.data.customers.length > 0) {
                        response.data.customers.forEach((item, i) => {
                            item.phone = item.phone.replace(/ /g, '');
                            const invalidPhoneClass = phoneNumber(item.phone) === true ? '' : 'invalid-phone-number';
                            this.shopifyCustomers.push({label: item.name, value: item.id, phone: item.phone, country: item.country, email: item.email, invalidPhone: invalidPhoneClass});
                        });
                    }else if (response.data.errors.length > 0) {
                        this.$notify.error(response.data.errors);
                    }
                });

        },
        methods:{
            addNewItem(){
                this.items.push({selected: {label: null}});
            },
            removeItem(key){
                const removingItem = this.items[key];

                if (removingItem && removingItem.selected && removingItem.selected.value) {

                    axios.post('/remove', {customer_id: removingItem.selected.value})
                        .then(response => {
                            if (response.data.success) {
                                this.$notify.danger('Customer removed successfully');
                            }
                            this.items.splice(key, 1);
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                } else {
                    this.items.splice(key, 1);
                    this.$notify.danger('Customer removed successfully');
                }
            },
            saveCustomers(){
                let _customers = [];
                let _invalidPhoneNumber = false;
                let _customerIds = [];

                if (this.items.length === 0) {
                    this.$notify.info('Please add customer and after that save');
                    return false;
                }

                this.items.forEach((item, i) => {
                    if (!_invalidPhoneNumber) {
                        _invalidPhoneNumber = !phoneNumber(item.selected.phone);
                    }
                    if (!_customerIds.includes(item.selected.value)) {
                        _customers.push({
                            country: item.selected.country,
                            email: item.selected.email,
                            name: item.selected.label,
                            phone: item.selected.phone,
                            customer_id: item.selected.value,
                        });
                        _customerIds.push(item.selected.value);
                    }

                });

                if (_invalidPhoneNumber === true) {
                    this.$notify.error('Please fix all invalid phone numbers');
                    return false;
                }

                axios.post('/store', {customers: _customers})
                    .then(response => {
                        console.log('response', response);
                        this.$notify.success('Customers saved successfully');
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            },
            checkPhoneNumber(item){
                if (item.selected) {
                    if (phoneNumber(item.selected.phone)) {
                        item.selected.invalidPhone = '';
                    } else {
                        item.selected.invalidPhone = 'invalid-phone-number';
                    }
                }
            }
        }
    }
</script>
<style>
    .v-select .dropdown-toggle .clear { display: none; }
    .invalid-phone-number {
        border: 1px solid red;
    }
    .dropdown-toggle:after{
        content: none;
        display: none;
    }
    .v-select.searchable .dropdown-toggle {
        height: 37px;
    }
    .remove_customer{
        float: right;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 19px;
    }
    .customer_item{
       border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }
    .add_new_item {
        float: right;
    }

</style>

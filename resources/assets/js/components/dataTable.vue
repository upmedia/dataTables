<template>

    <div class="panel panel-default">
        <div class="panel-heading">{{ response.table }}</div>
        <div class="panel-body">
            <form action="#" @submit.prevent="getRecords()">
                <label for="search">Search</label>
                <div class="row row-fluid">
                    <div class="form-group col-md-3">
                        <select class="form-control" v-model="search.column">
                            <option :value="column" v-for="column in response.displayable">{{ column }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <select class="form-control" v-model="search.operator">
                            <option value="equals">=</option>
                            <option value="contains">Contains</option>
                            <option value="starts_with">Starts with</option>
                            <option value="end_with">Ends with</option>
                            <option value="greater_than">Greater than</option>
                            <option value="less_than">Less than</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <input type="text" id="search" v-model="search.value" class="form-control">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">Search</button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="form-group col-md-10">
                        <input type="search" class="form-control" id="id" v-model="quickSearchQuery" placeholder="Quick search current result">
                        <span class="form-control-clear glyphicon glyphicon-remove form-control-feedback" :class="{ 'hidden': quickSearchQuery === ''}" @click="quickSearchQuery = ''"></span>
                </div>
                <div class="form-group col-md-2">
                    <select id="limit" class="form-control" v-model="limit" @change="getRecords">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="1000">1000</option>
                        <option value="">All</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th v-for="column in response.displayable">
                            <span class="sortable" @click="sortBy(column)">{{ column }}</span>
                            <span
                                v-if="sort.key == column"
                                class="arrow"
                                :class="{ 'arrow--asc' : sort.order === 'asc', 'arrow--desc' : sort.order === 'desc' }"
                            ></span>
                        </th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in filteredRecords" :key="record.id">
                        <td v-for="columnValue, column in record">
                            <template v-if="editing.id === record.id && isUpdatable(column)">
                                <div class="form-group" :class="{ 'has-error': editing.errors[column] }">
                                    <input type="text" class="form-control" :value="columnValue" v-model="editing.form[column]">
                                    <span class="help-block" v-if="editing.errors[column]">
                                        <strong>
                                            {{ editing.errors[column][0] }}
                                        </strong>
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                {{ columnValue }}
                            </template>
                        </td>
                        <td>
                            <template v-if="editing.id !== record.id">
                                <a href="#" @click.prevent="edit(record)" >Edit</a>
                            </template>
                            <template v-if="editing.id === record.id">
                                <a href="#" @click.prevent="update" >Save</a><br>
                                <a href="#" @click.prevent="editing.id = null">Cancel</a>
                            </template>

                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</template>

<script>
    import queryString from 'query-string'

    export default {
        props: [
            'endpoint'
        ],

        data () {
            return {
                response: {
                    table: '',
                    displayable: [],
                    updateable: [],
                    records: []
                },
                sort: {
                    key: 'id',
                    order: 'asc'
                },
                limit: 50,
                quickSearchQuery: '',
                editing: {
                    id: null,
                    form: {},
                    errors: []
                },
                search: {
                    value: '',
                    operator: 'equals',
                    column: 'id'
                }
            }
        },

        mounted() {
            this.getRecords()
        },

        computed: {
            filteredRecords () {
                let data = this.response.records

                data = data.filter((row) => {
                    return Object.keys(row).some((key) => {
                        return String(row[key]).toLowerCase().indexOf(this.quickSearchQuery.toLowerCase()) > -1
                    })
                })

                if (this.sort.key) {
                    data = _.orderBy(data, (i) => {
                        let value = i[this.sort.key]

                        if (!isNaN(parseFloat(value))) {
                            return parseFloat(value)
                        }

                        return String(i[this.sort.key]).toLowerCase()
                    }, this.sort.order)
                }

                return data
            }
        },

        methods: {
            getRecords() {
                return axios.get(`${this.endpoint}?${this.getQueryParameters()}`).then((response) => {
                    this.response = response.data.data
                })
            },
            getQueryParameters() {
                return queryString.stringify({
                    limit: this.limit,
                    ...this.search
                })
            },
            sortBy(column) {
                this.sort.key = column;
                this.sort.order = this.sort.order == 'asc' ? 'desc' : 'asc'
            },
            edit(record) {
                this.editing.errors = []
                this.editing.id = record.id
                this.editing.form = _.pick(record, this.response.updateable)
            },
            isUpdatable(column) {
                return this.response.updateable.includes(column)
            },
            update() {
                axios.patch(`${this.endpoint}/${this.editing.id}`, this.editing.form).then(() => {
                    this.getRecords().then(() => {
                        this.editing.id = null
                        this.editing.form = {}
                    })
                }).catch((error) => {
                    this.editing.errors = error.response.data.errors
                })
            },
            clearQuickSearch() {
                this.quickSearchQuery = '';
            }
        }
    }
</script>

<style lang="scss">
    .sortable {
        cursor: pointer;
    }

    .arrow {
        display: inline-block;
        vertical-align: middle;
        width: 0;
        height: 0;
        margin-left: 5px;
        opacity: .6;

        &--asc {
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-bottom: 4px solid #222;
        }

        &--desc {
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #222;
        }
    }

    .form-control-clear {
      z-index: 10;
      pointer-events: auto;
      cursor: pointer;
      margin-right: 16px;
    }
</style>

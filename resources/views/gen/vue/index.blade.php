@php
    use App\Util\Gen;
@endphp
<template>
  <el-row class="app-container">
    <el-col :span="24">
      <el-form ref="form" :inline="true" :model="form" @submit.native.prevent="onSubmit">
        <el-form-item>
          <el-button type="primary" icon="el-icon-circle-plus-outline" @click="handleCreate">
            @{{ $t('common.create') }}
          </el-button>
        </el-form-item>

@foreach( $searchColumns as $column )
@if( $column->_show === Gen::TYPE_INPUT_TEXT )
        <el-form-item label="{{ $column->comment }}" prop="{{ $column->name }}">
          <el-input v-model="form.{{ $column->name }}" placeholder="{{ $column->comment }}" clearable />
        </el-form-item>
@elseif( !empty($column->dict_type_id) )
        <el-form-item label="{{ $column->comment }}" prop="{{ $column->name }}">
          <el-select v-model="form.{{ $column->name }}" placeholder="{{ $column->comment }}" clearable filterable>
            <el-option
              v-for="item in dict.filter((e) => e.dict_type_id === {{ $column->dict_type_id }})"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
@elseif( $column->_show === Gen::TYPE_DATE )
        <el-form-item label="{{ $column->comment }}" prop="{{ $column->name }}">
          <el-date-picker
            v-model="form.{{ $column->name }}"
            range-separator="至"
            start-placeholder="開始日期"
            end-placeholder="結束日期"
            type="datetimerange"
            value-format="yyyy-MM-dd HH:mm:ss"
            :default-time="['00:00:00', '23:59:59']"
          />
        </el-form-item>
@endif
@endforeach

        <el-form-item>
          <el-button :loading="loading" icon="el-icon-search" type="primary" native-type="submit">
            @{{ $t('common.search') }}
          </el-button>
          <el-button icon="el-icon-refresh-left" @click="resetForm">
            @{{ $t('common.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-col>

    <el-col :span="24">
      <el-table
        highlight-current-row
        :data="data"
        style="width: 100%"
        :default-sort="{ prop: form.sort, order: form.order }"
        @sort-change="tableSortChange"
      >
@foreach( $listColumns as $column )
@if( $column->_show === Gen::TYPE_INPUT_TEXT || $column->_show === Gen::TYPE_DATE )
        <el-table-column
          prop="{{ $column->name }}"
          label="{{ $column->comment }}"
@if( $column->_sort )
          sortable
@endif
        />
@elseif( !empty($column->dict_type_id) )
        <el-table-column
          prop="{{ $column->name }}"
          label="{{ $column->comment }}"
@if( $column->_sort )
          sortable
@endif
        >
          <template v-slot="{ row }">
            <DictTag
              v-if="dict.length > 0"
              :dict-data="dict"
              :dict-type-id="{{ $column->dict_type_id }}"
              :value="row.{{ $column->name }}"
            />
          </template>
        </el-table-column>
@elseif( $column->_show === Gen::TYPE_IMAGE )
        <el-table-column
          prop="{{ $column->name }}"
          label="{{ $column->comment }}"
@if( $column->_sort )
          sortable
@endif
        >
          <template v-slot="{ row }">
            <el-image
              class="table-image table-image-50"
              :src="row.{{ $column->name }}"
              :preview-src-list="[row.{{ $column->name }}]"
            >
              <div slot="error" class="image-error-slot">
                <i class="el-icon-picture-outline" />
              </div>
            </el-image>
          </template>
        </el-table-column>
@elseif( $column->_show === Gen::TYPE_FILE )
        <el-table-column
          prop="{{ $column->name }}"
          label="{{ $column->comment }}"
@if( $column->_sort )
          sortable
@endif
        >
          <template v-slot="{ row }">
            <el-link
              v-if="row.{{ $column->name }}"
              icon="el-icon-download"
              :underline="false"
              :href="row.{{ $column->name }}"
              target="_blank">
                @{{ $t('common.download') }}
            </el-link>
          </template>
        </el-table-column>
@else
        <el-table-column
          prop="{{ $column->name }}"
          label="{{ $column->comment }}"
@if( $column->_sort )
          sortable
@endif
        />
@endif
@endforeach

        <el-table-column :label="$t('common.handle')" width="300px">
          <template v-slot="{ row }">
            <el-button icon="el-icon-edit-outline" type="primary" @click="handleEdit(row)">
              @{{ $t('common.update') }}
            </el-button>
            <el-button icon="el-icon-delete" type="danger" @click="handleDelete(row)">
              @{{ $t('common.delete') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-col>

    <el-col :span="24" class="margin-t-10">
      <el-pagination
        :page-sizes="[10, 25, 50]"
        :page-size="form.limit"
        :current-page="form.page"
        layout="total, sizes, prev, pager, next, jumper"
        :total="total"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-col>

    <create ref="create" @done="getList" />
    <update ref="update" @done="getList" />
  </el-row>
</template>

<script>
import { {{ $routeName }}Delete, {{ $routeName }}List } from '@/api/{{ $routeName }}Api'
import { ReturnCode } from '@/utils/return-code'
@if( $searchColumns->where('dict_type_id', '=', true)->count() )
import { dictDataSelect } from '@/api/dict'
@endif

export default {
  name: '{{ $routeName .'.'. $routeName }}',
  components: {
    create: () => import('@/views/{{ $routeName }}/create'),
    update: () => import('@/views/{{ $routeName }}/update'),
@if( $searchColumns->where('dict_type_id', '=', true)->count() )
    DictTag: () => import('@/components/DictTag')
@endif
  },
  data() {
    return {
      loading: false,
      data: [],
      total: 0,
      form: {
        page: 1,
        limit: 10,
        order: '',
        sort: '',
@foreach( $searchColumns as $column)
@if( $column->_validate === 'string' )
        {{ $column->name }}: '',
@elseif( $column->_validate === 'date' )
        {{ $column->name }}: [],
@else
        {{ $column->name }}: null,
@endif
@endforeach
      },
@if( $searchColumns->where('dict_type_id', '=', true)->count() )
      dict: [],
@endif
    }
  },
  mounted() {
@if( $searchColumns->where('dict_type_id', '=', true)->count() )
    this.getDictData()
@endif
    this.getList()
  },
  methods: {
    getList() {
      this.loading = true
      {{ $routeName }}List(this.form).then(response => {
        if (response.code === ReturnCode.OK) {
          this.data = response.data.list
          this.total = response.data.total
        }
      }).catch(_ => {
        this.data = []
        this.total = 0
      }).finally(_ => {
        this.loading = false
      })
    },
    handleSizeChange(val) {
      this.form.limit = val
      this.getList()
    },
    handleCurrentChange(val) {
      this.form.page = val
      this.getList()
    },
    tableSortChange(column) {
      this.form.order = column.order ? column.order : 'descending'
      this.form.sort = column.prop
      this.form.page = 1
      this.getList()
    },
    onSubmit() {
      this.form.page = 1
      this.getList()
    },
    resetForm() {
      this.$refs.form.resetFields()
      this.form.page = 1
      this.getList()
    },
    handleDelete({ {{ $primaryKey }} }) {
      this.$confirm(this.$t('common.confirmDelete'), this.$t('common.tips'), {
        confirmButtonText: this.$t('common.confirmButtonText'),
        cancelButtonText: this.$t('common.cancelButtonText'),
        type: 'warning'
      }).then(() => {
        const loading = this.$loading({
          lock: true,
          text: 'Loading',
          spinner: 'el-icon-loading',
          background: 'rgba(0, 0, 0, 0.7)'
        })
        {{ $routeName }}Delete({
          {{ $primaryKey }}: {{ $primaryKey }}
        }).then(response => {
          if (response.code === ReturnCode.OK) {
            this.$message.success(response.message)
            this.getList()
          }
        }).finally(_ => {
          loading.close()
        })
      }).catch(() => {

      })
    },
    handleCreate() {
      this.$refs.create.init()
    },
    handleEdit({ {{ $primaryKey }} }) {
      this.$refs.update.init({{ $primaryKey }})
    },
@if( $searchColumns->where('dict_type_id', '=', true)->count() )
    getDictData() {
      dictDataSelect().then(response => {
        const { list = [] } = response.data
        this.dict = list
      })
    },
@endif
  }
}
</script>

<style scoped>
</style>

@php
    use App\Util\Gen;
    use Illuminate\Support\Str;
@endphp
<template>
  <el-dialog
    :visible.sync="dialogVisible"
    modal-append-to-body
    append-to-body
    :close-on-click-modal="false"
    @close="close"
  >
    <template slot="title">
      <i class="el-icon-circle-plus-outline" /> @{{ $t('common.create') }}
    </template>
    <el-form ref="form" :model="form" :rules="formRules" label-width="80px">
@foreach( $createColumns as $column )
@php
    $camelName = Str::of($column->name)->camel()->toString();
@endphp
@if( $column->_show === Gen::TYPE_INPUT_TEXT )
@if( $column->_validate === 'string' )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-input v-model="form.{{ $column->name }}" placeholder="{{ $column->comment }}" />
      </el-form-item>
@elseif( $column->_validate === 'integer' )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-input-number v-model="form.{{ $column->name }}" :min="0" />
      </el-form-item>
@endif
@elseif( $column->_show === Gen::TYPE_INPUT_TEXTAREA )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-input
          type="textarea"
          :rows="4"
          v-model="form.{{ $column->name }}"
          placeholder="{{ $column->comment }}"
        />
      </el-form-item>
@elseif( !empty($column->dict_type_id) )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-select v-if="dict.length > 0" v-model="form.{{ $column->name }}" placeholder="{{ $column->comment }}" filterable>
          <el-option
            v-for="item in dict.filter((e) => e.dict_type_id === {{ $column->dict_type_id }})"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
@elseif( $column->_show === Gen::TYPE_IMAGE )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <upload-image v-model="form.{{ $column->name }}" />
      </el-form-item>
@elseif( $column->_show === Gen::TYPE_IMAGES )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <upload-images v-model="form.{{ $column->name }}" />
      </el-form-item>
@elseif( $column->_show === Gen::TYPE_FILE )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <upload-file v-model="form.{{ $column->name }}" />
      </el-form-item>
@elseif( $column->_show === Gen::TYPE_EDITOR )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <WangEditor ref="{{ $column->name }}Editor" v-model="form.{{ $column->name }}" />
      </el-form-item>
@elseif( $column->_show === Gen::TYPE_DATE )
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-date-picker
          v-model="form.{{ $column->comment }}"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          time-arrow-control
        />
      </el-form-item>
@else
      <el-form-item
        label="{{ $column->comment }}"
        prop="{{ $column->name }}"
@if( $column->_required )
        class="form-item-required"
@endif
      >
        <el-input v-model="form.{{ $column->name }}" placeholder="{{ $column->comment }}" />
      </el-form-item>
@endif
@endforeach
    </el-form>

    <div slot="footer">
      <el-button type="primary" :loading="loading" @click="handleCreate">
        @{{ $t('common.confirmButtonText') }}
      </el-button>
      <el-button @click="dialogVisible = false">
        @{{ $t('common.cancelButtonText') }}
      </el-button>
    </div>
  </el-dialog>
</template>

<script>
import { {{ $routeName }}Create } from '@/api/{{ $routeName }}Api'
import { ReturnCode } from '@/utils/return-code'
@if( $createColumns->where('dict_type_id', '=', true)->count() )
import { dictDataSelect } from '@/api/dict'
@endif

export default {
  name: '{{ $routeName }}.create',
  components: {
@if( $createColumns->where('_show', '=', Gen::TYPE_EDITOR)->count() )
    WangEditor: () => import('@/components/WangEditor')
@endif
  },
  data() {
    const form = {
@foreach( $formVarList as $key => $val )
      {{ $key }}: {!! $val !!},
@endforeach
    }
    return {
      dialogVisible: false,
      form,
      formCopy: JSON.parse(JSON.stringify(form)),
      formRules: {},
{{-- 字典列表變量 --}}
@if( $createColumns->where('dict_type_id', '=', true)->count() )
      dict: [],
@endif
      loading: false
    }
  },
  methods: {
    init() {
      this.dialogVisible = true
@if( $createColumns->where('dict_type_id', '=', true)->count() )
      this.getDictData()
@endif
    },
    close() {
      this.resetForm()
    },
    resetForm() {
      this.form = JSON.parse(JSON.stringify(this.formCopy))
      this.$nextTick(_ => {
        this.$refs.form.clearValidate()
      })
    },
    handleCreate() {
      this.$refs.form.validate(valid => {
        if (valid) {
          this.loading = true
          this.error = {}
          {{ $routeName }}Create(this.form).then(response => {
            if (response.code === ReturnCode.OK) {
              this.$message({
                showClose: true,
                message: response.message,
                type: 'success'
              })
              this.dialogVisible = false
              this.$emit('done', response.data)
            }
          }).catch(reason => {

          }).finally(_ => {
            this.loading = false
          })
        } else {
          return false
        }
      })
    },
@if( $createColumns->where('dict_type_id', '=', true)->count() )
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

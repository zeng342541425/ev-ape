import request from '@/utils/request'

export function {{ $routeName }}List(data) {
  return request({
    url: '/{{ $routeName }}/list',
    method: 'post',
    data
  })
}

export function {{ $routeName }}Info(data) {
  return request({
    url: '/{{ $routeName }}/detail',
    method: 'post',
    data
  })
}

export function {{ $routeName }}Select(data) {
  return request({
    url: '/{{ $routeName }}/all',
    method: 'post',
    data
  })
}

export function {{ $routeName }}Create(data) {
  return request({
    url: '/{{ $routeName }}/create',
    method: 'post',
    data
  })
}

export function {{ $routeName }}Update(data) {
  return request({
    url: '/{{ $routeName }}/update',
    method: 'post',
    data
  })
}

export function {{ $routeName }}Delete(data) {
  return request({
    url: '/{{ $routeName }}/delete',
    method: 'post',
    data
  })
}

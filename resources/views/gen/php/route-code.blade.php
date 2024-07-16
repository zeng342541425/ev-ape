// {{ $permissionName }}
Route::post('{{ $routeName }}/list', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'list'])
    ->middleware('permission:{{ $routeName }}.list');
Route::post('{{ $routeName }}/detail', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'detail'])
    ->middleware('permission:{{ $routeName }}.list');
Route::post('{{ $routeName }}/all', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'all']);
Route::post('{{ $routeName }}/create', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'create'])
    ->middleware('permission:{{ $routeName }}.create');
Route::post('{{ $routeName }}/update', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'update'])
    ->middleware('permission:{{ $routeName }}.update');
Route::post('{{ $routeName }}/delete', [App\Http\Controllers\Backend\{{ $className }}Controller::class, 'delete'])
    ->middleware('permission:{{ $routeName }}.delete');

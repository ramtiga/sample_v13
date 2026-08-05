@extends('layouts.app')

@section('title', '所属マスタ編集')

@section('content')
    <h1>所属マスタ編集</h1>

    @include('departments._form', [
        'department' => $department,
        'action' => route('departments.update', $department->getId()),
        'method' => 'PUT',
    ])

    <p><a href="{{ route('departments.index') }}">一覧に戻る</a></p>
@endsection

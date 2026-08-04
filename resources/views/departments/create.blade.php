@extends('layouts.app')

@section('title', '所属マスタ新規作成')

@section('content')
    <h1>所属マスタ新規作成</h1>

    @include('departments._form', [
        'department' => null,
        'action' => route('departments.store'),
        'method' => 'POST',
    ])

    <p><a href="{{ route('departments.index') }}">一覧に戻る</a></p>
@endsection

@extends('layouts.app')

@section('title', '所属マスタ詳細')

@section('content')
    <h1>所属マスタ詳細</h1>

    <table>
        <tr><th>コード</th><td>{{ $department->getCode() }}</td></tr>
        <tr><th>名称</th><td>{{ $department->getName() }}</td></tr>
        <tr><th>表示順</th><td>{{ $department->getSortOrder() }}</td></tr>
        <tr><th>有効</th><td>{{ $department->isActive() ? '有効' : '無効' }}</td></tr>
    </table>

    <p>
        <a href="{{ route('departments.edit', $department->getId()) }}">編集</a>
        <a href="{{ route('departments.index') }}">一覧に戻る</a>
    </p>
@endsection

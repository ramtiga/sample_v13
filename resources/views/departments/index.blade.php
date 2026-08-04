@extends('layouts.app')

@section('title', '所属マスタ一覧')

@section('content')
    <h1>所属マスタ一覧</h1>

    <p><a href="{{ route('departments.create') }}">新規作成</a></p>

    <table>
        <thead>
            <tr>
                <th>コード</th>
                <th>名称</th>
                <th>表示順</th>
                <th>有効</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($departments as $department)
                <tr>
                    <td>{{ $department->getCode() }}</td>
                    <td>{{ $department->getName() }}</td>
                    <td>{{ $department->getSortOrder() }}</td>
                    <td>{{ $department->isActive() ? '有効' : '無効' }}</td>
                    <td class="actions">
                        <a href="{{ route('departments.show', $department->getId()) }}">詳細</a>
                        <a href="{{ route('departments.edit', $department->getId()) }}">編集</a>
                        <form action="{{ route('departments.destroy', $department->getId()) }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

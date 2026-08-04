<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="code">コード</label>
        <input type="text" id="code" name="code" value="{{ old('code', $department?->getCode()) }}">
        @error('code')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="name">名称</label>
        <input type="text" id="name" name="name" value="{{ old('name', $department?->getName()) }}">
        @error('name')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="sort_order">表示順</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $department?->getSortOrder() ?? 0) }}">
        @error('sort_order')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department?->isActive() ?? true) ? 'checked' : '' }}>
            有効
        </label>
    </div>

    <button type="submit">保存</button>
</form>

{{--
    Up / down controls for any sortable admin list.

    @param string $type  Reorder type, must be one of the values allowed in
                         the admin.reorder route.
    @param int    $id
    @param bool   $first Disable the up button on the first row.
    @param bool   $last  Disable the down button on the last row.
--}}
<span class="reorder">
    @foreach (['up' => 'chevron-up', 'down' => 'chevron-down'] as $direction => $icon)
        <form method="POST" action="{{ route('admin.reorder', ['type' => $type, 'id' => $id]) }}">
            @csrf
            <input type="hidden" name="direction" value="{{ $direction }}">
            <button class="icon-btn" type="submit"
                    title="Move {{ $direction }}"
                    aria-label="Move {{ $direction }}"
                    @disabled($direction === 'up' ? $first : $last)>
                @include('partials.icon', ['name' => $icon])
            </button>
        </form>
    @endforeach
</span>

<x-layouts.admin title="Message">
    <div class="topbar">
        <div>
            <h1>{{ $message->subject ?: 'Message' }}</h1>
            <p>
                From {{ $message->name }} on
                {{ $message->created_at->format('j M Y, H:i') }}
                @if ($message->ip_address) · {{ $message->ip_address }} @endif
            </p>
        </div>

        <div class="topbar__actions">
            <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn--ghost btn--sm" type="submit">
                    Mark as {{ $message->is_read ? 'unread' : 'read' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                  onsubmit="return confirm('Delete this message?');">
                @csrf
                @method('DELETE')
                <button class="btn btn--danger btn--sm" type="submit">Delete</button>
            </form>

            <a class="btn btn--ghost btn--sm" href="{{ route('admin.messages.index') }}">Back to inbox</a>
        </div>
    </div>

    <div class="card">
        <p class="msg__body">{{ $message->message }}</p>

        <div class="form__actions" style="margin-top: 18px;">
            <a class="btn btn--primary btn--sm" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Your message')) }}">
                Reply by email
            </a>
        </div>
    </div>
</x-layouts.admin>

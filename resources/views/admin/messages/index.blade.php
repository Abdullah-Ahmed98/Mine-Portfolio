<x-layouts.admin title="Inbox">
    <div class="topbar">
        <div>
            <h1>Inbox</h1>
            <p>Messages sent from the contact form.</p>
        </div>
    </div>

    @forelse ($messages as $message)
        <a class="msg{{ $message->is_read ? '' : ' is-unread' }}"
           href="{{ route('admin.messages.show', $message) }}">
            <div class="msg__top">
                <span class="msg__from">
                    {{ $message->name }}
                    @unless ($message->is_read)
                        <span class="badge badge--accent" style="margin-left: 6px;">new</span>
                    @endunless
                </span>
                <span class="msg__date">{{ $message->created_at->format('j M Y, H:i') }}</span>
            </div>
            <p class="msg__preview">{{ $message->subject ?: 'No subject' }} — {{ Str::limit($message->message, 90) }}</p>
        </a>
    @empty
        <p class="empty">No messages yet.</p>
    @endforelse

    @if ($messages->hasPages())
        {{ $messages->links('admin.partials.pagination') }}
    @endif
</x-layouts.admin>

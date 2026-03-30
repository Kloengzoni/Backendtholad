{{-- resources/views/admin/messages/index.blade.php --}}
{{-- Affiche les conversations Flutter (Conversation/Message) côté admin --}}
@extends('admin.layouts.app')

@section('title', 'Messages clients')

@section('content')
<div class="flex h-[calc(100vh-64px)] overflow-hidden">

    {{-- ── Sidebar : liste des conversations ─────────────────────────── --}}
    <div class="w-80 border-r border-gray-200 flex flex-col bg-white">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Conversations</h2>
            <p class="text-xs text-gray-500 mt-1">Messages depuis l'application mobile</p>
        </div>

        <div class="overflow-y-auto flex-1">
            @forelse($conversations as $conv)
                @php
                    $client = $conv->user1?->role !== 'admin' ? $conv->user1 : $conv->user2;
                    $isActive = isset($activeConv) && $activeConv->id === $conv->id;
                    $unread = ($conv->user1?->role === 'admin') ? $conv->user2_unread : $conv->user1_unread;
                @endphp
                <a href="{{ route('admin.messages.show', $conv->id) }}"
                   class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition
                          {{ $isActive ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' }}">

                    {{-- Avatar initiales --}}
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-blue-600 font-bold text-sm">
                            {{ strtoupper(substr($client?->name ?? '?', 0, 1)) }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-800 truncate">
                                {{ $client?->name ?? 'Client inconnu' }}
                            </span>
                            @if($conv->last_message_at)
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-1">
                                    {{ $conv->last_message_at->diffForHumans(null, true) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center mt-0.5">
                            <p class="text-xs text-gray-500 truncate">
                                {{ Str::limit($conv->last_message ?? 'Aucun message', 40) }}
                            </p>
                            @if($unread > 0)
                                <span class="ml-1 flex-shrink-0 bg-blue-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>
                        @if($conv->property)
                            <p class="text-xs text-blue-400 truncate mt-0.5">
                                📍 {{ Str::limit($conv->property->title ?? '', 30) }}
                            </p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <p class="text-3xl mb-2">💬</p>
                    <p class="text-sm">Aucune conversation</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination sidebar --}}
        @if($conversations->hasPages())
            <div class="p-3 border-t border-gray-200 text-xs text-gray-500 text-center">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>

    {{-- ── Zone principale : conversation sélectionnée ──────────────── --}}
    <div class="flex-1 flex flex-col bg-gray-50">

        @if(isset($activeConv))
            @php
                $client = $activeConv->user1?->role !== 'admin' ? $activeConv->user1 : $activeConv->user2;
            @endphp

            {{-- Header conversation --}}
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold">
                            {{ strtoupper(substr($client?->name ?? '?', 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $client?->name ?? 'Client inconnu' }}</p>
                        <p class="text-xs text-gray-500">{{ $client?->email }} · {{ $client?->phone }}</p>
                    </div>
                </div>
                @if($activeConv->property)
                    <div class="text-right">
                        <p class="text-xs text-gray-400">Propriété</p>
                        <p class="text-sm font-medium text-blue-600">
                            {{ Str::limit($activeConv->property->title ?? '', 40) }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-3" id="messages-container">
                @forelse($messages as $msg)
                    @php
                        $isSentByAdmin = $msg->sender?->role === 'admin';
                    @endphp
                    <div class="flex {{ $isSentByAdmin ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-md">
                            @if(!$isSentByAdmin)
                                <p class="text-xs text-gray-400 mb-1 ml-1">
                                    {{ $msg->sender?->name ?? 'Client' }}
                                </p>
                            @endif
                            <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed
                                        {{ $isSentByAdmin
                                            ? 'bg-blue-500 text-white rounded-tr-sm'
                                            : 'bg-white text-gray-800 shadow-sm rounded-tl-sm' }}">
                                {{ $msg->content }}
                            </div>
                            <p class="text-xs text-gray-400 mt-1 {{ $isSentByAdmin ? 'text-right mr-1' : 'ml-1' }}">
                                {{ optional($msg->created_at)->format('H:i') }}
                                @if($isSentByAdmin && $msg->is_read)
                                    · <span class="text-blue-400">Lu</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-12">
                        <p class="text-3xl mb-2">💬</p>
                        <p>Aucun message dans cette conversation</p>
                    </div>
                @endforelse
            </div>

            {{-- Zone de réponse --}}
            <div class="bg-white border-t border-gray-200 px-6 py-4">
                @if(session('success'))
                    <div class="mb-3 text-sm text-green-600 bg-green-50 rounded-lg px-3 py-2">
                        ✓ {{ session('success') }}
                    </div>
                @endif
                <form action="{{ route('admin.messages.reply', $activeConv->id) }}" method="POST"
                      class="flex gap-3 items-end">
                    @csrf
                    <textarea name="content" rows="2" required
                        placeholder="Écrire un message..."
                        class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('content') }}</textarea>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white rounded-xl px-5 py-2.5 text-sm font-medium
                               transition-colors flex items-center gap-2 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rotate-45" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Envoyer
                    </button>
                </form>
            </div>

        @else
            {{-- Aucune conversation sélectionnée --}}
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center text-gray-400">
                    <p class="text-6xl mb-4">💬</p>
                    <p class="text-xl font-medium text-gray-600">Sélectionnez une conversation</p>
                    <p class="text-sm mt-2">Choisissez une conversation dans la liste à gauche</p>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Auto-scroll vers le bas des messages --}}
@push('scripts')
<script>
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
</script>
@endpush
@endsection

{{-- resources/views/admin/messages/index.blade.php --}}
{{-- Conversations Flutter — style identique aux autres vues admin TholadImmo --}}
@extends('admin.layouts.app')
@section('title', 'Messages clients')

@section('content')

<style>
.msg-layout   { display:flex; gap:20px; height:calc(100vh - 160px); min-height:500px; }
.msg-sidebar  { width:300px; flex-shrink:0; display:flex; flex-direction:column; background:var(--white); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
.msg-main     { flex:1; display:flex; flex-direction:column; background:var(--white); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
.msg-sidebar-header { padding:16px 18px; border-bottom:1px solid var(--border); background:var(--bg-soft); }
.msg-sidebar-header h3 { font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:700; color:var(--navy); margin:0; }
.msg-sidebar-header p  { font-size:11px; color:var(--txt3); margin:2px 0 0; }
.msg-list     { overflow-y:auto; flex:1; }
.msg-item     { display:flex; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid var(--border); text-decoration:none; transition:.15s; }
.msg-item:hover        { background:var(--bg-soft); }
.msg-item.active       { background:#EFF6FF; border-left:3px solid var(--tholad-blue); }
.msg-item .avatar-lg   { width:40px; height:40px; border-radius:50%; background:#EFF6FF; display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--tholad-blue); font-size:15px; flex-shrink:0; }
.msg-item-body         { flex:1; min-width:0; }
.msg-item-top          { display:flex; justify-content:space-between; align-items:center; }
.msg-item-name         { font-size:13px; font-weight:600; color:var(--navy); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
.msg-item-time         { font-size:10px; color:var(--txt3); flex-shrink:0; }
.msg-item-preview      { font-size:11px; color:var(--txt3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.msg-item-prop         { font-size:10px; color:var(--tholad-blue); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.badge-unread          { background:var(--tholad-blue); color:#fff; font-size:10px; font-weight:700; border-radius:50%; width:18px; height:18px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.msg-header   { padding:14px 20px; border-bottom:1px solid var(--border); background:var(--bg-soft); display:flex; align-items:center; justify-content:space-between; }
.msg-header-left { display:flex; align-items:center; gap:12px; }
.msg-header h3 { font-family:'Cormorant Garamond',serif; font-size:16px; font-weight:700; color:var(--navy); margin:0; }
.msg-header p  { font-size:11px; color:var(--txt3); margin:2px 0 0; }
.msg-body     { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:12px; background:var(--bg-soft); }
.bubble-wrap         { display:flex; }
.bubble-wrap.right   { justify-content:flex-end; }
.bubble-wrap.left    { justify-content:flex-start; }
.bubble              { max-width:60%; padding:10px 14px; border-radius:16px; font-size:13px; line-height:1.5; }
.bubble.right        { background:linear-gradient(135deg,var(--tholad-blue),var(--tholad-blue-dark)); color:#fff; border-bottom-right-radius:4px; }
.bubble.left         { background:var(--white); color:var(--txt); border:1px solid var(--border); border-bottom-left-radius:4px; }
.bubble-meta         { font-size:10px; color:var(--txt3); margin-top:4px; }
.bubble-meta.right   { text-align:right; }
.bubble-sender       { font-size:10px; color:var(--txt3); margin-bottom:3px; }
.msg-footer   { padding:14px 20px; border-top:1px solid var(--border); background:var(--white); }
.msg-input-row { display:flex; gap:10px; align-items:flex-end; }
.msg-textarea  { flex:1; border:1.5px solid var(--border); border-radius:12px; padding:10px 14px; font-size:13px; resize:none; font-family:'DM Sans',sans-serif; color:var(--txt); background:var(--bg-soft); transition:.2s; }
.msg-textarea:focus { outline:none; border-color:var(--tholad-blue); background:var(--white); }
.msg-placeholder { flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:10px; color:var(--txt3); }
.msg-placeholder i { font-size:56px; opacity:.2; }
.msg-placeholder p { font-size:14px; font-weight:500; }
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--navy);margin:0">
        Messages clients
    </h2>
    <span style="font-size:12px;color:var(--txt3)">Conversations depuis l'application mobile</span>
</div>

@if(session('success'))
    <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:var(--green);padding:10px 16px;border-radius:10px;margin-bottom:16px;font-size:13px">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="msg-layout">

    {{-- Sidebar --}}
    <div class="msg-sidebar">
        <div class="msg-sidebar-header">
            <h3><i class="fas fa-comments" style="color:var(--tholad-blue);margin-right:6px"></i>Conversations</h3>
            <p>{{ $conversations->total() }} conversation(s)</p>
        </div>
        <div class="msg-list">
            @forelse($conversations as $conv)
                @php
                    $client  = ($conv->user1 && $conv->user1->role !== 'admin') ? $conv->user1 : $conv->user2;
                    $isActive = isset($activeConv) && $activeConv->id === $conv->id;
                    $adminIds = \App\Models\User::where('role','admin')->pluck('id')->toArray();
                    $unread  = in_array($conv->user1_id, $adminIds) ? ($conv->user2_unread ?? 0) : ($conv->user1_unread ?? 0);
                @endphp
                <a href="{{ route('admin.messages.show', $conv->id) }}" class="msg-item {{ $isActive ? 'active' : '' }}">
                    <div class="avatar-lg">{{ strtoupper(substr($client?->name ?? '?', 0, 1)) }}</div>
                    <div class="msg-item-body">
                        <div class="msg-item-top">
                            <span class="msg-item-name">{{ $client?->name ?? 'Client' }}</span>
                            <span class="msg-item-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                        </div>
                        <div class="msg-item-preview">{{ Str::limit($conv->last_message ?? 'Aucun message', 38) }}</div>
                        @if($conv->property)
                            <div class="msg-item-prop"><i class="fas fa-map-marker-alt" style="font-size:9px"></i> {{ Str::limit($conv->property->title ?? '', 30) }}</div>
                        @endif
                    </div>
                    @if($unread > 0)
                        <div class="badge-unread">{{ $unread }}</div>
                    @endif
                </a>
            @empty
                <div style="padding:40px;text-align:center;color:var(--txt3)">
                    <i class="fas fa-comments" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                    <span style="font-size:13px">Aucune conversation</span>
                </div>
            @endforelse
        </div>
        @if($conversations->hasPages())
            <div style="padding:10px 14px;border-top:1px solid var(--border);font-size:11px;color:var(--txt3);text-align:center">{{ $conversations->links() }}</div>
        @endif
    </div>

    {{-- Zone principale --}}
    <div class="msg-main">
        @if(isset($activeConv))
            @php
                $client = ($activeConv->user1 && $activeConv->user1->role !== 'admin') ? $activeConv->user1 : $activeConv->user2;
            @endphp
            <div class="msg-header">
                <div class="msg-header-left">
                    <div class="avatar-lg">{{ strtoupper(substr($client?->name ?? '?', 0, 1)) }}</div>
                    <div>
                        <h3>{{ $client?->name ?? 'Client inconnu' }}</h3>
                        <p>{{ $client?->email }} · {{ $client?->phone }}</p>
                    </div>
                </div>
                @if($activeConv->property)
                    <div style="text-align:right">
                        <div style="font-size:10px;color:var(--txt3)">Propriété concernée</div>
                        <div style="font-size:12px;font-weight:600;color:var(--tholad-blue)">{{ Str::limit($activeConv->property->title ?? '', 40) }}</div>
                    </div>
                @endif
            </div>
            <div class="msg-body" id="msg-body">
                @forelse($messages as $msg)
                    @php $isAdmin = $msg->sender?->role === 'admin'; @endphp
                    <div class="bubble-wrap {{ $isAdmin ? 'right' : 'left' }}">
                        <div>
                            @if(!$isAdmin)
                                <div class="bubble-sender">{{ $msg->sender?->name ?? 'Client' }}</div>
                            @endif
                            <div class="bubble {{ $isAdmin ? 'right' : 'left' }}">{{ $msg->content }}</div>
                            <div class="bubble-meta {{ $isAdmin ? 'right' : '' }}">
                                {{ optional($msg->created_at)->format('H:i') }}
                                @if($isAdmin && $msg->is_read) · <span style="color:var(--tholad-blue)">Lu</span> @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:var(--txt3)">
                        <i class="fas fa-comment-dots" style="font-size:36px;opacity:.3"></i>
                        <p style="font-size:13px">Aucun message dans cette conversation</p>
                    </div>
                @endforelse
            </div>
            <div class="msg-footer">
                <form action="{{ route('admin.messages.reply', $activeConv->id) }}" method="POST">
                    @csrf
                    <div class="msg-input-row">
                        <textarea name="content" rows="2" required placeholder="Écrire un message au client..." class="msg-textarea">{{ old('content') }}</textarea>
                        <button type="submit" class="btn btn-gold"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </div>
                </form>
            </div>
        @else
            <div class="msg-placeholder">
                <i class="fas fa-comments"></i>
                <p>Sélectionnez une conversation</p>
                <span style="font-size:12px">Choisissez un client dans la liste à gauche</span>
            </div>
        @endif
    </div>
</div>

<script>
    const body = document.getElementById('msg-body');
    if (body) body.scrollTop = body.scrollHeight;
</script>

@endsection

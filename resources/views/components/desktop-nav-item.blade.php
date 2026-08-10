{{-- Componente de Item de Navegação Sidebar --}}
@props(['href', 'icon', 'label', 'active' => false, 'badge' => null])

<a 
    href="{{ $href }}" 
    class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group
        {{ $active 
            ? 'bg-white/10 text-white shadow-lg shadow-black/10' 
            : 'text-blue-100 hover:bg-white/5 hover:text-white' 
        }}"
>
    <i class="fa-solid {{ $icon }} {{ $active ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
    <span class="text-sm font-bold flex-1">{{ $label }}</span>
    
    @if($badge)
    <span class="px-2 py-0.5 bg-red-500 text-white text-[10px] font-black rounded-full">{{ $badge }}</span>
    @endif
</a>

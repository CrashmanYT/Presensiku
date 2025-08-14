<div class="flex flex-wrap gap-2">
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{nama_siswa}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{nama_siswa}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{kelas}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{kelas}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{tanggal}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{tanggal}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{jam_masuk}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{jam_masuk}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{jam_seharusnya}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{jam_seharusnya}</span>
        <span x-show="copied">Disalin</span>
    </button>
</div>

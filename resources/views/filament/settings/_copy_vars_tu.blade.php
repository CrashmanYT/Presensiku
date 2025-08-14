<div class="flex flex-wrap gap-2">
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{date_title}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{date_title}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{pdf_url}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{pdf_url}</span>
        <span x-show="copied">Disalin</span>
    </button>
    <button type="button" x-data="{copied:false}" @click="navigator.clipboard.writeText('{list}'); copied=true; setTimeout(()=>copied=false,1200)" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">
        <span x-show="!copied">{list}</span>
        <span x-show="copied">Disalin</span>
    </button>
</div>

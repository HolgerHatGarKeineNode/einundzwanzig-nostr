<div class="px-4 py-2 rounded-lg text-sm bg-violet-100 text-gray-700">
    @if($row->application_text )
        <div class="flex w-full justify-between items-start">
            <div class="flex">
                <svg class="shrink-0 fill-current text-violet-500 mt-[3px] mr-3" width="16" height="16" viewBox="0 0 16 16">
                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 12H7V7h2v5zM8 6c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1z"></path>
                </svg>
                <div>{{ $row->application_text }}</div>
            </div>
        </div>
    @else
        keine Bewerbung vorhanden
    @endif
</div>

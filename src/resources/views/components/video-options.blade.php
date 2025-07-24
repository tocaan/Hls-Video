<div>
    @switch($video?->status)
        @case(HlsVideos\Models\HlsVideo::READY)
            <a href="javascript:;" onclick="deleteVideo('{{ route('dashboard.videos.delete', $video->id) }}')"
                class="btn btn-sm red">
                <i class="fa fa-trash"></i>
            </a>
            <div class="col-md-12">
                <video id="player" playsinline controls poster="{{ $video->thumb_url }}">
                    <!-- Optional fallback -->
                    <source src="https://stg.thewinkw.com/api/vd/6aba8782-bb8a-4d31-90ed-5994b334aabc/stream"
                        type="application/x-mpegURL">
                </video>
            </div>
        @break

        @case(HlsVideos\Models\HlsVideo::UPLOADED)
        @case(HlsVideos\Models\HlsVideo::PROCESSING)
            <div class="col-md-12">
                <div class="alert alert-warning text-center" role="alert">
                    @lang('Video is Processing')....
                    <a href="javascript:;" onclick="refreshVideoViewContent()" class="btn btn-sm btn-primary">
                        @lang('Refresh')
                    </a>
                </div>
            </div>
        @break

        @default
            <div id="drag-drop-area"></div>
        @break
    @endswitch
</div>
